<?php

declare(strict_types=1);

namespace Shimmie2;

enum ResizeType: string
{
    case FIT = "Fit";
    case FIT_BLUR = "Fit Blur";
    case FIT_BLUR_PORTRAIT = "Fit Blur Tall, Fill Wide";
    case FILL = "Fill";
    case STRETCH = "Stretch";
}
