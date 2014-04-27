<?php
	error_reporting(E_ALL);

	include '../php/filestorage.class.php';
	include '../preferences.php';
	include '../php/functions.php';
	include '../php/yshout.class.php';

	$html = '<div id="history-posts">';

	$admin = loggedIn();
	
	if (isset($_GET['log']))
		$log = $_GET['log'];
	
	if (isset($_POST['log']))
		$log = $_POST['log'];

	if (!isset($log))
		$log = 1;
		
	$ys = ys($log);
	$posts = $ys->posts();

	if (sizeof($posts) === 0)
		$html .= '
			<div id="ys-post-1" class="ys-post ys-first ys-admin-post">
				<span class="ys-post-timestamp">13:37</span>
				<span class="ys-post-nickname">Yurivish:<span>
				<span class="ys-post-message">Hey, there aren\'t any posts in this log.</span>
			</div>
		';

	$id = 0;

	foreach($posts as $post) {
		$id++;

		$banned = $ys->banned($post['adminInfo']['ip']);
		$html .= '<div ' . ($admin ? 'rel="' . $post['adminInfo']['ip'] . '" '  : '') . 'id="ys-post-' . $id . '" class="ys-post' . ($post['admin'] ? ' ys-admin-post' : '') . ($banned ? ' ys-banned-post' : '') . '">' . "\n";
		
			$ts = '';
			
			switch($prefs['timestamp']) {
				case 12:
					$ts = date('h:i', $post['timestamp']);
					break;
				case 24:
					$ts = date('H:i', $post['timestamp']);
					break;
				case 0:
					$ts = '';
					break;
			}

			$html .= '	<span class="ys-post-timestamp">' . $ts . '</span> ' . "\n";
			$html .= '	<span class="ys-post-nickname">' . $post['nickname'] . '</span>' . $prefs['nicknameSeparator'] . ' ' . "\n";
			$html .= '	<span class="ys-post-message">' . $post['message'] . '</span>' . "\n";
			$html .= '	<span class="ys-post-info' . ($prefs['info'] == 'overlay' ? ' ys-info-overlay' : ' ys-info-inline') . '">' . ($admin ? '<em>IP:</em> ' . $post['adminInfo']['ip'] . ', ' : '') . '<em>Posted:</em> ' . date('l M. j, Y \a\t ' . ($prefs['timestamp'] > 12 ? 'G:i' : 'g:i')) .'.</span>' . "\n";

			$html .= '	<span class="ys-post-actions">' . "\n";
			$html .= '		<a title="Show post information" class="ys-info-link" href="#">Info</a>' . ($admin ? ' | <a title="Delete post" class="ys-delete-link" href="#">Delete</a> | ' . ($banned ? '<a title="Unban ' . $post['nickname'] . '" class="ys-ban-link" href="#">Unban</a>' : '<a title="Ban ' . $post['nickname'] . '" class="ys-ban-link" href="#">Ban</a>') : '') . "\n";
			$html .= '	</span>' . "\n";

			if ($admin) {
				$html .= '<div class="ys-history" style="display: none;">';
				$html .= '	<span class="ys-h-ip">' . $post['adminInfo']['ip'] . '</span>';
				$html .= '	<span class="ys-h-nickname">' . $post['nickname'] . '</span>';
				$html .= '	<span class="ys-h-uid">' . $post['uid'] . '</span>';
				$html .= '</div>';
			}

		$html .= '</div>' . "\n";
	}

	$html .=	'</div>' . "\n";


if (isset($_POST['p'])) {
	echo $html;
	exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>YShout: History</title>
		<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
		<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
		<script type="text/javascript" src="js/history.js"></script>

		<link rel="stylesheet" href="../example/css/example.yshout.css" />
		<link rel="stylesheet" href="css/style.css" />

		<script type="text/javascript">
			new History({
				prefsInfo: '<?= $prefs['info'] ?>',
				log: <?= $log ?>
			});

		</script>
	</head>
	<body>
		<div id="top">
			<h1>YShout.History</h1>
			<div id="controls">
				<?php if($admin) : ?>
					<a id="clear-log" href="#">Clear this log</a>, or
					<a id="clear-logs" href="#">Clear all logs</a>.
				<?php endif; ?>

				<select id="log">
					<?php
						for ($i = 1; $i <= $prefs['logs']; $i++)
							echo '<option' . ($log == $i ? ' selected' : '') . ' rel="' . $i . '">Log ' . $i . '</option>' . "\n";
					?>
				</select>
			</div>
		</div>
		<div id="yshout">
			<div id="ys-before-posts"></div>
			<div id="ys-posts">
				<?= $html ?>
			</div>
			<div id="ys-after-posts"></div>
		</div>
		
		<div id="bottom">
			<a id="to-top" href="#top">Back to top</a>
		</div>
	</body>
</html>