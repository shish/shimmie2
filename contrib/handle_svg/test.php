<?php
class SVGHandlerTest extends ShimmieWebTestCase {
	function testSVGHander() {
		file_put_contents("test.svg", '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg
   xmlns="http://www.w3.org/2000/svg"
   width="128"
   height="128"
   id="svg2"
   version="1.0">
  <g id="layer1">
    <path
       style="fill:#0000ff;stroke:#213847;stroke-opacity:1"
       id="path2383"
       d="M 120.07832,64.983688 A 55.573441,53.092484 0 1 1 8.9314423,64.983688 A 55.573441,53.092484 0 1 1 120.07832,64.983688 z" />
  </g>
</svg>');

		$this->log_in_as_user();
		$image_id = $this->post_image("favicon.ico", "shimmie favicon");
		$this->assertResponse(302);
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();

		unlink("test.svg");

		# FIXME: test that the thumb works
		# FIXME: test that it gets displayed properly
	}
}
?>
