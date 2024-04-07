<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageRating
{
    /** @var array<string, ImageRating> */
    public static array $known_ratings = [];
    public string $name;
    public string $code;
    public string $search_term;
    public int $order = 0;

    public function __construct(string $code, string $name, string $search_term, int $order)
    {
        assert(strlen($code) == 1, "Rating code must be exactly one character");

        $this->name = $name;
        $this->code = $code;
        $this->search_term = $search_term;
        $this->order = $order;
    }
}

function add_rating(ImageRating $rating): void
{
    if ($rating->code == "?" && array_key_exists("?", ImageRating::$known_ratings)) {
        throw new \RuntimeException("? is a reserved rating code that cannot be overridden");
    }
    if ($rating->code != "?" && in_array(strtolower($rating->search_term), Ratings::UNRATED_KEYWORDS)) {
        throw new \RuntimeException("$rating->search_term is a reserved search term");
    }
    ImageRating::$known_ratings[$rating->code] = $rating;
}

add_rating(new ImageRating("?", "Unrated", "unrated", 99999));
add_rating(new ImageRating("s", "Safe", "safe", 0));
add_rating(new ImageRating("q", "Questionable", "questionable", 500));
add_rating(new ImageRating("e", "Explicit", "explicit", 1000));
/** @noinspection PhpIncludeInspection */
@include_once "data/config/ratings.conf.php";

class RatingSetException extends UserError
{
    public ?string $redirect;

    public function __construct(string $msg, ?string $redirect = null)
    {
        parent::__construct($msg);
        $this->redirect = $redirect;
    }
}

class RatingSetEvent extends Event
{
    public Image $image;
    public string $rating;

    public function __construct(Image $image, string $rating)
    {
        parent::__construct();

        assert(in_array($rating, array_keys(ImageRating::$known_ratings)));

        $this->image = $image;
        $this->rating = $rating;
    }
}

abstract class RatingsConfig
{
    public const VERSION = "ext_ratings2_version";
    public const USER_DEFAULTS = "ratings_default";
}

class Ratings extends Extension
{
    /** @var RatingsTheme */
    protected Themelet $theme;

    public const UNRATED_KEYWORDS = ["unknown", "unrated"];

    private string $search_regexp;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

        $codes = implode("", array_keys(ImageRating::$known_ratings));
        $search_terms = [];
        foreach (ImageRating::$known_ratings as $key => $rating) {
            $search_terms[] = $rating->search_term;
        }
        $this->search_regexp = "/^rating[=|:](?:(\*|[" . $codes . "]+)|(" .
            implode("|", $search_terms) . "|".implode("|", self::UNRATED_KEYWORDS)."))$/D";

        foreach (array_keys(UserClass::$known_classes) as $key) {
            if ($key == "base" || $key == "hellbanned") {
                continue;
            }
            $config->set_default_array("ext_rating_" . $key . "_privs", array_keys(ImageRating::$known_ratings));
        }

