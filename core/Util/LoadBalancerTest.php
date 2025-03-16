<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class LoadBalancerTest extends TestCase
{
    public function test_load_balancing_parse(): void
    {
        self::assertEquals(
            ["foo" => 10, "bar" => 5, "baz" => 5, "quux" => 0],
            LoadBalancer::parse_load_balancer_config("foo=10,bar=5,baz=5,quux=0")
        );
    }

    public function test_load_balancing_choose(): void
    {
        $string_config = "foo=10,bar=5,baz=5,quux=0";
        $array_config = ["foo" => 10, "bar" => 5, "baz" => 5, "quux" => 0];
        $hash = "7ac19c10d6859415";

        self::assertEquals(
            $array_config,
            LoadBalancer::parse_load_balancer_config($string_config)
        );
        self::assertEquals(
            "foo",
            LoadBalancer::choose_load_balancer_node($array_config, $hash)
        );

        // Check that the balancing gives results in approximately
        // the right ratio (compatible implmentations should give
        // exactly these results)
        $results = ["foo" => 0, "bar" => 0, "baz" => 0, "quux" => 0];
        for ($i = 0; $i < 2000; $i++) {
            $results[LoadBalancer::choose_load_balancer_node($array_config, (string)$i)]++;
        }
        self::assertEquals(
            ["foo" => 1001, "bar" => 502, "baz" => 497, "quux" => 0],
            $results
        );
    }

    public function test_load_balancing_url(): void
    {
        $hash = "7ac19c10d6859415";
        $ext = "jpg";

        // pseudo-randomly select one of the image servers, balanced in given ratio
        self::assertEquals(
            "https://foo.mycdn.com/7ac19c10d6859415.jpg",
            LoadBalancer::load_balance_url("https://{foo=10,bar=5,baz=5,quux=0}.mycdn.com/$hash.$ext", $hash)
        );

        // N'th and N+1'th results should be different
        self::assertNotEquals(
            LoadBalancer::load_balance_url("https://{foo=10,bar=5,baz=5,quux=0}.mycdn.com/$hash.$ext", $hash, 0),
            LoadBalancer::load_balance_url("https://{foo=10,bar=5,baz=5,quux=0}.mycdn.com/$hash.$ext", $hash, 1)
        );
    }
}
