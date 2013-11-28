<?php
/*
 * Name: Ouroboros API
 * Author: Diftraku <diftraku[at]derpy.me>
 * Description: Ouroboros-like API for Shimmie
 * Documentation:
 *   Currently working features
 *   <ul>
 *     <li>Post:
 *       <ul>
 *         <li>Index/List</li>
 *         <li>Show</li>
 *       </ul>
 *     </li>
 *     <li>Tag:
 *       <ul>
 *         <li>Index/List</li>
 *       </ul>
 *     </li>
 *   </ul>
 *   Tested to work with CartonBox using "Danbooru 1.18.x" as site type.
 *   Does not work with Andbooru or Danbooru Gallery for reasons beyond me, took me a while to figure rating "u" is bad...
 *   Lots of Ouroboros/Danbooru specific values use their defaults (or what I gathered them to be default)
 *   and tons of stuff not supported directly in Shimmie is botched to work
 */


class _SafeOuroborosImage
{
    /**
     * Author
     */

    /**
     * Post author
     * @var string
     */
    public $author = '';
    /**
     * Post author user ID
     * @var integer
     */
    public $creator_id = null;

    /**
     * Image
     */

    /**
     * Image height
     * @var integer
     */
    public $height = null;
    /**
     * Image width
     * @var integer
     */
    public $width = null;
    /**
     * File Size in bytes
     * @var integer
     */
    public $file_size = null;
    /**
     * URL to the static file
     * @var string
     */
    public $file_url = '';
    /**
     * File MD5 hash
     * @var string
     */
    public $md5 = '';

    /**
     * Post Meta
     */

    /**
     * (Unknown) Change
     * @var integer
     */
    public $change = null;
    /**
     * Timestamp for post creation
     * @var integer
     */
    public $created_at = null;
    /**
     * Post ID
     * @var integer
     */
    public $id = null;
    /**
     * Parent post ID
     * @var integer
     */
    public $parent_id = null;
    /**
     * Post content rating
     * @var string
     */
    public $rating = 'q';
    /**
     * Post score
     * @var integer
     */
    public $score = 1;
    /**
     * Post source
     * @var string
     */
    public $source = '';
    /**
     * Post status
     * @var string
     */
    public $status = '';
    /**
     * Post tags
     * @var string
     */
    public $tags = '';
    /**
     * Flag if the post has child posts
     * @var bool
     */
    public $has_children = false;
    /**
     * Flag if the post has comments
     * @var bool
     */
    public $has_comments = false;
    /**
     * Flag if the post has notes
     * @var bool
     */
    public $has_notes = false;
    /**
     * Post description
     * @var string
     */
    public $description = '';

    /**
     * Thumbnail
     */

    /**
     * Thumbnail Height
     * @var integer
     */
    public $preview_height = null;
    /**
     * Thumbnail URL
     * @var string
     */
    public $preview_url = '';
    /**
     * Thumbnail Width
     * @var integer
     */
    public $preview_width = null;

    /**
     * Downscaled Image
     */

    /**
     * Downscaled image height
     * @var integer
     */
    public $sample_height = null;
    /**
     * Downscaled image
     * @var string
     */
    public $sample_url = '';
    /**
     * Downscaled image
     * @var integer
     */
    public $sample_width = null;

    /**
     * Constructor
     * @param Image $img
     */
    function __construct(Image $img)
    {
        global $config;
        // author
        $author = $img->get_owner();
        $this->author = $author->name;
        $this->creator_id = intval($author->id);

        // file
        $this->height = intval($img->height);
        $this->width = intval($img->width);
        $this->file_ext = $img->ext;
        $this->file_size = intval($img->filesize);
        $this->file_url = make_http($img->get_image_link());
        $this->md5 = $img->hash;

        // meta
        $this->change = intval($img->id); //DaFug is this even supposed to do? ChangeID?
        // Should be JSON specific, just strip this when converting to XML
        $this->created_at = array('n' => 123456789, 's' => $img->posted_timestamp, 'json_class' => 'Time');
        $this->id = intval($img->id);
        $this->parent_id = null;
        if (defined('ENABLED_EXTS')) {
            if (strstr(ENABLED_EXTS, 'rating') !== false) {
                //$this->rating = $img->rating;
            }
            if (strstr(ENABLED_EXTS, 'numeric_score') !== false) {
                $this->score = $img->numeric_score;
            }
        }
        $this->source = $img->source;
        $this->status = 'active'; //not supported in Shimmie... yet
        $this->tags = $img->get_tag_list();
        $this->has_children = false;
        $this->has_comments = false;
        $this->has_notes = false;

        // thumb
        $this->preview_height = $config->get_int('thumb_height');
        $this->preview_width = $config->get_int('thumb_width');
        $this->preview_url = make_http($img->get_thumb_link());

        // sample (use the full image here)
        $this->sample_height = intval($img->height);
        $this->sample_width = intval($img->width);
        $this->sample_url = make_http($img->get_image_link());
    }
}
class _SafeOuroborosTag
{
    public $ambiguous = false;
    public $count = 0;
    public $id = 0;
    public $name = '';
    public $type = 0;

