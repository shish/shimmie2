/*
 * Name: Report Images
 * Author: ATravelingGeek (atg@atravelinggeek.com
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Report images as dupes/illegal/etc
 * Version 0.2a - See changelog in main.php
 * October 24, 2007
 */

function validate_report()
{
	if(document.ReportImage.reason_type.value=="Select a reason...") {
		alert("Please select a reason!");
		document.ReportImage.reason_type.focus();
		return false;
	}
	
	if(document.ReportImage.reason.value == "Please enter a reason" || document.ReportImage.reason.value == '' || document.ReportImage.reason.value == "Please enter the Image ID") {
		alert("Please enter a reason!");
		document.ReportImage.reason.focus();
		document.ReportImage.reason.select();
		return false;
	}

}

function change_reason()
{
	if(document.ReportImage.reason_type.value == "Duplicate"){
		document.ReportImage.reason.value = "Enter the Image ID";
	} else {
		document.ReportImage.reason.value = "Please enter a reason";
	}


}