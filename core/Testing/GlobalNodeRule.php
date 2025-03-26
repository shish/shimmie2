<?php

namespace Shimmie2;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\Global_>
 */
class GlobalNodeRule implements \PHPStan\Rules\Rule
{
    public function getNodeType(): string
    {
        return \PhpParser\Node::class;
        //return \PhpParser\Node\Expr\Variable::class;
        //return \PhpParser\Node\Stmt\Global_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        // todo
        print("Processing node: " . $node::class . " " . $node->name . "\n");
        //var_dump($node::class);
        //var_dump($scope);
        //var_dump($node->vars);
        return [];
    }

}
