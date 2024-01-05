<?php

declare(strict_types=1);

namespace Shimmie2;

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
use function MicroHTML\SPAN;

use function MicroHTML\P;

class UploadTheme extends Themelet
{
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
        $max_total_size = parse_shorthand_int(ini_get('post_max_size'));
        $max_total_kb = to_shorthand_int($max_total_size);
        $upload_list = $this->build_upload_list();

        $form = SHM_FORM("upload", "POST", true, "file_upload");
        $form->appendChild(
            TABLE(
                ["id" => "large_upload_form"],
                TR(
                    TD(["width" => "20"], rawHTML("Common&nbsp;Tags")),
                    TD(["colspan" => "6"], INPUT(["name" => "tags", "type" => "text", "placeholder" => "tagme", "class" => "autocomplete_tags"]))
                ),
                TR(
                    TD(["width" => "20"], rawHTML("Common&nbsp;Source")),
                    TD(["colspan" => "6"], INPUT(["name" => "source", "type" => "text", "placeholder" => "https://..."]))
                ),
                $upload_list,
                TR(
                    TD(["colspan" => "7"], INPUT(["id" => "uploadbutton", "type" => "submit", "value" => "Post"]))
                ),
            )
        );
        $html = emptyHTML(
            $form,
            SMALL(
                "(",
                $max_size > 0 ? "Per-file limit: $max_kb" : null,
                $max_total_size > 0 ? " / Total limit: $max_total_kb" : null,
                " / Current total: ",
                SPAN(["id" => "upload_size_tracker"], "0KB"),
                ")"
            ),
            rawHTML("<script>
            window.shm_max_size = $max_size;
            window.shm_max_total_size = $max_total_size;
            </script>")
        );

        $page->set_title("Upload");
        $page->set_heading("Upload");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Upload", $html, "main", 20));
        if ($tl_enabled) {
            $page->add_block(new Block("Bookmarklets", $this->build_bookmarklets(), "left", 20));
        }
    }

    protected function build_upload_list(): HTMLElement
    {
        global $config;
        $upload_list = emptyHTML();
        $upload_count = $config->get_int(UploadConfig::COUNT);
        $tl_enabled = ($config->get_string(UploadConfig::TRANSLOAD_ENGINE, "none") != "none");
        $accept = $this->get_accept();

        $upload_list->appendChild(
            TR(
                TD(["colspan" => 2], "Select File"),
                TD($tl_enabled ? "or URL" : null),
                TD("Post-Specific Tags"),
                TD("Post-Specific Source"),
            )
        );

        for ($i = 0; $i < $upload_count; $i++) {
            $upload_list->appendChild(
                TR(
                    TD(
                        ["colspan" => 2, "style" => "white-space: nowrap;"],
                        DIV([
                            "id" => "canceldata{$i}",
                            "style" => "display:inline;margin-right:5px;font-size:15px;visibility:hidden;",
                            "onclick" => "document.getElementById('data{$i}').value='';updateTracker();",
                        ], "✖"),
                        INPUT([
                            "type" => "file",
                            "id" => "data{$i}",
                            "name" => "data{$i}[]",
                            "accept" => $accept,
                            "multiple" => true,
                        ]),
                    ),
                    TD(
                        $tl_enabled ? INPUT([
                            "type" => "text",
                            "name" => "url{$i}",
                            "value" => ($i == 0) ? @$_GET['url'] : null,
                        ]) : null
                    ),
                    TD(
                        INPUT([
                            "type" => "text",
                            "name" => "tags{$i}",
                            "class" => "autocomplete_tags",
                            "value" => ($i == 0) ? @$_GET['tags'] : null,
                        ])
                    ),
                    TD(
                        INPUT([
                            "type" => "text",
                            "name" => "source{$i}",
                            "value" => ($i == 0) ? @$_GET['source'] : null,
                        ])
                    ),
                )
            );
        }

        return $upload_list;
    }

    protected function build_bookmarklets(): HTMLElement
    {
        global $config;
        $link = make_http(make_link("upload"));
        $main_page = make_http(make_link());
        $title = $config->get_string(SetupConfig::TITLE);
        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);
        $delimiter = $config->get_bool('nice_urls') ? '?' : '&amp;';

