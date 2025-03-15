<?php

declare(strict_types=1);

namespace Shimmie2;

final class PoolsConfig extends ConfigGroup
{
    public const KEY = "pools";

    #[ConfigMeta("Max results on import", ConfigType::INT, default: 1000)]
    public const MAX_IMPORT_RESULTS = "poolsMaxImportResults";

    #[ConfigMeta("Posts per page", ConfigType::INT, default: 20)]
    public const IMAGES_PER_PAGE = "poolsImagesPerPage";

    #[ConfigMeta("Index list items per page", ConfigType::INT, default: 20)]
    public const LISTS_PER_PAGE = "poolsListsPerPage";

    #[ConfigMeta("Updated list items per page", ConfigType::INT, default: 20)]
    public const UPDATED_PER_PAGE = "poolsUpdatedPerPage";

    #[ConfigMeta("Show pool info on image", ConfigType::BOOL, default: false)]
    public const INFO_ON_VIEW_IMAGE = "poolsInfoOnViewImage";

    #[ConfigMeta("Show 'Prev' & 'Next' links when viewing pool images", ConfigType::BOOL, default: false)]
    public const SHOW_NAV_LINKS = "poolsShowNavLinks";

    #[ConfigMeta("Autoincrement order when post is added to pool", ConfigType::BOOL, default: false)]
    public const AUTO_INCREMENT_ORDER = "poolsAutoIncrementOrder";

    #[ConfigMeta("Show pool adder on image", ConfigType::BOOL, default: false, advanced: true)]
    public const ADDER_ON_VIEW_IMAGE = "poolsAdderOnViewImage";
}
