<?php declare(strict_types=1);

class JsonRpcTest extends ShimmiePHPUnitTestCase
{
    public function testEcho()
    {
        $evt = new ApiRequestEvent("echo", ["foo"=>"bar"], 1);
        send_event($evt);
        $this->assertEquals(1, $evt->id);
        $this->assertEquals(["foo"=>"bar"], $evt->result);
    }
}
