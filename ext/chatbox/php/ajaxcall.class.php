<?
	class AjaxCall {
		function AjaxCall($log = null) {
			header('Content-type: application/json');
			session_start();
			
			if (isset($log)) $_SESSION['yLog'] = $log;
			
			$this->reqType = $_POST['reqType'];
		}

		function process() {
			switch($this->reqType) {
				case 'init':

					$this->initSession();
					$this->sendFirstUpdates();
					break;

				case 'post':
					$nickname = $_POST['nickname'];
					$message = $_POST['message'];
					cookie('yNickname', $nickname);
					$ys = ys($_SESSION['yLog']);
					
					if ($ys->banned(ip())) { $this->sendBanned(); break; }
					if ($post = $ys->post($nickname, $message))	// To use $post somewheres later
							$this->sendUpdates();
					break;

				case 'refresh':
					$ys = ys($_SESSION['yLog']);
					if ($ys->banned(ip())) { $this->sendBanned(); break; }
					
						$this->sendUpdates();
					break;

				case 'reload':
					$this->reload();
					break;
					
				case 'ban':
					$this->doBan();
					break;

				case 'unban':
					$this->doUnban();
					break;
					
				case 'delete':
					$this->doDelete();
					break;

				case 'banself':
					$this->banSelf();
					break;

				case 'unbanself':
					$this->unbanSelf();
					break;

				case 'clearlog':
					$this->clearLog();
					break;
				
				case 'clearlogs':
					$this->clearLogs();
					break;
			}
		}

		function doBan() {
			$ip = $_POST['ip'];
			$nickname = $_POST['nickname'];
			$send = array();
			$ys = ys($_SESSION['yLog']);

			switch(true) {
				case !loggedIn():
					$send['error'] = 'admin';
					break;
				case $ys->banned($ip):
					$send['error'] = 'already';
					break;
				default:
					$ys->ban($ip, $nickname);
					if ($ip == ip())
					$send['bannedSelf'] = true;
					$send['error'] = false;
			}

			echo jsonEncode($send);
		}

		function doUnban() {
			$ip = $_POST['ip'];
			$send = array();
			$ys = ys($_SESSION['yLog']);

			switch(true) {
				case !loggedIn():
					$send['error'] = 'admin';
					break;
				case !$ys->banned($ip):
					$send['error'] = 'already';
					break;
				default:
					$ys->unban($ip);
					$send['error'] = false;
			}

			echo jsonEncode($send);
		}

		function doDelete() {
			$uid = $_POST['uid'];
			$send = array();
			$ys = ys($_SESSION['yLog']);

			switch(true) {
				case !loggedIn():
					$send['error'] = 'admin';
					break;
				default:
					$ys->delete($uid);
					$send['error'] = false;
			}

			echo jsonEncode($send);
		}

		function banSelf() {
			$ys = ys($_SESSION['yLog']);
			$nickname = $_POST['nickname'];
			$ys->ban(ip(), $nickname);

			$send = array();
			$send['error'] = false;
			
			echo jsonEncode($send);

		}

		function unbanSelf() {
			if (loggedIn()) {
				$ys = ys($_SESSION['yLog']);
				$ys->unban(ip());
	
				$send = array();
				$send['error'] = false;
			} else {
				$send = array();
				$send['error'] = 'admin';
			}
			
			echo jsonEncode($send);
		}
		
		function reload() {
			global $prefs;
			$ys = ys($_SESSION['yLog']);

			$posts = $ys->latestPosts($prefs['truncate']);
			$this->setSessTimestamp($posts);
			$this->updates['posts'] = $posts;						
			echo jsonEncode($this->updates);
		}

		function initSession() {
			$_SESSION['yLatestTimestamp'] = 0;
			$_SESSION['yYPath'] = $_POST['yPath'];
			$_SESSION['yLog'] = $_POST['log'];
			$loginHash = cookieGet('yLoginHash') ;
			if (isset($loginHash) && $loginHash != '') {
				login($loginHash);
			}
		}

		function sendBanned()  {
			$this->updates = array(
				'banned' => true
			);

			echo jsonEncode($this->updates);
		}
		
		function sendUpdates() {
			global $prefs;
			$ys = ys($_SESSION['yLog']);
			if (!$ys->hasPostsAfter($_SESSION['yLatestTimestamp'])) return;

			$posts = $ys->postsAfter($_SESSION['yLatestTimestamp']);
			$this->setSessTimestamp($posts);

			$this->updates['posts'] = $posts;

			echo jsonEncode($this->updates);
		}

		function setSessTimestamp(&$posts) {
			if (!$posts) return;

			$latest = array_slice( $posts, -1, 1);
			$_SESSION['yLatestTimestamp'] = $latest[0]['timestamp'];
		}

		function sendFirstUpdates() {
			global $prefs, $overrideNickname;

			$this->updates = array();

			$ys = ys($_SESSION['yLog']);

			$posts = $ys->latestPosts($prefs['truncate']);
			$this->setSessTimestamp($posts);

			$this->updates['posts'] = $posts;
			$this->updates['prefs'] = $this->cleanPrefs($prefs);

			if ($nickname = cookieGet('yNickname'))
				$this->updates['nickname'] = $nickname;
			
			if ($overrideNickname)
				$this->updates['nickname'] = $overrideNickname;
				
			if ($ys->banned(ip()))
				$this->updates['banned'] = true;

			echo jsonEncode($this->updates);
		}
		
		function cleanPrefs($prefs) {
			unset($prefs['password']);
			return $prefs;
		}
		
		function clearLog() {
			$log = $_POST['log'];
			$send = array();
			$ys = ys($_SESSION['yLog']);

			switch(true) {
				case !loggedIn():
					$send['error'] = 'admin';
					break;
				default:
					$ys->clear();
					$send['error'] = false;
			}

			echo jsonEncode($send);
		}
		
		function clearLogs() {
			global $prefs;
		
			$log = $_POST['log'];
			$send = array();

			$ys = ys($_SESSION['yLog']);

			switch(true) {
				case !loggedIn():
					$send['error'] = 'admin';
					break;
				default:
					for ($i = 1; $i <= $prefs['logs']; $i++) {
						$ys = ys($i);
						$ys->clear();
					}
					
					$send['error'] = false;
			}

			echo jsonEncode($send);		
		}
	}

?>