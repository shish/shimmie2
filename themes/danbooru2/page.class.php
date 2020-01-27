<?php declare(strict_types=1);

class Page extends BasePage
{
    public $left_enabled = true;
    public function disable_left()
    {
        $this->left_enabled = false;
    }
}
