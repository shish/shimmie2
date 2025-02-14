<?php

declare(strict_types=1);

namespace Shimmie2;

class PoolsConfig extends ConfigGroup
{
    #[ConfigMeta("Max results on import", ConfigType::INT)]
    public const MAX_IMPORT_RESULTS = "poolsMaxImportResults";

    #[ConfigMeta("Posts per page", ConfigType::INT)]
    public const IMAGES_PER_PAGE = "poolsImagesPerPage";

    #[ConfigMeta("Index list items per page", ConfigType::INT)]
    public const LISTS_PER_PAGE = "poolsListsPerPage";

    #[ConfigMeta("Updated list items per page", ConfigType::INT)]
    public const UPDATED_PER_PAGE = "poolsUpdatedPerPage";

    #[ConfigMeta("Show pool info on image", ConfigType::BOOL)]
    public const INFO_ON_VIEW_IMAGE = "poolsInfoOnViewImage";

    #[ConfigMeta("Show 'Prev' & 'Next' links when viewing pool images", ConfigType::BOOL)]
    public const SHOW_NAV_LINKS = "poolsShowNavLinks";

    #[ConfigMeta("Autoincrement order when post is added to pool", ConfigType::BOOL)]
    public const AUTO_INCREMENT_ORDER = "poolsAutoIncrementOrder";

    #[ConfigMeta("Show pool adder on image", ConfigType::BOOL, advanced: true)]
    public const ADDER_ON_VIEW_IMAGE = "poolsAdderOnViewImage";
}
