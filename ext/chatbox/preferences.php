<?php
	// If you want to change the nickname, the line below is the one to modify.
	// Simply set $overrideNickname to whatever variable you want to appear as the nickname,
	// or leave it null to use the set nicknames.
	
	$overrideNickname = null;
	
	$storage = 'FileStorage';
	
	function loadPrefs() {
		global $prefs, $storage, $null;
		$s = new $storage('yshout.prefs');
		$s->open();
		$prefs = $s->load();
		$s->close($null);
	}

	function savePrefs($newPrefs) {
		global $prefs, $storage;

		$s = new $storage('yshout.prefs');
		$s->open(true);
		$s->close($newPrefs);
		$prefs = $newPrefs;
	}
	
	function resetPrefs() {
		$defaultPrefs = array(
			'password' => 'fortytwo',								// The password for the CP

			'refresh' => 6000,										// Refresh rate

			'logs' => 5,											// Amount of different log files to allow
			'history' => 200,										// Shouts to keep in history

			'inverse' => false,										// Inverse shoutbox / form on top

			'truncate' => 15,										// Truncate messages client-side
			'doTruncate' => true,									// Truncate messages?

			'timestamp' => 12,										// Timestamp format 12- or 24-hour

			'defaultNickname' => 'Nickname',
			'defaultMessage' => 'Message Text',
			'defaultSubmit' => 'Shout!',
			'showSubmit' => true,
			
			'nicknameLength' => 25,
			'messageLength' => 175,

			'nicknameSeparator' => ':',
			
			'flood' => true,
			'floodTimeout' => 5000,
			'floodMessages' => 4,
			'floodDisable' => 8000,
			'floodDelete' => false,
			
			'autobanFlood' => 0,									// Autoban people for flooding after X messages

			'censorWords' => 'fuck shit bitch ass',
			
			'postFormLink' => 'history',

			'info' => 'inline'
		);

		savePrefs($defaultPrefs);
	}
	
	 resetPrefs();
	//loadPrefs();

?>
