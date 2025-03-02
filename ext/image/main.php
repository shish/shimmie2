<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

use function MicroHTML\{INPUT, emptyHTML, STYLE};

/**
 * A class to handle adding / getting / removing image files from the disk.
 */
class ImageIO extends Extension
{
    public const KEY = "image";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $config;

        if ($this->get_version(ImageConfig::VERSION) < 1) {
            switch ($config->get_string("thumb_type")) {
                case FileExtension::WEBP:
                    $config->set_string(ThumbnailConfig::MIME, MimeType::WEBP);
                    break;
                case FileExtension::JPEG:
                    $config->set_string(ThumbnailConfig::MIME, MimeType::JPEG);
                    break;
            }
            $config->delete("thumb_type");

            $this->set_version(ImageConfig::VERSION, 1);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;

        $thumb_width = $config->get_int(ThumbnailConfig::WIDTH, 192);
        $thumb_height = $config->get_int(ThumbnailConfig::HEIGHT, 192);
        $page->add_html_header(STYLE(":root {--thumb-width: {$thumb_width}px; --thumb-height: {$thumb_height}px;}"));

        if ($event->page_matches("image/delete", method: "POST")) {
            $image = Image::by_id_ex(int_escape($event->req_POST('image_id')));
            if ($this->can_user_delete_image($user, $image)) {
                send_event(new ImageDeletionEvent($image));

                if ($config->get_string(ImageConfig::ON_DELETE) === 'next') {
                    $this->redirect_to_next_image($image, $event->get_GET('search'));
                } else {
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(referer_or(make_link(), ['post/view']));
                }
            } else {
                throw new PermissionDenied("You do not have permission to delete this image.");
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

        if ($user->can(ImagePermission::DELETE_IMAGE)) {
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
        Log::info("image", "Uploaded >>{$event->image->id} ({$event->image->hash})");
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
        if (isset($event->image->posted)) {
            $event->replace('$date', autodate($event->image->posted, false));
        }
        $event->replace("\\n", "\n");
    }

    private function redirect_to_next_image(Image $image, ?string $search = null): void
    {
        global $page;

        if (!is_null($search)) {
            $search_terms = Tag::explode($search);
            $query = "search=" . url_escape($search);
        } else {
            $search_terms = [];
            $query = null;
        }

        $target_image = $image->get_next($search_terms);

        if ($target_image === null) {
            $redirect_target = referer_or(search_link(), ['post/view']);
        } else {
            $redirect_target = make_link("post/view/{$target_image->id}", null, $query);
        }

        $page->set_mode(PageMode::REDIRECT);
        $page->set_redirect($redirect_target);
    }

    private function can_user_delete_image(User $user, Image $image): bool
    {
        if ($user->can(ImagePermission::DELETE_OWN_IMAGE) && $image->owner_id == $user->id) {
            return true;
        }
        return $user->can(ImagePermission::DELETE_IMAGE);
    }

    /**
     * @param array<string, string|string[]> $params
     */
    private function send_file(int $image_id, string $type, array $params): void
    {
        global $config, $page;

        $image = Image::by_id_ex($image_id);

        if ($type == "thumb") {
            $mime = $config->get_string(ThumbnailConfig::MIME);
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
            $if_modified_since = \Safe\preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
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
