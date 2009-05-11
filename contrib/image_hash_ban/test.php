<?php
class ImageHashBanTest extends WebTestCase {}

if(!defined(VERSION)) return;

class ImageHashBanUnitTest extends UnitTestCase {
	public function _broken_testUploadFailsWhenBanned() {
		$ihb = new ImageHashBan();
		$due = new DataUploadEvent();

		try {
			$ihb->receive_event($due);
			$this->assertTrue(false); // shouldn't work
		}
		catch(DataUploadException $ex) {
			$this->assertTrue(true); // should fail
		}
		catch(Exception $ex) {
			$this->assertTrue(false); // but not with any other error
		}
	}
}
?>
