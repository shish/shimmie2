<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkDownload extends Extension
{
    public const KEY = "bulk_download";

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action("download", "Download ZIP", permission: BulkDownloadPermission::BULK_DOWNLOAD);
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        if (
            Ctx::$user->can(BulkDownloadPermission::BULK_DOWNLOAD)
            && ($event->action === "download")
        ) {
            $download_filename = Ctx::$user->name . '-' . date('YmdHis') . '.zip';
            $zip_filename = shm_tempnam("bulk_download");
            $zip = new \ZipArchive();
            $size_total = 0;
            $max_size = Ctx::$config->get(BulkDownloadConfig::SIZE_LIMIT);

            if ($zip->open($zip_filename->str(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($event->items as $image) {
                    $img_loc = Filesystem::warehouse_path(Image::IMAGE_DIR, $image->hash, false);
                    $size_total += $img_loc->filesize();
                    if ($size_total > $max_size) {
                        throw new UserError("Bulk download limited to ".human_filesize($max_size));
                    }

                    $filename = urldecode($image->get_nice_image_name());
                    $filename = str_replace(":", ";", $filename);
                    $zip->addFile($img_loc->str(), $filename);
                }

                $zip->close();

                Ctx::$page->set_file(MimeType::ZIP, $zip_filename, true);
                Ctx::$page->set_filename($download_filename);

                $event->redirect = false;
            }
        }
    }
}
