<?php

declare(strict_types=1);

namespace Shimmie2;

class Terms extends Extension
{
    /** @var TermsTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string("terms_message", "Cookies may be used. Please read our [url=site://wiki/privacy]privacy policy[/url] for more information.\nBy accepting to enter you agree to our [url=site://wiki/rules]rules[/url] and [url=site://wiki/terms_of_service]terms of service[/url].");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Terms & Conditions Gate");
        $sb->add_longtext_option("terms_message", 'Message (Use BBCode)');
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page, $user;
        if ($event->page_starts_with("accept_terms")) {
            $page->add_cookie("accepted_terms", "true", time() + 60 * 60 * 24 * $config->get_int('login_memory'), "/");
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
                $body = format_text($config->get_string("terms_message"));
                $this->theme->display_page($page, $sitename, $event->path, $body);
            }
        }
    }
}
