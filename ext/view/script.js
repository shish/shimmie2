$(document).ready(function() {
	if(document.location.hash.length > 3) {
		var query = document.location.hash.substring(1);

		$('#prevlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
		$('#nextlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
	}
});
