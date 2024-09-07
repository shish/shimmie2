<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML, rawHTML, TITLE, LINK};

class DowntimeTheme extends Themelet
{
    /**
     * Show the admin that downtime mode is enabled
     */
    public function display_notification(Page $page): void
    {
        $page->add_block(new Block(
            "Downtime",
            rawHTML("<span style='font-size: 1.5rem; text-align: center;'><b>DOWNTIME MODE IS ON!</b></span>"),
            "left",
            0
        ));
    }

    /**
     * Display $message and exit
     */
    public function display_message(string $message): void
    {
        global $config, $user, $page;
        $theme_name = $config->get_string(SetupConfig::THEME);
        $data_href = get_base_href();
        $login_link = make_link("user_admin/login");
        $form = make_form($login_link);

        $head = emptyHTML(
            TITLE("Downtime"),
            LINK(["rel" => "stylesheet", "href" => "$data_href/themes/$theme_name/style.css", "type" => "text/css"])
        );
        $body = rawHTML(<<<EOD
<div id="downtime">
	<section>
		<h1 style="text-align: center;">Down for Maintenance</h1>
		<div id="message" class="blockbody">
			$message
		</div>
	</section>
	<section>
		<h3>Admin Login</h3>
		<div id="login" class="blockbody">
			$form
				<table id="login_table" summary="Login Form">
					<tr>
						<td width="70"><label for="user">Name</label></td>
						<td width="70"><input id="user" type="text" name="user"></td>
					</tr>
					<tr>
						<td><label for="pass">Password</label></td>
						<td><input id="pass" type="password" name="pass"></td>
					</tr>
					<tr><td colspan="2"><input type="submit" value="Log In"></td></tr>
				</table>
			</form>
		</div>
	</section>
</div>
EOD);
        $page->set_mode(PageMode::DATA);
        $page->set_code(503);
        $page->set_data((string)$page->html_html($head, $body));
    }
}
