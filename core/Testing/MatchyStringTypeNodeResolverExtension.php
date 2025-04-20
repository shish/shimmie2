<?php

namespace Shimmie2;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\{TypeNodeResolverExtension};
use PHPStan\PhpDocParser\Ast\Type\{IdentifierTypeNode, TypeNode};
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\{IntersectionType, Type};

class MatchyStringTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    /** @param array<string, string> $types */
    public function __construct(
        private array $types
    ) {
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof IdentifierTypeNode) {
            foreach ($this->types as $typeName => $typeRegex) {
                if ($typeNode->name === $typeName) {
                    $non_empty = preg_match($typeRegex, "") === 0;
                    if ($non_empty) {
                        return new IntersectionType([
                            new AccessoryNonEmptyStringType(),
                            new MatchyStringType(
                                $typeName,
                                fn ($v) => preg_match($typeRegex, $v) === 1
                            )
                        ]);
                    } else {
                        return new MatchyStringType(
                            $typeName,
                            fn ($v) => preg_match($typeRegex, $v) === 1
                        );
                    }
                }
            }
        }
        return null;
    }
}
