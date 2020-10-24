<?php declare(strict_types=1);
class MimeSystemTest extends ShimmiePHPUnitTestCase
{
    public function testJPEG()
    {
        $result = MimeType::get_for_file("tests/bedroom_workshop.jpg");
        $this->assertEquals(MimeType::JPEG, $result);
    }
}
