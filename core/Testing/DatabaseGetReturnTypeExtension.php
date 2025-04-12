<?php

namespace Shimmie2;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\{ArrayType, IntegerType, NullType, Type, UnionType};
use PHPStan\Type\Constant\{ConstantArrayType,ConstantStringType};
use PHPStan\Type\DynamicMethodReturnTypeExtension;

class DatabaseGetReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return \Shimmie2\Database::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get_all';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        $args = $methodCall->getArgs();
        if (count($args) === 0) {
            return null;
        }

        $query_arg = $methodCall->getArgs()[0];
        $type = $scope->getType($query_arg->value);
        $query_strings = $type->getConstantStrings();
        $result_type = null;
        if ($query_strings) {
            $sql = $query_strings[0]->getValue();
            $parser = new \PHPSQLParser\PHPSQLParser();
            $parsed = $parser->parse($sql);
            $select = $parsed['SELECT'];
            $from = $parsed['FROM'];
            print_r($parsed);
            $col_types = [
                'id' => new IntegerType(),
                'name' => new ConstantStringType('John Doe'),
                'email' => new ConstantStringType($query),
            ];
            $row_type = new ConstantArrayType(
                array_map(fn ($name) => new ConstantStringType($name), array_keys($col_types)),
                array_values($col_types)
            );
            $result_type = new ArrayType(new IntegerType(), $row_type);
        }
        //if ($configType !== null && !$hasDefault) {
        //    $configType = new UnionType([$configType, new NullType()]);
        //}
        return $result_type;
    }
}
