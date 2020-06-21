var metatags = ['order:id', 'order:width', 'order:height', 'order:filesize', 'order:filename'];

function enableTagAutoComplete(element, limit, search_categories) {
    $(element).tagit({
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
                var requestData = {
                    'query': (isNegative ? request.term.substring(1) : request.term),
                    'limit': limit,
                    'search_categories': search_categories
                };

                $.ajax({
                    url: base_href + '/api/internal/tags/search',
                    data: requestData,
                    dataType : 'json',
                    type : 'GET',
                    success : function (data) {
                        var output = $.merge(ac_metatags,
                            $.map(data.tags, function (item, i) {
                                console.log(item);
                                var tag  = (isNegative ? '-'+item.tag : item.tag);
                                return {
                                    label : tag + ' ('+item.count+')',
                                    value : tag
                                };
                            })
                        );

                        response(output);
                    },
                    error : function (request, status, error) {
                        console.log(error);
                    }
                });
            },
            minLength: 1
        })
    });
    $(element).find('.ui-autocomplete-input').keydown(function(e) {
        var keyCode = e.keyCode || e.which;

        //Stop tags containing space.
        if(keyCode == 32) {
            e.preventDefault();

            $('.autocomplete_tags').tagit('createTag', $(this).val());
            $(this).autocomplete('close');
        } else if (keyCode == 9) {
            e.preventDefault();

            var tag = $('.tagit-autocomplete[style*=\"display: block\"] > li:focus, .tagit-autocomplete[style*=\"display: block\"] > li:first').first();
            if(tag.length){
                $(tag).click();
                $('.ui-autocomplete-input').val(''); //If tag already exists, make sure to remove duplicate.
            }
        }
    });
}
