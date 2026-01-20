<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

final class NavLink
{
    public bool $active;

    /**
     * @param page-string[] $matches
     */
    public function __construct(
        public readonly Url $link,
        public readonly string|HTMLElement $description,
        public readonly string $key,
        array $matches = [],
        public readonly int $order = 50,
        ?string $_query = null,
        public readonly ?string $parent = null,
    ) {
        $query = $_query ?: _get_query() ?: Ctx::$config->get(SetupConfig::FRONT_PAGE);
        $active = false;
        if ($query === $link->getPage()) {
            $active = true;
        } else {
            foreach ($matches as $match) {
                if (str_starts_with($query, $match)) {
                    $active = true;
                    break;
                }
            }
        }
        $this->active = $active;
    }
}
