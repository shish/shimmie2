<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, B, BR, CODE, DIV, INPUT, LABEL, LI, OPTION, SELECT, TABLE, TBODY, TD, TFOOT, TH, TR, UL, emptyHTML};

use MicroHTML\HTMLElement;

class CronUploaderTheme extends Themelet
{
    /**
     * @param array{path:Path,total_files:int,total_mb:string} $queue_dirinfo
     * @param array{path:Path,total_files:int,total_mb:string} $uploaded_dirinfo
     * @param array{path:Path,total_files:int,total_mb:string} $failed_dirinfo
     * @param array<array{date_sent:string,message:string}>|null $log_entries
     */
    public function display_documentation(
        bool $running,
        array $queue_dirinfo,
        array $uploaded_dirinfo,
        array $failed_dirinfo,
        string $cron_cmd,
        string $cron_url,
        ?array $log_entries
    ): void {
        $page = Ctx::$page;
        $page->set_title("Cron Uploader");

        $info_html = emptyHTML();
        if (!Ctx::$config->get(UserAccountsConfig::ENABLE_API_KEYS)) {
            $info_html->appendChild(B(["style" => "color:red"], "THIS EXTENSION REQUIRES USER API KEYS TO BE ENABLED IN BOARD ADMIN"));
        }

        $info_html->appendChild(TABLE(
            $running ? TR(TD(["colspan" => '4'], B(["style" => "color:red"], "Cron upload is currently running"))) : null,
            TR(
                TH("Directory"),
                TH("Files"),
                TH("Size (MB)"),
                TH("Directory Path")
            ),
            TR(
                TD("Queue"),
                TD($queue_dirinfo['total_files']),
                TD($queue_dirinfo['total_mb']),
                TD($queue_dirinfo['path']->str())
            ),
            TR(
                TD("Uploaded"),
                TD($uploaded_dirinfo['total_files']),
                TD($uploaded_dirinfo['total_mb']),
                TD($uploaded_dirinfo['path']->str())
            ),
            TR(
                TD("Failed"),
                TD($failed_dirinfo['total_files']),
                TD($failed_dirinfo['total_mb']),
                TD($failed_dirinfo['path']->str())
            ),
        ));
        $page->add_block(new Block("Information", $info_html, "main", 10));

        $usage_html = DIV(
            ["style" => "text-align:left;"],
            "Upload your images you want to be uploaded to the queue directory using your FTP client or other means.",
            BR(),
            CODE($queue_dirinfo['path']->absolute()->str()),
            UL(
                LI("Any sub-folders will be turned into tags."),
                LI("If the file name matches \"## - tag1 tag2.png\" the tags will be used."),
                LI("If both are found, they will all be used."),
                LI("The character \";\" will be changed into \":\" in any tags.")
            ),
            "The cron uploader works by importing files from the queue folder whenever this url is visited:",
            BR(),
            CODE($cron_url),
            UL(
                LI("If an import is already running, another cannot start until it is done."),
                LI("Each time it runs it will import for up to ".number_format(intval(ini_get('max_execution_time')) * .8)." seconds. This is controlled by the PHP max execution time."),
                LI("Uploaded images will be moved to the 'uploaded' directory into a subfolder named after the time the import started. It's recommended that you remove everything out of this directory from time to time. If you have admin controls enabled, this can be done from <a href='".make_link("admin")."'>Board Admin</a>."),
                LI("If you enable the db logging extension, you can view the log output on this screen. Otherwise the log will be written to a file at ".Ctx::$user->get_config()->get(CronUploaderUserConfig::DIR).DIRECTORY_SEPARATOR."uploads.log")
            )
        );
        $page->add_block(new Block("Usage Guide", $usage_html, "main", 20));

        if (!empty($log_entries)) {
            $log_html = TABLE(["class" => "cron_uploader_log"]);
            foreach ($log_entries as $entry) {
                $log_html->appendChild(TR(
                    TD($entry["date_sent"]),
                    TD($entry["message"])
                ));
            }
            $page->add_block(new Block("Log", $log_html, "main", 40));
        }
    }

    public function get_user_options(): HTMLElement
    {
        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/cron_uploader"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("Cron Uploader")
                    ),
                    TR(
                        TH("Root dir"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "required" => true]))
                    ),
                    TR(
                        TH(),
                        TD(
                            LABEL(INPUT(["type" => 'checkbox', "name" => 'stop_on_error']), "Stop On Error")
                        )
                    ),
                    TR(
                        TH(\MicroHTML\rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    )
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Save Settings"])))
                )
            )
        );
        $html = emptyHTML($form);
        return $html;
    }

    /**
     * @param Path[] $failed_dirs
     */
    public function display_form(array $failed_dirs): void
    {
        $failed_dir_select = SELECT(["name" => "failed_dir", "required" => true]);
        foreach ($failed_dirs as $dir) {
            $failed_dir_select->appendChild(OPTION(["value" => $dir->str()], $dir->str()));
        }

        $html = emptyHTML(
            A(["href" => make_link("cron_upload")], "Cron uploader documentation"),
            SHM_SIMPLE_FORM(
                make_link("admin/cron_uploader_restage"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TH("Failed dir"),
                        TD($failed_dir_select),
                    ),
                    TR(
                        TD(["colspan" => 2], SHM_SUBMIT("Re-stage files to queue"))
                    )
                ),
            ),
            SHM_SIMPLE_FORM(
                make_link("admin/cron_uploader_clear_queue"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TD(["colspan" => 2], SHM_SUBMIT("Clear queue folder"))
                    )
                ),
            ),
            SHM_SIMPLE_FORM(
                make_link("admin/cron_uploader_clear_uploaded"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TD(["colspan" => 2], SHM_SUBMIT("Clear uploaded folder"))
                    )
                ),
            ),
            SHM_SIMPLE_FORM(
                make_link("admin/cron_uploader_clear_failed"),
                TABLE(
                    ["class" => "form"],
                    TR(
                        TD(["colspan" => 2], SHM_SUBMIT("Clear failed folder"))
                    )
                ),
            ),
        );

        Ctx::$page->add_block(new Block("Cron Upload", $html));
    }
}
