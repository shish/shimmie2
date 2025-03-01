<?php

declare(strict_types=1);

namespace Shimmie2;

class Terms extends Extension
{
    public const KEY = "terms";
    /** @var TermsTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;
        if ($event->page_starts_with("accept_terms")) {
            $page->add_cookie("accepted_terms", "true", time() + 60 * 60 * 24 * $config->get_int(UserAccountsConfig::LOGIN_MEMORY), "/");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link(explode('/', $event->path, 2)[1]));
        } else {
            // run on all pages unless any of:
            // - user is logged in
            // - cookie exists
            // - user is viewing the wiki (because that's where the privacy policy / TOS / etc are)
            if (
                $user->is_anonymous()
                && !$page->get_cookie('accepted_terms')
                && !$event->page_starts_with("wiki")
            ) {
                $sitename = $config->get_string(SetupConfig::TITLE);
                $body = format_text($config->get_string(TermsConfig::MESSAGE));
                $this->theme->display_page($page, $sitename, $event->path, $body);
            }
        }
    }
}
