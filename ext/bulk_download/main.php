<?php



class BulkDownload extends Extension
{
    private const DOWNLOAD_ACTION_NAME = "bulk_download";

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user, $config;

        if ($user->can(Permissions::BULK_DOWNLOAD)) {
            $event->add_action(BulkDownload::DOWNLOAD_ACTION_NAME, "Download ZIP");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user, $page;

        if($user->can(Permissions::BULK_DOWNLOAD)&&
            ($event->action == BulkDownload::DOWNLOAD_ACTION_NAME)) {

            $zip_filename = data_path($user->name.'-'.date('YmdHis').'.zip');
            $zip = new ZipArchive;

            if ($zip->open($zip_filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true) {
                foreach ($event->items as $image) {
                    $img_loc = warehouse_path(Image::IMAGE_DIR, $image->hash, false);
                    $filename = urldecode($image->get_nice_image_name());
                    $filename = str_replace(":",";",$filename);
                    $zip->addFile($img_loc, $filename);
                }

                $zip->close();
                $event->download_file = $zip_filename;
                $event->download_delete = true;
            }

        }
    }

}