    function __construct(array $tag)
    {
        $this->count = $tag['count'];
        $this->id = $tag['id'];
        $this->name = $tag['tag'];
    }
}
class OuroborosAPI extends Extension
{
    private $event;
    const ERROR_HTTP_200 = 'Request was successful';
    const ERROR_HTTP_403 = 'Access denied';
    const ERROR_HTTP_404 = 'Not found';
    const ERROR_HTTP_420 = 'Record could not be saved';
    const ERROR_HTTP_421 = 'User is throttled, try again later';
    const ERROR_HTTP_422 = 'The resource is locked and cannot be modified';
    const ERROR_HTTP_423 = 'Resource already exists';
    const ERROR_HTTP_424 = 'The given parameters were invalid';
    const ERROR_HTTP_500 = 'Some unknown error occurred on the server';
    const ERROR_HTTP_503 = 'Server cannot currently handle the request, try again later';

    const ERROR_POST_CREATE_MD5 = 'MD5 mismatch';
    const ERROR_POST_CREATE_DUPE = 'Duplicate';

    public function onPageRequest(PageRequestEvent $event)
    {
        global $database, $page, $config, $user;

        if (preg_match("%\.(xml|json)$%", implode('/', $event->args), $matches) === 1) {
            $this->event = $event;
            $type = $matches[1];
            if ($type == 'json') {
                $page->set_type('application/json; charset=utf-8');
            }
            elseif ($type == 'xml') {
                $page->set_type('text/xml');
            }
            $page->set_mode('data');

            if ($event->page_matches('post')) {
                if ($this->match('create')) {
                    // Create
                    $post = array(
                        'tags' => !empty($_REQUEST['post']['tags']) ? filter_var($_REQUEST['post']['tags'], FILTER_SANITIZE_STRING) : 'tagme',
                        'file' => !empty($_REQUEST['post']['file']) ? filter_var($_REQUEST['post']['file'], FILTER_UNSAFE_RAW) : null,
                        'rating' => !empty($_REQUEST['post']['rating']) ? filter_var($_REQUEST['post']['rating'], FILTER_SANITIZE_NUMBER_INT) : null,
                        'source' => !empty($_REQUEST['post']['source']) ? filter_var($_REQUEST['post']['source'], FILTER_SANITIZE_URL) : null,
                        'sourceurl' => !empty($_REQUEST['post']['sourceurl']) ? filter_var($_REQUEST['post']['sourceurl'], FILTER_SANITIZE_URL) : '',
                        'description' => !empty($_REQUEST['post']['description']) ? filter_var($_REQUEST['post']['description'], FILTER_SANITIZE_STRING) : '',
                        'is_rating_locked' => !empty($_REQUEST['post']['is_rating_locked']) ? filter_var($_REQUEST['post']['is_rating_locked'], FILTER_SANITIZE_NUMBER_INT) : false,
                        'is_note_locked' => !empty($_REQUEST['post']['is_note_locked']) ? filter_var($_REQUEST['post']['is_note_locked'], FILTER_SANITIZE_NUMBER_INT) : false,
                        'parent_id' => !empty($_REQUEST['post']['parent_id']) ? filter_var($_REQUEST['post']['parent_id'], FILTER_SANITIZE_NUMBER_INT) : null,
                    );
                    $md5 = !empty($_REQUEST['md5']) ? filter_var($_REQUEST['md5'], FILTER_SANITIZE_STRING) : null;

                }
                elseif ($this->match('update')) {
                    // Update
                }
                elseif ($this->match('show')) {
                    // Show
                    if (isset($_REQUEST['id'])) {
                        $id = $_REQUEST['id'];
                        $posts = array();
                        $posts[] = new _SafeOuroborosImage(Image::by_id($id));
                        $page->set_data(json_encode($posts));
                    }
                    else {
                        $page->set_data(json_encode(array()));
                    }
                }
                elseif ($this->match('index') || $this->match('list')) {
                    // List
                    $limit = !empty($_REQUEST['limit']) ? intval(filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT)) : 45;
                    $p = !empty($_REQUEST['page']) ? intval(filter_var($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
                    $tags = !empty($_REQUEST['tags']) ? filter_var($_REQUEST['tags'], FILTER_SANITIZE_STRING) : array();
                    if (!empty($tags)) {
                        $tags = Tag::explode($tags);
                    }
                    $start = ( $p - 1 ) * $limit;
                    //var_dump($limit, $p, $tags, $start);die();
                    $results = Image::find_images(max($start, 0), min($limit, 100), $tags);
                    $posts = array();
                    foreach ($results as $img) {
                        if (!is_object($img)) {
                            continue;
                        }
                        $posts[] = new _SafeOuroborosImage($img);
                    }
                    $page->set_data(json_encode($posts));
                }
            }
            elseif ($event->page_matches('tag')) {
                if ($this->match('index') || $this->match('list')) {
                    $limit = !empty($_REQUEST['limit']) ? intval(filter_var($_REQUEST['limit'], FILTER_SANITIZE_NUMBER_INT)) : 50;
                    $p = !empty($_REQUEST['page']) ? intval(filter_var($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
                    $order = (!empty($_REQUEST['order']) && ($_REQUEST['order'] == 'date' || $_REQUEST['order'] == 'count' || $_REQUEST['order'] == 'name')) ? filter_var($_REQUEST['order'], FILTER_SANITIZE_STRING) : 'date';
                    $id = !empty($_REQUEST['id']) ? intval(filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT)) : null;
                    $after_id = !empty($_REQUEST['after_id']) ? intval(filter_var($_REQUEST['after_id'], FILTER_SANITIZE_NUMBER_INT)) : null;
                    $name = !empty($_REQUEST['name']) ? filter_var($_REQUEST['name'], FILTER_SANITIZE_STRING) : '';
                    $name_pattern = !empty($_REQUEST['name_pattern']) ? filter_var($_REQUEST['name_pattern'], FILTER_SANITIZE_STRING) : '';
                    $start = ( $p - 1 ) * $limit;
                    $tag_data = array();
                    switch ($order) {
                        case 'name':
                            $tag_data = $database->get_col($database->scoreql_to_sql("
                                SELECT DISTINCT
                                    id, SCORE_STRNORM(substr(tag, 1, 1)), count
                                FROM tags
                                WHERE count >= :tags_min
                                ORDER BY SCORE_STRNORM(substr(tag, 1, 1)) LIMIT :start, :max_items
                            "), array("tags_min" => $config->get_int('tags_min'), 'start' => $start, 'max_items' => $limit));
                            break;
                        case 'count':
                            $tag_data = $database->get_all("
                                SELECT id, tag, count
                                FROM tags
                                WHERE count >= :tags_min
                                ORDER BY count DESC, tag ASC LIMIT :start, :max_items
                                ", array("tags_min" => $config->get_int('tags_min'), 'start' => $start, 'max_items' => $limit));
                            break;
                        case 'date':
                            $tag_data = $database->get_all("
                                SELECT id, tag, count
                                FROM tags
                                WHERE count >= :tags_min
                                ORDER BY count DESC, tag ASC LIMIT :start, :max_items
                                ", array("tags_min" => $config->get_int('tags_min'), 'start' => $start, 'max_items' => $limit));
                            break;
                    }
                    $tags = array();
                    foreach ($tag_data as $tag) {
                        if (!is_array($tag)) {
                            continue;
                        }
                        $tags[] = new _SafeOuroborosTag($tag);
                    }
                    $page->set_data(json_encode($tags));
                }
            }
        }
    }

    private function match($page) {
        return (preg_match("%{$page}\.(xml|json)$%", implode('/', $this->event->args), $matches) === 1);
    }
}
