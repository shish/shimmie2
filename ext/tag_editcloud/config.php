<?php

declare(strict_types=1);

namespace Shimmie2;

class TagEditCloudConfig extends ConfigGroup
{
    public const KEY = "tag_editcloud";

    #[ConfigMeta("Sort the tags by", ConfigType::STRING, options: "Shimmie2\TagEditCloudConfig::get_sort_options")]
    public const SORT = "tageditcloud_sort";

    #[ConfigMeta("Always show used tags first", ConfigType::BOOL)]
    public const USED_FIRST = "tageditcloud_usedfirst";

    #[ConfigMeta("Only show tags used at least N times", ConfigType::INT)]
    public const MIN_USAGE = "tageditcloud_minusage";

    #[ConfigMeta("Show N tags by default", ConfigType::INT)]
    public const DEF_COUNT = "tageditcloud_defcount";

    #[ConfigMeta("Show a maximum of N tags", ConfigType::INT)]
    public const MAX_COUNT = "tageditcloud_maxcount";

    #[ConfigMeta("Ignore tags (space separated)", ConfigType::STRING)]
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
            Extension::is_enabled(TagCategoriesInfo::KEY)
            && $database->get_driver_id() == DatabaseDriverID::MYSQL
        ) {
            $sort_by['Categories'] = 'c';
        }
        return $sort_by;
    }
}
