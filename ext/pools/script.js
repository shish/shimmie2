/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

document.addEventListener('DOMContentLoaded', () => {
	$('#order_pool').change(function(){
		var val = $("#order_pool option:selected").val();
		shm_cookie_set("shm_ui-order-pool", val); //FIXME: This won't play nice if COOKIE_PREFIX is not "shm_".
		window.location.href = '';
	});
});