        $js = 'javascript:(
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
                    if(tags !== "" && tags !== null) {
                        var link = "'. $link . $delimiter .'url="+location.href+"&tags="+tags;
                        var w = window.open(link, "_blank");
                    }
                }
            }
        )();';
        $html1 = P(
            A(["href" => $js], "Upload to $title"),
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
            A(["href" => $js], $title),
            rawHTML(" (Click when looking at a post page. Works on sites running Shimmie / Danbooru / Gelbooru. (This also grabs the tags / rating / source!))"),
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
                TD(INPUT(["name" => "data[]", "type" => "file", "accept" => $accept]))
            )
        );
        if ($tl_enabled) {
            $upload_list->appendChild(
                TR(
                    TD("or URL"),
                    TD(INPUT(["name" => "url", "type" => "text", "value" => @$_GET['url']]))
                )
            );
        }

        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);

        $image = Image::by_id($image_id);
        $thumbnail = $this->build_thumb_html($image);

        $form = SHM_FORM("replace/".$image_id, "POST", true);
        $form->appendChild(emptyHTML(
            TABLE(
                ["id" => "large_upload_form"],
                $upload_list,
                TR(TD("Source"), TD(["colspan" => 3], INPUT(["name" => "source", "type" => "text"]))),
                TR(TD(["colspan" => 4], INPUT(["id" => "uploadbutton", "type" => "submit", "value" => "Post"]))),
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
            $max_size > 0 ? SMALL("(Max file size is $max_kb)") : null,
        );

        $page->set_title("Replace Post");
        $page->set_heading("Replace Post");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Upload Replacement Post", $html, "main", 20));
    }

    /**
     * @param UploadResult[] $results
     */
    public function display_upload_status(Page $page, array $results): void
    {
        global $user;

        /** @var UploadSuccess[] */
        $successes = array_filter($results, fn ($r) => is_a($r, UploadSuccess::class));

        /** @var UploadError[] */
        $errors = array_filter($results, fn ($r) => is_a($r, UploadError::class));

        if (count($errors) > 0) {
            $page->set_title("Upload Status");
            $page->set_heading("Upload Status");
            $page->add_block(new NavBlock());
            foreach($errors as $error) {
                $page->add_block(new Block($error->name, format_text($error->error)));
            }
        } elseif (count($successes) == 0) {
            $page->set_title("No images uploaded");
            $page->set_heading("No images uploaded");
            $page->add_block(new NavBlock());
            $page->add_block(new Block("No images uploaded", "Upload attempted, but nothing succeeded and nothing failed?"));
        } elseif (count($successes) == 1) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/{$successes[0]->image_id}"));
            $page->add_http_header("X-Shimmie-Post-ID: " . $successes[0]->image_id);
        } else {
            $ids = join(",", array_reverse(array_map(fn ($s) => $s->image_id, $successes)));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(search_link(["id={$ids}"]));
        }
    }

    protected function build_upload_block(): HTMLElement
    {
        global $config;

        $accept = $this->get_accept();

        $max_size = $config->get_int(UploadConfig::SIZE);
        $max_kb = to_shorthand_int($max_size);
        $max_total_size = parse_shorthand_int(ini_get('post_max_size')) - 102400; //leave room for http request data
        $max_total_kb = to_shorthand_int($max_total_size);

        // <input type='hidden' name='max_file_size' value='$max_size' />
        $form = SHM_FORM("upload", "POST", true);
        $form->appendChild(
            emptyHTML(
                INPUT(["id" => "data[]", "name" => "data[]", "size" => "16", "type" => "file", "accept" => $accept, "multiple" => true]),
                INPUT(["name" => "tags", "type" => "text", "placeholder" => "tagme", "class" => "autocomplete_tags", "required" => true]),
                INPUT(["type" => "submit", "value" => "Post"]),
            )
        );

        return DIV(
            ["class" => 'mini_upload'],
            $form,
            $max_size > 0 ? SMALL("(Max file size is $max_kb)") : null,
            $max_total_size > 0 ? SMALL(BR(), "(Max total size is $max_total_kb)") : null,
            NOSCRIPT(BR(), A(["href" => make_link("upload")], "Larger Form"))
        );
    }

    protected function get_accept(): string
    {
        return ".".join(",.", DataHandlerExtension::get_all_supported_exts());
    }
}
