<?php
/*
 * ConfigSaveEvent:
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class ConfigSaveEvent extends Event {
	var $config;
	
	public function ConfigSaveEvent($config) {
		$this->config = $config;
	}
}
?>
