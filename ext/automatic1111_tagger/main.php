<?php

declare(strict_types=1);

namespace Shimmie2;

class Automatic1111Tagger extends Extension
{
    public const KEY = "automatic1111_tagger";

    public function onImageBlockBuilding(ImageBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(Automatic1111TaggerPermission::INTERROGATE_IMAGE)) {
            if (property_exists($event, 'image') && isset($event->image->id)) {
                if (method_exists($this->theme, 'display_interrogate_button')) {
                    $this->theme->display_interrogate_button($event->image->id);
                }
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        // Add button to post view
        if ($event->page_matches("post/view/*")) {
            $post_id = (int)$event->get_arg('id');
            if (Ctx::$user->can(Automatic1111TaggerPermission::INTERROGATE_IMAGE)) {
                if (method_exists($this->theme, 'display_interrogate_button')) {
                    $this->theme->display_interrogate_button($post_id);
                }
            }
        }

        // Handle interrogation request
        if ($event->page_matches("automatic1111_tagger/interrogate/{post_id}")) {
            $post_id = $event->get_iarg('post_id');
            $image_obj = Image::by_id_ex($post_id);
            if (!$image_obj) {
                Ctx::$page->flash("Image not found.");
                Ctx::$page->set_redirect(make_link("post/list"));
                return;
            }
            $image_path = $image_obj->get_image_filename()->str();
            $image_contents = file_get_contents($image_path);
            if ($image_contents === false) {
                Ctx::$page->flash("Failed to read image file.");
                Ctx::$page->set_redirect(make_link("post/list"));
                return;
            }
            $image_data = base64_encode($image_contents);
            $payload = [
                "image" => $image_data,
                "model" => Ctx::$config->get(Automatic1111TaggerConfig::MODEL),
                "threshold" => (float)Ctx::$config->get(Automatic1111TaggerConfig::THRESHOLD)
            ];
            $endpoint = Ctx::$config->get(Automatic1111TaggerConfig::API_ENDPOINT);
            $result = $this->send_api_request($endpoint, $payload);
            $tags = [];
            $rating_tag = null;
            if (isset($result['caption']['tag']) && is_array($result['caption']['tag'])) {
                foreach ($result['caption']['tag'] as $tag => $score) {
                    $tags[] = $tag;
                }
                // Handle rating
                if (isset($result['caption']['rating']) && is_array($result['caption']['rating'])) {
                    $rating_arr = $result['caption']['rating'];
                    $max_rating = null;
                    $max_value = -1;
                    foreach ($rating_arr as $rating => $value) {
                        if ($value > $max_value) {
                            $max_value = $value;
                            $max_rating = $rating;
                        }
                    }
                    if ($max_rating === 'general' || $max_rating === 'sensitive') {
                        $rating_tag = 'rating:safe';
                    } elseif ($max_rating === 'questionable') {
                        $rating_tag = 'rating:questionable';
                    } elseif ($max_rating === 'explicit') {
                        $rating_tag = 'rating:explicit';
                    }
                }
                // Get all tags for the image, including artist and meta tags
                $current_tags = [];
                if (method_exists($image_obj, 'get_tag_array')) {
                    $current_tags = $image_obj->get_tag_array();
                } else {
                    $rows = Ctx::$database->get_col(
                        "SELECT t.tag FROM image_tags it JOIN tags t ON it.tag_id = t.id WHERE it.image_id = :image_id",
                        ['image_id' => $image_obj->id]
                    );
                    foreach ($rows as $t) {
                        $current_tags[] = $t;
                    }
                }
                $all_tags = $current_tags;
                foreach ($tags as $tag) {
                    if (!in_array(strtolower($tag), array_map('strtolower', $current_tags), true)) {
                        $all_tags[] = $tag;
                    }
                }
                if ($rating_tag && !in_array(strtolower($rating_tag), array_map('strtolower', $all_tags), true)) {
                    $all_tags[] = $rating_tag;
                }
                $all_tags = array_filter(array_unique($all_tags), fn ($t) => $t !== '');
                // Remove 'tagme' if new tags are added
                if (count($all_tags) > count($current_tags)) {
                    $all_tags = array_filter($all_tags, fn ($t) => strtolower($t) !== 'tagme');
                    send_event(new TagSetEvent($image_obj, $all_tags));
                    if (Ctx::$config->get(Automatic1111TaggerConfig::RESOLVE_ALIASES)) {
                        // Alias resolution: fetch aliases and apply them
                        $resolved_tags = $all_tags;
                        $alias_map = [];
                        $rows = Ctx::$database->get_all("SELECT oldtag, newtag FROM aliases");
                        foreach ($rows as $row) {
                            $alias_map[strtolower($row['oldtag'])] = $row['newtag'];
                        }
                        foreach ($resolved_tags as &$tag) {
                            $tag_lower = strtolower($tag);
                            if (isset($alias_map[$tag_lower])) {
                                $tag = $alias_map[$tag_lower];
                            }
                        }
                        unset($tag);
                        $resolved_tags = array_filter(array_unique($resolved_tags), fn ($t) => $t !== '');
                        send_event(new TagSetEvent($image_obj, $resolved_tags));
                    }
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
            if (!$image_obj) {
                Ctx::$page->flash("Image not found.");
                Ctx::$page->set_redirect(make_link("post/list"));
                return;
            }
            $image_path = $image_obj->get_image_filename()->str();
            $image_contents = file_get_contents($image_path);
            if ($image_contents === false) {
                Ctx::$page->flash("Failed to read image file.");
                Ctx::$page->set_redirect(make_link("post/list"));
                return;
            }
            $image_data = base64_encode($image_contents);
            $payload = [
                "image" => $image_data,
                "model" => Ctx::$config->get(Automatic1111TaggerConfig::MODEL),
                "threshold" => (float)Ctx::$config->get(Automatic1111TaggerConfig::THRESHOLD)
            ];
            $endpoint = Ctx::$config->get(Automatic1111TaggerConfig::API_ENDPOINT);
            $result = $this->send_api_request($endpoint, $payload);
            $rating_tag = null;
            if (isset($result['caption']['rating']) && is_array($result['caption']['rating'])) {
                $rating_arr = $result['caption']['rating'];
                $max_rating = null;
                $max_value = -1;
                foreach ($rating_arr as $rating => $value) {
                    if ($value > $max_value) {
                        $max_value = $value;
                        $max_rating = $rating;
                    }
                }
                if ($max_rating === 'general' || $max_rating === 'sensitive') {
                    $rating_tag = 'rating:safe';
                } elseif ($max_rating === 'questionable') {
                    $rating_tag = 'rating:questionable';
                } elseif ($max_rating === 'explicit') {
                    $rating_tag = 'rating:explicit';
                }
            }
            if ($rating_tag) {
                // Get all tags for the image
                $current_tags = [];
                if (method_exists($image_obj, 'get_tag_array')) {
                    $current_tags = $image_obj->get_tag_array();
                } else {
                    $rows = Ctx::$database->get_col(
                        "SELECT t.tag FROM image_tags it JOIN tags t ON it.tag_id = t.id WHERE it.image_id = :image_id",
                        ['image_id' => $image_obj->id]
                    );
                    foreach ($rows as $t) {
                        $current_tags[] = $t;
                    }
                }
                // Add rating tag if not present
                if (!in_array(strtolower($rating_tag), array_map('strtolower', $current_tags), true)) {
                    $all_tags = $current_tags;
                    $all_tags[] = $rating_tag;
                    $all_tags = array_filter(array_unique($all_tags), fn ($t) => $t !== '');
                    send_event(new TagSetEvent($image_obj, $all_tags));
                    Ctx::$page->flash("Tags added");
                } else {
                    Ctx::$page->flash("Rating tag already present.");
                }
            } else {
                Ctx::$page->flash("No rating found in API response.");
            }
            Ctx::$page->set_redirect(make_link("post/view/" . $post_id));
        }
    }

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
        $json_payload = json_encode($payload);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload !== false ? $json_payload : '{}');
        $response = curl_exec($ch);
        curl_close($ch);
        return is_string($response) ? (json_decode($response, true) ?? []) : [];
    }
}
