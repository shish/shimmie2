<?php declare(strict_types=1);
use function MicroHTML\TEXTAREA;

class BiographyTheme extends Themelet
{
    public function display_biography(Page $page, string $bio)
    {
        $page->add_block(new Block("About Me", format_text($bio), "main", 30, "about-me"));
    }

    public function display_composer(Page $page, string $bio)
    {
        global $user;
        $post_url = make_link("biography");
        $auth = $user->get_auth_html();

        $html = SHM_SIMPLE_FORM(
            $post_url,
            TEXTAREA(["style"=>"width: 100%", "rows"=>"6", "name"=>"biography"], $bio),
            SHM_SUBMIT("Save")
        );

        $page->add_block(new Block("About Me", (string)$html, "main", 30));
    }
}
