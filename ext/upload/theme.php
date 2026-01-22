<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events/upload_common_building_event.php";
require_once "events/upload_specific_building_event.php";
require_once "events/upload_header_building_event.php";

use function MicroHTML\{A, BR, DIV, INPUT, NOSCRIPT, P, SCRIPT, SMALL, SPAN, emptyHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\{TABLE, TD, TH, TR};

class UploadTheme extends Themelet
{
    public function display_block(): void
    {
        Ctx::$page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20, is_content: false));
    }

    public function display_full(): void
    {
        Ctx::$page->add_block(new Block("Upload", emptyHTML("Disk nearly full, uploads disabled"), "left", 20));
    }

    public function display_page(): void
    {
        $limits = get_upload_limits();

        $tl_enabled = (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none");
        $max_size = $limits['shm_filesize'];
        $max_kb = to_shorthand_int($max_size);
        $max_total_size = $limits['shm_post'];
        $max_total_kb = to_shorthand_int($max_total_size);
        $upload_list = $this->build_upload_list();

        $common_fields = emptyHTML();
        $ucbe = send_event(new UploadCommonBuildingEvent());
        foreach ($ucbe->get_parts() as $part) {
            $common_fields->appendChild($part);
        }
        $captcha = Captcha::get_html(UploadPermission::SKIP_UPLOAD_CAPTCHA);

        $form = SHM_FORM(make_link("upload"), multipart: true, id: "file_upload");
        $form->appendChild(
            TABLE(
                ["id" => "large_upload_form", "class" => "form"],
                $common_fields,
                $upload_list,
                $captcha ? TR(
                    TD(["colspan" => "7"], $captcha)
                ) : null,
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
            SCRIPT("
            window.shm_max_size = $max_size;
            window.shm_max_total_size = $max_total_size;
            ")
        );

        $page = Ctx::$page;
        $page->set_title("Upload");
        $page->add_block(new Block("Upload", $html, "main", 20));
        if ($tl_enabled) {
            $page->add_block(new Block("Bookmarklets", $this->build_bookmarklets(), "left", 20));
        }
    }

    protected function build_upload_list(): HTMLElement
    {
        $upload_list = emptyHTML();
        $upload_count = Ctx::$config->get(UploadConfig::COUNT);
        $tl_enabled = (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none");
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
                            "value" => ($i === 0) ? @$_GET['url'] : null,
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
        $limits = get_upload_limits();
        $link = make_link("upload")->asAbsolute();
        $main_page = make_link()->asAbsolute();
        $title = Ctx::$config->get(SetupConfig::TITLE);
        $max_size = $limits['shm_filesize'];
        $max_kb = to_shorthand_int($max_size);
        $delimiter = Url::are_niceurls_enabled() ? '?' : '&amp;';

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
            emptyHTML(' (Drag & drop onto your bookmarks toolbar, then click when looking at a post)')
        );

        // Bookmarklet checks if shimmie supports ext. If not, won't upload to site/shows alert saying not supported.
        $supported_ext = join(" ", DataHandlerExtension::get_all_supported_exts());

        $title = "Booru to " . Ctx::$config->get(SetupConfig::TITLE);
        // CA=0: Ask to use current or new tags | CA=1: Always use current tags | CA=2: Always use new tags
        $js = '
            javascript:
            var ste=&quot;'. $link . $delimiter .'url=&quot;;
            var supext=&quot;'.$supported_ext.'&quot;;
            var maxsize=&quot;'.$max_kb.'&quot;;
            var CA=0;
            void(document.body.appendChild(document.createElement(&quot;script&quot;)).src=&quot;'.Url::base()->asAbsolute()."/ext/upload/bookmarklet.js".'&quot;)
        ';
        $html2 = P(
            A(["href" => $js], $title),
            emptyHTML(" (Click when looking at a post page. Works on sites running Shimmie / Danbooru / Gelbooru. (This also grabs the tags / rating / source!))"),
        );

        return emptyHTML($html1, $html2);
    }

    /**
     * @param UploadResult[] $results
     */
    public function display_upload_status(array $results): void
    {
        $page = Ctx::$page;

        /** @var UploadSuccess[] */
        $successes = array_filter($results, fn ($r) => is_a($r, UploadSuccess::class));

        /** @var UploadError[] */
        $errors = array_filter($results, fn ($r) => is_a($r, UploadError::class));

        if (count($errors) > 0) {
            $page->set_title("Upload Status");
            foreach ($errors as $error) {
                $page->add_block(new Block($error->name, format_text($error->error)));
            }
        } elseif (count($successes) === 0) {
            $page->set_title("No images uploaded");
            $page->add_block(new Block("No images uploaded", emptyHTML("Upload attempted, but nothing succeeded and nothing failed?")));
        } elseif (count($successes) === 1) {
            $page->set_redirect(make_link("post/view/{$successes[0]->image_id}"));
            $page->add_http_header("X-Shimmie-Post-ID: " . $successes[0]->image_id);
        } else {
            $ids = join(",", array_reverse(array_map(fn ($s) => $s->image_id, $successes)));
            $page->set_redirect(search_link(["id={$ids}"]));
        }
    }

    protected function build_upload_block(): HTMLElement
    {
        $limits = get_upload_limits();

        $accept = $this->get_accept();

        $max_size = $limits['shm_filesize'];
        $max_kb = to_shorthand_int($max_size);
        $max_total_size = $limits['shm_post'];
        $max_total_kb = to_shorthand_int($max_total_size);

        // <input type='hidden' name='max_file_size' value='$max_size' />
        $form = SHM_FORM(make_link("upload"), multipart: true);
        $form->appendChild(
            emptyHTML(
                INPUT(["id" => "data[]", "name" => "data[]", "size" => "16", "type" => "file", "accept" => $accept, "multiple" => true]),
                INPUT(["name" => "tags", "type" => "text", "placeholder" => "tagme", "class" => "autocomplete_tags", "required" => true]),
                Captcha::get_html(UploadPermission::SKIP_UPLOAD_CAPTCHA),
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
