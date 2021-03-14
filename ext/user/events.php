<?php declare(strict_types=1);

class UserBlockBuildingEvent extends Event
{
    public array $parts = [];

    public function add_link(string $name, string $link, int $position=50): void
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
    public User $user;
    public BaseConfig $user_config;

    public function __construct(User $user, BaseConfig $user_config)
    {
        parent::__construct();
        $this->user = $user;
        $this->user_config = $user_config;
    }

    public function add_html(string $html): void
    {
        $this->parts[] = $html;
    }
}

class UserPageBuildingEvent extends Event
{
    public User $display_user;
    public array $stats = [];

    public function __construct(User $display_user)
    {
        parent::__construct();
        $this->display_user = $display_user;
    }

    public function add_stats(string $html, int $position=50)
    {
        while (isset($this->stats[$position])) {
            $position++;
        }
        $this->stats[$position] = $html;
    }
}

class UserCreationEvent extends Event
{
    public string $username;
    public string $password;
    public string $email;
    public bool $login;

    public function __construct(string $name, string $pass, string $email, bool $login)
    {
        parent::__construct();
        $this->username = $name;
        $this->password = $pass;
        $this->email = $email;
        $this->login = $login;
    }
}

class UserLoginEvent extends Event
{
    public User $user;
    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
    }
}

class UserDeletionEvent extends Event
{
    public int $id;

    public function __construct(int $id)
    {
        parent::__construct();
        $this->id = $id;
    }
}
