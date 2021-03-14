<?php declare(strict_types=1);

class _SafeImage
{
    public int $id;
    public int $height;
    public int $width;
    public string $hash;
    public int $filesize;
    public string $ext;
    public ?string $mime;
    public int $posted;
    public ?string $source;
    public int $owner_id;
    public array $tags;

    public function __construct(Image $img)
    {
        $_id = $img->id;
        assert($_id != null);
        $this->id       = $_id;
        $this->height   = $img->height;
        $this->width    = $img->width;
        $this->hash     = $img->hash;
        $this->filesize = $img->filesize;
        $this->ext      = $img->get_ext();
        $this->mime     = $img->get_mime();
        $this->posted   = strtotime($img->posted);
        $this->source   = $img->source;
        $this->owner_id = $img->owner_id;
        $this->tags     = $img->get_tag_array();
    }
}

class ShimmieApi extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("api/shimmie")) {
            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::TEXT);

            if ($event->page_matches("api/shimmie/get_tags")) {
                if ($event->count_args() > 0) {
                    $tag = $event->get_arg(0);
                } elseif (isset($_GET['tag'])) {
                    $tag = $_GET['tag'];
                } else {
                    $tag = null;
                }
                $res = $this->api_get_tags($tag);
                $page->set_data(json_encode($res));
            } elseif ($event->page_matches("api/shimmie/get_image")) {
                $arg = $event->get_arg(0);
                if (empty($arg) && isset($_GET['id'])) {
                    $arg = $_GET['id'];
                }
                $image = Image::by_id(int_escape($arg));
                // FIXME: handle null image
                $image->get_tag_array(); // tag data isn't loaded into the object until necessary
                $safe_image = new _SafeImage($image);
                $page->set_data(json_encode($safe_image));
            } elseif ($event->page_matches("api/shimmie/find_images")) {
                $search_terms = $event->get_search_terms();
                $page_number = $event->get_page_number();
                $page_size = $event->get_page_size();
                $images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
                $safe_images = [];
                foreach ($images as $image) {
                    $image->get_tag_array();
                    $safe_images[] = new _SafeImage($image);
                }
                $page->set_data(json_encode($safe_images));
            } elseif ($event->page_matches("api/shimmie/get_user")) {
                $query = $user->id;
                $type = "id";
                if ($event->count_args() == 1) {
                    $query = $event->get_arg(0);
                    $type = "name";
                } elseif (isset($_GET['id'])) {
                    $query = $_GET['id'];
                } elseif (isset($_GET['name'])) {
                    $query = $_GET['name'];
                    $type = "name";
                }

                $all = $this->api_get_user($type, $query);
                $page->set_data(json_encode($all));
            } else {
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("ext_doc/shimmie_api"));
            }
        }
    }

    /**
     * #return string[]
     */
    private function api_get_tags(?string $arg): array
    {
        global $database;
        if (!empty($arg)) {
            $all = $database->get_all("SELECT tag FROM tags WHERE tag LIKE :tag", ['tag'=>$arg . "%"]);
        } else {
            $all = $database->get_all("SELECT tag FROM tags");
        }
        $res = [];
        foreach ($all as $row) {
            $res[] = $row["tag"];
        }
        return $res;
    }

    private function api_get_user(string $type, string $query): array
    {
        global $database;
        $all = $database->get_row(
            "SELECT id, name, joindate, class FROM users WHERE $type=:query",
            ['query'=>$query]
        );

        if (!empty($all)) {
            //FIXME?: For some weird reason, get_all seems to return twice. Unsetting second value to make things look nice..
            // - it returns data as eg  array(0=>1234, 'id'=>1234, 1=>'bob', 'name'=>bob, ...);
            for ($i = 0; $i < 4; $i++) {
                unset($all[$i]);
            }
            $all['uploadcount'] = Image::count_images(["user_id=" . $all['id']]);
            $all['commentcount'] = $database->get_one(
                "SELECT COUNT(*) AS count FROM comments WHERE owner_id=:owner_id",
                ["owner_id" => $all['id']]
            );

            if (isset($_GET['recent'])) {
                $recents = $database->get_all(
                    "SELECT * FROM images WHERE owner_id=:owner_id ORDER BY id DESC LIMIT 0, 5",
                    ['owner_id'=>$all['id']]
                );

                $i = 0;
                foreach ($recents as $recent) {
                    $all['recentposts'][$i] = $recent;
                    unset($all['recentposts'][$i]['owner_id']); //We already know the owners id..
                    unset($all['recentposts'][$i]['owner_ip']);

                    for ($x = 0; $x < 14; $x++) {
                        unset($all['recentposts'][$i][$x]);
                    }
                    if (empty($all['recentposts'][$i]['author'])) {
                        unset($all['recentposts'][$i]['author']);
                    }
                    if ($all['recentposts'][$i]['notes'] > 0) {
                        $all['recentposts'][$i]['has_notes'] = "Y";
                    } else {
                        $all['recentposts'][$i]['has_notes'] = "N";
                    }
                    unset($all['recentposts'][$i]['notes']);
                    $i += 1;
                }
            }
        }
        return $all;
    }
}
