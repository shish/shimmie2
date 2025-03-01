<?php

declare(strict_types=1);

namespace Shimmie2;

class ResolutionLimit extends Extension
{
    public const KEY = "res_limit";

    public function get_priority(): int
    {
        // early, to veto ImageUploadEvent
        return 40;
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        global $config;
        $min_w = $config->get_int(ResolutionLimitConfig::MIN_WIDTH, -1);
        $min_h = $config->get_int(ResolutionLimitConfig::MIN_HEIGHT, -1);
        $max_w = $config->get_int(ResolutionLimitConfig::MAX_WIDTH, -1);
        $max_h = $config->get_int(ResolutionLimitConfig::MAX_HEIGHT, -1);
        $rs = $config->get_string(ResolutionLimitConfig::RATIOS, "");
        $ratios = trim($rs) ? explode(" ", $rs) : [];

        $image = $event->image;

        if ($min_w > 0 && $image->width < $min_w) {
            throw new UploadException("Post too small");
        }
        if ($min_h > 0 && $image->height < $min_h) {
            throw new UploadException("Post too small");
        }
        if ($max_w > 0 && $image->width > $max_w) {
            throw new UploadException("Post too large");
        }
        if ($max_h > 0 && $image->height > $max_h) {
            throw new UploadException("Post too large");
        }

        if (count($ratios) > 0) {
            $ok = false;
            $valids = 0;
            foreach ($ratios as $ratio) {
                $parts = explode(":", $ratio);
                if (count($parts) < 2) {
                    continue;
                }
                $valids++;
                $width = (int)$parts[0];
                $height = (int)$parts[1];
                if ($image->width / $width == $image->height / $height) {
                    $ok = true;
                    break;
                }
            }
            if ($valids > 0 && !$ok) {
                throw new UploadException(
                    "Post needs to be in one of these ratios: $rs"
                );
            }
        }
    }
}
