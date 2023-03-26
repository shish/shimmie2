document.addEventListener('DOMContentLoaded', () => {
	var metatags = ['order:id', 'order:width', 'order:height', 'order:filesize', 'order:filename', 'order:favorites'];

	$('[name="search"]').tagit({
		singleFieldDelimiter: ' ',
		beforeTagAdded: function(event, ui) {
			if(metatags.indexOf(ui.tagLabel) !== -1) {
				ui.tag.addClass('tag-metatag');
			} else {
				console.log(ui.tagLabel);
				// give special class to negative tags
				if(ui.tagLabel[0] === '-') {
					ui.tag.addClass('tag-negative');
				}else{
					ui.tag.addClass('tag-positive');
				}
			}
		},
		autocomplete : ({
			source: function (request, response) {
				var ac_metatags = $.map(
					$.grep(metatags, function(s) {
						// Only show metatags for strings longer than one character
						return (request.term.length > 1 && s.indexOf(request.term) === 0);
					}),
					function(item) {
						return {
							label : item + ' [metatag]',
							value : item
						};
					}
				);

				var isNegative = (request.term[0] === '-');
				$.ajax({
					url: base_href + '/api/internal/autocomplete',
					data: {'s': (isNegative ? request.term.substring(1) : request.term)},
					dataType : 'json',
					type : 'GET',
					success : function (data) {
						response(
							$.merge(ac_metatags,
								$.map(data, function (count, item) {
									item = (isNegative ? '-'+item : item);
									return {
										label : item + ' ('+count+')',
										value : item
									};
								})
							)
						);
					},
					error : function (request, status, error) {
						console.log(error);
					}
				});
			},
			minLength: 1
		})
	});

	$('#tag_editor,[name="bulk_tags"]').tagit({
		singleFieldDelimiter: ' ',
		autocomplete : ({
			source: function (request, response) {
				$.ajax({
					url: base_href + '/api/internal/autocomplete',
					data: {'s': request.term},
					dataType : 'json',
					type : 'GET',
					success : function (data) {
						response(
							$.map(data, function (count, item) {
								return {
									label : item + ' ('+count+')',
									value : item
								};
							})
						);
					},
					error : function (request, status, error) {
						console.log(error);
					}
				});
			},
			minLength: 1
		})
	});

	$('.ui-autocomplete-input').keydown(function(e) {
		var keyCode = e.keyCode || e.which;

		//Stop tags containing space.
		if(keyCode === 32) {
			e.preventDefault();
			var el = $('.ui-widget-content:focus');

			//Find the correct element in a page with multiple tagit input boxes.
			$('.autocomplete_tags').each(function(_,n){
				if (n.parentNode.contains(el[0])){
					$(n.parentNode).find('.autocomplete_tags').tagit('createTag', el.val());
				}
			});
			$(this).autocomplete('close');
		} else if (keyCode === 9) {
			e.preventDefault();

			var tag = $('.tagit-autocomplete[style*=\"display: block\"] > li:focus, .tagit-autocomplete[style*=\"display: block\"] > li:first').first();
			if(tag.length){
				$(tag).click();
				$('.ui-autocomplete-input').val(''); //If tag already exists, make sure to remove duplicate.
			}
		}
	});
});
