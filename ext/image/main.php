<?php declare(strict_types=1);

require_once "config.php";

/**
 * A class to handle adding / getting / removing image files from the disk.
 */
class ImageIO extends Extension
{
    /** @var ImageIOTheme */
    protected ?Themelet $theme;

    const COLLISION_OPTIONS = [
        'Error'=>ImageConfig::COLLISION_ERROR,
        'Merge'=>ImageConfig::COLLISION_MERGE
    ];

    const ON_DELETE_OPTIONS = [
        'Return to post list'=>ImageConfig::ON_DELETE_LIST,
        'Go to next post'=>ImageConfig::ON_DELETE_NEXT
    ];

    const EXIF_READ_FUNCTION = "exif_read_data";

    const THUMBNAIL_ENGINES = [
        'Built-in GD' => MediaEngine::GD,
        'ImageMagick' => MediaEngine::IMAGICK
    ];

    const THUMBNAIL_TYPES = [
        'JPEG' => MimeType::JPEG,
        'WEBP (Not IE/Safari compatible)' => MimeType::WEBP
    ];

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string(ImageConfig::THUMB_ENGINE, MediaEngine::GD);
        $config->set_default_int(ImageConfig::THUMB_WIDTH, 192);
        $config->set_default_int(ImageConfig::THUMB_HEIGHT, 192);
        $config->set_default_int(ImageConfig::THUMB_SCALING, 100);
        $config->set_default_int(ImageConfig::THUMB_QUALITY, 75);
        $config->set_default_string(ImageConfig::THUMB_MIME, MimeType::JPEG);
        $config->set_default_string(ImageConfig::THUMB_FIT, Media::RESIZE_TYPE_FIT);
        $config->set_default_string(ImageConfig::THUMB_ALPHA_COLOR, Media::DEFAULT_ALPHA_CONVERSION_COLOR);

        if (function_exists(self::EXIF_READ_FUNCTION)) {
            $config->set_default_bool(ImageConfig::SHOW_META, false);
        }
        $config->set_default_string(ImageConfig::ILINK, '');
        $config->set_default_string(ImageConfig::TLINK, '');
        $config->set_default_string(ImageConfig::TIP, '$tags // $size // $filesize');
        $config->set_default_string(ImageConfig::UPLOAD_COLLISION_HANDLER, ImageConfig::COLLISION_ERROR);
        $config->set_default_int(ImageConfig::EXPIRES, (60*60*24*31));	// defaults to one month
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $config;

