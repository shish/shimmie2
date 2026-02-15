<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageRating
{
    /** @var array<string, ImageRating> */
    public static array $known_ratings = [];

    public function __construct(
        public string $code,
        public string $name,
        public string $search_term,
        public int $order
    ) {
        assert(strlen($code) === 1, "Rating code must be exactly one character");
    }
}

function add_rating(ImageRating $rating): void
{
    if ($rating->code === "?" && array_key_exists("?", ImageRating::$known_ratings)) {
        throw new \RuntimeException("? is a reserved rating code that cannot be overridden");
    }
    if ($rating->code !== "?" && in_array(strtolower($rating->search_term), Ratings::UNRATED_KEYWORDS)) {
        throw new \RuntimeException("$rating->search_term is a reserved search term");
    }
    ImageRating::$known_ratings[$rating->code] = $rating;
}

add_rating(new ImageRating("?", "Unrated", "unrated", 99999));
add_rating(new ImageRating("s", "Safe", "safe", 0));
add_rating(new ImageRating("q", "Questionable", "questionable", 500));
add_rating(new ImageRating("e", "Explicit", "explicit", 1000));
// @phpstan-ignore-next-line
@include_once "data/config/ratings.conf.php";

final class RatingSetException extends UserError
{
    public ?string $redirect;

    public function __construct(string $msg, ?string $redirect = null)
    {
        parent::__construct($msg);
        $this->redirect = $redirect;
    }
}

final class RatingSetEvent extends Event
{
    public function __construct(
        public Image $image,
        public string $rating
    ) {
        parent::__construct();
        assert(in_array($rating, array_keys(ImageRating::$known_ratings)));
    }
}

/** @extends Extension<RatingsTheme> */
final class Ratings extends Extension
{
    public const KEY = "rating";
    public const VERSION_KEY = "ext_ratings2_version";

    public const UNRATED_KEYWORDS = ["unknown", "unrated"];

    private string $search_regexp;

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        $codes = implode("", array_keys(ImageRating::$known_ratings));
        $search_terms = [];
        foreach (ImageRating::$known_ratings as $key => $rating) {
            $search_terms[] = $rating->search_term;
        }
        $this->search_regexp = "/^rating[=:](?:(\*|[" . $codes . "]+)|(" .
            implode("|", $search_terms) . "|".implode("|", self::UNRATED_KEYWORDS)."))$/iD";

