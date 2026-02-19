<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, LABEL};

final class BulkDownload extends Extension
{
    public const KEY = "bulk_download";
    public const EXPORT_INFO_FILE_NAME = "export.json";

    #[EventListener]
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action(
            "download",
            "Download ZIP",
            permission: BulkDownloadPermission::BULK_DOWNLOAD,
            block: LABEL(INPUT(["type" => 'checkbox', "name" => 'bulk_download_metadata', "value" => 'true', "checked" => true]), " with metadata"),
        );
    }

    #[EventListener]
    public function onBulkAction(BulkActionEvent $event): void
    {
        if (!Ctx::$user->can(BulkDownloadPermission::BULK_DOWNLOAD)) {
            return;
        }
        if ($event->action !== "download") {
            return;
        }

        $include_metadata = bool_escape($event->params['bulk_download_metadata'] ?? "false");

        $download_filename = Ctx::$user->name . '-' . date('YmdHis') . '.zip';
        $zip_filename = shm_tempnam("bulk_download");
        $zip = new \ZipArchive();

        $size_total = 0;
        $max_size = Ctx::$config->get(BulkDownloadConfig::SIZE_LIMIT);
        $json_data = [];

        if ($zip->open($zip_filename->str(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($event->items as $image) {
                $img_loc = $image->get_media_filename();

                if (!Ctx::$user->can(BulkDownloadPermission::UNLIMITED_SIZE)) {
                    $size_total += $img_loc->filesize();
                    if ($size_total > $max_size) {
                        throw new UserError("Bulk download limited to ".human_filesize($max_size));
                    }
                }

                if ($include_metadata) {
                    $image_info = send_event(new PostInfoGetEvent($image))->params;
                    $image_info["hash"] = $image->hash;
                    $image_info["filename"] = $image->filename;
                    $image_info["_filename"] = $image->get_nice_media_name();
                    $json_data[] = $image_info->toArray();
                }

                $zip->addFile($img_loc->str(), $image->get_nice_media_name());
            }

            if (count($json_data) > 0) {
                $json_str = \Safe\json_encode($json_data, JSON_PRETTY_PRINT);
                $zip->addFromString(self::EXPORT_INFO_FILE_NAME, $json_str);
            }
        }

        $zip->close();
        Ctx::$page->set_file(MimeType::ZIP, $zip_filename, true, filename: $download_filename);
        $event->redirect = false;
    }
}
