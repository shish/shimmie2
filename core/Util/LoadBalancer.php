<?php

declare(strict_types=1);

namespace Shimmie2;

final class LoadBalancer
{
    public static function load_balance_url(string $tmpl, string $hash, int $n = 0): string
    {
        $matches = [];
        if (\Safe\preg_match("/(.*){(.*)}(.*)/", $tmpl, $matches)) {
            $pre = $matches[1];
            $opts = $matches[2];
            $post = $matches[3];

            $nodes = LoadBalancer::parse_load_balancer_config($opts);
            $choice = LoadBalancer::choose_load_balancer_node($nodes, $hash, $n);

            $tmpl = $pre . $choice . $post;
        }
        return $tmpl;
    }

    /**
     * "foo=1,bar=2,baz=3" -> ['foo' => 1, 'bar' => 2, 'baz' => 3]
     *
     * @param string $s
     * @throws \Shimmie2\InvalidInput
     * @return array<string, int>
     */
    public static function parse_load_balancer_config(string $s): array
    {
        $nodes = [];

        foreach (explode(",", $s) as $opt) {
            $parts = explode("=", $opt);
            $parts_count = count($parts);
            if ($parts_count === 2) {
                $opt_val = $parts[0];
                $opt_weight = (int)$parts[1];
            } elseif ($parts_count === 1) {
                $opt_val = $parts[0];
                $opt_weight = 1;
            } else {
                throw new InvalidInput("Invalid load balancer weights: $s");
            }
            $nodes[$opt_val] = $opt_weight;
        }

        return $nodes;
    }

    /**
     * Choose a node from a list of nodes based on a key.
     *
     * @param array<string, int> $nodes
     * @param string $key
     * @param int $n
     * @return string
     */
    public static function choose_load_balancer_node(array $nodes, string $key, int $n = 0): string
    {
        if (count($nodes) === 0) {
            throw new InvalidInput("No load balancer nodes to choose from");
        }

        // create a list of [score, node] pairs
        $results = [];
        foreach ($nodes as $node => $weight) {
            // hash the node + key as an unsigned 32-bit integer
            $u32hash = hexdec(hash("murmur3a", "$node: $key"));
            // turn that into a float between 0 and 1
            $f32hash = ($u32hash + 1) / (1 << 32);
            // $hash * $weight gives an exponential bias to higher-weighted nodes,
            // 1/log($hash)*$weight gives a uniform distribution across the range
            $score = (1.0 / -log($f32hash)) * $weight;
            $results[] = [$score, $node];
        }

        // sort by score, highest first
        rsort($results);

        // return the highest node, fall back to the second-highest, etc
        return $results[$n % count($results)][1];
    }
}
