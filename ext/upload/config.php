<?php

declare(strict_types=1);

namespace Shimmie2;

class UploadConfig extends ConfigGroup
{
    public ?int $position = 10;
    #[ConfigMeta("Max uploads", ConfigType::INT)]
    public const COUNT = "upload_count";

    #[ConfigMeta("Max size per file", ConfigType::INT, ui_type: "shorthand_int")]
    public const SIZE = "upload_size";

    #[ConfigMeta("Minimum free space", ConfigType::INT, ui_type: "shorthand_int", advanced: true)]
    public const MIN_FREE_SPACE = "upload_min_free_space";

    #[ConfigMeta("Upload collisions", ConfigType::STRING, options: [
        "Error" => 'error',
        "Merge" => 'merge'
    ])]
    public const COLLISION_HANDLER = 'upload_collision_handler';

    #[ConfigMeta("Transload", ConfigType::STRING, options: "Shimmie2\UploadConfig::get_transload_options")]
    public const TRANSLOAD_ENGINE = "transload_engine";

    #[ConfigMeta("Use transload URL as source", ConfigType::BOOL)]
    public const TLSOURCE = "upload_tlsource";

    #[ConfigMeta("MIME checks", ConfigType::BOOL)]
    public const MIME_CHECK_ENABLED = "mime_check_enabled";

    #[ConfigMeta("Allowed MIMEs", ConfigType::ARRAY, options: "Shimmie2\UploadConfig::get_mime_options")]
    public const ALLOWED_MIME_STRINGS = "allowed_mime_strings";

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

    /**
     * @return array<string, string>
     */
    public static function get_mime_options(): array
    {
        $output = [];
        foreach (DataHandlerExtension::get_all_supported_mimes() as $mime) {
            $output[MimeMap::get_name_for_mime($mime)] = $mime;
        }
        return $output;
    }
}
