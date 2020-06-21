<?php

require_once "a_api_controller.php";
require_once "tags_controller.php";

class ApiImagesController extends AApiController
{
    public function process(array $args)
    {
        $fourth_arg = @$args[3];

        if (!is_numeric($fourth_arg)) {
            // 4th arg should always be a number
            return;
        }
        $id = intval($fourth_arg);

        $image = Image::by_id($id);

        if ($image===null) {
            throw new SCoreException("Image $id not found");
        }

        if (@$args[4]==="tags") {
            switch ($_SERVER['REQUEST_METHOD']) {
                case "GET":
                    ApiTagsController::get_for_image_id($id);
                    break;
                case "POST":
                    if (empty($_GET["tags"])) {
                        return;
                    }
                    $this->set_tags($image, json_decode($_GET["tags"]), false);
                    ApiTagsController::get_for_image_id($id);
                    break;
            }
        } else {
            $this->search_tags();
        }
    }

    private function set_tags(Image $image, array $tags, bool $replace)
    {
        global $user;
        if ($user->can(Permissions::EDIT_IMAGE_TAG)) {
            $tags = Tag::explode(implode(" ", $tags));

            $tags = Tag::sanitize_array($tags);

            $pos_tag_array = [];
            $neg_tag_array = [];
            foreach ($tags as $new_tag) {
                if (strpos($new_tag, '-') === 0) {
                    $neg_tag_array[] = substr($new_tag, 1);
                } else {
                    $pos_tag_array[] = $new_tag;
                }
            }

            if ($replace) {
                send_event(new TagSetEvent($image, $tags));
            } else {
                $img_tags = array_map("strtolower", $image->get_tag_array());

                if (!empty($neg_tag_array)) {
                    $neg_tag_array = array_map("strtolower", $neg_tag_array);

                    $img_tags = array_merge($pos_tag_array, $img_tags);
                    $img_tags = array_diff($img_tags, $neg_tag_array);
                } else {
                    $img_tags = array_merge($tags, $img_tags);
                }
                send_event(new TagSetEvent($image, $img_tags));
            }
        }
    }
}
