<?php declare(strict_types=1);

abstract class ImageConfig
{
    const VERSION =          'ext_image_version';

    const THUMB_ENGINE =     'thumb_engine';
    const THUMB_WIDTH =      'thumb_width';
    const THUMB_HEIGHT =     'thumb_height';
    const THUMB_SCALING =    'thumb_scaling';
    const THUMB_QUALITY =    'thumb_quality';
    const THUMB_MIME =       'thumb_mime';
    const THUMB_FIT =        'thumb_fit';
    const THUMB_ALPHA_COLOR ='thumb_alpha_color';

    const SHOW_META =        'image_show_meta';
    const ILINK =            'image_ilink';
    const TLINK =            'image_tlink';
    const TIP =              'image_tip';
    const INFO =             'image_info';
    const EXPIRES =          'image_expires';
    const UPLOAD_COLLISION_HANDLER = 'upload_collision_handler';

    const COLLISION_MERGE =  'merge';
    const COLLISION_ERROR =  'error';

    const ON_DELETE =        'image_on_delete';
    const ON_DELETE_NEXT =   'next';
    const ON_DELETE_LIST =   'list';
}
