<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events/upload_common_building_event.php";
require_once "events/upload_specific_building_event.php";
require_once "events/upload_header_building_event.php";

use MicroHTML\HTMLElement;

use function MicroHTML\{TABLE,TR,TH,TD};
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
        $max_total_size = parse_shorthand_int(ini_get('post_max_size') ?: "0");
        $max_total_kb = to_shorthand_int($max_total_size);
        $upload_list = $this->build_upload_list();

        $common_fields = emptyHTML();
        $ucbe = send_event(new UploadCommonBuildingEvent());
        foreach ($ucbe->get_parts() as $part) {
            $common_fields->appendChild($part);
        }

        $form = SHM_FORM("upload", multipart: true, form_id: "file_upload");
        $form->appendChild(
            TABLE(
                ["id" => "large_upload_form", "class" => "form"],
                $common_fields,
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
                $max_size > 0 ? "File limit $max_kb" : null,
                $max_size > 0 && $max_total_size > 0 ? " / " : null,
                $max_total_size > 0 ? "Total limit $max_total_kb" : null,
                " / Current total ",
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

        $headers = emptyHTML();
        $uhbe = send_event(new UploadHeaderBuildingEvent());
        foreach ($uhbe->get_parts() as $part) {
            $headers->appendChild(
                TH("Post-Specific $part")
            );
        }

        $upload_list->appendChild(
            TR(
                ["class" => "header"],
                TH(["colspan" => 2], "Select File"),
                TH($tl_enabled ? "or URL" : null),
                $headers,
            )
        );

        for ($i = 0; $i < $upload_count; $i++) {
            $specific_fields = emptyHTML();
            $usfbe = send_event(new UploadSpecificBuildingEvent((string)$i));
            foreach ($usfbe->get_parts() as $part) {
                $specific_fields->appendChild($part);
            }

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
                    $specific_fields,
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
        $delimiter = $config->get_bool(SetupConfig::NICE_URLS) ? '?' : '&amp;';

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
        $max_total_size = parse_shorthand_int(ini_get('post_max_size') ?: "0");
        $max_total_kb = to_shorthand_int($max_total_size);

        // <input type='hidden' name='max_file_size' value='$max_size' />
        $form = SHM_FORM("upload", multipart: true);
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
            SMALL(
                "(",
                $max_size > 0 ? "File limit $max_kb" : null,
                $max_size > 0 && $max_total_size > 0 ? " / " : null,
                $max_total_size > 0 ? "Total limit $max_total_kb" : null,
                ")",
            ),
            NOSCRIPT(BR(), A(["href" => make_link("upload")], "Larger Form"))
        );
    }
    protected function get_accept(): string
    {
        return ".".join(",.", DataHandlerExtension::get_all_supported_exts());
    }
}
