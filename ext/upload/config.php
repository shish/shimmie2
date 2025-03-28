<?php

declare(strict_types=1);

namespace Shimmie2;

final class UploadConfig extends ConfigGroup
{
    public const KEY = "upload";

    public ?int $position = 10;
    #[ConfigMeta("Max uploads", ConfigType::INT, default: 3)]
    public const COUNT = "upload_count";

    #[ConfigMeta("Max size per file", ConfigType::INT, input: ConfigInput::BYTES, default: 1 * 1024 * 1024)]
    public const SIZE = "upload_size";

    #[ConfigMeta("Minimum free space", ConfigType::INT, input: ConfigInput::BYTES, default: 100 * 1024 * 1024, advanced: true)]
    public const MIN_FREE_SPACE = "upload_min_free_space";

    #[ConfigMeta("Upload collisions", ConfigType::STRING, default: 'error', options: [
        "Error" => 'error',
        "Merge" => 'merge'
    ], advanced: true)]
    public const COLLISION_HANDLER = 'upload_collision_handler';

    #[ConfigMeta("Transload", ConfigType::STRING, default: "none", options: "Shimmie2\UploadConfig::get_transload_options")]
    public const TRANSLOAD_ENGINE = "transload_engine";

    #[ConfigMeta("Use transload URL as source", ConfigType::BOOL, default: true, advanced: true)]
    public const TLSOURCE = "upload_tlsource";

    /**
     * @return array<string, string>
     */
    public static function get_transload_options(): array
    {
        $tes = [];
        $tes["Disabled"] = "none";
        if (function_exists("curl_init")) {
            $tes["cURL"] = "curl";
        }
        $tes["fopen"] = "fopen";
        $tes["WGet"] = "wget";
        return $tes;
    }

    public function tweak_html(\MicroHTML\HTMLElement $html): \MicroHTML\HTMLElement
    {
        $limits = get_upload_limits();

        $files = $limits['files'] ? to_shorthand_int($limits['files']) : "unlimited";
        $filesize = $limits['filesize'] ? to_shorthand_int($limits['filesize']) : "unlimited";
        $post = $limits['post'] ? to_shorthand_int($limits['post']) : "unlimited";

        return \MicroHTML\emptyHTML(
            \MicroHTML\I("(System limits are $files uploads of $filesize each, with a combined size of $post)"),
            $html
        );
    }
}
