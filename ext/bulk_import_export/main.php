<?php


class BulkImportExport extends Extension
{
    const EXPORT_ACTION_NAME = "bulk_export";
    const EXPORT_INFO_FILE_NAME = "export.json";
    const SUPPORTED_EXT = ["zip"];

    private function supported_ext($ext)
    {
        return in_array(strtolower($ext), self::SUPPORTED_EXT);
    }

    public function onDataUpload(DataUploadEvent $event)
    {
        global $user, $config;

        if ($this->supported_ext($event->type) &&
            $user->can(Permissions::BULK_IMPORT)) {

            $zip = new ZipArchive;

            $json_data = [];

            if ($zip->open($event->tmpname) === true) {
                $info = $zip->getStream(self::EXPORT_INFO_FILE_NAME);
                $json_data = [];
                if ($info !== false) {
                    try {
                        $json_string = stream_get_contents($info);
                        $json_data = json_decode($json_string);
                    } finally {
                        fclose($info);
                    }

                } else {
                    throw new Exception("Could not get " . self::EXPORT_INFO_FILE_NAME . " from archive");
                }
                $total = 0;
                foreach ($json_data as $item) {
                    $image = Image::by_hash($item->hash);
                    if($image!=null) {
                        continue;
                    }

                    $tmpfile = tempnam("/tmp", "shimmie_bulk_import");
                    $stream = $zip->getStream($item->hash);
                    if ($zip === false) {
                        log_error("BulkImportExport", "Could not import " . $item->zip_file . ": File not in zip", "Could not import " . $item->zip_file . ": File not in zip");
                        continue;
                    }

                    try {
                        file_put_contents($tmpfile, $stream);
                        add_image($tmpfile, $item->filename, Tag::implode($item->tags));

                        $image = Image::by_hash($item->hash);
                        if($item->source!=null) {
                            $image->set_source($item->source);
                        }
                        send_event(new BulkImportEvent($image, $item));



                        $total++;
                    } catch (Exception $ex) {
                        log_error("BulkImportExport", "Could not import " . $item->zip_file . ": " . $ex->getMessage(), "Could not import " . $item->zip_file . ": " . $ex->getMessage());
                        continue;
                    }
                }
                $event->image_id = -2; // default -1 = upload wasn't handled

                log_info("BulkImportExport", "Imported " . $total . " items", "Imported " . $total . " items");
            } else {
                throw new Exception("Could not open zip archive");
            }
        }
    }



    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user, $config;

        if ($user->can(Permissions::BULK_EXPORT)) {
            $event->add_action(self::EXPORT_ACTION_NAME, "Export");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user, $page;

        if ($user->can(Permissions::BULK_EXPORT) &&
            ($event->action == self::EXPORT_ACTION_NAME)) {

            $zip_filename = data_path($user->name . '-' . date('YmdHis') . '.zip');
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
                $event->download_file = $zip_filename;
                $event->download_delete = true;
            }

        }
    }

}
