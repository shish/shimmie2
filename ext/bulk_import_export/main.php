<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkImportExport extends DataHandlerExtension
{
    public const KEY = "bulk_import_export";
    public const SUPPORTED_MIME = [MimeType::ZIP];

    public const EXPORT_INFO_FILE_NAME = "export.json";

    #[EventListener]
    public function onDataUpload(DataUploadEvent $event): void
    {
        global $database;

        if (
            !$this->supported_mime($event->mime)
            || !Ctx::$user->can(BulkImportExportPermission::BULK_IMPORT)
        ) {
            return;
        }

        $zip = new \ZipArchive();
        if (!$zip->open($event->tmpname->str())) {
            throw new UserError("Could not open zip archive");
        }

        $json_data = $this->get_export_data($zip);
        if (empty($json_data)) {
            return;
        }

        $total = 0;
        $skipped = 0;
        $failed = 0;

        while (!empty($json_data)) {
            $item = array_pop($json_data);
            try {
                $image = Image::by_hash($item->hash);
                if ($image !== null) {
                    $skipped++;
                    Log::info(BulkImportExportInfo::KEY, "Post $item->hash already present, skipping");
                    continue;
                }

                $tmpfile = shm_tempnam("bulk_import");
                $stream = $zip->getStream($item->hash);
                if ($stream === false) {
                    throw new UserError("Could not import " . $item->hash . ": File not in zip");
                }

                $tmpfile->put_contents($stream);

                $database->with_savepoint(function () use ($item, $tmpfile, $event) {
                    $images = send_event(new DataUploadEvent($tmpfile, basename($item->filename), 0, new QueryArray([
                        'tags' => $item->tags,
                    ])))->images;

                    if (count($images) === 0) {
                        throw new UserError("Unable to import file $item->hash");
                    }
                    foreach ($images as $image) {
                        $event->images[] = $image;
                        if ($item->source !== null) {
                            $image->set_source($item->source);
                        }
                        send_event(new BulkImportEvent($image, $item));
                    }
                });

                $total++;
            } catch (\Exception $ex) {
                $failed++;
                Log::error(BulkImportExportInfo::KEY, "Could not import " . $item->hash . ": " . $ex->getMessage(), "Could not import " . $item->hash . ": " . $ex->getMessage());
            } finally {
                if (!empty($tmpfile) && $tmpfile->is_file()) {
                    $tmpfile->unlink();
                }
            }
        }

        Log::info(
            BulkImportExportInfo::KEY,
            "Imported $total items, skipped $skipped, $failed failed",
            "Imported $total items, skipped $skipped, $failed failed"
        );
    }

    #[EventListener]
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action("export", "Export", permission: BulkImportExportPermission::BULK_EXPORT);
    }

    #[EventListener]
    public function onBulkAction(BulkActionEvent $event): void
    {
        if (Ctx::$user->can(BulkImportExportPermission::BULK_EXPORT)
            && ($event->action === "export")
        ) {
            $zip_filename = shm_tempnam("bulk_export");
            $zip = new \ZipArchive();

            $json_data = [];

            if ($zip->open($zip_filename->str(), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                foreach ($event->items as $image) {
                    $export_event = send_event(new BulkExportEvent($image));
                    $data = $export_event->fields;
                    $data["hash"] = $image->hash;
                    $data["tags"] = $image->get_tag_array();
                    $data["filename"] = $image->filename;
                    $data["source"] = $image->source;

                    $json_data[] = $data;

                    $zip->addFile($image->get_image_filename()->str(), $image->hash);
                }

                $json_data = \Safe\json_encode($json_data, JSON_PRETTY_PRINT);
                $zip->addFromString(self::EXPORT_INFO_FILE_NAME, $json_data);

                $zip->close();

                Ctx::$page->set_file(MimeType::ZIP, $zip_filename, true, filename: Ctx::$user->name . '-' . date('YmdHis') . '.zip');

                $event->redirect = false;
            }
        }
    }

    // we don't actually do anything, just accept one upload and spawn several
    protected function media_check_properties(Image $image): ?MediaProperties
    {
        return null;
    }

    protected function check_contents(Path $tmpname): bool
    {
        return false;
    }

    protected function create_thumb(Image $image): bool
    {
        return false;
    }

    /**
     * @return null|array<\stdClass>
     */
    private function get_export_data(\ZipArchive $zip): ?array
    {
        $info = $zip->getStream(self::EXPORT_INFO_FILE_NAME);
        if ($info !== false) {
            try {
                $json_string = \Safe\stream_get_contents($info);
                $json_data = json_decode($json_string);
                return $json_data;
            } finally {
                fclose($info);
            }
        } else {
            return null;
        }
    }
}
