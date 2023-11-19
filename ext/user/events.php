<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

class UserBlockBuildingEvent extends Event
{
    public array $parts = [];

    public function add_link(string|HTMLElement $name, string $link, int $position = 50): void
    {
        while (isset($this->parts[$position])) {
            $position++;
        }
        $this->parts[$position] = ["name" => $name, "link" => $link];
    }
}

class UserOperationsBuildingEvent extends Event
{
    public array $parts = [];

    public function __construct(public User $user, public BaseConfig $user_config)
    {
        parent::__construct();
    }

    public function add_html(string $html): void
    {
        $this->parts[] = $html;
    }
}

class UserPageBuildingEvent extends Event
{
    public array $stats = [];

    public function __construct(public User $display_user)
    {
        parent::__construct();
    }

    public function add_stats(string $html, int $position = 50)
    {
        while (isset($this->stats[$position])) {
            $position++;
        }
        $this->stats[$position] = $html;
    }
}

class UserCreationEvent extends Event
{
    public function __construct(
        public string $username,
        public string $password,
        public string $password2,
        public string $email,
        public bool $login
    ) {
        parent::__construct();
    }
}

class UserLoginEvent extends Event
{
    public function __construct(public User $user)
    {
        parent::__construct();
    }
}

class UserDeletionEvent extends Event
{
    public function __construct(public int $id)
    {
        parent::__construct();
    }
}
