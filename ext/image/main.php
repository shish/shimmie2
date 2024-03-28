<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

use function MicroHTML\{INPUT, emptyHTML};

require_once "config.php";

/**
 * A class to handle adding / getting / removing image files from the disk.
 */
class ImageIO extends Extension
{
    public const COLLISION_OPTIONS = [
        'Error' => ImageConfig::COLLISION_ERROR,
        'Merge' => ImageConfig::COLLISION_MERGE
    ];

    public const ON_DELETE_OPTIONS = [
        'Return to post list' => ImageConfig::ON_DELETE_LIST,
        'Go to next post' => ImageConfig::ON_DELETE_NEXT
    ];

    public const EXIF_READ_FUNCTION = "exif_read_data";

    public const THUMBNAIL_ENGINES = [
        'Built-in GD' => MediaEngine::GD,
        'ImageMagick' => MediaEngine::IMAGICK
    ];

    public const THUMBNAIL_TYPES = [
        'JPEG' => MimeType::JPEG,
        'WEBP (Not IE compatible)' => MimeType::WEBP
    ];

    public function onInitExt(InitExtEvent $event): void
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
        $config->set_default_int(ImageConfig::EXPIRES, (60 * 60 * 24 * 31));	// defaults to one month
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
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

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        $thumb_width = $config->get_int(ImageConfig::THUMB_WIDTH, 192);
        $thumb_height = $config->get_int(ImageConfig::THUMB_HEIGHT, 192);
        $page->add_html_header("<style>:root {--thumb-width: {$thumb_width}px; --thumb-height: {$thumb_height}px;}</style>");

        if ($event->page_matches("image/delete", method: "POST", permission: Permissions::DELETE_IMAGE)) {
            global $page, $user;
            $image = Image::by_id(int_escape($event->req_POST('image_id')));
            if ($image) {
                send_event(new ImageDeletionEvent($image));

                if ($config->get_string(ImageConfig::ON_DELETE) === ImageConfig::ON_DELETE_NEXT) {
                    redirect_to_next_image($image, $event->get_GET('search'));
                } else {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(referer_or(make_link(), ['post/view']));
                }
            }
        } elseif ($event->page_matches("image/{image_id}/{filename}")) {
            $num = $event->get_iarg('image_id');
            $this->send_file($num, "image", $event->GET);
        } elseif ($event->page_matches("thumb/{image_id}/{filename}")) {
            $num = $event->get_iarg('image_id');
            $this->send_file($num, "thumb", $event->GET);
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::DELETE_IMAGE)) {
            $form = SHM_FORM("image/delete", form_id: "image_delete_form");
            $form->appendChild(emptyHTML(
                INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $event->image->id]),
                INPUT(["type" => 'submit', "value" => 'Delete', "onclick" => 'return confirm("Delete the image?");', "id" => "image_delete_button"]),
            ));
            $event->add_part($form);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('delete')
            ->addArgument('id', InputArgument::REQUIRED)
            ->setDescription('Delete a specific post')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $post_id = (int)$input->getArgument('id');
                $image = Image::by_id_ex($post_id);
                send_event(new ImageDeletionEvent($image));
                return Command::SUCCESS;
            });
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        send_event(new ThumbnailGenerationEvent($event->image));
        log_info("image", "Uploaded >>{$event->image->id} ({$event->image->hash})");
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $event->image->delete();
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $u_name = url_escape($event->display_user->name);
        $i_image_count = Search::count_images(["user={$event->display_user->name}"]);
        $i_days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;
        $h_image_rate = sprintf("%.1f", ($i_image_count / $i_days_old));
        $images_link = search_link(["user=$u_name"]);
        $event->add_part("<a href='$images_link'>Posts uploaded</a>: $i_image_count, $h_image_rate per day");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
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
        if ($config->get_string(ImageConfig::THUMB_MIME) === MimeType::JPEG) {
            $sb->add_color_option(ImageConfig::THUMB_ALPHA_COLOR, "Alpha Conversion Color", true);
        }

        $sb->end_table();
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        $fname = $event->image->filename;
        $base_fname = basename($fname, '.' . $event->image->get_ext());

        $event->replace('$id', (string)$event->image->id);
        $event->replace('$hash_ab', substr($event->image->hash, 0, 2));
        $event->replace('$hash_cd', substr($event->image->hash, 2, 2));
        $event->replace('$hash', $event->image->hash);
        $event->replace('$filesize', to_shorthand_int($event->image->filesize));
        $event->replace('$filename', $base_fname);
        $event->replace('$ext', $event->image->get_ext());
        if(isset($event->image->posted)) {
            $event->replace('$date', autodate($event->image->posted, false));
        }
        $event->replace("\\n", "\n");
    }

    /**
     * @param array<string, string|string[]> $params
     */
    private function send_file(int $image_id, string $type, array $params): void
    {
        global $config, $page;

        $image = Image::by_id_ex($image_id);

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
        $gmdate_mod = gmdate('D, d M Y H:i:s', \Safe\filemtime($file)) . ' GMT';

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

        send_event(new ImageDownloadingEvent($image, $file, $mime, $params));
    }
}
