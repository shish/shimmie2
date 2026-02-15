<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{LI,UL,emptyHTML};

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

/** @extends Extension<BulkAddCSVTheme> */
final class BulkAddCSV extends Extension
{
    public const KEY = "bulk_add_csv";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("bulk_add_csv", method: "POST", permission: BulkAddPermission::BULK_ADD)) {
            $csv = $event->POST->req('csv');
            Ctx::$event_bus->set_timeout(null);
            $this->add_csv(new Path($csv));
            $this->theme->display_upload_results();
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('bulk-add-csv')
            ->addArgument('path-to-csv', InputArgument::REQUIRED)
            ->setDescription('Import posts from a given CSV file')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                if (!Ctx::$user->can(BulkAddPermission::BULK_ADD)) {
                    $output->writeln("Not running as an admin, which can cause problems.");
                    $output->writeln("Please add the parameter: -u admin_username");
                    return Command::FAILURE;
                }

                $this->add_csv(new Path($input->getArgument('path-to-csv')));
                return Command::SUCCESS;
            });
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block();
    }

    /**
     * Generate the necessary DataUploadEvent for a given image and tags.
     *
     * @param tag-array $tags
     */
    private function add_image(Path $tmpname, string $filename, array $tags, string $source, string $rating, Path $thumbfile): void
    {
        global $database;
        $database->with_savepoint(function () use ($tmpname, $filename, $tags, $source, $rating, $thumbfile) {
            $event = send_event(new DataUploadEvent($tmpname, basename($filename), 0, new QueryArray([
                'tags' => Tag::implode($tags),
                'source' => $source,
                'rating' => $rating,
            ])));

            if (count($event->images) === 0) {
                throw new UploadException("File type not recognised");
            } else {
                if ($thumbfile->exists()) {
                    $thumbfile->copy(Filesystem::warehouse_path(Image::THUMBNAIL_DIR, $event->hash));
                }
            }
        });
    }

    private function add_csv(Path $csvfile): void
    {
        if (!$csvfile->exists()) {
            $this->theme->add_status("Error", emptyHTML("{$csvfile->str()} not found"));
            return;
        }
        if (!$csvfile->is_file() || !str_ends_with(strtolower($csvfile->str()), ".csv")) {
            $this->theme->add_status("Error", emptyHTML("{$csvfile->str()} doesn't appear to be a csv file"));
            return;
        }

        $linenum = 1;
        $results = UL();
        $csvhandle = \Safe\fopen($csvfile->str(), "r");

        while (($csvdata = \Safe\fgetcsv($csvhandle, 0, ",")) !== false) {
            if (count($csvdata) !== 5) {
                $results->appendChild(LI("Line $linenum: Malformed data"));
                break;
            }
            [$fullpath, $tags_string, $source, $rating, $thumbfile] = $csvdata;
            $tags = Tag::explode(trim($tags_string));
            $shortpath = pathinfo($fullpath, PATHINFO_BASENAME);

            if (file_exists($csvdata[0]) && is_file($csvdata[0])) {
                try {
                    $this->add_image(new Path($fullpath), $shortpath, $tags, $source, $rating, new Path($thumbfile));
                    $results->appendChild(LI("$shortpath - added with tags: " . implode(", ", $tags)));
                } catch (\Exception $ex) {
                    $results->appendChild(LI("$shortpath - failed: " . $ex->getMessage()));
                }
            } else {
                $results->appendChild(LI("$shortpath - file doesn't exist"));
            }
            $linenum += 1;
        }

        $this->theme->add_status("Adding {$csvfile->str()}", $results);
        fclose($csvhandle);
    }
}
