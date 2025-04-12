<?php

namespace Shimmie2;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\{ArrayType, BooleanType, IntegerType, NullType, StringType, Type, UnionType};
use PHPStan\Type\DynamicMethodReturnTypeExtension;

class ConfigGetReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var array<string, ConfigMeta>
     */
    private array $metas;

    public function __construct()
    {
        foreach (\Safe\glob("ext/*/*.php") as $fn) {
            require_once($fn);
        }
        $this->metas = array_merge(
            ConfigGroup::get_all_metas(),
            UserConfigGroup::get_all_metas(),
        );
    }

    public function getClass(): string
    {
        return \Shimmie2\Config::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        $configType = null;
        $hasDefault = false;
        if (count($methodCall->getArgs()) === 2) {
            $type = $scope->getType($methodCall->getArgs()[1]->value);
            if ($type->getEnumCases()) {
                $name = $type->getEnumCases()[0]->getEnumCaseName();
                $configType = match($name) {
                    "INT" => new IntegerType(),
                    "STRING" => new StringType(),
                    "BOOL" => new BooleanType(),
                    "ARRAY" => new ArrayType(new IntegerType(), new StringType()),
                    default => throw new \Exception("Unsupported type for Config::get(): $name"),
                };
            }
        } elseif (count($methodCall->getArgs()) === 1) {
            $type = $scope->getType($methodCall->getArgs()[0]->value);
            if ($type->getConstantStrings()) {
                $key = $type->getConstantStrings()[0]->getValue();
                if (isset($this->metas[$key])) {
                    $configType = match($this->metas[$key]->type) {
                        ConfigType::INT => new IntegerType(),
                        ConfigType::STRING => new StringType(),
                        ConfigType::BOOL => new BooleanType(),
                        ConfigType::ARRAY => new ArrayType(new IntegerType(), new StringType()),
                    };
                    $hasDefault = $this->metas[$key]->default !== null;
                }
            }
        }
        if ($configType !== null && !$hasDefault) {
            $configType = new UnionType([$configType, new NullType()]);
        }
        return $configType;
    }
}
