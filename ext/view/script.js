document.addEventListener('DOMContentLoaded', () => {
	if(document.location.hash.length > 3) {
		var query = document.location.hash.substring(1);

		$('LINK#prevlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
		$('LINK#nextlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
		$('A#prevlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
		$('A#nextlink').attr('href', function(i, attr) {
			return attr + '?' + query;
		});
	}
});
