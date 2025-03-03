<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class EnablableTest extends TestCase
{
    public function testGetEnabled(): void
    {
        $class = new \ReflectionClass(Enablable::class);
        $prop = $class->getProperty("enabled_extensions");
        $prop->setValue(null, null);
        $this->assertNull($prop->getValue());
        Enablable::get_enabled_extensions();
        // phpstan doesn't know that we changed the value
        // @phpstan-ignore-next-line
        $this->assertContains("admin", $prop->getValue());
    }
}
