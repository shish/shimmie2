<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * All of the stuff related to redirect-type responses
 */
trait Page_Redirect
{
    use WithFlash;

    abstract public function set_mode(PageMode $mode): void;

    public ?Url $redirect = null;

    /**
     * Set the URL to redirect to (remember to use make_link() if linking
     * to a page in the same site).
     */
    public function set_redirect(Url $redirect): void
    {
        $this->set_mode(PageMode::REDIRECT);
        $this->redirect = $redirect;
    }

    protected function display_redirect(): void
    {
        $this->send_headers();
        if ($this->redirect === null) {
            throw new ServerError("PageMode is REDIRECT but redirect is not set");
        }
        if ($this->flash) {
            $this->redirect = $this->redirect->withModifiedQuery(["flash" => implode("\n", $this->flash)]);
        }
        header('Location: ' . $this->redirect);
        print 'You should be redirected to <a href="' . $this->redirect . '">' . $this->redirect . '</a>';
    }
}
