<?php
/**
 * Name: Holiday Theme
 * Author: DakuTree <thedakutree@codeanimu.net>
 * Link: http://www.codeanimu.net
 * License: GPLv2
 * Description: Use an additional stylesheet on certain holidays.
 */
class Holiday extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_bool("holiday_aprilfools", false);
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Holiday Theme");
		$sb->add_bool_option("holiday_aprilfools", "Enable April Fools");
		$event->panel->add_block($sb);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config;
		$date = /*date('d/m') == '01/01' ||date('d/m') == '14/02' || */date('d/m') == '01/04'/* || date('d/m') == '24/12' || date('d/m') == '25/12' || date('d/m') == '31/12'*/;
		if($date){
			if($config->get_bool("holiday_aprilfools")){
				$this->theme->display_holiday($date);
			}
		}
	}
}

