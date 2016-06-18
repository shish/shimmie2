$(function(){
	$('[name=search]').tagit({
		singleFieldDelimiter: ' ',
		beforeTagAdded: function(event, ui) {
			// give special class to negative tags
			if(ui.tagLabel[0] === '-') {
				ui.tag.addClass('tag-negative');
			}else{
				ui.tag.addClass('tag-positive');
			}
		},
		autocomplete : ({
			source: function (request, response) {
				var isNegative = (request.term[0] === '-');
				$.ajax({
					url: base_href + '/api/internal/autocomplete',
					data: {'s': (isNegative ? request.term.substring(1) : request.term)},
					dataType : 'json',
					type : 'GET',
					success : function (data) {
						response($.map(data, function (item) {
							item = (isNegative ? '-'+item : item);
							return {
								label : item,
								value : item
							}
						}));
					},
					error : function (request, status, error) {
						alert(error);
					}
				});
			},
			minLength: 1
		})
	});

	$('.ui-autocomplete-input').keydown(function(e) {
		var keyCode = e.keyCode || e.which;

		//Stop tags containing space.
		if(keyCode == 32) {
			e.preventDefault();

			$('[name=search]').tagit('createTag', $(this).val());
			$(this).autocomplete('close');
		} else if (keyCode == 9) {
			e.preventDefault();

			var tag = $('.tagit-autocomplete[style*=\"display: block\"] > li:first').text();
			if(tag){
				$('[name=search]').tagit('createTag', tag);
				$('.ui-autocomplete-input').autocomplete('close');
				$('.ui-autocomplete-input').val(''); //If tag already exists, make sure to remove duplicate.
			}
		}
	});
});