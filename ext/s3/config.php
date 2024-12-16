<?php

declare(strict_types=1);

namespace Shimmie2;

class S3Config extends ConfigGroup
{
    public const ACCESS_KEY_ID =     's3_access_key_id';
    public const ACCESS_KEY_SECRET = 's3_access_key_secret';
    public const ENDPOINT =          's3_endpoint';
    public const IMAGE_BUCKET =      's3_image_bucket';
}
