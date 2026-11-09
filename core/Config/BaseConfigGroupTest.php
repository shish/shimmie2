<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

/**
 * BaseConfigGroup
 *  |- MockBaseConfigGroup
 *     |- MockSysConfigGroup
 *     |  |- FooMockSysConfigGroup
 *     |  |- BarMockSysConfigGroup
 *     |- MockUserConfigGroup
 *        |- BazMockUserConfigGroup
 *        |- QuxMockUserConfigGroup
 */
abstract class MockBaseConfigGroup extends BaseConfigGroup
{
}

abstract class MockSysConfigGroup extends MockBaseConfigGroup
{
}

class FooMockSysConfigGroup extends MockSysConfigGroup
{
    #[ConfigMeta("Foo", ConfigType::BOOL)]
    public const FOO = "foo";
}

class BarMockSysConfigGroup extends MockSysConfigGroup
{
    #[ConfigMeta("Bar", ConfigType::INT)]
    public const BAR = "bar";
}

abstract class MockUserConfigGroup extends MockBaseConfigGroup
{
}

class BazMockUserConfigGroup extends MockUserConfigGroup
{
    #[ConfigMeta("Baz", ConfigType::STRING)]
    public const BAZ = "baz";

}

class QuxMockUserConfigGroup extends MockUserConfigGroup
{
    #[ConfigMeta("Qux", ConfigType::ARRAY)]
    public const QUX = "qux";
}

class TypedMockConfigGroup extends BaseConfigGroup
{
    #[ConfigMeta("MyNull", ConfigType::STRING)]
    public const MY_NULL = "mynull";

    #[ConfigMeta("MyString", ConfigType::STRING)]
    public const MY_STRING = "mystring";

    #[ConfigMeta("MyInt", ConfigType::INT)]
    public const MY_INT = "myint";

    #[ConfigMeta("MyBoolTrue", ConfigType::BOOL)]
    public const MY_BOOL_TRUE = "mybool_true";

    #[ConfigMeta("MyBoolFalse", ConfigType::BOOL)]
    public const MY_BOOL_FALSE = "mybool_false";

    #[ConfigMeta("MyArray", ConfigType::ARRAY)]
    public const MY_ARRAY = "myarray";

    #[ConfigMeta("EmptyString", ConfigType::STRING)]
    public const EMPTY_STRING = "emptystring";

    #[ConfigMeta("EmptyInt", ConfigType::INT)]
    public const EMPTY_INT = "emptyint";

    #[ConfigMeta("EmptyArray", ConfigType::ARRAY)]
    public const EMPTY_ARRAY = "emptyarray";

    #[ConfigMeta("Zero", ConfigType::INT)]
    public const ZERO = "zero";
}

class TypedOptionsMockConfigGroup extends BaseConfigGroup
{
    #[ConfigMeta("String Option", ConfigType::STRING, options: ["Foo Label" => "foo", "Bar Label" => "bar"])]
    public const STRING_OPTION = "string_option";

    #[ConfigMeta("Int Option", ConfigType::INT, options: ["Ten" => "10", "Twenty" => "20"])]
    public const INT_OPTION = "int_option";

    #[ConfigMeta("Array Option", ConfigType::ARRAY, options: ["WebP" => "webp", "JPEG" => "jpeg"])]
    public const ARRAY_OPTION = "array_option";
}

class PermissionedConfigGroup extends BaseConfigGroup
{
    #[ConfigMeta(
        "Permissioned Option",
        ConfigType::STRING,
        permission: PermManagerPermission::MANAGE_USER_PERMISSIONS,
    )]
    public const PERMISSIONED_OPTION = "permissioned_option";
}

final class BaseConfigGroupTest extends TestCase
{
    public function testTitle(): void
    {
        $group = new MyExampleConfig();
        self::assertSame("My Example", $group->get_title());
    }