        if ($this->get_version(ImageConfig::VERSION) < 1) {
            switch ($config->get_string("thumb_type")) {
                case FileExtension::WEBP:
                    $config->set_string(ImageConfig::THUMB_MIME, MimeType::WEBP);
                    break;
                case FileExtension::JPEG:
                    $config->set_string(ImageConfig::THUMB_MIME, MimeType::JPEG);
                    break;
            }
            $config->set_string("thumb_type", null);

            $this->set_version(ImageConfig::VERSION, 1);
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config;
        if ($event->page_matches("image/delete")) {
            global $page, $user;
            if ($user->can(Permissions::DELETE_IMAGE) && isset($_POST['image_id']) && $user->check_auth_token()) {
                $image = Image::by_id(int_escape($_POST['image_id']));
                if ($image) {
                    send_event(new ImageDeletionEvent($image));

                    if ($config->get_string(ImageConfig::ON_DELETE)===ImageConfig::ON_DELETE_NEXT) {
                        redirect_to_next_image($image);
                    } else {
                        $page->set_mode(PageMode::REDIRECT);
                        $page->set_redirect(referer_or(make_link("post/list"), ['post/view']));
                    }
                }
            }
        } elseif ($event->page_matches("image/replace")) {
            global $page, $user;
            if ($user->can(Permissions::REPLACE_IMAGE) && isset($_POST['image_id']) && $user->check_auth_token()) {
                $image = Image::by_id(int_escape($_POST['image_id']));
                if ($image) {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link('upload/replace/'.$image->id));
                } else {
                    /* Invalid image ID */
                    throw new ImageReplaceException("Post to replace does not exist.");
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
        global $config;

        try {
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
                    $im = Image::by_id($existing->id);
                    assert(!is_null($image));
                    $event->image = $im;
                    return;
                } else {
                    $error = "Post <a href='".make_link("post/view/{$existing->id}")."'>{$existing->id}</a> ".
                        "already has hash {$image->hash}:<p>".$this->theme->build_thumb_html($existing);
                    throw new ImageAdditionException($error);
                }
            }

            // actually insert the info
            $image->save_to_db();

            log_info("image", "Uploaded >>{$image->id} ({$image->hash})");

            # at this point in time, the image's tags haven't really been set,
            # and so, having $image->tag_array set to something is a lie (but
            # a useful one, as we want to know what the tags are /supposed/ to
            # be). Here we correct the lie, by first nullifying the wrong tags
            # then using the standard mechanism to set them properly.
            $tags_to_set = $image->get_tag_array();
            $image->tag_array = [];
            send_event(new TagSetEvent($image, $tags_to_set));

            if ($image->source !== null) {
                log_info("core-image", "Source for >>{$image->id} set to: {$image->source}");
            }
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
            $id = $event->id;
            $image = $event->image;

            $image->set_mime(
                MimeType::get_for_file($image->get_image_filename())
            );

            /* Check to make sure the image exists. */
            $existing = Image::by_id($id);

            if (is_null($existing)) {
                throw new ImageReplaceException("Post to replace does not exist!");
            }

            $duplicate = Image::by_hash($image->hash);
            if (!is_null($duplicate) && $duplicate->id!=$id) {
                $error = "Post <a href='" . make_link("post/view/{$duplicate->id}") . "'>{$duplicate->id}</a> " .
                    "already has hash {$image->hash}:<p>" . $this->theme->build_thumb_html($duplicate);
                throw new ImageReplaceException($error);
            }

            if (strlen(trim($image->source ?? '')) == 0) {
                $image->source = $existing->get_source();
            }

            // Update the data in the database.
            $image->id = $id;
            send_event(new MediaCheckPropertiesEvent($image));
            $image->save_to_db();

            /*
                This step could be optional, ie: perhaps move the image somewhere
                and have it stored in a 'replaced images' list that could be
                inspected later by an admin?
            */

            log_debug("image", "Removing image with hash " . $existing->hash);
            $existing->remove_image_only(); // Actually delete the old image file from disk

            /* Generate new thumbnail */
            send_event(new ThumbnailGenerationEvent($image->hash, strtolower($image->get_mime())));

            log_info("image", "Replaced >>{$id} with ({$image->hash})");
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
        $event->add_stats("<a href='$images_link'>Posts uploaded</a>: $i_image_count, $h_image_rate per day");
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        global $config;

        $sb = $event->panel->create_new_block("Post Options");
        $sb->start_table();
        $sb->position = 30;
        // advanced only
        //$sb->add_text_option(ImageConfig::ILINK, "Image link: ");
        //$sb->add_text_option(ImageConfig::TLINK, "<br>Thumbnail link: ");
        $sb->add_text_option(ImageConfig::TIP, "Post tooltip", true);
        $sb->add_text_option(ImageConfig::INFO, "Post info", true);
        $sb->add_choice_option(ImageConfig::UPLOAD_COLLISION_HANDLER, self::COLLISION_OPTIONS, "Upload collision handler", true);
        $sb->add_choice_option(ImageConfig::ON_DELETE, self::ON_DELETE_OPTIONS, "On Delete", true);
        if (function_exists(self::EXIF_READ_FUNCTION)) {
            $sb->add_bool_option(ImageConfig::SHOW_META, "Show metadata", true);
        }
        $sb->end_table();

        $sb = $event->panel->create_new_block("Thumbnailing");
        $sb->start_table();
        $sb->add_choice_option(ImageConfig::THUMB_ENGINE, self::THUMBNAIL_ENGINES, "Engine", true);
        $sb->add_choice_option(ImageConfig::THUMB_MIME, self::THUMBNAIL_TYPES, "Filetype", true);

        $sb->add_int_option(ImageConfig::THUMB_WIDTH, "Max Width", true);
        $sb->add_int_option(ImageConfig::THUMB_HEIGHT, "Max Height", true);

        $options = [];
        foreach (MediaEngine::RESIZE_TYPE_SUPPORT[$config->get_string(ImageConfig::THUMB_ENGINE)] as $type) {
            $options[$type] = $type;
        }

        $sb->add_choice_option(ImageConfig::THUMB_FIT, $options, "Fit", true);

        $sb->add_int_option(ImageConfig::THUMB_QUALITY, "Quality", true);
        $sb->add_int_option(ImageConfig::THUMB_SCALING, "High-DPI Scale %", true);
        if ($config->get_string(ImageConfig::THUMB_MIME)===MimeType::JPEG) {
            $sb->add_color_option(ImageConfig::THUMB_ALPHA_COLOR, "Alpha Conversion Color", true);
        }

        $sb->end_table();
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event)
    {
        $fname = $event->image->get_filename();
        $base_fname = str_contains($fname, '.') ? substr($fname, 0, strrpos($fname, '.')) : $fname;

        $event->replace('$id', (string)$event->image->id);
        $event->replace('$hash_ab', substr($event->image->hash, 0, 2));
        $event->replace('$hash_cd', substr($event->image->hash, 2, 2));
        $event->replace('$hash', $event->image->hash);
        $event->replace('$filesize', to_shorthand_int($event->image->filesize));
        $event->replace('$filename', $base_fname);
        $event->replace('$ext', $event->image->get_ext());
        $event->replace('$date', autodate($event->image->posted, false));
        $event->replace("\\n", "\n");
    }

    private function send_file(int $image_id, string $type)
    {
        global $config, $page;

        $image = Image::by_id($image_id);
        if (!is_null($image)) {
            if ($type == "thumb") {
                $mime = $config->get_string(ImageConfig::THUMB_MIME);
                $file = $image->get_thumb_filename();
            } else {
                $mime = $image->get_mime();
                $file = $image->get_image_filename();
            }
            if (!file_exists($file)) {
                http_response_code(404);
                die();
            }

            $page->set_mime($mime);


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

            send_event(new ImageDownloadingEvent($image, $file, $mime));
        } else {
            $page->set_title("Not Found");
            $page->set_heading("Not Found");
            $page->add_block(new Block("Navigation", "<a href='" . make_link() . "'>Index</a>", "left", 0));
            $page->add_block(new Block(
                "Post not in database",
                "The requested image was not found in the database"
            ));
        }
    }
}
