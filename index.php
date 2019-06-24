<?php
/**
 * \mainpage Shimmie2 / SCore Documentation
 *
 * SCore is a framework designed for writing flexible, extendable applications.
 * Whereas most PHP apps are built monolithically, score's event-based nature
 * allows parts to be mixed and matched. For instance, the most famous
 * collection of score extensions is the Shimmie image board, which includes
 * user management, a wiki, a private messaging system, etc. But one could
 * easily remove the image board bits and simply have a wiki with users and
 * PMs; or one could replace it with a blog module; or one could have a blog
 * which links to images on an image board, with no wiki or messaging, and so
 * on and so on...
 *
 * Dijkstra will kill me for personifying my architecture, but I can't think
 * of a better way without going into all the little details.
 * There are a bunch of Extension subclasses, they talk to each other by sending
 * and receiving  Event subclasses. The primary driver for each conversation is the
 * initial PageRequestEvent. If an Extension wants to display something to the
 * user, it adds a block to the Page data store. Once the conversation is over, the Page is passed to the
 * current theme's Layout class which will tidy up the data and present it to
 * the user. To see this in a more practical sense, see \ref hello.
 *
 * To learn more about the architecture:
 *
 * \li \ref eande
 * \li \ref themes
 *
 * To learn more about practical development:
 *
 * \li \ref scglobals
 * \li \ref unittests
 *
 * \page scglobals SCore Globals
 *
 * There are four global variables which are pretty essential to most extensions:
 *
 * \li $config -- some variety of Config subclass
 * \li $database -- a Database object used to get raw SQL access
 * \li $page -- a Page to holds all the loose bits of extension output
 * \li $user -- the currently logged in User
 *
 * Each of these can be imported at the start of a function with eg "global $page, $user;"
 */

if (!file_exists("data/config/shimmie.conf.php")) {
    require_once "core/_install.php";
    exit;
}

if (file_exists("images") && !file_exists("data/images")) {
    die("As of Shimmie 2.7 images and thumbs should be moved to data/images and data/thumbs");
}

if (!file_exists("vendor/")) {
    //CHECK: Should we just point to install.php instead? Seems unsafe though.
    print <<<EOD
<!DOCTYPE html>
<html>
	<head>
		<title>Shimmie Error</title>
		<link rel="shortcut icon" href="ext/handle_static/static/favicon.ico">
		<link rel="stylesheet" href="lib/shimmie.css" type="text/css">
	</head>
	<body>
		<div id="installer">
			<h1>Install Error</h1>
			<h3>Warning: Composer vendor folder does not exist!</h3>
			<div class="container">
				<p>Shimmie is unable to find the composer vendor directory.<br>
				Have you followed the composer setup instructions found in the
				<a href="https://github.com/shish/shimmie2#installation-development">README</a>?</p>

				<p>If you are not intending to do any development with Shimmie,
				it is highly recommend you use one of the pre-packaged releases
				found on <a href="https://github.com/shish/shimmie2/releases">Github</a> instead.</p>
			</div>
		</div>
	</body>
</html>
EOD;
    http_response_code(500);
    exit;
}

try {
    require_once "core/_bootstrap.php";
    $_shm_ctx->log_start(@$_SERVER["REQUEST_URI"], true, true);

    // start the page generation waterfall
    $user = _get_user();
    if (PHP_SAPI === 'cli' || PHP_SAPI == 'phpdbg') {
        send_event(new CommandEvent($argv));
    } else {
        send_event(new PageRequestEvent(_get_query()));
        $page->display();
    }

    if($database->transaction===true) {
        $database->commit();
    }

    // saving cache data and profiling data to disk can happen later
    if (function_exists("fastcgi_finish_request")) {
        fastcgi_finish_request();
    }
    $_shm_ctx->log_endok();
} catch (Exception $e) {
    if ($database) {
        $database->rollback();
    }
    _fatal_error($e);
    $_shm_ctx->log_ender();
}
log_slow();
