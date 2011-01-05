$(function(){
    var index=0;
    $('.checkgroup').each(function(){
        var master=$(this).closest('ul');
        if (!master.hasClass('checkgroup_master')){
            var id='chkgm_'+index;
            ++index;
            master.addClass('checkgroup_master').attr('id', id);
            master.before(
                '<div><ul class="object-tools" style="margin-top:0 !important;"><li><a href="#" rel="#'+id+
                '" class="checkbtn checkall">Check All</a></li><li><a href="#" rel="#'+id+
                '" class="checkbtn uncheckall">Uncheck All</a></li></ul></div>'
            );
        }
    });
    $('.checkbtn').click(function(){
        var button=$(this);
        var check=button.hasClass('checkall');
        $(button.attr('rel')+' .checkgroup').each(function(){
            $(this).attr('checked',check);
        });
        return false;
    });
});