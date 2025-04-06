<?php

namespace Shimmie2;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\{TypeNodeResolver, TypeNodeResolverAwareExtension, TypeNodeResolverExtension};
use PHPStan\PhpDocParser\Ast\Type\{IdentifierTypeNode, TypeNode};
use PHPStan\Type\Type;

//use PHPStan\Type\TypeCombinator;

class GenericStringTypeNodeResolverExtension implements TypeNodeResolverExtension //, TypeNodeResolverAwareExtension
{
    /*
    // @ phpstan-ignore-next-line
    private TypeNodeResolver $typeNodeResolver;

    public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
    {
        $this->typeNodeResolver = $typeNodeResolver;
    }
*/
    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof IdentifierTypeNode) {
            return match ($typeNode->name) {
                'url-string' => new MatchyStringType(
                    'url-string',
                    fn ($v) => preg_match('#^(http://|https://|/)#', $v) === 1
                ),
                'page-string' => new MatchyStringType(
                    'page-string',
                    fn ($v) => (preg_match('/^(|[a-z\$][a-zA-Z0-9\/_:\$\.]*)$/', $v) === 1 && !str_contains($v, '://'))
                ),
                // 'page-string' => new PageStringType(),
                'fragment-string' => new MatchyStringType(
                    'fragment-string',
                    fn ($v) => preg_match('/^[a-z\-]+$/', $v) === 1
                ),
                'internal-hash-string' => new MatchyStringType(
                    'hash-string',
                    fn ($v) => preg_match('#^[0-9a-fA-F]{32}$#', $v) === 1
                ),
                default => null,
            };
        }
        return null;
    }
}
