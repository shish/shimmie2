<?php declare(strict_types=1);

/**
 * Class CustomPage
 */
class CustomPage extends Page
{
    /** @var bool  */
    public $left_enabled = true;

    public function disable_left()
    {
        $this->left_enabled = false;
    }
}
