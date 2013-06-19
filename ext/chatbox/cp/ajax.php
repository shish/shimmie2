<?
error_reporting(E_ALL);
$kioskMode = false;

include '../php/filestorage.class.php';
include '../preferences.php';
include '../php/json.class.php';
include '../php/functions.php';
include '../php/yshout.class.php';
include '../php/ajaxcall.class.php';

if (isset($_POST['mode']))
	switch($_POST['mode']) {
		case 'login':
			doLogin();
			break;
		case 'logout':
			doLogout();
			break;
		case 'unban':
			doUnban();
			break;
		case 'unbanall':
			doUnbanAll();
			break;
		case 'setpreference':
			doSetPreference();
			break;
		case 'resetpreferences':
			doResetPreferences();
			break;
	}

function doLogin() {
	global $kioskMode;
	
	if ($kioskMode) {
		logout();
		$result = array(
			'error' => false,
			'html' => cp()
		);
		
		echo jsonEncode($result);
		return;
	}
	
	login(md5($_POST['password']));
	$result = array();
	if (loggedIn()) {
		$result['error'] = false;
		$result['html'] = cp();
	} else
		$result['error'] = 'invalid';

	echo jsonEncode($result);
}

function doLogout() {
	logout();

	$result = array(
		'error' => false
	);

	echo jsonEncode($result);
}

function doUnban() {
	global $kioskMode;
	
	if ($kioskMode) {
		$result = array(
			'error' => false
		);
		
		echo jsonEncode($result);
		return;
	}
	
	if (!loggedIn()) return;

	$ys = ys();
	$result = array();

	$ip = $_POST['ip'];

	if ($ys->banned($ip)) {
		$ys->unban($ip);
		$result['error'] = false;
	} else
		$result['error'] = 'notbanned';


	echo jsonEncode($result);
}

function doUnbanAll() {
	global $kioskMode;
	
	if ($kioskMode) {
		$result = array(
			'error' => false
		);
		
		echo jsonEncode($result);
		return;
	}
	
	if (!loggedIn()) return;

	$ys = ys();
	$ys->unbanAll();

	$result = array(
		'error' => false
	);

	echo jsonEncode($result);
}


function doSetPreference() {
	global $prefs, $kioskMode;
	
	if ($kioskMode) {
		$result = array(
			'error' => false
		);
		
		echo jsonEncode($result);
		return;
	}
	
	if (!loggedIn()) return;

	$pref = $_POST['preference'];
	$value = magic($_POST['value']);

	if ($value === 'true') $value = true;
	if ($value === 'false') $value = false;

	$prefs[$pref] = $value;

	savePrefs($prefs);

	if ($pref == 'password') login(md5($value));

	$result = array(
		'error' => false
	);

	echo jsonEncode($result);
}


function doResetPreferences() {
	global $prefs, $kioskMode;
	
	if ($kioskMode) {
		$result = array(
			'error' => false
		);
		
		echo jsonEncode($result);
		return;
	}
	
	if (!loggedIn()) return;

	resetPrefs();
	login(md5($prefs['password']));

	//	$prefs['password'] = 'lol no';
	$result = array(
		'error' => false,
		'prefs' => $prefs
	);

	echo jsonEncode($result);
}

/* CP Display */

function cp() {
	global $kioskMode;
	
	if (!loggedIn() && !$kioskMode) return 'You\'re not logged in!';

	return '

				<div class="section" id="preferences">
				<span style="display: none;" id="cp-loaded">true</span>
					<div class="header">
						<h1>YShout.Preferences</h1>
						<a href="#" class="logout">Logout</a>
					</div>

					<ul class="subnav">
						<li id="sn-administration"><a href="#">Administration</a></li>
						<li id="sn-display"><a href="#">Display</a></li>
						<li id="sn-resetall"><a href="#">Reset All</a></li>
						<span class="sn-loading">Loading...</span>
					</ul>

					' . preferencesForm() . '
				</div>

				<div class="section" id="about">
					<div class="header">
						<h1>YShout.About</h1>
						<a href="#" class="logout">Logout</a>
					</div>

					<ul class="subnav">
						<li id="sn-about"><a href="#">About</a></li>
						<li id="sn-contact"><a href="#">Contact</a></li>
						<span class="sn-loading">Loading...</span>
					</ul>

					' . about() . ' 
				</div>

				<div class="section" id="bans">
					<div class="header">
						<h1>YShout.Bans</h1>
						<a href="#" class="logout">Logout</a>
					</div>

					<ul class="subnav">
						<li id="sn-unbanall"><a href="#">Unban All</a></li>
						<span class="sn-loading">Loading...</span>
					</ul>

					' . bansList() . '
					
				</div>';
}

