/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

$(function() {
	$('#order_pool').change(function(){
		var val = $("#order_pool option:selected").val();
		Cookies.set("shm_ui-order-pool", val, {path: '/', expires: 365}); //FIXME: This won't play nice if COOKIE_PREFIX is not "shm_".
		window.location.href = '';
	});
});
