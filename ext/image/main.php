<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, INPUT, STYLE, emptyHTML};

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A class to handle adding / getting / removing image files from the disk.
 */
final class ImageIO extends Extension
{
    public const KEY = "image";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 1) {
            switch (Ctx::$config->get("thumb_type")) {
                case FileExtension::WEBP:
                    Ctx::$config->set(ThumbnailConfig::MIME, MimeType::WEBP);
                    break;
                case FileExtension::JPEG:
                    Ctx::$config->set(ThumbnailConfig::MIME, MimeType::JPEG);
                    break;
            }
            Ctx::$config->delete("thumb_type");
            $this->set_version(1);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $thumb_width = Ctx::$config->get(ThumbnailConfig::WIDTH);
        $thumb_height = Ctx::$config->get(ThumbnailConfig::HEIGHT);
        Ctx::$page->add_html_header(STYLE(":root {--thumb-width: {$thumb_width}px; --thumb-height: {$thumb_height}px;}"));

        if ($event->page_matches("image/delete", method: "POST")) {
            $image = Image::by_id_ex(int_escape($event->POST->req('image_id')));
            if ($this->can_user_delete_image(Ctx::$user, $image)) {
                send_event(new ImageDeletionEvent($image));

                if (Ctx::$config->get(ImageConfig::ON_DELETE) === 'next') {
                    $this->redirect_to_next_image($image, $event->GET->get('search'));
                } else {
                    Ctx::$page->set_redirect(Url::referer_or(ignore: ['post/view']));
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

    #[EventListener]
    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        $event->add_disallow("thumb/");
        $event->add_disallow("_thumbs/");
    }

    #[EventListener]
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        $image = Image::by_id_ex($event->image->id);
        if ($this->can_user_delete_image(Ctx::$user, $image)) {
            $event->add_part(SHM_FORM(
                action: make_link("image/delete"),
                id: "image_delete_form",
                children: [
                    INPUT(["type" => 'hidden', "name" => 'image_id', "value" => $event->image->id]),
                    INPUT(["type" => 'submit', "value" => 'Delete', "onclick" => 'return confirm("Delete the image?");', "id" => "image_delete_button"]),
                ]
            ));
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('post:delete')
            ->addArgument('id', InputArgument::REQUIRED)
            ->setDescription('Delete a specific post')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $post_id = (int)$input->getArgument('id');
                $image = Image::by_id_ex($post_id);
                send_event(new ImageDeletionEvent($image));
                return Command::SUCCESS;
            });
    }

    #[EventListener]
    public function onImageAddition(ImageAdditionEvent $event): void
    {
        send_event(new ThumbnailGenerationEvent($event->image));
        Log::info("image", "Uploaded >>{$event->image->id} ({$event->image->hash})");
    }

    #[EventListener]
    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        $event->image->delete();
    }

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $i_image_count = Search::count_images(["user={$event->display_user->name}"]);
        $i_days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;
        $h_image_rate = sprintf("%.1f", ($i_image_count / $i_days_old));
        $images_link = search_link(["user={$event->display_user->name}"]);
        $event->add_part(emptyHTML(
            A(["href" => $images_link], "Posts uploaded"),
            ": $i_image_count, $h_image_rate per day"
        ));
    }

    #[EventListener]
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
            $event->replace('$date', date('c', \Safe\strtotime($event->image->posted)));
        }
        $event->replace("\\n", "\n");
    }

    private function redirect_to_next_image(Image $image, ?string $search = null): void
    {
        if (!is_null($search)) {
            $terms = SearchTerm::explode($search);
            $fragment = "search=" . url_escape($search);
        } else {
            $terms = [];
            $fragment = null;
        }

        $target_image = $image->get_next($terms);

        if ($target_image === null) {
            $redirect_target = Url::referer_or(search_link(), ['post/view']);
        } else {
            $redirect_target = make_link("post/view/{$target_image->id}", fragment: $fragment);
        }

        Ctx::$page->set_redirect($redirect_target);
    }

    private function can_user_delete_image(User $user, Image $image): bool
    {
        if ($user->can(ImagePermission::DELETE_OWN_IMAGE) && $image->owner_id === $user->id) {
            return true;
        }
        return $user->can(ImagePermission::DELETE_IMAGE);
    }

    private function send_file(int $image_id, string $type, QueryArray $params): void
    {
        $page = Ctx::$page;
        $image = Image::by_id_ex($image_id);

        if ($type === "thumb") {
            $mime = new MimeType(Ctx::$config->get(ThumbnailConfig::MIME));
            $file = $image->get_thumb_filename();
        } else {
            $mime = $image->get_mime();
            $file = $image->get_image_filename();
        }
        if (!$file->exists()) {
            throw new PostNotFound("Image not found");
        }

        if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
            $if_modified_since = \Safe\preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
        } else {
            $if_modified_since = "";
        }
        $gmdate_mod = gmdate('D, d M Y H:i:s', $file->filemtime()) . ' GMT';

        if ($if_modified_since === $gmdate_mod) {
            $page->set_code(304);
            $page->set_data(MimeType::TEXT, "");
        } else {
            $page->add_http_header("Last-Modified: $gmdate_mod");
            if ($type === "thumb") {
                $page->set_file($mime, $file);
            } else {
                $page->set_file($mime, $file, filename: $image->get_nice_image_name(), disposition: "inline");
            }

            if (Ctx::$config->get(ImageConfig::EXPIRES)) {
                $expires = date(DATE_RFC1123, time() + Ctx::$config->get(ImageConfig::EXPIRES));
            } else {
                $expires = 'Fri, 2 Sep 2101 12:42:42 GMT'; // War was beginning
            }
            $page->add_http_header('Expires: ' . $expires);
        }

        send_event(new ImageDownloadingEvent($image, $file, $mime, $params));
    }
}
