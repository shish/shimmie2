<?php declare(strict_types=1);

require_once "config.php";

/**
 * A class to handle adding / getting / removing image files from the disk.
 */
class ImageIO extends Extension
{
    const COLLISION_OPTIONS = ['Error'=>ImageConfig::COLLISION_ERROR, 'Merge'=>ImageConfig::COLLISION_MERGE];

    const EXIF_READ_FUNCTION = "exif_read_data";


    const THUMBNAIL_ENGINES = [
        'Built-in GD' => MediaEngine::GD,
        'ImageMagick' => MediaEngine::IMAGICK
    ];

    const THUMBNAIL_TYPES = [
        'JPEG' => "jpg",
        'WEBP (Not IE/Safari compatible)' => "webp"
    ];

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_int(ImageConfig::THUMB_WIDTH, 192);
        $config->set_default_int(ImageConfig::THUMB_HEIGHT, 192);
        $config->set_default_int(ImageConfig::THUMB_SCALING, 100);
        $config->set_default_int(ImageConfig::THUMB_QUALITY, 75);
        $config->set_default_string(ImageConfig::THUMB_TYPE, 'jpg');

        if (function_exists(self::EXIF_READ_FUNCTION)) {
            $config->set_default_bool(ImageConfig::SHOW_META, false);
        }
        $config->set_default_string(ImageConfig::ILINK, '');
        $config->set_default_string(ImageConfig::TLINK, '');
        $config->set_default_string(ImageConfig::TIP, '$tags // $size // $filesize');
        $config->set_default_string(ImageConfig::UPLOAD_COLLISION_HANDLER, ImageConfig::COLLISION_ERROR);
        $config->set_default_int(ImageConfig::EXPIRES, (60*60*24*31));	// defaults to one month
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->page_matches("image/delete")) {
            global $page, $user;
            if ($user->can(Permissions::DELETE_IMAGE) && isset($_POST['image_id']) && $user->check_auth_token()) {
                $image = Image::by_id($_POST['image_id']);
                if ($image) {
                    send_event(new ImageDeletionEvent($image));
                    $page->set_mode(PageMode::REDIRECT);
                    if (isset($_SERVER['HTTP_REFERER']) && !strstr($_SERVER['HTTP_REFERER'], 'post/view')) {
                        $page->set_redirect($_SERVER['HTTP_REFERER']);
                    } else {
                        $page->set_redirect(make_link("post/list"));
                    }
                }
            }
        } elseif ($event->page_matches("image/replace")) {
            global $page, $user;
            if ($user->can(Permissions::REPLACE_IMAGE) && isset($_POST['image_id']) && $user->check_auth_token()) {
                $image = Image::by_id($_POST['image_id']);
                if ($image) {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link('upload/replace/'.$image->id));
                } else {
                    /* Invalid image ID */
                    throw new ImageReplaceException("Image to replace does not exist.");
                }
            }
        } elseif ($event->page_matches("image")) {
            $num = int_escape($event->get_arg(0));
            $this->send_file($num, "image");
        } elseif ($event->page_matches("thumb")) {
            $num = int_escape($event->get_arg(0));
            $this->send_file($num, "thumb");
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::DELETE_IMAGE)) {
            $event->add_part($this->theme->get_deleter_html($event->image->id));
        }
        /* In the future, could perhaps allow users to replace images that they own as well... */
        if ($user->can(Permissions::REPLACE_IMAGE)) {
            $event->add_part($this->theme->get_replace_html($event->image->id));
        }
    }

    public function onImageAddition(ImageAdditionEvent $event)
    {
        try {
            $this->add_image($event);
        } catch (ImageAdditionException $e) {
            throw new UploadException($e->error);
        }
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        $event->image->delete();
    }

    public function onImageReplace(ImageReplaceEvent $event)
    {
        try {
            $this->replace_image($event->id, $event->image);
        } catch (ImageReplaceException $e) {
            throw new UploadException($e->error);
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event)
    {
        $u_name = url_escape($event->display_user->name);
        $i_image_count = Image::count_images(["user={$event->display_user->name}"]);
        $i_days_old = ((time() - strtotime($event->display_user->join_date)) / 86400) + 1;
        $h_image_rate = sprintf("%.1f", ($i_image_count / $i_days_old));
        $images_link = make_link("post/list/user=$u_name/1");
        $event->add_stats("<a href='$images_link'>Images uploaded</a>: $i_image_count, $h_image_rate per day");
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Image Options");
        $sb->position = 30;
        // advanced only
        //$sb->add_text_option(ImageConfig::ILINK, "Image link: ");
        //$sb->add_text_option(ImageConfig::TLINK, "<br>Thumbnail link: ");
        $sb->add_text_option(ImageConfig::TIP, "Image tooltip: ");
        $sb->add_choice_option(ImageConfig::UPLOAD_COLLISION_HANDLER, self::COLLISION_OPTIONS, "<br>Upload collision handler: ");
        if (function_exists(self::EXIF_READ_FUNCTION)) {
            $sb->add_bool_option(ImageConfig::SHOW_META, "<br>Show metadata: ");
        }

        $event->panel->add_block($sb);

        $sb = new SetupBlock("Thumbnailing");
        $sb->add_choice_option(ImageConfig::THUMB_ENGINE, self::THUMBNAIL_ENGINES, "Engine: ");
        $sb->add_label("<br>");
        $sb->add_choice_option(ImageConfig::THUMB_TYPE, self::THUMBNAIL_TYPES, "Filetype: ");

        $sb->add_label("<br>Size ");
        $sb->add_int_option(ImageConfig::THUMB_WIDTH);
        $sb->add_label(" x ");
        $sb->add_int_option(ImageConfig::THUMB_HEIGHT);
        $sb->add_label(" px at ");
        $sb->add_int_option(ImageConfig::THUMB_QUALITY);
        $sb->add_label(" % quality ");

        $sb->add_label("<br>High-DPI scaling ");
        $sb->add_int_option(ImageConfig::THUMB_SCALING);
        $sb->add_label("%");

        $event->panel->add_block($sb);
    }

    private function add_image(ImageAdditionEvent $event)
    {
        global $user, $database, $config;

        $image = $event->image;

        /*
         * Validate things
         */
        if (strlen(trim($image->source ?? '')) == 0) {
            $image->source = null;
        }

        /*
         * Check for an existing image
         */
        $existing = Image::by_hash($image->hash);
        if (!is_null($existing)) {
            $handler = $config->get_string(ImageConfig::UPLOAD_COLLISION_HANDLER);
            if ($handler == ImageConfig::COLLISION_MERGE || isset($_GET['update'])) {
                $merged = array_merge($image->get_tag_array(), $existing->get_tag_array());
                send_event(new TagSetEvent($existing, $merged));
                if (isset($_GET['rating']) && isset($_GET['update']) && Extension::is_enabled(RatingsInfo::KEY)) {
                    send_event(new RatingSetEvent($existing, $_GET['rating']));
                }
                if (isset($_GET['source']) && isset($_GET['update'])) {
                    send_event(new SourceSetEvent($existing, $_GET['source']));
                }
                $event->merged = true;
                $event->image = Image::by_id($existing->id);
                return;
            } else {
                $error = "Image <a href='".make_link("post/view/{$existing->id}")."'>{$existing->id}</a> ".
                        "already has hash {$image->hash}:<p>".$this->theme->build_thumb_html($existing);
                throw new ImageAdditionException($error);
            }
        }

        // actually insert the info
        $database->Execute(
            "INSERT INTO images(
					owner_id, owner_ip, filename, filesize,
					hash, ext, width, height, posted, source
				)
				VALUES (
					:owner_id, :owner_ip, :filename, :filesize,
					:hash, :ext, 0, 0, now(), :source
				)",
            [
                "owner_id" => $user->id, "owner_ip" => $_SERVER['REMOTE_ADDR'], "filename" => substr($image->filename, 0, 255), "filesize" => $image->filesize,
                "hash" => $image->hash, "ext" => strtolower($image->ext), "source" => $image->source
            ]
        );
        $image->id = $database->get_last_insert_id('images_id_seq');

        log_info("image", "Uploaded Image #{$image->id} ({$image->hash})");

        # at this point in time, the image's tags haven't really been set,
        # and so, having $image->tag_array set to something is a lie (but
        # a useful one, as we want to know what the tags are /supposed/ to
        # be). Here we correct the lie, by first nullifying the wrong tags
        # then using the standard mechanism to set them properly.
        $tags_to_set = $image->get_tag_array();
        $image->tag_array = [];
        send_event(new TagSetEvent($image, $tags_to_set));

        if ($image->source !== null) {
            log_info("core-image", "Source for Image #{$image->id} set to: {$image->source}");
        }

        try {
            Media::update_image_media_properties($image->hash, strtolower($image->ext));
        } catch (MediaException $e) {
            log_warning("add_image", "Error while running update_image_media_properties: ".$e->getMessage());
        }
    }

    private function send_file(int $image_id, string $type)
    {
        global $config;
        $image = Image::by_id($image_id);

        global $page;
        if (!is_null($image)) {
            if ($type == "thumb") {
                $ext = $config->get_string(ImageConfig::THUMB_TYPE);
                if (array_key_exists($ext, MIME_TYPE_MAP)) {
                    $page->set_type(MIME_TYPE_MAP[$ext]);
                } else {
                    $page->set_type("image/jpeg");
                }

                $file = $image->get_thumb_filename();
            } else {
                $page->set_type($image->get_mime_type());
                $file = $image->get_image_filename();
            }

            if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
                $if_modified_since = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
            } else {
                $if_modified_since = "";
            }
            $gmdate_mod = gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT';

            if ($if_modified_since == $gmdate_mod) {
                $page->set_mode(PageMode::DATA);
                $page->set_code(304);
                $page->set_data("");
            } else {
                $page->set_mode(PageMode::FILE);
                $page->add_http_header("Last-Modified: $gmdate_mod");
                if ($type != "thumb") {
                    $page->set_filename($image->get_nice_image_name(), 'inline');
                }

                $page->set_file($file);

                if ($config->get_int(ImageConfig::EXPIRES)) {
                    $expires = date(DATE_RFC1123, time() + $config->get_int(ImageConfig::EXPIRES));
                } else {
                    $expires = 'Fri, 2 Sep 2101 12:42:42 GMT'; // War was beginning
                }
                $page->add_http_header('Expires: ' . $expires);
            }
        } else {
            $page->set_title("Not Found");
            $page->set_heading("Not Found");
            $page->add_block(new Block("Navigation", "<a href='" . make_link() . "'>Index</a>", "left", 0));
            $page->add_block(new Block(
                "Image not in database",
                "The requested image was not found in the database"
            ));
        }
    }

    private function replace_image(int $id, Image $image)
    {
        global $database;

        /* Check to make sure the image exists. */
        $existing = Image::by_id($id);

        if (is_null($existing)) {
            throw new ImageReplaceException("Image to replace does not exist!");
        }

        $duplicate = Image::by_hash($image->hash);

        if (!is_null($duplicate) && $duplicate->id!=$id) {
            $error = "Image <a href='" . make_link("post/view/{$duplicate->id}") . "'>{$duplicate->id}</a> " .
                "already has hash {$image->hash}:<p>" . $this->theme->build_thumb_html($duplicate);
            throw new ImageReplaceException($error);
        }

        if (strlen(trim($image->source)) == 0) {
            $image->source = $existing->get_source();
        }

        // Update the data in the database.
        $database->Execute(
            "UPDATE images SET 
					filename = :filename, filesize = :filesize,	hash = :hash,
					ext = :ext, width = 0, height = 0, source = :source
                WHERE 
					id = :id
				",
            [
                "filename" => substr($image->filename, 0, 255),
                "filesize" => $image->filesize,
                "hash" => $image->hash,
                "ext" => strtolower($image->ext),
                "source" => $image->source,
                "id" => $id,
            ]
        );

        /*
            This step could be optional, ie: perhaps move the image somewhere
            and have it stored in a 'replaced images' list that could be
            inspected later by an admin?
        */

        log_debug("image", "Removing image with hash " . $existing->hash);
        $existing->remove_image_only(); // Actually delete the old image file from disk

        try {
            Media::update_image_media_properties($image->hash, $image->ext);
        } catch (MediaException $e) {
            log_warning("image_replace", "Error while running update_image_media_properties: ".$e->getMessage());
        }

        /* Generate new thumbnail */
        send_event(new ThumbnailGenerationEvent($image->hash, strtolower($image->ext)));

        log_info("image", "Replaced Image #{$id} with ({$image->hash})");
    }
}