    public function testGetAllMetas(): void
    {
        self::assertCount(4, MockBaseConfigGroup::get_all_metas());
        self::assertCount(2, MockSysConfigGroup::get_all_metas());
        self::assertCount(1, FooMockSysConfigGroup::get_all_metas());
    }

    public function testFromPostValid(): void
    {
        self::assertSame(
            [
                "mynull" => null,
                "mystring" => "hello world!",
                "myint" => 42 * 1024,
                "mybool_true" => true,
                "mybool_false" => false,
                "myarray" => ["hello", "world"],
                "emptystring" => null,
                "emptyint" => null,
                "emptyarray" => [],
                "zero" => 0,
            ],
            TypedMockConfigGroup::fromPost(new QueryArray([
                // keys in POST that don't start with _type or _config are ignored
                "some_post" => "value",
                // _type with no _config means the value is null
                "_type_mynull" => "*",
                // strings left as-is
                "_type_mystring" => "*",
                "_config_mystring" => "hello world!",
                // ints parsed from human-readable form
                "_type_myint" => "*",
                "_config_myint" => "42KB",
                // HTML booleans (HTML checkboxes are "on" or undefined, there is no "off")
                "_type_mybool_true" => "*",
                "_config_mybool_true" => "on",
                "_type_mybool_false" => "*",
                // Arrays are... passed as arrays? Does this work?
                "_type_myarray" => "*",
                "_config_myarray" => ["hello", "world"],
                // Empty things should be null
                "_type_emptystring" => "*",
                "_config_emptystring" => "",
                "_type_emptyint" => "*",
                "_config_emptyint" => "",
                "_type_emptyarray" => "*",
                // "0" should not be null
                "_type_zero" => "*",
                "_config_zero" => "0",
            ]))
        );
    }

    public function testFromPostInvalid(): void
    {
        self::expectException(InvalidInput::class);
        TypedMockConfigGroup::fromPost(new QueryArray([
            "_type_myint" => "*",
            "_config_myint" => "tasty",
        ]));
    }

    public function testConfigOptionsValid(): void
    {
        self::assertSame(
            [
                "string_option" => "foo",
                "int_option" => 10,
                "array_option" => ["webp"],
            ],
            TypedOptionsMockConfigGroup::fromPost(new QueryArray([
                "_type_string_option" => "*",
                "_config_string_option" => "foo",
                "_type_int_option" => "*",
                "_config_int_option" => "10",
                "_type_array_option" => "*",
                "_config_array_option" => ["webp"],
            ]))
        );
    }

    public function testConfigOptionsInvalidString(): void
    {
        self::expectException(InvalidInput::class);
        TypedOptionsMockConfigGroup::fromPost(new QueryArray([
            "_type_string_option" => "*",
            "_config_string_option" => "asdf",
        ]));
    }

    public function testConfigOptionsInvalidInt(): void
    {
        self::expectException(InvalidInput::class);
        TypedOptionsMockConfigGroup::fromPost(new QueryArray([
            "_type_int_option" => "*",
            "_config_int_option" => "asdf",
        ]));
    }

    public function testConfigOptionsInvalidArray(): void
    {
        self::expectException(InvalidInput::class);
        TypedOptionsMockConfigGroup::fromPost(new QueryArray([
            "_type_array_option" => "*",
            "_config_array_option" => ["webp", "mp4"],
        ]));
    }
}

final class BaseConfigGroupShimmieTest extends ShimmiePHPUnitTestCase
{
    public function testConfigOptionsPermissionAllowed(): void
    {
        Ctx::$user = User::by_name("admin");
        self::assertSame(
            "foo",
            PermissionedConfigGroup::fromPost(new QueryArray([
                "_type_permissioned_option" => "*",
                "_config_permissioned_option" => "foo",
            ])),
        );
    }

    public function testConfigOptionsPermissionDenied(): void
    {
        self::expectException(PermissionDenied::class);
        PermissionedConfigGroup::fromPost(new QueryArray([
            "_type_permissioned_option" => "*",
            "_config_permissioned_option" => "foo",
        ]));
    }
}
