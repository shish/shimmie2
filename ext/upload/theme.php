<?php

declare(strict_types=1);

use MicroHTML\HTMLElement;

use function MicroHTML\TABLE;
use function MicroHTML\TR;
use function MicroHTML\TD;
use function MicroHTML\SMALL;
use function MicroHTML\rawHTML;
use function MicroHTML\INPUT;
use function MicroHTML\emptyHTML;
use function MicroHTML\NOSCRIPT;
use function MicroHTML\DIV;
use function MicroHTML\BR;
use function MicroHTML\A;

use function MicroHTML\P;

class UploadTheme extends Themelet
{
    protected bool $has_errors = false;

    public function display_block(Page $page): void
    {
        $b = new Block("Upload", (string)$this->build_upload_block(), "left", 20);
        $b->is_content = false;
        $page->add_block($b);
    }

    public function display_full(Page $page): void
    {
        $page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "left", 20));
    }

    public function display_page(Page $page): void
    {
        global $config, $page;

        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);
        $upload_list = $this->h_upload_list_1();

        $form = SHM_FORM("upload", "POST", true, "file_upload");
        $form->appendChild(
            TABLE(
                ["id"=>"large_upload_form", "class"=>"vert"],
                TR(
                    TD(["width"=>"20"], rawHTML("Common&nbsp;Tags")),
                    TD(["colspan"=>"5"], INPUT(["name"=>"tags", "type"=>"text", "placeholder"=>"tagme", "class"=>"autocomplete_tags", "autocomplete"=>"off"]))
                ),
                TR(
                    TD(["width"=>"20"], rawHTML("Common&nbsp;Source")),
                    TD(["colspan"=>"5"], INPUT(["name"=>"source", "type"=>"text"]))
                ),
                $upload_list,
                TR(
                    TD(["colspan"=>"6"], INPUT(["id"=>"uploadbutton", "type"=>"submit", "value"=>"Post"]))
                ),
            )
        );
        $html = emptyHTML(
            $form,
            SMALL("(Max file size is $max_kb)")
        );

        $page->set_title("Upload");
        $page->set_heading("Upload");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Upload", (string)$html, "main", 20));
        if ($tl_enabled) {
            $page->add_block(new Block("Bookmarklets", (string)$this->h_bookmarklets(), "left", 20));
        }
    }

    protected function h_upload_list_1(): HTMLElement
    {
        global $config;
        $upload_list = emptyHTML();
        $upload_count = $config->get_int(UploadConfig::COUNT);
        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $accept = $this->get_accept();

        $upload_list->appendChild(
            TR(
                TD(["colspan"=>$tl_enabled ? 2 : 4], "Files"),
                $tl_enabled ? TD(["colspan"=>"2"], "URLs") : emptyHTML(),
                TD(["colspan"=>"2"], "Post-Specific Tags"),
            )
        );

        for ($i=0; $i<$upload_count; $i++) {
            $upload_list->appendChild(
                TR(
                    TD(["colspan"=>$tl_enabled ? 2 : 4], INPUT(["type"=>"file", "name"=>"data${i}[]", "accept"=>$accept, "multiple"=>true])),
                    $tl_enabled ? TD(["colspan"=>"2"], INPUT(["type"=>"text", "name"=>"url${i}"])) : emptyHTML(),
                    TD(["colspan"=>"2"], INPUT(["type"=>"text", "name"=>"tags${i}", "class"=>"autocomplete_tags", "autocomplete"=>"off"])),
                )
            );
        }

        return $upload_list;
    }

    protected function h_bookmarklets(): HTMLElement
    {
        global $config;
        $link = make_http(make_link("upload"));
        $main_page = make_http(make_link());
        $title = $config->get_string(SetupConfig::TITLE);
        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);
        $delimiter = $config->get_bool('nice_urls') ? '?' : '&amp;';

        $js='javascript:(
            function() {
                if(typeof window=="undefined" || !window.location || window.location.href=="about:blank") {
                    window.location = "'. $main_page .'";
                }
                else if(typeof document=="undefined" || !document.body) {
                    window.location = "'. $main_page .'?url="+encodeURIComponent(window.location.href);
                }
                else if(window.location.href.match("\/\/'. $_SERVER["HTTP_HOST"] .'.*")) {
                    alert("You are already at '. $title .'!");
                }
                else {
                    var tags = prompt("Please enter tags", "tagme");
                    if(tags != "" && tags != null) {
                        var link = "'. $link . $delimiter .'url="+location.href+"&tags="+tags;
                        var w = window.open(link, "_blank");
                    }
                }
            }
        )();';
        $html1 = P(
            A(["href"=>$js], "Upload to $title"),
            rawHTML(' (Drag &amp; drop onto your bookmarks toolbar, then click when looking at a post)')
        );

        // Bookmarklet checks if shimmie supports ext. If not, won't upload to site/shows alert saying not supported.
        $supported_ext = join(" ", DataHandlerExtension::get_all_supported_exts());

        $title = "Booru to " . $config->get_string(SetupConfig::TITLE);
        // CA=0: Ask to use current or new tags | CA=1: Always use current tags | CA=2: Always use new tags
        $js = '
            javascript:
            var ste=&quot;'. $link . $delimiter .'url=&quot;;
            var supext=&quot;'.$supported_ext.'&quot;;
            var maxsize=&quot;'.$max_kb.'&quot;;
            var CA=0;
            void(document.body.appendChild(document.createElement(&quot;script&quot;)).src=&quot;'.make_http(get_base_href())."/ext/upload/bookmarklet.js".'&quot;)
        ';
        $html2 = P(
            A(["href"=>$js], $title),
            rawHTML("(Click when looking at a post page. Works on sites running Shimmie / Danbooru / Gelbooru. (This also grabs the tags / rating / source!))"),
        );

        return emptyHTML($html1, $html2);
    }

    /**
     * Only allows 1 file to be uploaded - for replacing another image file.
     */
    public function display_replace_page(Page $page, int $image_id)
    {
        global $config, $page;
        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $accept = $this->get_accept();

        $upload_list = emptyHTML(
            TR(
                TD("File"),
                TD(INPUT(["name"=>"data[]", "type"=>"file", "accept"=>$accept]))
            )
        );
        if ($tl_enabled) {
            $upload_list->appendChild(
                TR(
                    TD("or URL"),
                    TD(INPUT(["name"=>"url", "type"=>"text"]))
                )
            );
        }

        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);

        $image = Image::by_id($image_id);
        $thumbnail = $this->build_thumb_html($image);

        $form = SHM_FORM("upload/replace/".$image_id, "POST", true);
        $form->appendChild(emptyHTML(
            INPUT(["type"=>"hidden", "name"=>"image_id", "value"=>$image_id]),
            TABLE(
                ["id"=>"large_upload_form", "class"=>"vert"],
                $upload_list,
                TR(TD("Source"), TD(["colspan"=>3], INPUT(["name"=>"source", "type"=>"text"]))),
                TR(TD(["colspan"=>4], INPUT(["id"=>"uploadbutton", "type"=>"submit", "value"=>"Post"]))),
            )
        ));

        $html = emptyHTML(
            P(
                "Replacing Post ID $image_id",
                BR(),
                "Please note: You will have to refresh the post page, or empty your browser cache."
            ),
            $thumbnail,
            BR(),
            $form,
            SMALL("(Max file size is $max_kb)"),
        );

        $page->set_title("Replace Post");
        $page->set_heading("Replace Post");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Upload Replacement Post", (string)$html, "main", 20));
    }

    public function display_upload_status(Page $page, array $image_ids): void
    {
        global $user;

        if ($this->has_errors) {
            $page->set_title("Upload Status");
            $page->set_heading("Upload Status");
            $page->add_block(new NavBlock());
        } else {
            if (count($image_ids) < 1) {
                $page->set_title("No images uploaded");
                $page->set_heading("No images uploaded");
                $page->add_block(new NavBlock());
            } elseif (count($image_ids) == 1) {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/view/{$image_ids[0]}"));
            } else {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("post/list/poster={$user->name}/1"));
            }
        }
    }

    public function display_upload_error(Page $page, string $title, string $message): void
    {
        // this message has intentional HTML in it...
        $message = str_contains($message, "already has hash") ? $message : html_escape($message);
        $page->add_block(new Block($title, $message));
        $this->has_errors = true;
    }

    protected function build_upload_block(): HTMLElement
    {
        global $config;

        $accept = $this->get_accept();

        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);

        // <input type='hidden' name='max_file_size' value='$max_size' />
        $form = SHM_FORM("upload", "POST", true);
        $form->appendChild(
            emptyHTML(
                INPUT(["id"=>"data[]", "name"=>"data[]", "size"=>"16", "type"=>"file", "accept"=>$accept, "multiple"=>true]),
                INPUT(["name"=>"tags", "type"=>"text", "placeholder"=>"tagme", "class"=>"autocomplete_tags", "required"=>true, "autocomplete"=>"off"]),
                INPUT(["type"=>"submit", "value"=>"Post"]),
            )
        );

        return DIV(
            ["class"=>'mini_upload'],
            $form,
            SMALL("(Max file size is $max_kb)"),
            NOSCRIPT(BR(), A(["href"=>make_link("upload")], "Larger Form"))
        );
    }

    protected function get_accept(): string
    {
        return ".".join(",.", DataHandlerExtension::get_all_supported_exts());
    }
}
