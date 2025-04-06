<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPStan\Type\{AcceptsResult, CompoundType, ConstantStringType, IsSuperTypeOfResult, StringType, Type, VerbosityLevel};

class MatchyStringType extends StringType
{
    /**
     * @param callable(string): bool $matcher
     */
    public function __construct(
        public string $name,
        private $matcher,
    ) {
        parent::__construct();
    }

    public function describe(VerbosityLevel $level): string
    {
        return $this->name ?? 'matchy-string';
    }

    protected function match(string $value): bool
    {
        return ($this->matcher)($value);
    }

    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        //if ($type->isLiteralString()->yes() && $type instanceof ConstantStringType) {
        //    $value = $type->getValue();
        //    return AcceptsResult::createFromBoolean($this->match($value));
        //}

        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) === 1) {
            $value = $constantStrings[0]->getValue();
            return AcceptsResult::createFromBoolean($this->match($value));
        }

        if ($type instanceof self) {
            return AcceptsResult::createFromBoolean($this->name === $type->name);
        }

        // FIXME: should be "maybe", but we have a lot of eg
        // make_link("user/$username") and $username could be
        // anything, and returning Maybe here makes them all
        // fail (which is technically correct, but not very useful)
        if ($type->isString()->yes()) {
            return AcceptsResult::createYes();
        }

        return AcceptsResult::createNo();
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        $constantStrings = $type->getConstantStrings();

        if (count($constantStrings) === 1) {
            $value = $constantStrings[0]->getValue();
            return IsSuperTypeOfResult::createFromBoolean($this->match($value));
        }

        if ($type instanceof self) {
            return IsSuperTypeOfResult::createYes();
        }

        if ($type->isString()->yes()) {
            return IsSuperTypeOfResult::createMaybe();
        }

        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return IsSuperTypeOfResult::createNo();
    }
}
