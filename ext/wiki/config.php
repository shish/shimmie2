<?php

declare(strict_types=1);

namespace Shimmie2;

final class WikiConfig extends ConfigGroup
{
    public const KEY = "wiki";

    #[ConfigMeta("Tag page template", ConfigType::STRING, input: ConfigInput::TEXTAREA, default: "{body}\n\n[b]Aliases: [/b][i]{aliases}[/i]")]
    public const TAG_PAGE_TEMPLATE = "wiki_tag_page_template";

    #[ConfigMeta("Empty list text", ConfigType::STRING, default: "none")]
    public const EMPTY_TAGINFO = "wiki_empty_taginfo";

    #[ConfigMeta("Show shortwiki on search results", ConfigType::BOOL, default: false)]
    public const TAG_SHORTWIKIS = "shortwikis_on_tags";

    #[ConfigMeta("Enable revisions", ConfigType::BOOL, default: true, advanced: true)]
    public const ENABLE_REVISIONS = "wiki_revisions";
}