function bansList() {
	global $kioskMode;
	
	$ys = ys();
	$bans = $ys->bans();

	$html = '<ul id="bans-list">';

	$hasBans = false;
	foreach($bans as $ban) {
		$hasBans = true;
		$html .= '
			<li>
				<span class="nickname">' . $ban['nickname']. '</span>
				(<span class="ip">' . ($kioskMode ? '[No IP in Kiosk Mode]' : $ban['ip']) . '</span>)
				<a title="Unban" class="unban-link" href="#" rel="' . $ban['timestamp'] . '">Unban</a>
			</li>
		';
	}
	
	if (!$hasBans)
		$html = '<p id="no-bans">No one is banned.</p>';
	else
		$html .= '</ul>';

	return $html;
}

function preferencesForm() {
	global $prefs, $kioskMode;

	return '
					<form id="preferences-form">
						<div id="cp-pane-administration" class="cp-pane">
							<fieldset id="prefs-cat-cp">
								<div class="legend">Control Panel</div class="legend">
								<ol>
									<li>
										<label for="pref-password">Password</label>
										<input rel="password" type="text" id="pref-password" value="' . ($kioskMode ? 'No password in Kiosk Mode.' : $prefs['password']) . '" />
									</li>
								</ol>
							</fieldset>

							<fieldset id="prefs-cat-flood">
								<div class="legend">Flood Control</div class="legend">
								<ol>
									<li>
										<label for="pref-flood">Use flood control</label>
										<select rel="flood" id="pref-flood">
											<option' . ($prefs['flood'] == true ? ' selected' : '') . ' rel="true">Yes</option>
											<option' . ($prefs['flood'] == false ? ' selected' : '') . ' rel="false">No</option>
										</select>
									</li>
									<li>
										<label for="pref-flood-timeout">Flood timeout</label>
										<input rel="floodTimeout" type="text" id="pref-flood-timeout" value="' . $prefs['floodTimeout'] . '" />
									</li>
									<li>
										<label for="pref-flood-messages">Flood messages</label>
										<input rel="floodMessages" type="text" id="pref-flood-messages" value="' . $prefs['floodMessages'] . '" />
									</li>
									<li>
										<label for="pref-flood-length">Flood length</label>
										<input rel="floodDisable" type="text" id="pref-flood-length" value="' . $prefs['floodDisable'] . '" />
									</li>
									<li>
										<label for="pref-flood-autoban">Automatically ban after</label>
										<select rel="autobanFlood" id="pref-flood-autoban">
											<option' . ($prefs['autobanFlood'] == 1 ? ' selected' : '') . ' rel="1">One activation</option>
											<option' . ($prefs['autobanFlood'] == 2 ? ' selected' : '') . ' rel="2">Two activations</option>
											<option' . ($prefs['autobanFlood'] == 3 ? ' selected' : '') . ' rel="3">Three activations</option>
											<option' . ($prefs['autobanFlood'] == 4 ? ' selected' : '') . ' rel="4">Four activations</option>
											<option' . ($prefs['autobanFlood'] == 5 ? ' selected' : '') . ' rel="5">Five activations</option>
											<option' . ($prefs['autobanFlood'] == 0 ? ' selected' : '') . ' rel="false">Never</option>
										</select>
									</li>
								</ol>
							</fieldset>

							<fieldset id="prefs-cat-history">
								<div class="legend">History</div class="legend">
								<ol>
									<li>
										<label for="pref-max-logs">Max. amount of logs</label>
										<input rel="logs" type="text" id="pref-max-logs" value="' . $prefs['logs'] . '" />
									</li>
									<li>
										<label for="pref-history-shouts">Shouts to keep in history</label>
										<input rel="history" type="text" id="pref-history-shouts" value="' . $prefs['history'] . '" />
									</li>
								</ol>
							</fieldset>

							<fieldset id="prefs-cat-misc">
								<div class="legend">Miscellaneous</div class="legend">
								<ol>
									<li>
										<label for="pref-refresh-rate">Refresh rate</label>
										<input rel="refresh" type="text" id="pref-refresh-rate" value="' . $prefs['refresh'] . '" />
									</li>
									<li>
										<label for="pref-censor-words">Censor words</label>
										<input rel="censorWords" type="text" id="pref-censor-words" value="' . $prefs['censorWords'] . '" />
									</li>
								</ol>
							</fieldset>
						</div>

						<div id="cp-pane-display" class="cp-pane">
							<fieldset id="prefs-cat-form">
								<div class="legend">Form</div class="legend">
								<ol>
									<li>
										<label for="pref-form-position">Form position</label>
										<select rel="inverse" id="pref-form-position">
											<option' . ($prefs['inverse'] == true ? ' selected' : '') . ' rel="true">Top</option>
											<option' . ($prefs['inverse'] == false ? ' selected' : '') . ' rel="false">Bottom</option>
										</select>
									</li>
									<li>
										<label for="pref-nickname-text">Default nickname text</label>
										<input rel="defaultNickname" type="text" id="pref-nickname-text" value="' . $prefs['defaultNickname'] . '" />
									</li>
									<li>
										<label for="pref-message-text">Default message text</label>
										<input rel="defaultMessage" type="text" id="pref-message-text" value="' . $prefs['defaultMessage'] . '" />
									</li>
									<li>
										<label for="pref-submit-text">Default submit text</label>
										<input rel="defaultSubmit" type="text" id="pref-submit-text" value="' . $prefs['defaultSubmit'] . '" />
									</li>
									<li>
										<label for="pref-nickname-length">Max. nickname length</label>
										<input rel="nicknameLength" type="text" id="pref-nickname-length" value="' . $prefs['nicknameLength'] . '" />
									</li>
									<li>
										<label for="pref-message-length">Max. message length</label>
										<input rel="messageLength" type="text" id="pref-message-length" value="' . $prefs['messageLength'] . '" />
									</li>
									<li>
										<label for="pref-show-submit">Show submit button</label>
										<select rel="showSubmit" id="pref-show-submit">
											<option' . ($prefs['showSubmit'] == true ? ' selected' : '') . ' rel="true">Yes</option>
											<option' . ($prefs['showSubmit'] == false ? ' selected' : '') . ' rel="false">No</option>
										</select>
									</li>
									<li>
										<label for="pref-post-form-link">Show link</label>
										<select rel="postFormLink" id="pref-post-form-link">
											<option' . ($prefs['postFormLink'] == 'none' ? ' selected' : '') . ' rel="none">None</option>
											<option' . ($prefs['postFormLink'] == 'history' ? ' selected' : '') . ' rel="history">History</option>
											<option' . ($prefs['postFormLink'] == 'cp' ? ' selected' : '') . ' rel="cp">Control Panel</option>
										</select>
									</li>
								</ol>
							</fieldset>

							<fieldset id="prefs-cat-shouts">
								<div class="legend">Shouts</div class="legend">
								<ol>
									<li>
										<label for="pref-timestamp-format">Timestamp format</label>
										<select rel="timestamp" id="pref-timestamp-format">
											<option' . ($prefs['timestamp'] == 12 ? ' selected' : '') . ' rel="12">12-hour</option>
											<option' . ($prefs['timestamp'] == 24 ? ' selected' : '') . ' rel="24">24-hour</option>
											<option' . ($prefs['timestamp'] == 0 ? ' selected' : '') . ' rel="false">No timestamps</option>
										</select>
									</li>
									<li>
										<label for="pref-truncate">Messages to show</label>
										<input rel="truncate" type="text" id="pref-truncate" value="' . $prefs['truncate'] . '" />
									</li>
									<li>
										<label for="pref-do-truncate">Truncate messages</label>
										<select rel="doTruncate" id="pref-do-truncate">
											<option' . ($prefs['doTruncate'] == true ? ' selected' : '') . ' rel="true">Yes</option>
											<option' . ($prefs['doTruncate'] == false ? ' selected' : '') . ' rel="false">No</option>
										</select>
									</li>
									<li>
										<label for="pref-nickname-suffix">Nickname suffix</label>
										<input rel="nicknameSeparator" type="text" id="pref-nickname-suffix" value="' . $prefs['nicknameSeparator'] . '" />
									</li>
									<li>
										<label for="pref-info-view">Info view</label>
										<select rel="info" id="pref-info-view">
											<option' . ($prefs['info'] == 'inline' ? ' selected' : '') . ' rel="inline">Inline</option>
											<option' . ($prefs['info'] == 'overlay' ? ' selected' : '') . ' rel="overlay">Overlay</option>
										</select>
									</li>
								</ol>
							</fieldset>
						</div>
					</form>
	';
}

function about() {
	global $prefs;

	$html = '
		<div id="cp-pane-about" class="cp-pane">
			<h2>About YShout</h2>
			<p>YShout was created and developed by Yuri Vishnevsky. Version 5 is the first one with an about page, so you\'ll have to excuse the lack of appropriate information &mdash; I\'m not quite sure what it is that goes on "About" pages anyway.</p>
			<p>Other than that obviously important tidbit of information, there\'s really nothing else that I can think of putting here... If anyone knows what a good and proper about page should contain, please contact me!
		</div>
		
		<div id="cp-pane-contact" class="cp-pane">
			<h2>Contact Yuri</h2>
			<p>If you have any questions or comments, you can contact me by email at <a href="mailto:yurivish@gmail.com">yurivish@gmail.com</a>, or on AIM at <a href="aim:goim?screnname=yurivish42">yurivish42</a>.</p>
			<p>I hope you\'ve enjoyed using YShout!</p>
		</div>
		';

	
	return $html;
}

?>