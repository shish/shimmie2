<?php declare(strict_types=1);

class Tagger extends Extension
{
    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $page, $user;

        if ($user->can(Permissions::EDIT_IMAGE_TAG) && ($event->image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK))) {
            $this->theme->build_tagger($page, $event);
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Tagger");
        $sb->add_int_option("ext_tagger_search_delay", "Delay queries by ");
        $sb->add_label(" milliseconds.");
        $sb->add_label("<br/>Limit queries returning more than ");
        $sb->add_int_option("ext_tagger_tag_max");
        $sb->add_label(" tags to ");
        $sb->add_int_option("ext_tagger_limit");
        $event->panel->add_block($sb);
    }
}
