$(function() {
	var order_pool = $.cookie("shm_ui-order-pool");
	$("#order_pool option[value="+order_pool+"]").attr("selected", true);

	$('#order_pool').change(function(){
		var val = $("#order_pool option:selected").val();
		$.cookie("shm_ui-order-pool", val, {path: '/', expires: 365}); //FIXME: This won't play nice if COOKIE_PREFIX is not "shm_".
		window.location.href = '';
	});
});
