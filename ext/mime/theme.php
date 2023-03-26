<?php

declare(strict_types=1);

namespace Shimmie2;

class MimeSystemTheme extends Themelet
{
    public function get_help_html(): string
    {
        $mimes = DataHandlerExtension::get_all_supported_mimes();
        sort($mimes);
        $exts = [];
        foreach ($mimes as $mime) {
            $exts[] = FileExtension::get_for_mime($mime);
        }
        $mimes = join("</li><li>", $mimes);
        sort($exts);
        $exts =  join("</li><li>", $exts);

        return '<p>Search for posts by extension</p>

        <div class="command_example">
        <pre>ext=jpg</pre>
        <p>Returns posts with the extension "jpg".</p>
        </div>

        These extensions are available in the system:
        <ul><li>'.$exts.'</li></ul>

        <hr/>

        <p>Search for posts by MIME type</p>

        <div class="command_example">
        <pre>mime=image/jpeg</pre>
        <p>Returns posts that have the MIME type "image/jpeg".</p>
        </div>

        These MIME types are available in the system:
        <ul><li>'.$mimes.'</li></ul>

        ';
    }
}
