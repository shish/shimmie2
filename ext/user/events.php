<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @extends PartListBuildingEvent<array{name: string|HTMLElement, link: string}>
 */
class UserBlockBuildingEvent extends PartListBuildingEvent
{
    public function add_link(string|HTMLElement $name, string $link, int $position = 50): void
    {
        $this->add_part(["name" => $name, "link" => $link], $position);
    }
}

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class UserOperationsBuildingEvent extends PartListBuildingEvent
{
    public function __construct(
        public User $user,
        public BaseConfig $user_config,
    ) {
        parent::__construct();
    }
}

/**
 * @extends PartListBuildingEvent<string>
 */
class UserPageBuildingEvent extends PartListBuildingEvent
{
    public function __construct(
        public User $display_user,
    ) {
        parent::__construct();
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
