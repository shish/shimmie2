<?php

declare(strict_types=1);

namespace Shimmie2;

final class MediaProperties
{
    /**
     * @param ?int<0, max> $width Width of the media, or null if unknown
     * @param ?int<0, max> $height Height of the media, or null if unknown
     * @param ?int<0, max> $length Length of the media in milliseconds, or null if unknown
     */
    public function __construct(
        public ?int $width,
        public ?int $height,
        public bool $image,
        public bool $video,
        public bool $audio,
        public bool $lossless,
        public ?VideoCodec $video_codec,
        public ?int $length
    ) {
    }
}
