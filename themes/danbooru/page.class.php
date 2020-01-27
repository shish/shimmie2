<?php declare(strict_types=1);

class Page extends BasePage
{
    /** @var bool */
    public $left_enabled = true;

    public function disable_left()
    {
        $this->left_enabled = false;
    }
}
