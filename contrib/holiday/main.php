<?php
/**
 * Name: Holiday Theme
 * Author: DakuTree <thedakutree@codeanimu.net>
 * Link: http://www.codeanimu.net
 * License: GPLv2
 * Description: Use an additional stylesheet on certain holidays.
 */
class Holiday extends SimpleExtension {
	public function onInitExt(Event $event) {
		global $config;
		$config->set_default_bool("holiday_aprilfools", false);
	}

	public function onSetupBuilding(Event $event) {
		global $config;
		$sb = new SetupBlock("Holiday Theme");
		$sb->add_bool_option("holiday_aprilfools", "Enable April Fools");
		$event->panel->add_block($sb);
	}

	public function onPageRequest(Event $event) {
		global $config;
		$date = /*date('d/m') == '01/01' ||date('d/m') == '14/02' || */date('d/m') == '01/04'/* || date('d/m') == '24/12' || date('d/m') == '25/12' || date('d/m') == '31/12'*/;
		if($date){
			if($config->get_bool("holiday_aprilfools")){
				$this->theme->display_holiday($date);
			}
		}
	}

}
?>
