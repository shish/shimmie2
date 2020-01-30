<?php declare(strict_types=1);
use function MicroHTML\INPUT;
use function MicroHTML\TABLE;
use function MicroHTML\TR;
use function MicroHTML\TH;
use function MicroHTML\TD;
use function MicroHTML\SELECT;
use function MicroHTML\OPTION;

class MediaTheme extends Themelet
{
    public function display_form(array $types)
    {
        global $page;

        $select = SELECT(["name"=>"media_rescan_type"]);
        $select->appendChild(OPTION(["value"=>""], "All"));
        foreach ($types as $type) {
            $select->appendChild(OPTION(["value"=>$type["ext"]], "{$type["ext"]} ({$type["count"]})"));
        }

        $html = (string)SHM_SIMPLE_FORM(
            "admin/media_rescan",
            "Use this to force scanning for media properties.",
            TABLE(
                ["class"=>"form"],
                TR(TH("Image Type"), TD($select)),
                TR(TD(["colspan"=>"2"], SHM_SUBMIT('Scan Media Information')))
            )
        );
        $page->add_block(new Block("Media Tools", $html));
    }

    public function get_buttons_html(int $image_id): string
    {
        return (string)SHM_SIMPLE_FORM(
            "media_rescan/",
            INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image_id]),
            SHM_SUBMIT('Scan Media Properties'),
        );
    }

    public function get_help_html()
    {
        return '<p>Search for items based on the type of media.</p>
        <div class="command_example">
        <pre>content:audio</pre>
        <p>Returns items that contain audio, including videos and audio files.</p>
        </div>
        <div class="command_example">
        <pre>content:video</pre>
        <p>Returns items that contain video, including animated GIFs.</p>
        </div>
        <p>These search terms depend on the items being scanned for media content. Automatic scanning was implemented in mid-2019, so items uploaded before, or items uploaded on a system without ffmpeg, will require additional scanning before this will work.</p>
        ';
    }
}