        Image::$prop_types["rating"] = ImagePropType::STRING;
    }

    private function check_permissions(Image $image): bool
    {
        global $user;

        $user_view_level = Ratings::get_user_class_privs($user);
        if (!in_array($image['rating'], $user_view_level)) {
            return false;
        }
        return true;
    }

    public function onInitUserConfig(InitUserConfigEvent $event): void
    {
        $event->user_config->set_default_array(RatingsConfig::USER_DEFAULTS, self::get_user_class_privs($event->user));
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        /**
         * Deny images upon insufficient permissions.
         **/
        if (!$this->check_permissions($event->image)) {
            throw new PermissionDenied("Access denied");
        }
    }

    public function onUserOptionsBuilding(UserOptionsBuildingEvent $event): void
    {
        global $user;

        $levels = self::get_user_class_privs($user);
        $options = [];
        foreach ($levels as $level) {
            $options[ImageRating::$known_ratings[$level]->name] = $level;
        }

        $sb = $event->panel->create_new_block("Default Rating Filter");
        $sb->start_table();
        $sb->add_multichoice_option(RatingsConfig::USER_DEFAULTS, $options, "Default Ratings: ", true);
        $sb->end_table();
        $sb->add_label("This controls the default rating search results will be filtered by, and nothing else. To override in your search results, add rating:* to your search.");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $ratings = self::get_sorted_ratings();

        $options = [];
        foreach ($ratings as $key => $rating) {
            $options[$rating->name] = $rating->code;
        }

        $sb = $event->panel->create_new_block("Post Rating Visibility");
        $sb->start_table();
        foreach (array_keys(UserClass::$known_classes) as $key) {
            if ($key == "base" || $key == "hellbanned") {
                continue;
            }
            $sb->add_multichoice_option("ext_rating_" . $key . "_privs", $options, $key, true);
        }
        $sb->end_table();
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $page;
        /**
         * Deny images upon insufficient permissions.
         **/
        if (!$this->check_permissions($event->image)) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
    }

    public function onBulkExport(BulkExportEvent $event): void
    {
        $event->fields["rating"] = $event->image['rating'];
    }
    public function onBulkImport(BulkImportEvent $event): void
    {
        if (array_key_exists("rating", $event->fields)
            && $event->fields['rating'] !== null
            && Ratings::rating_is_valid($event->fields['rating'])) {
            $this->set_rating($event->image->id, $event->fields['rating'], "");
        }
    }

    public function onRatingSet(RatingSetEvent $event): void
    {
        if (empty($event->image['rating'])) {
            $old_rating = "";
        } else {
            $old_rating = $event->image['rating'];
        }
        $this->set_rating($event->image->id, $event->rating, $old_rating);
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        global $user;
        $event->add_part(
            $this->theme->get_image_rater_html(
                $event->image->id,
                $event->image['rating'],
                $user->can(Permissions::EDIT_IMAGE_RATING)
            ),
            80
        );
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $page, $user;
        if (
            $user->can(Permissions::EDIT_IMAGE_RATING) && (
                isset($event->params['rating'])
                || isset($event->params["rating{$event->slot}"])
            )
        ) {
            $common_rating = $event->params['rating'] ?? "";
            $my_rating = $event->params["rating{$event->slot}"] ?? "";
            $rating = Ratings::rating_is_valid($my_rating) ? $my_rating : $common_rating;
            if (Ratings::rating_is_valid($rating)) {
                try {
                    send_event(new RatingSetEvent($event->image, $rating));
                } catch (RatingSetException $e) {
                    if ($e->redirect) {
                        $page->flash("{$e->getMessage()}, please see {$e->redirect}");
                    } else {
                        $page->flash($e->getMessage());
                    }
                    throw $e;
                }
            }
        }
    }

    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        if(!is_null($event->image['rating'])) {
            $event->replace('$rating', $this->rating_to_human($event->image['rating']));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $ratings = self::get_sorted_ratings();
            $event->add_block(new Block("Ratings", $this->theme->get_help_html($ratings)));
        }
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $user;

        $matches = [];
        if (is_null($event->term) && $this->no_rating_query($event->context)) {
            $set = Ratings::privs_to_sql(Ratings::get_user_default_ratings());
            $event->add_querylet(new Querylet("rating IN ($set)"));
        }

        if (is_null($event->term)) {
            return;
        }

        if (preg_match($this->search_regexp, strtolower($event->term), $matches)) {
            $ratings = $matches[1] ? $matches[1] : $matches[2][0];

            if (count($matches) > 2 && in_array($matches[2], self::UNRATED_KEYWORDS)) {
                $ratings = "?";
            }

            if ($ratings == '*') {
                $ratings = Ratings::get_user_class_privs($user);
            } else {
                $ratings = array_intersect(str_split($ratings), Ratings::get_user_class_privs($user));
            }

            $set = "'" . join("', '", $ratings) . "'";
            $event->add_querylet(new Querylet("rating IN ($set)"));
        }
    }

    public function onTagTermCheck(TagTermCheckEvent $event): void
    {
        if (preg_match($this->search_regexp, $event->term)) {
            $event->metatag = true;
        }
    }

    public function onTagTermParse(TagTermParseEvent $event): void
    {
        global $user;
        $matches = [];

        if (preg_match($this->search_regexp, strtolower($event->term), $matches)) {
            $ratings = $matches[1] ? $matches[1] : $matches[2][0];

            if (count($matches) > 2 && in_array($matches[2], self::UNRATED_KEYWORDS)) {
                $ratings = "?";
            }

            $ratings = array_intersect(str_split($ratings), Ratings::get_user_class_privs($user));
            $rating = $ratings[0];
            $image = Image::by_id_ex($event->image_id);
            send_event(new RatingSetEvent($image, $rating));
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        global $database;

        $results = $database->get_col("SELECT DISTINCT rating FROM images ORDER BY rating");
        $original_values = [];
        foreach ($results as $result) {
            assert(is_string($result));
            if (array_key_exists($result, ImageRating::$known_ratings)) {
                $original_values[$result] = ImageRating::$known_ratings[$result]->name;
            } else {
                $original_values[$result] = $result;
            }
        }

        $this->theme->display_form($original_values);
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database, $user;
        $action = $event->action;
        switch ($action) {
            case "update_ratings":
                $event->redirect = true;
                if (!array_key_exists("rating_old", $event->params) || empty($event->params["rating_old"])) {
                    return;
                }
                if (!array_key_exists("rating_new", $event->params) || empty($event->params["rating_new"])) {
                    return;
                }
                $old = $event->params["rating_old"];
                $new = $event->params["rating_new"];

                if ($user->can(Permissions::BULK_EDIT_IMAGE_RATING)) {
                    $database->execute("UPDATE images SET rating = :new WHERE rating = :old", ["new" => $new, "old" => $old ]);
                }

                break;
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::BULK_EDIT_IMAGE_RATING)) {
            $event->add_action("bulk_rate", "Set (R)ating", "r", "", (string)$this->theme->get_selection_rater_html(selected_options: ["?"]));
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_rate":
                if (!isset($event->params['rating'])) {
                    return;
                }
                if ($user->can(Permissions::BULK_EDIT_IMAGE_RATING)) {
                    $rating = $event->params['rating'];
                    $total = 0;
                    foreach ($event->items as $image) {
                        send_event(new RatingSetEvent($image, $rating));
                        $total++;
                    }
                    $page->flash("Rating set for $total items");
                }
                break;
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $user, $page;

        if ($event->page_matches("admin/bulk_rate", method: "POST", permission: Permissions::BULK_EDIT_IMAGE_RATING)) {
            $n = 0;
            while (true) {
                $images = Search::find_images($n, 100, Tag::explode($event->req_POST("query")));
                if (count($images) == 0) {
                    break;
                }

                reset($images); // rewind to first element in array.

                foreach ($images as $image) {
                    send_event(new RatingSetEvent($image, $event->req_POST('rating')));
                }
                $n += 100;
            }
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
    }

    public function onUploadHeaderBuilding(UploadHeaderBuildingEvent $event): void
    {
        $event->add_part("Rating");
    }

    public function onUploadSpecificBuilding(UploadSpecificBuildingEvent $event): void
    {
        $event->add_part($this->theme->get_upload_specific_rater_html($event->suffix));
    }

    /**
     * @return ImageRating[]
     */
    public static function get_sorted_ratings(): array
    {
        $ratings = array_values(ImageRating::$known_ratings);
        usort($ratings, function ($a, $b) {
            return $a->order <=> $b->order;
        });
        return $ratings;
    }

    /**
     * @param ImageRating[]|null $ratings
     * @return array<string, string>
     */
    public static function get_ratings_dict(array $ratings = null): array
    {
        if (!isset($ratings)) {
            $ratings = self::get_sorted_ratings();
        }
        return array_combine(
            array_map(function ($o) {
                return $o->code;
            }, $ratings),
            array_map(function ($o) {
                return $o->name;
            }, $ratings)
        );
    }

    /**
     * Figure out which ratings a user is allowed to see
     *
     * @return string[]
     */
    public static function get_user_class_privs(User $user): array
    {
        global $config;

        return $config->get_array("ext_rating_".$user->class->name."_privs");
    }

    /**
     * Figure out which ratings a user would like to see by default
     * (Which will be a subset of what they are allowed to see)
     *
     * @return string[]
     */
    public static function get_user_default_ratings(): array
    {
        global $user_config, $user;

        $available = self::get_user_class_privs($user);
        $selected = $user_config->get_array(RatingsConfig::USER_DEFAULTS);

        return array_intersect($available, $selected);
    }

    /**
     * @param string[] $privs
     */
    public static function privs_to_sql(array $privs): string
    {
        $arr = [];
        foreach ($privs as $i) {
            $arr[] = "'" . $i . "'";
        }
        if (sizeof($arr) == 0) {
            return "' '";
        }
        return join(', ', $arr);
    }

    public static function rating_to_human(string $rating): string
    {
        if (array_key_exists($rating, ImageRating::$known_ratings)) {
            return ImageRating::$known_ratings[$rating]->name;
        }
        return "Unknown";
    }

    public static function rating_is_valid(string $rating): bool
    {
        return in_array($rating, array_keys(ImageRating::$known_ratings));
    }

    /**
     * @param string[] $context
     */
    private function no_rating_query(array $context): bool
    {
        foreach ($context as $term) {
            if (preg_match("/^rating[=|:]/", $term)) {
                return false;
            }
        }
        return true;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database, $config;

        if ($this->get_version(RatingsConfig::VERSION) < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN rating CHAR(1) NOT NULL DEFAULT '?'");
            $database->execute("CREATE INDEX images__rating ON images(rating)");
            $this->set_version(RatingsConfig::VERSION, 3);
        }

        if ($this->get_version(RatingsConfig::VERSION) < 2) {
            $database->execute("CREATE INDEX images__rating ON images(rating)");
            $this->set_version(RatingsConfig::VERSION, 2);
        }

        if ($this->get_version(RatingsConfig::VERSION) < 3) {
            $database->execute("UPDATE images SET rating = 'u' WHERE rating is null");
            switch ($database->get_driver_id()) {
                case DatabaseDriverID::MYSQL:
                    $database->execute("ALTER TABLE images CHANGE rating rating CHAR(1) NOT NULL DEFAULT 'u'");
                    break;
                case DatabaseDriverID::PGSQL:
                    $database->execute("ALTER TABLE images ALTER COLUMN rating SET DEFAULT 'u'");
                    $database->execute("ALTER TABLE images ALTER COLUMN rating SET NOT NULL");
                    break;
            }
            $this->set_version(RatingsConfig::VERSION, 3);
        }

        if ($this->get_version(RatingsConfig::VERSION) < 4) {
            $value = $config->get_string("ext_rating_anon_privs");
            if (!empty($value)) {
                $config->set_array("ext_rating_anonymous_privs", str_split($value));
            }
            $value = $config->get_string("ext_rating_user_privs");
            if (!empty($value)) {
                $config->set_array("ext_rating_user_privs", str_split($value));
            }
            $value = $config->get_string("ext_rating_admin_privs");
            if (!empty($value)) {
                $config->set_array("ext_rating_admin_privs", str_split($value));
            }

            switch ($database->get_driver_id()) {
                case DatabaseDriverID::MYSQL:
                    $database->execute("ALTER TABLE images CHANGE rating rating CHAR(1) NOT NULL DEFAULT '?'");
                    break;
                case DatabaseDriverID::PGSQL:
                    $database->execute("ALTER TABLE images ALTER COLUMN rating SET DEFAULT '?'");
                    break;
            }

            $database->set_timeout(null); // These updates can take a little bit

            $database->execute("UPDATE images SET rating = :new WHERE rating = :old", ["new" => '?', "old" => 'u' ]);

            $this->set_version(RatingsConfig::VERSION, 4);
        }
    }

    private function set_rating(int $image_id, string $rating, string $old_rating): void
    {
        global $database;
        if ($old_rating != $rating) {
            $database->execute("UPDATE images SET rating=:rating WHERE id=:id", ['rating' => $rating, 'id' => $image_id]);
            log_info("rating", "Rating for >>{$image_id} set to: ".$this->rating_to_human($rating));
        }
    }
}
