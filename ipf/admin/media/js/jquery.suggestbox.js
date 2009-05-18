(function($){
    $.fn.suggestbox = function(opt){
        var options = $.extend({
            animate: true,
            inputWidth: 100,
            delayDropDown: 500,
            url: '/data.php',
            reqest_method: 'GET',
            zindexDropDown: 20000,
            loadingMessage: 'loading...'
        }, opt);

        $(document).bind("click", function(){
            $('.suggestbox_dropdown').hide();
        });
        $(document).bind("keypress", function(e){
            if (e.keyCode == 13) return false;
        });

        return this.each(function(index) {
            var $original = $(this);
            var $container;
            var $input;
            var $ul;
            var max_opt=0;

            var jC = '#id_suggestbox_dropdown_' + index;
            var jC0 = 'id_suggestbox_dropdown_' + index;

            function init() {
                $("body").append('<div class="suggestbox_dropdown" id="id_suggestbox_dropdown_'+index+'"></div>');
                $('#suggestbox_dropdown_'+index).hide();

                $container = $("<div></div>")
                    .addClass('suggestbox')
                    .attr('id', 'id_suggestbox_' + index)
                    .width($original.width());

                $ul = $("<ul></ul>")
                    .addClass('ul')
                    .attr('id', 'id_suggestbox_ul_' + index);

                $input = $("<input>")
                    .addClass('input')
                    .attr('id', 'id_suggestbox_input_' + index)
                    .attr('autocomplete', 'off')
                    .width(options.inputWidth);

                //$container.append($input);
                buildSelect();
                $original.wrap($container).before($ul).before($input);
                $('#id_suggestbox_input_' + index).bind('keyup', clickInput);
            }

            function appendNewItem(id){
                var a = $('#'+id).attr('rel').split(':');
                var val = a[0];
                var name = a[1];
                max_opt++;
                var $option = $("<option></option>").text(name).attr('id', 'id_soo_' + index + '_' + max_opt).attr("value",val).attr("selected", true);
                $original.append($option).change();
                addListItem('id_soo_' + index + '_' + max_opt);
            }

            function clickInput(e){
                var textBox = this;
                var textVal = this.value;
                var iniVal = '';

                if (this.value.length >= 0) {
                    var offSet = $(this).offset();
                    $(jC).css({
                        position: "absolute",
                        top: (offSet.top + $(this).outerHeight() - 1) + "px",
                        left: offSet.left,
                        width: $(this).outerWidth()-2 + "px",
                        zIndex: options.zindexDropDown
                    }).show();

                    if (e.keyCode == 27 ) {
                        // esc
                        $(jC).hide();
                    }
                    else if (e.keyCode == 13 ) {
                        if ($(jC + ' ul li.hilite').length==1) {
                            appendNewItem($(jC + ' ul li.hilite').attr('id'));
                            $input.val('');
                            $(jC).hide();
                        }
                        return false;
                    }
                    else if (e.keyCode == 40) {
                        // bottom arrow
                        if ($(jC + ' ul li.hilite').length==1) {
                            if (!$(jC + ' ul li.hilite').next().length == 0) {
                                var item_new = $(jC + ' ul li.hilite').next();
                                $(jC + ' ul li.hilite').removeClass('hilite');
                                $(item_new).addClass('hilite');
                            }
                        }
                        else
                            $(jC + " ul li:first-child").addClass('hilite');
                    }
                    else if (e.keyCode == 38) {
                        // if up arrow
                        if ($(jC + ' ul li.hilite').length==1) {
                            if (!$(jC + ' ul li.hilite').prev().length == 0) {
                                var item_new = $(jC + ' ul li.hilite').prev();
                                $(jC + ' ul li.hilite').removeClass('hilite');
                                $(item_new).addClass('hilite');
                            }
                        }
                        else
                            $(jC + ' ul li.hilite').removeClass('hilite');
                    }
                    // new query detected
                    else if (textBox.value != iniVal){
                        iniVal = textBox.value;
                        $(jC).html('<div class="loading">'+options.loadingMessage+'</div>');
                        setTimeout(function () {
                            $.ajax({
                                type: options.reqest_method,
                                url: options.url,
                                data: 'q=' + $input.val() + '&rnd=' + Math.random(),
                                dataType: 'json',
                                success: function(res){
                                    $(jC).html('');
                                    jL = $("<ul></ul>");
                                    $(jC).append(jL);
                                    var t = $.template(options.template);
                                    for (i=0; i<res.length; i++){
                                        var li = $("<li></li>").attr('rel', res[i].id+':'+res[i].name).attr('id',jC0+'_'+i);
                                        $(li).append(t,res[i]);
                                        $(jL).append(li);
                                    }
                                    $(jC + " ul li").bind("mouseover", function(){
                                        $(jC + " ul li").removeClass('hilite');
                                        $(this).addClass('hilite');
                                    });
                                    $(jC+ " ul li").click(function(){
                                        appendNewItem(this.id);
                                        //appendNewItem($(this).attr('rel'));
                                        $(jC).hide();
                                    });
                                }
                            });
                        }, options.delayDropDown);
                    }
                }
                // if text is too short do nothing and hide everything
                else {
                    $(jH).removeClass(jsH);
                    $(jC).hide();
                }

                // no bubbling, click is binded to textBox to prevent document bind from firing
                return false;

            }

            function buildSelect() {
                buildingSelect = true;
                $original.children("option").each(function(n) {
                    var $t = $(this);
                    var id;
                    if(!$t.attr('id')) $t.attr('id', 'id_soo_' + index + '_' + n);
                    id = $t.attr('id');
                    addListItem(id);
                    if (max_opt<n) max_opt=n;
                });
                $original.hide();
                buildingSelect = false;
            }

            function addListItem(optionId) {
                // add a new item to the html list

                var $O = $('#' + optionId);

                if(!$O) return; // this is the first item, selectLabel

                var $removeLink = $('<img>')
                    .attr("src", "remove.gif")
                    .addClass('remove')
                    .click(function() {
                        dropListItem($(this).parent('li').attr('rel'));
                        return false;
                    });

                var $itemLabel = $("<span></span>")
                    .addClass(options.listItemLabelClass)
                    .html($O.html());

                var $item = $("<li></li>")
                    .attr('rel', optionId)
                    .addClass(options.listItemClass)
                    .append($itemLabel)
                    .append($removeLink)
                    //.hide();

                $ul.append($item);
                //addListItemShow($item);
            }


            function dropListItem(optionId) {
                $('#' + optionId).remove();
                $item = $ul.children("li[rel=" + optionId + "]");
                dropListItemHide($item);
            }

            function dropListItemHide($item) {
                if(options.animate && !buildingSelect) {
                    $prevItem = $item.prev("li");
                    $item.animate({
                        opacity: "hide",
                        height: "hide"
                    }, 100, "linear", function() {
                        $prevItem.animate({
                            height: "-=2px"
                        }, 50, "swing", function() {
                            $prevItem.animate({
                                height: "+=2px"
                            }, 100, "swing");
                        });
                        $item.remove();
                    });
                } else {
                    $item.remove();
                }
            }
            init();
        }
    )}
})(jQuery);

