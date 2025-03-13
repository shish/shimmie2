<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class NavLink
{
    public bool $active = false;

    /**
     * @param url-string $link
     * @param page-string[] $matches
     */
    public function __construct(
        public string $link,
        public string|HTMLElement $description,
        array $matches = [],
        public ?string $category = null,
        public int $order = 50,
        ?string $_query = null,
    ) {
        global $config;
        $query = make_link($_query ?: _get_query() ?: $config->get_string(SetupConfig::FRONT_PAGE));
        if ($query === $link) {
            $this->active = true;
        } else {
            $matches = array_map(fn ($match) => make_link($match), $matches);
            foreach ($matches as $match) {
                if (str_starts_with($query, $match)) {
                    $this->active = true;
                    break;
                }
            }
        }
    }
}
