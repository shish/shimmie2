<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkImportExport extends DataHandlerExtension
{
    public const EXPORT_ACTION_NAME = "bulk_export";
    public const EXPORT_INFO_FILE_NAME = "export.json";
    protected array $SUPPORTED_MIME = [MimeType::ZIP];


    public function onDataUpload(DataUploadEvent $event): void
    {
        global $user, $database;

        if ($this->supported_mime($event->mime) &&
            $user->can(Permissions::BULK_IMPORT)) {
            $zip = new \ZipArchive();

            if ($zip->open($event->tmpname) === true) {
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
                        if ($image != null) {
                            $skipped++;
                            log_info(BulkImportExportInfo::KEY, "Post $item->hash already present, skipping");
                            continue;
                        }

                        $tmpfile = shm_tempnam("bulk_import");
                        $stream = $zip->getStream($item->hash);
                        if ($stream === false) {
                            throw new UserError("Could not import " . $item->hash . ": File not in zip");
                        }

                        file_put_contents($tmpfile, $stream);

                        $database->with_savepoint(function () use ($item, $tmpfile, $event) {
                            $images = send_event(new DataUploadEvent($tmpfile, basename($item->filename), 0, [
                                'tags' => $item->new_tags,
                            ]))->images;

                            if (count($images) == 0) {
                                throw new UserError("Unable to import file $item->hash");
                            }
                            foreach ($images as $image) {
                                $event->images[] = $image;
                                if ($item->source != null) {
                                    $image->set_source($item->source);
                                }
                                send_event(new BulkImportEvent($image, $item));
                            }
                        });

                        $total++;
                    } catch (\Exception $ex) {
                        $failed++;
                        log_error(BulkImportExportInfo::KEY, "Could not import " . $item->hash . ": " . $ex->getMessage(), "Could not import " . $item->hash . ": " . $ex->getMessage());
                    } finally {
                        if (!empty($tmpfile) && is_file($tmpfile)) {
                            unlink($tmpfile);
                        }
                    }
                }

                log_info(
                    BulkImportExportInfo::KEY,
                    "Imported $total items, skipped $skipped, $failed failed",
                    "Imported $total items, skipped $skipped, $failed failed"
                );
            } else {
                throw new UserError("Could not open zip archive");
            }
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::BULK_EXPORT)) {
            $event->add_action(self::EXPORT_ACTION_NAME, "Export");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $user, $page;

        if ($user->can(Permissions::BULK_EXPORT) &&
            ($event->action == self::EXPORT_ACTION_NAME)) {
            $download_filename = $user->name . '-' . date('YmdHis') . '.zip';
            $zip_filename = shm_tempnam("bulk_export");
            $zip = new \ZipArchive();

            $json_data = [];

            if ($zip->open($zip_filename, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE) === true) {
                foreach ($event->items as $image) {
                    $img_loc = warehouse_path(Image::IMAGE_DIR, $image->hash, false);

                    $export_event = send_event(new BulkExportEvent($image));
                    $data = $export_event->fields;
                    $data["hash"] = $image->hash;
                    $data["tags"] = $image->get_tag_array();
                    $data["filename"] = $image->filename;
                    $data["source"] = $image->source;

                    $json_data[] = $data;

                    $zip->addFile($img_loc, $image->hash);
                }

                $json_data = \Safe\json_encode($json_data, JSON_PRETTY_PRINT);
                $zip->addFromString(self::EXPORT_INFO_FILE_NAME, $json_data);

                $zip->close();

                $page->set_mode(PageMode::FILE);
                $page->set_file($zip_filename, true);
                $page->set_filename($download_filename);

                $event->redirect = false;
            }
        }
    }

    // we don't actually do anything, just accept one upload and spawn several
    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
    }

    protected function check_contents(string $tmpname): bool
    {
        return false;
    }

    protected function create_thumb(Image $image): bool
    {
        return false;
    }

    /**
     * @return array<mixed>
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
