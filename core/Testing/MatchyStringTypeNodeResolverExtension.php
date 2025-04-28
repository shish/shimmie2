<?php

namespace Shimmie2;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\{TypeNodeResolverExtension};
use PHPStan\PhpDocParser\Ast\Type\{IdentifierTypeNode, TypeNode};
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\{IntersectionType, Type};

class MatchyStringTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    /**
     * @param array<string,array{regex:string}|array{callable:callable}> $types
     */
    public function __construct(
        private array $types
    ) {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof IdentifierTypeNode) {
            if (array_key_exists($typeNode->name, $this->types)) {
                $typeName = $typeNode->name;
                $typeConfig = $this->types[$typeName];

                if (array_key_exists("regex", $typeConfig)) {
                    $matcher = fn ($value) => preg_match($typeConfig["regex"], $value) === 1;
                } elseif (array_key_exists("callable", $typeConfig)) {
                    $matcher = $typeConfig["callable"];
                }

                $type = new MatchyStringType($typeName, fn ($v) => $matcher($v));

                // If an empty string fails the matcher,
                // intersect with AccessoryNonEmptyStringType
                if (!$matcher("")) {
                    $type = new IntersectionType([
                        new AccessoryNonEmptyStringType(),
                        $type,
                    ]);
                }

                return $type;
            }
        }
        return null;
    }
}
