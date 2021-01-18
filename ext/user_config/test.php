<?php declare(strict_types=1);
class UserConfigTest extends ShimmiePHPUnitTestCase
{
    private const OPTIONS_BLOCK_TITLE = "User Options";

    public function testUserConfigPage()
    {
        $this->get_page('user_config');
        $this->assert_title("Permission Denied");
        $this->assert_no_text(self::OPTIONS_BLOCK_TITLE);

        $this->log_in_as_user();
        $this->get_page('user_config');
        $this->assert_title(self::OPTIONS_BLOCK_TITLE);
        $this->log_out();
    }
}
