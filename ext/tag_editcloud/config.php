<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagEditCloudConfig extends ConfigGroup
{
    public const KEY = "tag_editcloud";

    #[ConfigMeta("Sort the tags by", ConfigType::STRING, default: 'a', options: "Shimmie2\TagEditCloudConfig::get_sort_options")]
    public const SORT = "tageditcloud_sort";

    #[ConfigMeta("Always show used tags first", ConfigType::BOOL, default: true)]
    public const USED_FIRST = "tageditcloud_usedfirst";

    #[ConfigMeta("Only show tags used at least N times", ConfigType::INT, default: 2)]
    public const MIN_USAGE = "tageditcloud_minusage";

    #[ConfigMeta("Show N tags by default", ConfigType::INT, default: 40)]
    public const DEF_COUNT = "tageditcloud_defcount";

    #[ConfigMeta("Show a maximum of N tags", ConfigType::INT, default: 4096)]
    public const MAX_COUNT = "tageditcloud_maxcount";

    #[ConfigMeta("Ignore tags (space separated)", ConfigType::STRING, default: "tagme")]
    public const IGNORE_TAGS = "tageditcloud_ignoretags";

    /**
     * @return array<string, string>
     */
    public static function get_sort_options(): array
    {
        global $database;
        $sort_by = [
            'Alphabetical' => 'a',
            'Popularity' => 'p',
            'Relevance' => 'r',
        ];
        if (
            TagCategoriesInfo::is_enabled()
            && $database->get_driver_id() === DatabaseDriverID::MYSQL
        ) {
            $sort_by['Categories'] = 'c';
        }
        return $sort_by;
    }
}
