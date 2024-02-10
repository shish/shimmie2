<?php

declare(strict_types=1);

namespace Shimmie2;

class IndexInfo extends ExtensionInfo
{
    public const KEY = "index";

    public string $key = self::KEY;
    public string $name = "Post List";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Show a list of uploaded posts";
    public bool $core = true;
    public ?string $documentation = " etc.
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
