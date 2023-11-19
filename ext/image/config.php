<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class ImageConfig
{
    public const VERSION =          'ext_image_version';

    public const THUMB_ENGINE =     'thumb_engine';
    public const THUMB_WIDTH =      'thumb_width';
    public const THUMB_HEIGHT =     'thumb_height';
    public const THUMB_SCALING =    'thumb_scaling';
    public const THUMB_QUALITY =    'thumb_quality';
    public const THUMB_MIME =       'thumb_mime';
    public const THUMB_FIT =        'thumb_fit';
    public const THUMB_ALPHA_COLOR = 'thumb_alpha_color';

    public const SHOW_META =        'image_show_meta';
    public const ILINK =            'image_ilink';
    public const TLINK =            'image_tlink';
    public const TIP =              'image_tip';
    public const INFO =             'image_info';
    public const EXPIRES =          'image_expires';
    public const UPLOAD_COLLISION_HANDLER = 'upload_collision_handler';

    public const COLLISION_MERGE =  'merge';
    public const COLLISION_ERROR =  'error';

    public const ON_DELETE =        'image_on_delete';
    public const ON_DELETE_NEXT =   'next';
    public const ON_DELETE_LIST =   'list';
}
