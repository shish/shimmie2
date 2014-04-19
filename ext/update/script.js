$(function() {
	if($('#updatecheck').length !== 0){
		$.getJSON('https://api.github.com/repos/shish/shimmie2/commits', function(data){
			var c = data[0];
			$('#updatecheck').html('<a href="'+ c.html_url+'">'+ c.sha+'</a>' + " ("+ c.commit.message+")");

			var params = $.param({sha: c.sha, date: c.commit.committer.date});
			$('#updatelink').attr('href', function(i, val){ return val + "?" + params; });
			$('#updatelink').text("Update");
		}).fail(function(){
			$('#updatecheck').text("Loading failed. (Github down?)");
		});
	}
});