        Image::$prop_types["rating"] = ImagePropType::STRING;
    }

    private function check_permissions(Image $image): bool
    {
        $user_view_level = Ratings::get_user_class_privs(Ctx::$user);
        if (!in_array($image['rating'], $user_view_level)) {
            return false;
        }
        return true;
    }

    #[EventListener]
    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        /**
         * Deny images upon insufficient permissions.
         **/
        if (!$this->check_permissions($event->image)) {
            throw new PermissionDenied("Access denied");
        }
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        /**
         * Deny images upon insufficient permissions.
         **/
        if (!$this->check_permissions($event->image)) {
            Ctx::$page->set_redirect(make_link());
        }
    }

    #[EventListener]
    public function onBulkExport(BulkExportEvent $event): void
    {
        $event->fields["rating"] = $event->image['rating'];
    }

    #[EventListener]
    public function onBulkImport(BulkImportEvent $event): void
    {
        if (property_exists($event->fields, 'rating')
            && $event->fields->rating !== null
            && Ratings::rating_is_valid($event->fields->rating)) {
            $this->set_rating($event->image->id, $event->fields->rating, "");
        }
    }

    #[EventListener]
    public function onRatingSet(RatingSetEvent $event): void
    {
        if (empty($event->image['rating'])) {
            $old_rating = "";
        } else {
            $old_rating = $event->image['rating'];
        }
        $this->set_rating($event->image->id, $event->rating, $old_rating);
    }

    #[EventListener]
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part(
            $this->theme->get_image_rater_html(
                $event->image->id,
                $event->image['rating'],
                Ctx::$user->can(RatingsPermission::EDIT_IMAGE_RATING)
            ),
            80
        );
    }

    #[EventListener]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        if (
            Ctx::$user->can(RatingsPermission::EDIT_IMAGE_RATING) && (
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
                        Ctx::$page->flash("{$e->getMessage()}, please see {$e->redirect}");
                    } else {
                        Ctx::$page->flash($e->getMessage());
                    }
                    throw $e;
                }
            }
        }
    }

    #[EventListener]
    public function onParseLinkTemplate(ParseLinkTemplateEvent $event): void
    {
        if (!is_null($event->image['rating'])) {
            $event->replace('$rating', self::rating_to_human($event->image['rating']));
        }
    }

    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $ratings = self::get_sorted_ratings();
            $event->add_section("Ratings", $this->theme->get_help_html($ratings));
        }
    }

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        $matches = [];
        if (is_null($event->term) && $this->no_rating_query($event->context)) {
            $set = Ratings::privs_to_sql(Ratings::get_user_default_ratings());
            $event->add_querylet(new Querylet("rating IN ($set)"));
        }

        if ($matches = $event->matches($this->search_regexp)) {
            $ratings = strtolower($matches[1] ? $matches[1] : $matches[2][0]);

            if (count($matches) > 2 && in_array(strtolower($matches[2]), self::UNRATED_KEYWORDS)) {
                $ratings = "?";
            }

            if ($ratings === '*') {
                $ratings = Ratings::get_user_class_privs(Ctx::$user);
            } else {
                $ratings = array_intersect(str_split($ratings), Ratings::get_user_class_privs(Ctx::$user));
            }

            $set = "'" . join("', '", $ratings) . "'";
            $event->add_querylet(new Querylet("rating IN ($set)"));
        }
    }

    #[EventListener]
    public function onTagTermCheck(TagTermCheckEvent $event): void
    {
        if ($event->matches($this->search_regexp)) {
            $event->metatag = true;
        }
    }

    #[EventListener]
    public function onTagTermParse(TagTermParseEvent $event): void
    {
        if ($matches = $event->matches($this->search_regexp)) {
            $ratings = strtolower($matches[1] ? $matches[1] : $matches[2][0]);

            if (count($matches) > 2 && in_array(strtolower($matches[2]), self::UNRATED_KEYWORDS)) {
                $ratings = "?";
            }

            $ratings = array_intersect(str_split($ratings), Ratings::get_user_class_privs(Ctx::$user));
            $rating = $ratings[0];
            $image = Image::by_id_ex($event->image_id);
            send_event(new RatingSetEvent($image, $rating));
        }
    }

    #[EventListener]
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

    #[EventListener]
    public function onAdminAction(AdminActionEvent $event): void
    {
        switch ($event->action) {
            case "update_ratings":
                $event->redirect = true;
                $old = $event->params["rating_old"] ?? null;
                $new = $event->params["rating_new"] ?? null;
                if (!$old || !$new) {
                    return;
                }

                if (Ctx::$user->can(RatingsPermission::BULK_EDIT_IMAGE_RATING)) {
                    Ctx::$database->execute("UPDATE images SET rating = :new WHERE rating = :old", ["new" => $new, "old" => $old ]);
                }

                break;
        }
    }

    #[EventListener]
    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        $event->add_action(
            "rate",
            "Set (R)ating",
            "r",
            "",
            $this->theme->get_selection_rater_html(selected_options: ["?"]),
            permission: RatingsPermission::BULK_EDIT_IMAGE_RATING
        );
    }

    #[EventListener]
    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "rate":
                if (!isset($event->params['rating'])) {
                    return;
                }
                if (Ctx::$user->can(RatingsPermission::BULK_EDIT_IMAGE_RATING)) {
                    $rating = $event->params['rating'];
                    $total = 0;
                    foreach ($event->items as $image) {
                        send_event(new RatingSetEvent($image, $rating));
                        $total++;
                    }
                    $event->log_action("Rating set for $total items");
                }
                break;
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("admin/bulk_rate", method: "POST", permission: RatingsPermission::BULK_EDIT_IMAGE_RATING)) {
            $n = 0;
            while (true) {
                $images = Search::find_images($n, 100, SearchTerm::explode($event->POST->req("query")));
                if (count($images) === 0) {
                    break;
                }

                reset($images); // rewind to first element in array.

                foreach ($images as $image) {
                    send_event(new RatingSetEvent($image, $event->POST->req('rating')));
                }
                $n += 100;
            }

            Ctx::$page->set_redirect(make_link());
        }
    }

    #[EventListener]
    public function onUploadHeaderBuilding(UploadHeaderBuildingEvent $event): void
    {
        $event->add_part("Rating");
    }

    #[EventListener]
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
    public static function get_ratings_dict(?array $ratings = null): array
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
        $val = Ctx::$config->get("ext_rating_".$user->class->name."_privs", ConfigType::ARRAY)
            ?? array_keys(ImageRating::$known_ratings);
        return $val;
    }

    /**
     * Figure out which ratings a user would like to see by default
     * (Which will be a subset of what they are allowed to see)
     *
     * @return string[]
     */
    public static function get_user_default_ratings(): array
    {
        $available = self::get_user_class_privs(Ctx::$user);
        $selected = Ctx::$user->get_config()->get(RatingsUserConfig::DEFAULTS) ?? $available;

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
        if (count($arr) === 0) {
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
            if (\Safe\preg_match("/^rating[=:]/", $term)) {
                return false;
            }
        }
        return true;
    }

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN rating CHAR(1) NOT NULL DEFAULT '?'");
            $database->execute("CREATE INDEX images__rating ON images(rating)");
            $this->set_version(3);
        }

        if ($this->get_version() < 2) {
            $database->execute("CREATE INDEX images__rating ON images(rating)");
            $this->set_version(2);
        }

        if ($this->get_version() < 3) {
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
            $this->set_version(3);
        }

        if ($this->get_version() < 4) {
            $value = Ctx::$config->get("ext_rating_anon_privs");
            if (is_string($value)) {
                Ctx::$config->set("ext_rating_anonymous_privs", str_split($value));
            }
            $value = Ctx::$config->get("ext_rating_user_privs");
            if (is_string($value)) {
                Ctx::$config->set("ext_rating_user_privs", str_split($value));
            }
            $value = Ctx::$config->get("ext_rating_admin_privs");
            if (is_string($value)) {
                Ctx::$config->set("ext_rating_admin_privs", str_split($value));
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

            $this->set_version(4);
        }
    }

    private function set_rating(int $image_id, string $rating, string $old_rating): void
    {
        global $database;
        if ($old_rating !== $rating) {
            $database->execute("UPDATE images SET rating=:rating WHERE id=:id", ['rating' => $rating, 'id' => $image_id]);
            Log::info("rating", "Rating for >>{$image_id} set to: ".self::rating_to_human($rating));
        }
    }
}
