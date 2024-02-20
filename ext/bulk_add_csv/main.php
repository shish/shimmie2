<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface,InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

class BulkAddCSV extends Extension
{
    /** @var BulkAddCSVTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("bulk_add_csv", method: "POST", permission: Permissions::BULK_ADD)) {
            $csv = $event->req_POST('csv');
            shm_set_timeout(null);
            $this->add_csv($csv);
            $this->theme->display_upload_results($page);
        }
    }

    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('bulk-add-csv')
            ->addArgument('path-to-csv', InputArgument::REQUIRED)
            ->setDescription('Import posts from a given CSV file')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                global $user;
                if (!$user->can(Permissions::BULK_ADD)) {
                    $output->writeln("Not running as an admin, which can cause problems.");
                    $output->writeln("Please add the parameter: -u admin_username");
                    return Command::FAILURE;
                }

                $this->add_csv($input->getArgument('path-to-csv'));
                return Command::SUCCESS;
            });
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block();
    }

    /**
     * Generate the necessary DataUploadEvent for a given image and tags.
     *
     * @param string[] $tags
     */
    private function add_image(string $tmpname, string $filename, array $tags, string $source, string $rating, string $thumbfile): void
    {
        global $database;
        $database->with_savepoint(function () use ($tmpname, $filename, $tags, $source, $rating, $thumbfile) {
            $event = send_event(new DataUploadEvent($tmpname, basename($filename), 0, [
                'tags' => Tag::implode($tags),
                'source' => $source,
                'rating' => $rating,
            ]));

            if (count($event->images) == 0) {
                throw new UploadException("File type not recognised");
            } else {
                if (file_exists($thumbfile)) {
                    copy($thumbfile, warehouse_path(Image::THUMBNAIL_DIR, $event->hash));
                }
            }
        });
    }

    private function add_csv(string $csvfile): void
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
        $csvhandle = \Safe\fopen($csvfile, "r");

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
            $tags = Tag::explode(trim($csvdata[1]));
            $source = $csvdata[2];
            $rating = $csvdata[3];
            $thumbfile = $csvdata[4];
            $shortpath = pathinfo($fullpath, PATHINFO_BASENAME);
            $list .= "<br>".html_escape("$shortpath (".implode(", ", $tags).")... ");
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
