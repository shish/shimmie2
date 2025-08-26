<?php

declare(strict_types=1);

namespace Shimmie2;

class BuildSupportedMimesEvent extends Event
{
    /** @var MimeType[] */
    private array $mimes = [];

    /**
     * @param MimeType[] $types
     */
    public function add_mimes(array $types): void
    {
        foreach ($types as $type) {
            if (!in_array($type, $this->mimes)) {
                $this->mimes[] = $type;
            }
        }
    }

    /**
     * @return MimeType[]
     */
    public function get_mimes(): array
    {
        return $this->mimes;
    }
}
