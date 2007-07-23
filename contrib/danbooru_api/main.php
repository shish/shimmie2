<?php
/*
Danbooru Client API for Shimmie2
Description: Provides simple interfaces for third party software to interact with Shimmie via
simple HTTP GET/POST requests.

Author: JJS <jsutinen@gmail.com>
Notes:
danbooru API based on documentation from danbooru 1.0 - http://attachr.com/7569
I've only been able to test add_post and find_tags because I use the old danbooru firefox extension for firefox 1.5

Functions currently implemented:
add_comment - NOT DONE YET, waiting on some backend shimmie code :)
add_post - title and rating are currently ignored because shimmie does not support them
find_posts - sort of works, filename is returned as the original filename and probably won't help when it comes to actually downloading it
find_tags - id, name, and after_id all work but the tags parameter is ignored just like danbooru 1.0 ignores it

*/

class DanbooruApi extends Extension 
{
	// Receive the event
	public function receive_event($event) 
	{
		// Check if someone is accessing /api/danbooru (us)
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "api") && ($event->get_arg(0) == 'danbooru')) 
		{
			// execute the danbooru processing code
			$this->api_danbooru($event);
		}
	}
	
	// Danbooru API
	private function api_danbooru($event)
	{
		global $page;
		global $config;
		global $database;
		global $user;
		$page->set_mode("data");
		$page->set_type("application/xml");
		//debug
		//$page->set_type("text/plain");
		
		$results = array();
		
		/*
		add_comment() 
		Adds a comment to a post. 
		Parameters 
		* body: the body of the comment 
		* post_id: the post id 
		* login: your login 
		* password: your password Response 
		* 200: success 
		* 500: error. response body will the the error message.
		*/
		if($event->get_arg(1) == 'add_comment')
		{
			// On error the response body is the error message so plain text is fine
			$page->set_type("text/plain");
			// We do wish to auth the user if possible, if it fails treat as anonymous
			$this->authenticate_user();
			// Check if anonymous commenting is allowed before proceeding
			if($config->get_bool("comment_anon") || !$user->is_anonymous())
			{
				// Did the user supply a post_id and a comment body?
				if(isset($_REQUEST['post_id']) && isset($_REQUEST['body']) && trim($_REQUEST['body']) != "")
				{
					// waiting for someone to write an event handler for the comments extension :)
				} else 
				{
					// User didn't supply necessary parameters, tell them that
					header("HTTP/1.0 500 Internal Server Error");
					$page->set_data("You forgot to supply either a post id or the body of your comment");
				}
			} else 
			{
				header("HTTP/1.0 500 Internal Server Error");
				$page->set_data("You supplied an invalid login or password or anonymous commenting is currently disabled");
			}
		}
		
		/*
		add_post()
		Adds a post to the database. 
		Parameters 
		* login: login
		* password: password 
		* file: file as a multipart form 
		* source: source url 
		* title: title **IGNORED**
		* tags: list of tags as a string, delimited by whitespace 
		* md5: MD5 hash of upload in hexadecimal format 
		* rating: rating of the post. can be explicit, questionable, or safe. **IGNORED**
		Notes 
		* The only necessary parameter is tags and either file or source. 
		* If you want to sign your post, you need a way to authenticate your account, either by supplying login and password, or by supplying a cookie. 
		* If an account is not supplied or if it doesn‘t authenticate, he post will be added anonymously. 
		* If the md5 parameter is supplied and does not match the hash of what‘s on the server, the post is rejected. 
		Response 
		The response depends on the method used: 
		Post 
		* X-Danbooru-Location set to the URL for newly uploaded post. 
		Get 
		* Redirected to the newly uploaded post.
		*/
		if($event->get_arg(1) == 'add_post')
		{
			// No XML data is returned from this function
			$page->set_type("text/plain");
			// Check first if a login was supplied, if it wasn't check if the user is logged in via cookie
			// If all that fails, it's an anonymous upload
			$this->authenticate_user();
			// Now we check if a file was uploaded or a url was provided to transload
			// Much of this code is borrowed from /ext/upload
			if($config->get_bool("upload_anon") || !$user->is_anonymous())
			{
				$file = null;
				$filename = "";
				$source = "";
				
				if(isset($_FILES['file']))
				{	// A file was POST'd in
					$file = $_FILES['file']['tmp_name'];
					$filename = $file['name'];
					// If both a file is posted and a source provided, I'm assuming source is the source of the file
					$source = isset($_REQUEST['source']) ? $_REQUEST['source'] : "";
				} elseif(isset($_REQUEST['source']))
				{	// A url was provided
					$url = $_REQUEST['source'];
					$source = $url;
					$tmp_filename = tempnam("/tmp", "shimmie_transload");
					
					// Are we using fopen wrappers or curl?
					if($config->get_string("transload_engine") == "fopen") 
					{
						$fp = fopen($url, "r");
						if(!$fp) {
							header("HTTP/1.0 409 Conflict");
							header("X-Danbooru-Errors: fopen read error");
						}
						
						$data = "";
						$length = 0;
						while(!feof($fp) && $length <= $config->get_int('upload_size')) 
						{
							$data .= fread($fp, 8192);
							$length = strlen($data);
						}
						fclose($fp);

						$fp = fopen($tmp_filename, "w");
						fwrite($fp, $data);
						fclose($fp);
					}

					if($config->get_string("transload_engine") == "curl") 
					{
						$ch = curl_init($url);
						$fp = fopen($tmp_filename, "w");

						curl_setopt($ch, CURLOPT_FILE, $fp);
						curl_setopt($ch, CURLOPT_HEADER, 0);

						curl_exec($ch);
						curl_close($ch);
						fclose($fp);
					}
					$file = $tmp_filename;
					$filename = basename($url);
				} else 
				{	// Nothing was specified at all
					header("HTTP/1.0 409 Conflict");
					header("X-Danbooru-Errors: no input files");
					return;
				}
				
				// Now that we have some sort of physical file, process it
				$image = new Image($file, $filename, $_REQUEST['tags'], $source);
				// This occurs if the uploaded file is not an image
				if(!$image->is_ok())
				{
					header("HTTP/1.0 409 Conflict");
					header("X-Danbooru-Errors: unknown");
					return;
				}
				// Was an md5 supplied? Does it match the image hash?
				if(isset($_REQUEST['md5']))
				{
					if($_REQUEST['md5'] != $image->hash)
					{
						header("HTTP/1.0 409 Conflict");
						header("X-Danbooru-Errors: md5 mismatch");
						return;
					}
				}
				// Is the image too large?
				if(filesize($file['tmp_name']) > $config->get_int('upload_size'))
				{
					header("HTTP/1.0 409 Conflict");
					header("X-Danbooru-Errors: too large");
					return;
				}
				// Does it exist already?
				$existing = $database->get_image_by_hash($image->hash);
				if(!is_null($existing)) {
					header("HTTP/1.0 409 Conflict");
					header("X-Danbooru-Errors: duplicate");
					$existinglink = "http://" . $_SERVER['HTTP_HOST'] . make_link("post/view/" . $existing->id);
					header("X-Danbooru-Location: $existinglink");
				}

				// Fire off an event which should process the new image and add it to the db
				$nevent = new UploadingImageEvent($image);
				send_event($nevent);
				// Did something screw up?
				if($event->vetoed) {
					header("X-Danbooru-Errors: $event->veto_reason");
					return;
				} else
				{	// If it went ok, grab the id for the newly uploaded image and pass it in the header
					$newimg = $database->get_image_by_hash($image->hash);
					$newid = make_link("post/view/" . $newimg->id);
					// Did we POST or GET this call?
					if($_SERVER['REQUEST_METHOD'] == 'POST')
					{
						header("X-Danbooru-Location: $newid");
					}
					else
					header("Location: $newid");
				}
			} else 
			{
				header("X-Danbooru-Errors: authentication error");
				return;
			}
		}
		
		/*
		find_posts() 
		Find all posts that match the search criteria. Posts will be ordered by id descending. 
		Parameters 
		* md5: md5 hash to search for (comma delimited) 
		* id: id to search for (comma delimited) 
		* tags: what tags to search for 
		* limit: limit 
		* offset: offset 
		* after_id: limit results to posts added after this id
		*/
		if($event->get_arg(1) == 'find_posts')
		{
			if(isset($_GET['md5']))
			{
				$md5list = explode(",",$_GET['md5']);
				foreach($md5list as $md5)
				{
					$results[] = $database->get_image_by_hash($md5);
				}
			} elseif(isset($_GET['id']))
			{
				$idlist = explode(",",$_GET['id']);
				foreach($idlist as $id)
				{
					$results[] = $database->get_image($id);
				}
			} else
			{
				$limit = isset($_GET['limit']) ? int_escape($_GET['limit']) : 100;
				$start = isset($_GET['offset']) ? int_escape($_GET['offset']) : 0;
				$tags = isset($_GET['tags']) ? tag_explode($_GET['tags']) : array();
				$results = $database->get_images($start,$limit,$tags);
			}
			
			// Now we have the array $results filled with Image objects
			// Let's display them
			$xml = "<posts>\n";
			foreach($results as $img)
			{
				// Sanity check to see if $img is really an image object
				// If it isn't (e.g. someone requested an invalid md5 or id), break out of the this
				if(!is_object($img))
					continue;
				$taglist = $img->get_tag_list();
				$owner = $img->get_owner();
				$xml .= "<post md5=\"$img->hash\" rating=\"Questionable\" date=\"$img->posted\" is_warehoused=\"false\" file_name=\"$img->filename\" tags=\"$taglist\" source=\"$img->source\" score=\"0\" id=\"$img->id\" author=\"$owner->name\"/>\n";
			}
			$xml .= "</posts>";
			$page->set_data($xml);
		}
		
		/*
		find_tags() Find all tags that match the search criteria. 
		Parameters 
		* id: A comma delimited list of tag id numbers. 
		* name: A comma delimited list of tag names. 
		* tags: any typical tag query. See Tag#parse_query for details. 
		* after_id: limit results to tags with an id number after after_id. Useful if you only want to refresh
		*/
		if($event->get_arg(1) == 'find_tags')
		{
			if(isset($_GET['id']))
			{
				$idlist = explode(",",$_GET['id']);
				foreach($idlist as $id)
				{
					$sqlresult = $database->execute("SELECT id,tag,count FROM tags WHERE id = ?", array($id));
					if(!$sqlresult->EOF)
					{
						$results[] = array($sqlresult->fields['count'], $sqlresult->fields['tag'], $sqlresult->fields['id']);
					}
				}
			} elseif(isset($_GET['name']))
			{
				$namelist = explode(",",$_GET['name']);
				foreach($namelist as $name)
				{
					$sqlresult = $database->execute("SELECT id,tag,count FROM tags WHERE tag = ?", array($name));
					if(!$sqlresult->EOF)
					{
						$results[] = array($sqlresult->fields['count'], $sqlresult->fields['tag'], $sqlresult->fields['id']);
					}
				}
			} 
			/* Currently disabled to maintain identical functionality to danbooru 1.0's own "broken" find_tags
			elseif(isset($_GET['tags']))
			{
				$start = isset($_GET['after_id']) ? int_escape($_GET['offset']) : 0;
				$tags = tag_explode($_GET['tags']);
				
			}
			*/
			else 
			{
				$start = isset($_GET['after_id']) ? int_escape($_GET['offset']) : 0;
				$sqlresult = $database->execute("SELECT id,tag,count FROM tags WHERE count > 0 AND id >= ? ORDER BY id DESC",array($start));
				while(!$sqlresult->EOF)
				{
					$results[] = array($sqlresult->fields['count'], $sqlresult->fields['tag'], $sqlresult->fields['id']);
					$sqlresult->MoveNext();
				}
			}
			
			// Tag results collected, build XML output
			$xml = "<tags>\n";
			foreach($results as $tag)
			{
				$xml .= "<tag type=\"0\" count=\"$tag[0]\" name=\"$tag[1]\" id=\"$tag[2]\"/>\n";
			}
			$xml .= "</tags>";
			$page->set_data($xml);
		}
	}
	
	// Turns out I use this a couple times so let's make it a utility function
	// Authenticates a user based on the contents of the login and password parameters
	// or makes them anonymous. Does not set any cookies or anything permanent.
	private function authenticate_user()
	{
		global $database;
		global $user;

		if(isset($_REQUEST['login']) && isset($_REQUEST['password']))
		{
			// Get this user from the db, if it fails the user becomes anonymous
			// Code borrowed from /ext/user
			$name = $_REQUEST['login'];
			$pass = $_REQUEST['password'];
			$hash = md5( strtolower($name) . $pass );
			$duser = $database->get_user_by_name_and_hash($name, $hash);
			if(!is_null($duser)) {
				$user = $duser;
			} else
			{
				$user = $database->get_user_by_id($config->get_int("anon_id", 0));
			}
		}
	}
}

add_event_listener(new DanbooruApi());
?>