<?
error_reporting(E_ALL);
ob_start();
set_error_handler('errorOccurred');
include 'include.php';
if (isset($_POST['reqFor']))
	switch($_POST['reqFor']) {
		case 'shout':

			$ajax = new AjaxCall();
			$ajax->process();
			break;

		case 'history':
		
			// echo $_POST['log'];
			$ajax = new AjaxCall($_POST['log']);
			$ajax->process();
			break;

		default:
			exit;
	}
else
	include 'example.html';

function errorOccurred($num, $str, $file, $line) {
	$err = array (
		'yError' => "$str. \n File: $file \n Line: $line"
	);

	if (function_exists('jsonEncode'))
		echo jsonEncode($err);
	else
		echo $err['yError'];
	exit;
}

?>