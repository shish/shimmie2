<?php

class DowntimeTheme extends Themelet {
	/**
	 * Show the admin that downtime mode is enabled
	 */
	public function display_notification(Page $page) {
		$page->add_block(new Block("Downtime",
			"<span style='font-size: 1.5em'><b><center>DOWNTIME MODE IS ON!</center></b></span>", "left", 0));
	}

	/**
	 * Display $message and exit
	 */
	public function display_message(/*string*/ $message) {
		global $config, $user;
		$theme_name = $config->get_string('theme');
		$data_href = get_base_href();
		$login_form = make_form("user_admin/login", "POST", array(), TRUE);

		header("HTTP/1.0 503 Service Temporarily Unavailable");

		print <<<EOD
<html>
	<head>
		<title>Downtime</title>
		<link rel="stylesheet" href="$data_href/themes/$theme_name/style.css" type="text/css">
	</head>
	<body>
		<div id="downtime">
			<section>
				<h1><center>Down for Maintenance</center></h1>
				<div id="message" class="blockbody">
					$message
				</div>
			</section>
			<section>
				<h3>Admin Login</h3>
				<div id="login" class="blockbody">
					$login_form
						<table id="login_table" summary="Login Form">
							<tr>
								<td width="70"><label for="user">Name</label></td>
								<td width="70"><input id="user" type="text" name="user"></td>
							</tr>
							<tr>
								<td><label for="pass">Password</label></td>
								<td><input id="pass" type="password" name="pass"></td>
							</tr>
							<tr><td colspan="2"><input type="submit" value="Log In"></td></tr>
						</table>
					</form>
				</div>
			</section>
		</div>
	</body>
</html>
EOD;
	}
}

