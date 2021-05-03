<?php declare(strict_types=1);

class ImageInfoBoxPart
{
    public string $header = "";
    public string $body = "";
    public int $order = 50;
    public bool $raw = false;

    public function __construct(string $header, string $body, int $order, bool $raw)
    {
        $this->header = $header;
        $this->body = $body;
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

    public function add_part(string $html, int $position=50, string $header="", bool $raw=false)
    {
        array_push($this->parts, new ImageInfoBoxPart($header, $html, $position, $raw));
    }

    public function get_sorted_parts()
    {
        $parts = $this->parts;
        usort($parts, function($a, $b) {
            return $a->order <=> $b->order;
        });
    }
}
