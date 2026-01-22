<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

/**
 * @extends PartListBuildingEvent<HTMLElement>
 */
class UserOperationsBuildingEvent extends PartListBuildingEvent
{
    public function __construct(
        public User $user,
        public Config $user_config,
    ) {
        parent::__construct();
    }
}

/**
 * @extends PartListBuildingEvent<HTMLElement>
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
    private ?User $user = null;

    public function __construct(
        public string $username,
        public string $password,
        public string $password2,
        public string $email,
        public bool $login
    ) {
        parent::__construct();
    }

    public function set_user(User $user): void
    {
        $this->user = $user;
    }

    public function get_user(): User
    {
        if (is_null($this->user)) {
            throw new \Exception("User not created");
        }
        return $this->user;
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
