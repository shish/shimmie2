<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class NavLink
{
    public bool $active = false;

    /**
     * @param page-string[] $matches
     */
    public function __construct(
        public Url $link,
        public string|HTMLElement $description,
        array $matches = [],
        public ?string $category = null,
        public int $order = 50,
        ?string $_query = null,
    ) {
        global $config;
        $query = $_query ?: _get_query() ?: $config->get_string(SetupConfig::FRONT_PAGE);
        if ($query === $link->getPage()) {
            $this->active = true;
        } else {
            foreach ($matches as $match) {
                if (str_starts_with($query, $match)) {
                    $this->active = true;
                    break;
                }
            }
        }
    }
}
