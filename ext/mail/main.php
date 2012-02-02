<?php
/**
* Name: Mail System
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: Provides an interface for sending and receiving mail.
*/

class Mail extends SimpleExtension {	
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Mailing Options");
		$sb->add_text_option("mail_sub", "<br>Subject prefix: ");
		$sb->add_text_option("mail_img", "<br>Banner Image URL: ");
		$sb->add_text_option("mail_style", "<br>Style URL: ");
		$sb->add_longtext_option("mail_fot", "<br>Footer (Use HTML)");
		$sb->add_label("<br><i>Should measure 550x110px. Use an absolute URL</i>");
		$event->panel->add_block($sb);
	}
	
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("mail_sub", $config->get_string("site_title")." - ");
		$config->set_default_string("mail_img", make_http("ext/mail/banner.png"));
		$config->set_default_string("mail_style", make_http("ext/mail/mail.css"));
		$config->set_default_string("mail_fot", "<a href='".make_http(make_link())."'>".$config->get_string("site_title")."</a>");
	}
}
class MailTest extends SimpleExtension {
	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("mail/test")) {
			global $page;
			$page->set_mode("data");
			echo "Alert: uncomment this page's code on /ext/mail/main.php starting on line 33, and change the email address. Make sure you're using a server with a domain, not localhost.";
			/*
			echo "Preparing to send message:<br>";
			echo "created new mail object. sending now... ";
			$email = new Email("example@localhost.com", "hello", "hello world", "this is a test message.");
			$email->send();
			echo "sent.";
			*/
		}
	}
}
?>
