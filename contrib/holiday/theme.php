<?php
class HolidayTheme extends Themelet {
	public function display_holiday($date) {
		global $page;
		if($date){
			$csssheet = "<link rel='stylesheet' href='".get_base_href()"/contrib/holiday/stylesheets/";

			// April Fools
			// Flips the entire page upside down!
			// TODO: Make it possible for the user to turn this off!
			if(date('d/m') == '01/04'){
				$csssheet .= "aprilfools.css";
				//$holtag = "april_fools";
			}

			$csssheet .= "' type='text/css'>";
			$page->add_html_header("$csssheet");
		}
	}
}
?>
