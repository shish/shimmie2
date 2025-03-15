<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagListConfig extends ConfigGroup
{
    public const KEY = "tag_list";

    #[ConfigMeta("Related tags", ConfigType::INT, default: 15)]
    public const LENGTH = "tag_list_length";

    #[ConfigMeta("Popular tags", ConfigType::INT, default: 15)]
    public const POPULAR_TAG_LIST_LENGTH = "popular_tag_list_length";

    #[ConfigMeta("Tag info link", ConfigType::STRING, default: 'https://en.wikipedia.org/wiki/$tag')]
    public const INFO_LINK = "info_link";

    #[ConfigMeta("Omit tags", ConfigType::STRING, default: 'tagme*')]
    public const OMIT_TAGS = "tag_list_omit_tags";

    #[ConfigMeta("Tag list type", ConfigType::STRING, default: TagListConfig::TYPE_RELATED, options: [
        "Post's tags only" => 'tags',
        'Related tags only' => 'related',
        'Both' => 'both'
    ])]
    public const IMAGE_TYPE = "tag_list_image_type";

    #[ConfigMeta("Related sort", ConfigType::STRING, default: TagListConfig::SORT_ALPHABETICAL, options: [
        'Tag Count' => 'tagcount',
        'Alphabetical' => 'alphabetical'
    ])]
    public const RELATED_SORT = "tag_list_related_sort";

    #[ConfigMeta("Popular sort", ConfigType::STRING, default: TagListConfig::SORT_TAG_COUNT, options: [
        'Tag Count' => 'tagcount',
        'Alphabetical' => 'alphabetical'
    ])]
    public const POPULAR_SORT = "tag_list_popular_sort";

    #[ConfigMeta("Tag counts", ConfigType::BOOL, default: true)]
    public const SHOW_NUMBERS = "tag_list_numbers";

    public const TYPE_RELATED = "related";
    public const TYPE_TAGS = "tags";
    public const TYPE_BOTH = "both";

    public const TYPE_CHOICES = [
        "Post's tags only" => TagListConfig::TYPE_TAGS,
        "Related tags only" => TagListConfig::TYPE_RELATED,
        "Both" => TagListConfig::TYPE_BOTH
    ];

    public const SORT_ALPHABETICAL = "alphabetical";
    public const SORT_TAG_COUNT = "tagcount";

    public const SORT_CHOICES = [
        "Tag Count" => TagListConfig::SORT_TAG_COUNT,
        "Alphabetical" => TagListConfig::SORT_ALPHABETICAL
    ];
}
