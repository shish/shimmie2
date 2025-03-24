<?php

declare(strict_types=1);

namespace Shimmie2;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class GlobalNodeVisitor extends NodeVisitorAbstract
{
    public const ATTRIBUTE_NAME = 'globalTypeHint';

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof Node\Stmt\Global_) {
            return null;
        }

        foreach ($node->vars as $var) {
            // FIXME: get this mapping from config file
            $type = match($var->name) {
                'config' => '\Shimmie2\Config',
                'database' => '\Shimmie2\Database',
                'page' => '\Shimmie2\Page',
                'user' => '\Shimmie2\User',
                default => 'mixed',
            };
            $var->setAttribute(self::ATTRIBUTE_NAME, $type);
        }

        //file_put_contents("log.txt", print_r($node, true), FILE_APPEND);

        return null;
    }

}
