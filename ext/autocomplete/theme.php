<?php

class AutoCompleteTheme extends Themelet {
	public function build_autocomplete(Page $page) {
		$base_href = get_base_href();
		// TODO: AJAX test and fallback.

		$page->add_html_header("<script src='$base_href/ext/autocomplete/lib/jquery-ui.min.js' type='text/javascript'></script>");
		$page->add_html_header("<script src='$base_href/ext/autocomplete/lib/tag-it.min.js' type='text/javascript'></script>");
		$page->add_html_header('<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/flick/jquery-ui.css">');
		$page->add_html_header("<link rel='stylesheet' type='text/css' href='$base_href/ext/autocomplete/lib/jquery.tagit.css' />");

		$page->add_html_header("<script>
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

					if (keyCode == 9 || keyCode == 32) {
						e.preventDefault();

						var tag = $('.tagit-autocomplete:not([style*=\"display: none\"]) > li:first').text();
						if(tag){
							$('[name=search]').tagit('createTag', tag);
							$('.ui-autocomplete-input').autocomplete('close');
						}
					}
				});
			});
		</script>");
	}
}
