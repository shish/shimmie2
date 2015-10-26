<?php
class AdminPageTest extends ShimmiePHPUnitTestCase {
	public function testAuth() {
		$this->get_page('admin');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");

		$this->log_in_as_user();
		$this->get_page('admin');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");

		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_response(200);
		$this->assert_title("Admin Tools");
	}

	public function testLowercase() {
		$ts = time(); // we need a tag that hasn't been used before

		$this->log_in_as_admin();
		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "TeStCase$ts");

		$this->get_page("post/view/$image_id_1");
		$this->assert_title("Image $image_id_1: TeStCase$ts");

		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		//$this->click("All tags to lowercase");
		send_event(new AdminActionEvent('lowercase_all_tags'));

		$this->get_page("post/view/$image_id_1");
		$this->assert_title("Image $image_id_1: testcase$ts");

		$this->delete_image($image_id_1);
	}

	# FIXME: make sure the admin tools actually work
	public function testRecount() {
		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_title("Admin Tools");

		//$this->click("Recount tag use");
		send_event(new AdminActionEvent('recount_tag_use'));
	}

	public function testDump() {
		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_title("Admin Tools");

		// this calls mysqldump which jams up travis prompting for a password
		//$this->click("Download database contents");
		//send_event(new AdminActionEvent('database_dump'));
		//$this->assert_response(200);
	}

	public function testDBQ() {
		$this->log_in_as_user();
		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "test");
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "test2");
		$image_id_3 = $this->post_image("tests/favicon.png", "test");

		$this->get_page("post/list/test/1");
		//$this->click("Delete All These Images");
		$_POST['query'] = 'test';
		//$_POST['reason'] = 'reason'; // non-null-reason = add a hash ban
		send_event(new AdminActionEvent('delete_by_query'));

		$this->get_page("post/view/$image_id_1");
		$this->assert_response(404);
		$this->get_page("post/view/$image_id_2");
		$this->assert_response(200);
		$this->get_page("post/view/$image_id_3");
		$this->assert_response(404);

		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->delete_image($image_id_3);
	}
}

