<?php

declare(strict_types=1);

namespace Shimmie2;

class Automatic1111Tagger extends Extension
{
    public const KEY = "automatic1111_tagger";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        // Handle interrogation request
        if ($event->page_matches("automatic1111_tagger/interrogate/{post_id}")) {
            $post_id = $event->get_iarg('post_id');
            $image_obj = Image::by_id_ex($post_id);
            $image_contents = $image_obj->get_image_filename()->get_contents();
            $image_data = base64_encode($image_contents);
            $payload = [
                "image" => $image_data,
                "model" => Ctx::$config->get(Automatic1111TaggerConfig::MODEL),
                "threshold" => (float)Ctx::$config->get(Automatic1111TaggerConfig::THRESHOLD)
            ];
            $endpoint = Ctx::$config->get(Automatic1111TaggerConfig::API_ENDPOINT);
            $result = $this->send_api_request($endpoint, $payload);
            if (isset($result['caption']['rating']) && is_array($result['caption']['rating'])) {
                $this->handle_rating($image_obj, $result['caption']['rating']);
            }
            if (isset($result['caption']['tag']) && is_array($result['caption']['tag'])) {
                // Get all tags for the image, including artist and meta tags
                $current_tags = $image_obj->get_tag_array();
                $all_tags = $current_tags;
                foreach ($result['caption']['tag'] as $tag => $score) {
                    if (is_string($tag) && strlen($tag) > 0) {
                        $all_tags[] = $tag;
                    }
                }
                $all_tags = Tag::explode(Tag::implode($all_tags));
                // Remove 'tagme' if new tags are added
                if (count($all_tags) > count($current_tags)) {
                    $all_tags = array_filter($all_tags, fn ($t) => strtolower($t) !== 'tagme');
                    send_event(new TagSetEvent($image_obj, $all_tags));
                    Ctx::$page->flash("Tags added");
                    Ctx::$page->set_redirect(make_link("post/view/" . $post_id));
                } else {
                    Ctx::$page->flash("No new tags to add.");
                }
            } else {
                Ctx::$page->flash("No tags found in API response. Raw response: " . json_encode($result));
            }
            Ctx::$page->set_redirect(make_link("post/view/" . $post_id));
        }

        if ($event->page_matches("automatic1111_tagger/get_rating/{post_id}")) {
            $post_id = $event->get_iarg('post_id');
            $image_obj = Image::by_id_ex($post_id);
            $image_contents = $image_obj->get_image_filename()->get_contents();
            $image_data = base64_encode($image_contents);
            $payload = [
                "image" => $image_data,
                "model" => Ctx::$config->get(Automatic1111TaggerConfig::MODEL),
                "threshold" => (float)Ctx::$config->get(Automatic1111TaggerConfig::THRESHOLD)
            ];
            $endpoint = Ctx::$config->get(Automatic1111TaggerConfig::API_ENDPOINT);
            $result = $this->send_api_request($endpoint, $payload);
            if (isset($result['caption']['rating']) && is_array($result['caption']['rating'])) {
                $this->handle_rating($image_obj, $result['caption']['rating']);
            } else {
                Ctx::$page->flash("No rating found in API response. Raw response: " . json_encode($result));
            }
            Ctx::$page->set_redirect(make_link("post/view/" . $image_obj->id));
        }
    }

    /**
     * @param array<string, int> $rating_arr
     */
    private function handle_rating(Image $image_obj, array $rating_arr): void
    {
        $max_rating = null;
        $max_value = -1;
        foreach ($rating_arr as $rating => $value) {
            if ($value > $max_value) {
                $max_value = $value;
                $max_rating = $rating;
            }
        }
        $rating_tag = null;
        if ($max_rating === 'general' || $max_rating === 'sensitive') {
            $rating_tag = 's';
        } elseif ($max_rating === 'questionable') {
            $rating_tag = 'q';
        } elseif ($max_rating === 'explicit') {
            $rating_tag = 'e';
        }
        if ($rating_tag) {
            send_event(new RatingSetEvent($image_obj, $rating_tag));
            Ctx::$page->flash("Rating set");
        }
    }

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(Automatic1111TaggerPermission::INTERROGATE_IMAGE)) {
            $event->add_button("Interrogate", "automatic1111_tagger/interrogate/{$event->image->id}");
        }
        if (Ctx::$user->can(Automatic1111TaggerPermission::GET_IMAGE_RATING)) {
            $event->add_button("Get Rating", "automatic1111_tagger/get_rating/{$event->image->id}");
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function send_api_request(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, \Safe\json_encode($payload));
        $response = curl_exec($ch);
        curl_close($ch);
        assert(is_string($response));
        return \Safe\json_decode($response, true);
    }
}
