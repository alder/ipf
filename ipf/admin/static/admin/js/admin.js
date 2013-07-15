$(function(){
    $('.dateinput').datepicker({dateFormat: 'yy-mm-dd'});
    $('.datetimeinput').datetimepicker({dateFormat: 'yy-mm-dd'});

    $('.checkgroup').closest('ul').each(function(){
        var master = $(this),
            tools = $('<ul class="object-tools"><li><a href="#" class="checkall">Check All</a></li><li><a href="#">Uncheck All</a></li></ul>');
        master.before(tools).addClass('checkgroup_master');
        tools.find('a').click(function(){
            var check = $(this).hasClass('checkall');
            master.find('input').attr('checked', check);
            return false;
        });
    });
});

