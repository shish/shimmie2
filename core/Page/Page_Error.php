<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * All of the stuff related to error-type responses
 */
trait Page_Error
{
    use Page_Page;

    abstract public function set_mode(PageMode $mode): void;
    abstract public function set_code(int $code): void;

    protected ?UserError $error = null;

    public function set_error(UserError $error): void
    {
        $this->set_mode(PageMode::ERROR);
        $this->error = $error;
    }

    protected function display_error(): void
    {
        $error = $this->error;
        assert($error !== null);
        $this->set_code($error->http_code);
        $this->set_title("Error");
        $this->blocks = [];
        $this->add_block(new Block("Navigation", \MicroHTML\A(["href" => make_link()], "Index"), "left"));
        $this->add_block(new Block(null, \MicroHTML\SPAN($error->getMessage())));
        $this->display_page();
    }
}
