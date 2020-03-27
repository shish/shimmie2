<?php declare(strict_types=1);

use MicroCRUD\ActionColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

class NotATagTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "untags";
        $this->base_query = "SELECT * FROM untags";
        $this->primary_key = "tag";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new TextColumn("tag", "Tag"),
            new TextColumn("redirect", "Redirect"),
            new ActionColumn("tag"),
        ]);
        $this->order_by = ["tag", "redirect"];
        $this->create_url = make_link("untag/add");
        $this->delete_url = make_link("untag/remove");
        $this->table_attrs = ["class" => "zebra"];
    }
}

class NotATag extends Extension
{
    /** @var NotATagTheme */
    protected $theme;

    public function get_priority(): int
    {
        return 30;
    } // before ImageUploadEvent and tag_history

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;
        if ($this->get_version("ext_notatag_version") < 1) {
            $database->create_table("untags", "
				tag VARCHAR(128) NOT NULL PRIMARY KEY,
				redirect VARCHAR(255) NOT NULL
			");
            $this->set_version("ext_notatag_version", 1);
        }
    }

    public function onImageAddition(ImageAdditionEvent $event)
    {
        $this->scan($event->image->get_tag_array());
    }

    public function onTagSet(TagSetEvent $event)
    {
        $this->scan($event->tags);
    }

    /**
     * #param string[] $tags_mixed
     */
    private function scan(array $tags_mixed)
    {
        global $database;

        $tags = [];
        foreach ($tags_mixed as $tag) {
            $tags[] = strtolower($tag);
        }

        $pairs = $database->get_all("SELECT * FROM untags");
        foreach ($pairs as $tag_url) {
            $tag = strtolower($tag_url[0]);
            $url = $tag_url[1];
            if (in_array($tag, $tags)) {
                header("Location: $url");
                exit; # FIXME: need a better way of aborting the tag-set or upload
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if ($event->parent==="tags") {
            if ($user->can(Permissions::BAN_IMAGE)) {
                $event->add_nav_link("untags", new Link('untag/list/1'), "UnTags");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event)
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->add_link("UnTags", make_link("untag/list/1"));
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $user;

        if ($event->page_matches("untag")) {
            if ($user->can(Permissions::BAN_IMAGE)) {
                if ($event->get_arg(0) == "add") {
                    $user->ensure_authed();
                    $input = validate_input(["c_tag"=>"string", "c_redirect"=>"string"]);
                    $database->execute(
                        "INSERT INTO untags(tag, redirect) VALUES (:tag, :redirect)",
                        ["tag"=>$input['c_tag'], "redirect"=>$input['c_redirect']]
                    );
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(referer_or(make_link()));
                } elseif ($event->get_arg(0) == "remove") {
                    $user->ensure_authed();
                    $input = validate_input(["d_tag"=>"string"]);
                    $database->execute(
                        "DELETE FROM untags WHERE LOWER(tag) = LOWER(:tag)",
                        ["tag"=>$input['d_tag']]
                    );
                    $page->flash("Image ban removed");
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(referer_or(make_link()));
                } elseif ($event->get_arg(0) == "list") {
                    $t = new NotATagTable($database->raw_db());
                    $t->token = $user->get_auth_token();
                    $t->inputs = $_GET;
                    $this->theme->display_untags($page, $t->table($t->query()), $t->paginator());
                }
            }
        }
    }

    public function get_untags(int $page, int $size=100): array
    {
        global $database;

        // FIXME: many
        $where = ["(1=1)"];
        $args = ["limit"=>$size, "offset"=>($page-1)*$size];
        if (!empty($_GET['tag'])) {
            $where[] = 'LOWER(tag) LIKE LOWER(:tag)';
            $args["tag"] = "%".$_GET['tag']."%";
        }
        if (!empty($_GET['redirect'])) {
            $where[] = 'LOWER(redirect) LIKE LOWER(:redirect)';
            $args["redirect"] = "%".$_GET['redirect']."%";
        }
        $where = implode(" AND ", $where);
        return $database->get_all("
			SELECT *
			FROM untags
			WHERE $where
			ORDER BY tag
			LIMIT :limit
			OFFSET :offset
		", $args);
    }
}
