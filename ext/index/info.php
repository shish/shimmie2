<?php declare(strict_types=1);

class IndexInfo extends ExtensionInfo
{
    public const KEY = "index";

    public string $key = self::KEY;
    public string $name = "Post List";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Show a list of uploaded posts";
    public bool $core = true;
    public ?string $documentation = "Here is a list of the search methods available out of the box;
Shimmie extensions may provide other filters:
<ul>
  <li>by tag, eg
    <ul>
      <li>cat
      <li>pie
      <li>somethi* -- wildcards are supported
    </ul>
  </li>
  <li>size (=, &lt;, &gt;, &lt;=, &gt;=) width x height, eg
    <ul>
      <li>size=1024x768 -- a specific wallpaper size
      <li>size&gt;=500x500 -- no small posts
      <li>size&lt;1000x1000 -- no large posts
    </ul>
  </li>
  <li>width (=, &lt;, &gt;, &lt;=, &gt;=) width, eg
    <ul>
      <li>width=1024 -- find posts with 1024 width
      <li>width>2000 -- find posts bigger than 2000 width
    </ul>
  </li>
  <li>height (=, &lt;, &gt;, &lt;=, &gt;=) height, eg
    <ul>
      <li>height=768 -- find posts with 768 height
      <li>height>1000 -- find posts bigger than 1000 height
    </ul>
  </li>
  <li>ratio (=, &lt;, &gt;, &lt;=, &gt;=) width : height, eg
    <ul>
      <li>ratio=4:3, ratio=16:9 -- standard wallpaper
      <li>ratio=1:1 -- square posts
      <li>ratio<1:1 -- tall posts
      <li>ratio>1:1 -- wide posts
    </ul>
  </li>
  <li>filesize (=, &lt;, &gt;, &lt;=, &gt;=) size, eg
    <ul>
      <li>filesize&gt;1024 -- no posts under 1KB
      <li>filesize&lt=3MB -- shorthand filesizes are supported too
    </ul>
  </li>
  <li>id (=, &lt;, &gt;, &lt;=, &gt;=) number, eg
    <ul>
      <li>id<20 -- search only the first few posts
      <li>id>=500 -- search later posts
    </ul>
  </li>
  <li>user=Username & poster=Username, eg
    <ul>
      <li>user=Shish -- find all of Shish's posts
      <li>poster=Shish -- same as above
    </ul>
  </li>
  <li>user_id=userID & poster_id=userID, eg
    <ul>
      <li>user_id=2 -- find all posts by user id 2
      <li>poster_id=2 -- same as above
    </ul>
  </li>
  <li>hash=md5sum & md5=md5sum, eg
    <ul>
      <li>hash=bf5b59173f16b6937a4021713dbfaa72 -- find the \"Taiga want up!\" post
      <li>md5=bf5b59173f16b6937a4021713dbfaa72 -- same as above
    </ul>
  </li>
  <li>filename=blah & name=blah, eg
    <ul>
      <li>filename=kitten -- find all posts with \"kitten\" in the original filename
      <li>name=kitten -- same as above
    </ul>
  </li>
  <li>posted (=, &lt;, &gt;, &lt;=, &gt;=) date, eg
    <ul>
      <li>posted&gt;=2009-12-25 posted&lt;=2010-01-01 -- find posts posted between christmas and new year
    </ul>
  </li>
  <li>tags (=, &lt;, &gt;, &lt;=, &gt;=) count, eg
    <ul>
      <li>tags=1 -- search for posts with only 1 tag
      <li>tags>=10 -- search for posts with 10 or more tags
      <li>tags<25 -- search for posts with less than 25 tags
    </ul>
  </li>
  <li>source=(URL, any, none) eg
    <ul>
      <li>source=http://example.com -- find all posts with \"http://example.com\" in the source
      <li>source=any -- find all posts with a source
      <li>source=none -- find all posts without a source
    </ul>
  </li>
  <li>order=(id, width, height, filesize, filename)_(ASC, DESC), eg
    <ul>
      <li>order=width -- find all posts sorted from highest > lowest width
      <li>order=filesize_asc -- find all posts sorted from lowest > highest filesize
    </ul>
  </li>
  <li>order=random_####, eg
    <ul>
      <li>order=random_8547 -- find all posts sorted randomly using 8547 as a seed
    </ul>
  </li>
</ul>
<p>Search items can be combined to search for posts which match both,
or you can stick \"-\" in front of an item to search for things that don't
match it.
<p>Metatags can be followed by \":\" rather than \"=\" if you prefer.
<br />I.E: \"posted:2014-01-01\", \"id:>=500\" etc.
<p>Some search methods provided by extensions:
<ul>
  <li>Numeric Score
    <ul>
      <li>score (=, &lt;, &gt;, &lt;=, &gt;=) number -- seach by score
      <li>upvoted_by=Username -- search for a user's likes
      <li>downvoted_by=Username -- search for a user's dislikes
      <li>upvoted_by_id=UserID -- search for a user's likes by user ID
      <li>downvoted_by_id=UserID -- search for a user's dislikes by user ID
      <li>order=score_(ASC, DESC) -- find all posts sorted from by score
    </ul>
  <li>Post Rating
    <ul>
      <li>rating=se -- find safe and explicit posts, ignore questionable and unknown
    </ul>
  <li>Favorites
    <ul>
      <li>favorites (=, &lt;, &gt;, &lt;=, &gt;=) number -- search for posts favourited a certain number of times
      <li>favourited_by=Username -- search for a user's choices by username
      <li>favorited_by_userno=UserID -- search for a user's choice by userID
    </ul>
  <li>Notes
    <ul>
      <li>notes (=, &lt;, &gt;, &lt;=, &gt;=) number -- search by the number of notes a post has
      <li>notes_by=Username -- search for posts containing notes created by username
      <li>notes_by_userno=UserID -- search for posts containing notes created by userID
    </ul>
  <li>Artists
    <ul>
      <li>author=ArtistName -- search for posts by artist
    </ul>
  <li>Post Comments
    <ul>
      <li>comments (=, &lt;, &gt;, &lt;=, &gt;=) number -- search for posts by number of comments
      <li>commented_by=Username -- search for posts containing user's comments by username
      <li>commented_by_userno=UserID -- search for posts containing user's comments by userID
    </ul>
  <li>Pools
    <ul>
      <li>pool=(PoolID, any, none) -- search for posts in a pool by PoolID.
      <li>pool_by_name=PoolName -- search for posts in a pool by PoolName. underscores are replaced with spaces
    </ul>
  <li>Post Relationships
    <ul>
      <li>parent=(parentID, any, none) -- search for posts by parentID / if they have, do not have a parent
      <li>child=(any, none) -- search for posts which have, or do not have children
    </ul>
</ul>
";
}
