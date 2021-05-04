<?php declare(strict_types=1);

class ImageInfoBoxPart
{
    public array $items = [];
    public array $attributes = [];
    public string $header = "";
    public int $order = 50;
    public bool $raw = false;

    public function __construct(string $header, array $items, int $order, bool $raw, array $attributes)
    {
        $this->header = $header;
        $this->items = $items;
        $this->attributes = $attributes;
        $this->order = $order;
        $this->raw = $raw;
    }
}

class ImageInfoBoxBuildingEvent extends Event
{
    public array $parts = [];
    public Image $image;
    public User $user;

    public function __construct(Image $image, User $user)
    {
        parent::__construct();
        $this->image = $image;
        $this->user = $user;
    }

    /* public function add_part(string $html, int $order=50, string $header="", bool $raw=false, array $attributes=[])
    {
        $this->parts[] = new ImageInfoBoxPart($header, [$html], $order, $raw, $attributes);
    } */

    public function add_part(array $html, int $order=50, string $header="", bool $raw=false, array $attributes=[])
    {
        $this->parts[] = new ImageInfoBoxPart($header, $html, $order, $raw, $attributes);
    }

    public function get_sorted_parts()
    {
        $parts = $this->parts;
        usort($parts, function($a, $b) {
            return $a->order <=> $b->order;
        });
        return $parts;
    }
}
