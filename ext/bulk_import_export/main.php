<?php declare(strict_types=1);


class BulkImportExport extends DataHandlerExtension
{
    const EXPORT_ACTION_NAME = "bulk_export";
    const EXPORT_INFO_FILE_NAME = "export.json";
    protected array $SUPPORTED_MIME = [MimeType::ZIP];


    public function onDataUpload(DataUploadEvent $event)
    {
        global $user, $database;

        if ($this->supported_mime($event->mime) &&
            $user->can(Permissions::BULK_IMPORT)) {
            $zip = new ZipArchive;

            if ($zip->open($event->tmpname) === true) {
                $json_data = $this->get_export_data($zip);

                if (empty($json_data)) {
                    return;
                }

                $total = 0;
                $skipped = 0;
                $failed = 0;

                $database->commit();

                while (!empty($json_data)) {
                    $item = array_pop($json_data);
                    $database->begin_transaction();
                    try {
                        $image = Image::by_hash($item->hash);
                        if ($image!=null) {
                            $skipped++;
                            log_info(BulkImportExportInfo::KEY, "Post $item->hash already present, skipping");
                            $database->commit();
                            continue;
                        }

                        $tmpfile = tempnam(sys_get_temp_dir(), "shimmie_bulk_import");
                        $stream = $zip->getStream($item->hash);
                        if ($zip === false) {
                            throw new SCoreException("Could not import " . $item->hash . ": File not in zip");
                        }

                        file_put_contents($tmpfile, $stream);

                        $id = add_image($tmpfile, $item->filename, Tag::implode($item->tags));

                        if ($id==-1) {
                            throw new SCoreException("Unable to import file $item->hash");
                        }

                        $image = Image::by_id($id);

                        if ($image==null) {
                            throw new SCoreException("Unable to import file $item->hash");
                        }

                        if ($item->source!=null) {
                            $image->set_source($item->source);
                        }
                        send_event(new BulkImportEvent($image, $item));

                        $database->commit();
                        $total++;
                    } catch (Exception $ex) {
                        $failed++;
                        try {
                            $database->rollBack();
                        } catch (Exception $ex2) {
                            log_error(BulkImportExportInfo::KEY, "Could not roll back transaction: " . $ex2->getMessage(), "Could not import " . $item->hash . ": " . $ex->getMessage());
                        }
                        log_error(BulkImportExportInfo::KEY, "Could not import " . $item->hash . ": " . $ex->getMessage(), "Could not import " . $item->hash . ": " . $ex->getMessage());
                        continue;
                    } finally {
                        if (!empty($tmpfile) && is_file($tmpfile)) {
                            unlink($tmpfile);
                        }
                    }
                }
                $event->image_id = -2; // default -1 = upload wasn't handled

                log_info(
                    BulkImportExportInfo::KEY,
                    "Imported $total items, skipped $skipped, $failed failed",
                    "Imported $total items, skipped $skipped, $failed failed"
                );
            } else {
                throw new SCoreException("Could not open zip archive");
            }
        }
    }



    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::BULK_EXPORT)) {
            $event->add_action(self::EXPORT_ACTION_NAME, "Export");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user, $page;

        if ($user->can(Permissions::BULK_EXPORT) &&
            ($event->action == self::EXPORT_ACTION_NAME)) {
            $download_filename = $user->name . '-' . date('YmdHis') . '.zip';
            $zip_filename = tempnam(sys_get_temp_dir(), "shimmie_bulk_export");
            $zip = new ZipArchive;

            $json_data = [];

            if ($zip->open($zip_filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true) {
                foreach ($event->items as $image) {
                    $img_loc = warehouse_path(Image::IMAGE_DIR, $image->hash, false);

                    $export_event = new BulkExportEvent($image);
                    send_event($export_event);
                    $data = $export_event->fields;
                    $data["hash"] = $image->hash;
                    $data["tags"] = $image->get_tag_array();
                    $data["filename"] = $image->filename;
                    $data["source"] = $image->source;

                    array_push($json_data, $data);

                    $zip->addFile($img_loc, $image->hash);
                }

                $json_data = json_encode($json_data, JSON_PRETTY_PRINT);
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

    protected function create_thumb(string $hash, string $mime): bool
    {
        return false;
    }

    private function get_export_data(ZipArchive $zip): ?array
    {
        $info = $zip->getStream(self::EXPORT_INFO_FILE_NAME);
        if ($info !== false) {
            try {
                $json_string = stream_get_contents($info);
                $json_data = json_decode($json_string);
            } finally {
                fclose($info);
            }
            return $json_data;
        } else {
            return null;
        }
    }
}
