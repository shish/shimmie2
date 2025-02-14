<?php

declare(strict_types=1);

namespace Shimmie2;

class WikiConfig extends ConfigGroup
{
    #[ConfigMeta("Tag page template", ConfigType::STRING, ui_type: "longtext")]
    public const TAG_PAGE_TEMPLATE = "wiki_tag_page_template";

    #[ConfigMeta("Empty list text", ConfigType::STRING)]
    public const EMPTY_TAGINFO = "wiki_empty_taginfo";

    #[ConfigMeta("Show shortwiki on search results", ConfigType::BOOL)]
    public const TAG_SHORTWIKIS = "shortwikis_on_tags";

    #[ConfigMeta("Enable revisions", ConfigType::BOOL, advanced: true)]
    public const ENABLE_REVISIONS = "wiki_revisions";
}
