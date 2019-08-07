<?php


class TagListConfig
{
    public const LENGTH = "tag_list_length";
    public const POPULAR_TAG_LIST_LENGTH = "popular_tag_list_length";
    public CONSt TAGS_MIN = "tags_min";
    public const INFO_LINK = "info_link";
    public const IMAGE_TYPE = "tag_list_image_type";
    public const RELATED_SORT = "tag_list_related_sort";
    public const POPULAR_SORT = "tag_list_popular_sort";
    public const PAGES = "tag_list_pages";
    public const OMIT_TAGS = "tag_list_omit_tags";

    public const TYPE_RELATED = "related";
    public const TYPE_TAGS= "tags";

    public const TYPE_CHOICES = [
        "Image's tags only" => TagListConfig::TYPE_TAGS,
        "Show related" => TagListConfig::TYPE_RELATED
    ];

    public const SORT_ALPHABETICAL = "alphabetical";
    public const SORT_TAG_COUNT = "tagcount";

    public const SORT_CHOICES = [
        "Tag Count" => TagListConfig::SORT_TAG_COUNT,
        "Alphabetical" => TagListConfig::SORT_ALPHABETICAL
    ];
}