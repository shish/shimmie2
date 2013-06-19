<?
	include 'ajax.php'; 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>YShout: Admin CP</title>
		<link rel="stylesheet" type="text/css" href="css/style.css" />
		<script type="text/javascript" src="../js/jquery.js"></script>
		<script type="text/javascript" src="js/admincp.js"></script>
	</head>
	<body>
		<div id="cp">
			<div id="nav">
				<ul>
					<li id="n-prefs"><a href="#">Preferences</a></li>
					<li id="n-bans"><a href="#">Bans</a></li>
					<li id="n-about"><a href="#">About</a></li>
				</ul>
			</div>

			<div id="content">
				<div class="section" id="login">
					<div class="header">
						<h1>YShout.Preferences</h1>
					</div>

					<form id="login-form" action="index.php" method="post">
						<label for="login-password">Password:</label>
						<input type="password" id="login-password" name="loginPassword">
						<span id="login-loading">Loading...</span>
					</form>
				</div>
				<?
					if (loggedIn()) echo cp();
				?>
			</div>
		</div>
	</body>
</html>