<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

abstract class ExtensionInfo extends Enablable
{
    // Every credit you get costs us RAM. It stops now.
    public const SHISH_NAME = "Shish";
    public const SHISH_EMAIL = "webmaster@shishnet.org";
    public const SHIMMIE_URL = "https://code.shishnet.org/shimmie2/";
    public const SHISH_AUTHOR = [self::SHISH_NAME => self::SHISH_EMAIL];

    public const LICENSE_GPLV2 = "GPLv2";
    public const LICENSE_MIT = "MIT";
    public const LICENSE_WTFPL = "WTFPL";

    public bool $core = false;
    public bool $beta = false;

    public string $name;
    public string $license;
    public string $description;
    /** @var array<string, string|null> */
    public array $authors = [];
    /** @var string[] */
    public array $dependencies = [];
    /** @var string[] */
    public array $conflicts = [];
    public ExtensionVisibility $visibility = ExtensionVisibility::DEFAULT;
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    /** @var url-string|null */
    public ?string $link = null;
    public ?string $documentation = null;

    /** @var DatabaseDriverID[] which DBs this ext supports (blank for 'all') */
    public array $db_support = [];
    private ?string $support_info = null;

    public function __construct()
    {
        assert(!empty($this::KEY), "KEY field is required");
        assert(!empty($this->name), "name field is required for extension " . $this::KEY);
    }

    public function is_supported(): bool
    {
        return empty($this->get_support_info());
    }

    public function get_support_info(): string
    {
        if ($this->support_info === null) {
            global $database;
            $this->support_info  = "";
            if (!empty($this->db_support) && !in_array($database->get_driver_id(), $this->db_support)) {
                $this->support_info .= "Database not supported. ";
            }
            if (!empty($this->conflicts)) {
                $intersects = array_intersect($this->conflicts, Extension::get_enabled_extensions());
                if (!empty($intersects)) {
                    $this->support_info .= "Conflicts with other extension(s): " . join(", ", $intersects);
                }
            }
        }
        return $this->support_info;
    }

    /**
     * @return ExtensionInfo[]
     */
    public static function get_all(): array
    {
        return array_values(self::get_all_infos());
    }

    /**
     * @return string[]
     */
    public static function get_all_keys(): array
    {
        return array_keys(self::get_all_infos());
    }

    /**
     * @return string[]
     */
    public static function get_core_extensions(): array
    {
        return array_map(
            fn ($info) => $info::KEY,
            array_filter(
                array_values(self::get_all_infos()),
                fn ($info) => $info->core
            )
        );
    }

    public static function get_by_key(string $key): ExtensionInfo
    {
        $info = self::get_all_infos()[$key] ?? null;
        if (is_null($info)) {
            throw new \InvalidArgumentException("No ExtensionInfo with key '$key'");
        }
        return $info;
    }

    /**
     * @return array<string, ExtensionInfo>
     */
    private static function get_all_infos(): array
    {
        static $infos = null;
        if (is_null($infos)) {
            $infos = [];
            foreach (ExtensionInfo::get_subclasses(all: true) as $class) {
                $extension_info = $class->newInstance();
                $infos[$extension_info::KEY] = $extension_info;
            }
        }
        return $infos;
    }
}
