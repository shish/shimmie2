<?php

declare(strict_types=1);

namespace Shimmie2;

class WikiTest extends ShimmiePHPUnitTestCase
{
    public function testIndex()
    {
        $this->get_page("wiki");
        $this->assert_title("Index");
        $this->assert_text("This is a default page");
    }

    public function testAccess()
    {
        global $config;
        foreach (["anon", "user", "admin"] as $user) {
            foreach ([false, true] as $allowed) {
                // admin has no settings to set
                if ($user != "admin") {
                    $config->set_bool("wiki_edit_$user", $allowed);
                }

                if ($user == "user") {
                    $this->log_in_as_user();
                }
                if ($user == "admin") {
                    $this->log_in_as_admin();
                }

                $this->get_page("wiki/test");
                $this->assert_title("test");
                $this->assert_text("This is a default page");

                if ($allowed || $user == "admin") {
                    $this->post_page("wiki_admin/edit", ["title"=>"test"]);
                    $this->assert_text("Editor");
                }
                /*
                // Everyone can see the editor
                else {
                    $this->post_page("wiki_admin/edit", ["title"=>"test"]);
                    $this->assert_no_text("Editor");
                }
                */

                if ($user == "user" || $user == "admin") {
                    $this->log_out();
                }
            }
        }
    }

    public function testDefault()
    {
        global $user;
        $this->log_in_as_admin();

        // Check default page is default
        $this->get_page("wiki/wiki:default");
        $this->assert_title("wiki:default");
        $this->assert_text("This is a default page");

        // Customise default page
        $wikipage = Wiki::get_page("wiki:default");
        $wikipage->revision = 1;
        $wikipage->body = "New Default Template";
        send_event(new WikiUpdateEvent($user, $wikipage));

        // Check that some random page is using the new default
        $this->get_page("wiki/something");
        $this->assert_text("New Default Template");

        // Delete the custom default
        send_event(new WikiDeletePageEvent("wiki:default"));

        // Check that the default page is back to normal
        $this->get_page("wiki/wiki:default");
        $this->assert_title("wiki:default");
        $this->assert_text("This is a default page");
    }

    public function testRevisions()
    {
        global $user;
        $this->log_in_as_admin();

        $this->get_page("wiki/test");
        $this->assert_title("test");
        $this->assert_text("This is a default page");

        $wikipage = Wiki::get_page("test");
        $wikipage->revision = $wikipage->revision + 1;
        $wikipage->body = "Mooooo 1";
        send_event(new WikiUpdateEvent($user, $wikipage));
        $this->get_page("wiki/test");
        $this->assert_text("Mooooo 1");
        $this->assert_text("Revision 1");

        $wikipage = Wiki::get_page("test");
        $wikipage->revision = $wikipage->revision + 1;
        $wikipage->body = "Mooooo 2";
        send_event(new WikiUpdateEvent($user, $wikipage));
        $this->get_page("wiki/test");
        $this->assert_text("Mooooo 2");
        $this->assert_text("Revision 2");

        send_event(new WikiDeleteRevisionEvent("test", 2));
        $this->get_page("wiki/test");
        $this->assert_text("Mooooo 1");
        $this->assert_text("Revision 1");

        send_event(new WikiDeletePageEvent("test"));
        $this->get_page("wiki/test");
        $this->assert_title("test");
        $this->assert_text("This is a default page");
    }
}
