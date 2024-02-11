<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LABEL;
use function MicroHTML\TABLE;
use function MicroHTML\TBODY;
use function MicroHTML\TFOOT;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;
use function MicroHTML\INPUT;
use function MicroHTML\rawHTML;
use function MicroHTML\emptyHTML;

class CronUploaderTheme extends Themelet
{
    /**
     * @param array{path:string,total_files:int,total_mb:string} $queue_dirinfo
     * @param array{path:string,total_files:int,total_mb:string} $uploaded_dirinfo
     * @param array{path:string,total_files:int,total_mb:string} $failed_dirinfo
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
        global $page, $config, $user_config;

        $info_html = "";

        $page->set_title("Cron Uploader");
        $page->set_heading("Cron Uploader");

        if (!$config->get_bool(UserConfig::ENABLE_API_KEYS)) {
            $info_html .= "<b style='color:red'>THIS EXTENSION REQUIRES USER API KEYS TO BE ENABLED IN <a href=''>BOARD ADMIN</a></b><br/>";
        }

        $info_html .= "<b>Information</b>
			<br>
			<table style='width:470px;'>
			" . ($running ? "<tr><td colspan='4'><b style='color:red'>Cron upload is currently running</b></td></tr>" : "") . "
			<tr>
			<td style='width:90px;'><b>Directory</b></td>
			<td style='width:90px;'><b>Files</b></td>
			<td style='width:90px;'><b>Size (MB)</b></td>
			<td style='width:200px;'><b>Directory Path</b></td>
			</tr><tr>
			<td>Queue</td>
			<td>{$queue_dirinfo['total_files']}</td>
			<td>{$queue_dirinfo['total_mb']}</td>
			<td>{$queue_dirinfo['path']}</td>
			</tr><tr>
			<td>Uploaded</td>
			<td>{$uploaded_dirinfo['total_files']}</td>
			<td>{$uploaded_dirinfo['total_mb']}</td>
			<td>{$uploaded_dirinfo['path']}</td>
			</tr><tr>
			<td>Failed</td>
			<td>{$failed_dirinfo['total_files']}</td>
			<td>{$failed_dirinfo['total_mb']}</td>
			<td>{$failed_dirinfo['path']}</td>
			</tr></table>

			<div>Cron Command: <input type='text' size='60' value='$cron_cmd' id='cron_command'>
			<button onclick='copyInputToClipboard(\"cron_command\")'>Copy</button></div>
			<div>Create a cron job with the command above.
				Read the documentation if you're not sure what to do.</div>
            <div>URL: <input type='text' size='60' value='$cron_url' id='cron_url'>
            <button onclick='copyInputToClipboard(\"cron_url\")'>Copy</button></div>";


        $install_html = "
			This cron uploader is fairly easy to use but has to be configured first.
			<ol>
			    <li style='text-align: left;'>Install & activate this plugin.</li>
			    <li style='text-align: left;'>Go to the <a href='".make_link("setup")."'>Board Config</a> and change any settings to match your preference.</li>
			    <li style='text-align: left;'>Copy the cron command above.</li>
			    <li style='text-align: left;'>Create a cron job or something else that can open a url on specified times.
                    <br/>cron is a service that runs commands over and over again on a a schedule. You can set up cron (or any similar tool) to run the command above to trigger the import on whatever schedule you desire.
			        <br />If you're not sure how to do this, you can give the command to your web host and you can ask them to create the cron job for you.
			        <br />When you create the cron job, you choose when to upload new posts.</li>
            </ol>";


        $max_time = intval(ini_get('max_execution_time')) * .8;

        $usage_html = "Upload your images you want to be uploaded to the queue directory using your FTP client or other means.
<br />(<b>{$queue_dirinfo['path']}</b>)
                    <ol>
                        <li style='text-align: left;'>Any sub-folders will be turned into tags.</li>
                        <li style='text-align: left;'>If the file name matches \"## - tag1 tag2.png\" the tags will be used.</li>
                        <li style='text-align: left;'>If both are found, they will all be used.</li>
                        <li style='text-align: left;'>The character \";\" will be changed into \":\" in any tags.</li>
                        <li style='text-align: left;'>You can inherit categories by creating a folder that ends with \";\". For instance category;\\tag1 would result in the tag category:tag1. This allows creating a category folder, then creating many subfolders that will use that category.</li>
                    </ol>
                    The cron uploader works by importing files from the queue folder whenever this url is visited:
                <br/><pre><a href='$cron_url'>$cron_url</a></pre>

            <ul>
                <li>If an import is already running, another cannot start until it is done.</li>
                <li>Each time it runs it will import for up to ".number_format($max_time)." seconds. This is controlled by the PHP max execution time.</li>
                <li>Uploaded images will be moved to the 'uploaded' directory into a subfolder named after the time the import started. It's recommended that you remove everything out of this directory from time to time. If you have admin controls enabled, this can be done from <a href='".make_link("admin")."'>Board Admin</a>.</li>
                <li>If you enable the db logging extension, you can view the log output on this screen. Otherwise the log will be written to a file at ".$user_config->get_string(CronUploaderConfig::DIR).DIRECTORY_SEPARATOR."uploads.log</li>
			</ul>
        ";


        $block = new Block("Cron Uploader", $info_html, "main", 10);
        $block_install = new Block("Setup Guide", $install_html, "main", 30);
        $block_usage = new Block("Usage Guide", $usage_html, "main", 20);
        $page->add_block($block);
        $page->add_block($block_install);
        $page->add_block($block_usage);

        if (!empty($log_entries)) {
            $log_html = "<table class='cron_uploader_log'>";
            foreach ($log_entries as $entry) {
                $log_html .= "<tr><th>{$entry["date_sent"]}</th><td>{$entry["message"]}</td></tr>";
            }
            $log_html .= "</table>";
            $block = new Block("Log", $log_html, "main", 40);
            $page->add_block($block);
        }
    }

    public function get_user_options(): string
    {
        $form = SHM_SIMPLE_FORM(
            "user_admin/cron_uploader",
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
                        TH(rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    )
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Save Settings"])))
                )
            )
        );
        $html = emptyHTML($form);
        return (string)$html;
    }

    /**
     * @param string[] $failed_dirs
     */
    public function display_form(array $failed_dirs): void
    {
        global $page;

        $link = make_http(make_link("cron_upload"));
        $html = "<a href='$link'>Cron uploader documentation</a>";

        $html .= make_form(make_link("admin/cron_uploader_restage"));
        $html .= "<table class='form'>";
        $html .= "<tr><th>Failed dir</th><td><select name='failed_dir' required='required'>";

        foreach ($failed_dirs as $dir) {
            $html .= "<option value='$dir'>$dir</option>";
        }

        $html .= "</select></td></tr>";
        $html .= "<tr><td colspan='2'><input type='submit' value='Re-stage files to queue' /></td></tr>";
        $html .= "</table></form>";

        $html .= make_form(make_link("admin/cron_uploader_clear_queue"), onsubmit: "return confirm('Are you sure you want to delete everything in the queue folder?');")
            ."<table class='form'><tr><td>"
            ."<input type='submit' value='Clear queue folder'></td></tr></table></form>";
        $html .= make_form(make_link("admin/cron_uploader_clear_uploaded"), onsubmit: "return confirm('Are you sure you want to delete everything in the uploaded folder?');")
            ."<table class='form'><tr><td>"
            ."<input type='submit' value='Clear uploaded folder'></td></tr></table></form>";
        $html .= make_form(make_link("admin/cron_uploader_clear_failed"), onsubmit: "return confirm('Are you sure you want to delete everything in the failed folder?');")
            ."<table class='form'><tr><td>"
            ."<input type='submit' value='Clear failed folder'></td></tr></table></form>";
        $html .= "</table>\n";
        $page->add_block(new Block("Cron Upload", $html));
    }
}
