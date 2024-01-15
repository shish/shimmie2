<?php

declare(strict_types=1);

namespace Shimmie2;

class ResolutionLimit extends Extension
{
    public function get_priority(): int
    {
        return 40;
    } // early, to veto ImageUploadEvent

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        global $config;
        $min_w = $config->get_int("upload_min_width", -1);
        $min_h = $config->get_int("upload_min_height", -1);
        $max_w = $config->get_int("upload_max_width", -1);
        $max_h = $config->get_int("upload_max_height", -1);
        $rs = $config->get_string("upload_ratios", "");
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
                    "Post needs to be in one of these ratios: ".
                    $config->get_string("upload_ratios", "")
                );
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Resolution Limits");

        $sb->add_label("Min ");
        $sb->add_int_option("upload_min_width");
        $sb->add_label(" x ");
        $sb->add_int_option("upload_min_height");
        $sb->add_label(" px");

        $sb->add_label("<br>Max ");
        $sb->add_int_option("upload_max_width");
        $sb->add_label(" x ");
        $sb->add_int_option("upload_max_height");
        $sb->add_label(" px");

        $sb->add_label("<br>(-1 for no limit)");

        $sb->add_label("<br>Ratios ");
        $sb->add_text_option("upload_ratios");
        $sb->add_label("<br>(eg. '4:3 16:9', blank for no limit)");
    }
}
