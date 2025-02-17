<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkDownload extends Extension
{
    private const DOWNLOAD_ACTION_NAME = "bulk_download";

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::BULK_DOWNLOAD)) {
            $event->add_action(BulkDownload::DOWNLOAD_ACTION_NAME, "Download ZIP");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $user, $page, $config;

        if ($user->can(Permissions::BULK_DOWNLOAD) &&
            ($event->action == BulkDownload::DOWNLOAD_ACTION_NAME)) {
            $download_filename = $user->name . '-' . date('YmdHis') . '.zip';
            $zip_filename = shm_tempnam("bulk_download");
            $zip = new \ZipArchive();
            $size_total = 0;
            $max_size = $config->get_int(BulkDownloadConfig::SIZE_LIMIT);

            if ($zip->open($zip_filename, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE) === true) {
                foreach ($event->items as $image) {
                    $img_loc = warehouse_path(Image::IMAGE_DIR, $image->hash, false);
                    $size_total += filesize($img_loc);
                    if ($size_total > $max_size) {
                        throw new UserError("Bulk download limited to ".human_filesize($max_size));
                    }

                    $filename = urldecode($image->get_nice_image_name());
                    $filename = str_replace(":", ";", $filename);
                    $zip->addFile($img_loc, $filename);
                }

                $zip->close();

                $page->set_mode(PageMode::FILE);
                $page->set_file($zip_filename, true);
                $page->set_filename($download_filename);

                $event->redirect = false;
            }
        }
    }
}
