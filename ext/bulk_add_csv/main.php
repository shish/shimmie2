<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkAddCSV extends Extension
{
    /** @var BulkAddCSVTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;
        if ($event->page_matches("bulk_add_csv")) {
            if ($user->can(Permissions::BULK_ADD) && $user->check_auth_token() && isset($_POST['csv'])) {
                shm_set_timeout(null);
                $this->add_csv($_POST['csv']);
                $this->theme->display_upload_results($page);
            }
        }
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "  bulk-add-csv [/path/to.csv]\n";
            print "	Import this .csv file (refer to documentation)\n\n";
        }
        if ($event->cmd == "bulk-add-csv") {
            global $user;

            //Nag until CLI is admin by default
            if (!$user->can(Permissions::BULK_ADD)) {
                print "Not running as an admin, which can cause problems.\n";
                print "Please add the parameter: -u admin_username";
            } elseif (count($event->args) == 1) {
                $this->add_csv($event->args[0]);
            }
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $this->theme->display_admin_block();
    }

    /**
     * Generate the necessary DataUploadEvent for a given image and tags.
     */
    private function add_image(string $tmpname, string $filename, string $tags, string $source, string $rating, string $thumbfile)
    {
        $event = add_image($tmpname, $filename, $tags, $source);
        if ($event->image_id == -1) {
            throw new UploadException("File type not recognised");
        } else {
            if (class_exists("Shimmie2\RatingSetEvent") && in_array($rating, ["s", "q", "e"])) {
                send_event(new RatingSetEvent(Image::by_id($event->image_id), $rating));
            }
            if (file_exists($thumbfile)) {
                copy($thumbfile, warehouse_path(Image::THUMBNAIL_DIR, $event->hash));
            }
        }
    }

    private function add_csv(string $csvfile)
    {
        if (!file_exists($csvfile)) {
            $this->theme->add_status("Error", "$csvfile not found");
            return;
        }
        if (!is_file($csvfile) || strtolower(substr($csvfile, -4)) != ".csv") {
            $this->theme->add_status("Error", "$csvfile doesn't appear to be a csv file");
            return;
        }

        $linenum = 1;
        $list = "";
        $csvhandle = fopen($csvfile, "r");

        while (($csvdata = fgetcsv($csvhandle, 0, ",")) !== false) {
            if (count($csvdata) != 5) {
                if (strlen($list) > 0) {
                    $this->theme->add_status("Error", "<b>Encountered malformed data. Line $linenum $csvfile</b><br>".$list);
                    fclose($csvhandle);
                    return;
                } else {
                    $this->theme->add_status("Error", "<b>Encountered malformed data. Line $linenum $csvfile</b><br>Check <a href=\"" . make_link("ext_doc/bulk_add_csv") . "\">here</a> for the expected format");
                    fclose($csvhandle);
                    return;
                }
            }
            $fullpath = $csvdata[0];
            $tags = trim($csvdata[1]);
            $source = $csvdata[2];
            $rating = $csvdata[3];
            $thumbfile = $csvdata[4];
            $shortpath = pathinfo($fullpath, PATHINFO_BASENAME);
            $list .= "<br>".html_escape("$shortpath (".str_replace(" ", ", ", $tags).")... ");
            if (file_exists($csvdata[0]) && is_file($csvdata[0])) {
                try {
                    $this->add_image($fullpath, $shortpath, $tags, $source, $rating, $thumbfile);
                    $list .= "ok\n";
                } catch (\Exception $ex) {
                    $list .= "failed:<br>". $ex->getMessage();
                }
            } else {
                $list .= "failed:<br> File doesn't exist ".html_escape($csvdata[0]);
            }
            $linenum += 1;
        }

        if (strlen($list) > 0) {
            $this->theme->add_status("Adding $csvfile", $list);
        }
        fclose($csvhandle);
    }
}
