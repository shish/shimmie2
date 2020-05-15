<?php declare(strict_types=1);

class ImageViewCounterTheme extends Themelet
{
    public function view_popular($images)
    {
        global $page, $config;
        $pop_images = "";
        $i = 0;
        foreach ($images as $image) {
            $thumb_html = $this->build_thumb_html($image);
           $pop_images .= $thumb_html . "\n";
        }


        $html = "\n".
            "<h3 style='text-align: center;'>\n".
            "	<a href='{$b_dte}'>&laquo;</a> {$dte[1]} <a href='{$f_dte}'>&raquo;</a>\n".
            "</h3>\n".
            "<br/>\n".$pop_images;


        $nav_html = "<a href=".make_link().">Index</a>";

        $page->set_heading($config->get_string(SetupConfig::TITLE));
        $page->add_block(new Block("Navigation", $nav_html, "left", 10));
        $page->add_block(new Block(null, $html, "main", 30));
    }


    public function get_help_html()
    {
        return '<p>Search for images that have received numeric scores by the score or by the scorer.</p>
        <div class="command_example">
        <pre>score=1</pre>
        <p>Returns images with a score of 1.</p>
        </div>
        <div class="command_example">
        <pre>score>0</pre>
        <p>Returns images with a score of 1 or more.</p>
        </div>
        <p>Can use &lt;, &lt;=, &gt;, &gt;=, or =.</p>

        <div class="command_example">
        <pre>upvoted_by=username</pre>
        <p>Returns images upvoted by "username".</p>
        </div>
        <div class="command_example">
        <pre>upvoted_by_id=123</pre>
        <p>Returns images upvoted by user 123.</p>
        </div>
        <div class="command_example">
        <pre>downvoted_by=username</pre>
        <p>Returns images downvoted by "username".</p>
        </div>
        <div class="command_example">
        <pre>downvoted_by_id=123</pre>
        <p>Returns images downvoted by user 123.</p>
        </div>

        <div class="command_example">
        <pre>order:score_desc</pre>
        <p>Sorts the search results by score, descending.</p>
        </div>
        <div class="command_example">
        <pre>order:score_asc</pre>
        <p>Sorts the search results by score, ascending.</p>
        </div>
        ';
    }
}
