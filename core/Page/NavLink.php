<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class NavLink
{
    public string $name;
    /** @var url-string $link */
    public string $link;
    public string|HTMLElement $description;
    public int $order;
    public bool $active = false;

    /** @param url-string $link */
    public function __construct(string $name, string $link, string|HTMLElement $description, ?bool $active = null, int $order = 50)
    {
        global $config;

        $this->name = $name;
        $this->link = $link;
        $this->description = $description;
        $this->order = $order;
        if ($active == null) {
            $query = _get_query();
            $link = trim($link, " \n\r\t\v\x00/\\");
            if ($query === "") {
                // This indicates the front page, so we check what's set as the front page
                $front_page = trim($config->get_string(SetupConfig::FRONT_PAGE), "/");

                if ($front_page === $link) {
                    $this->active = true;
                } else {
                    $this->active = self::is_active([$link], $front_page);
                }
            } elseif ($query === $link) {
                $this->active = true;
            } else {
                $this->active = self::is_active([$link]);
            }
        } else {
            $this->active = $active;
        }
    }

    /**
     * @param string[] $pages_matched
     */
    public static function is_active(array $pages_matched, ?string $url = null): bool
    {
        /**
         * Woo! We can actually SEE THE CURRENT PAGE!! (well... see it highlighted in the menu.)
         */
        $url = $url ?? _get_query();

        if (\Safe\preg_match_all("/.*?((?:[a-z][a-z_]+))/is", $url, $matches) > 0) {
            $url = $matches[1][0];
        }

        $count_pages_matched = count($pages_matched);

        for ($i = 0; $i < $count_pages_matched; $i++) {
            if ($url == $pages_matched[$i]) {
                return true;
            }
        }

        return false;
    }
}
