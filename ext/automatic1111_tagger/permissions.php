<?php

declare(strict_types=1);

namespace Shimmie2;

final class Automatic1111TaggerPermission extends PermissionGroup
{
    public const KEY = "automatic1111_tagger";

    #[PermissionMeta("Interrogate image using Automatic1111")]
    public const INTERROGATE_IMAGE = "interrogate_image";

    #[PermissionMeta("Get image rating using Automatic1111")]
    public const GET_IMAGE_RATING = "get_image_rating";
}
