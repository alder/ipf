<?php

class IPF_Form_Widget_HTMLInput extends IPF_Form_Widget
{
    public $mode = 'textareas';
    public $theme = 'simple';
    public $include_tinymce = true;

    public function __construct($attrs=array())
    {
        $defaults = array('cols' => '70', 
                          'rows' => '20');
        $config = array('tinymce_url', 'mode', 'theme', 'include_tinymce');
        foreach ($config as $cfg) {
            if (isset($attrs[$cfg])) {
                $this->$cfg = $attrs[$cfg];
                unset($attrs[$cfg]);
            }
        }
        $this->attrs = array_merge($defaults, $attrs);
    }

    public function render($name, $value, $extra_attrs=array())
    {
        if ($value === null) $value = '';
        $extra_config = '';
        if (isset($this->attrs['editor_config'])) {
            $_ec = $this->attrs['editor_config'];
            unset($this->attrs['editor_config']);
            $_st = array();
            foreach ($_ec as $key=>$val) {
                if (is_bool($val)) {
                    if ($val) {
                        $_st[] = $key.' : true';
                    } else {
                        $_st[] = $key.' : false';
                    }
                } else {
                    $_st[] = $key.' : "'.$val.'"';
                }
            }
            if ($_st) {
                $extra_config = ",\n".implode(",\n", $_st);
            }
        }
        $final_attrs = $this->buildAttrs(array('name' => $name),
                                         $extra_attrs);
        // The special include for tinyMCE
        $out = '';
        if ($this->include_tinymce) {
            $out .= '<script language="javascript" type="text/javascript" src="'.IPF::get('admin_media_url').'tiny_mce/tiny_mce.js"></script>'."\n";
        }
        $out .='<script language="javascript" type="text/javascript">
	tinyMCE.init({
        mode : "specific_textareas",
        editor_selector : "htmlEditor",
     	theme : "advanced",
 	    theme_advanced_toolbar_location : "top",
     	theme_advanced_toolbar_align: "left",
        theme_advanced_buttons1 : "bold, italic, separator, undo, redo, separator, bullist, numlist, outdent, indent, separator, justifyleft, justifycenter, justifyright, separator, link, unlink, separator, selectall, removeformat, separator,sub,sup,separator, forecolor, backcolor",
        theme_advanced_buttons2 : "", 
        theme_advanced_buttons3 : "",
     	convert_urls:"false",
        plugins : "paste, table",
        button_tile_map : true,
        fix_list_elements : true,
        gecko_spellcheck : true,
        verify_html : true,
        dialog_type : "modal",
        height : "800",
        height : "300"
	});
</script>';

// buttons: code, separator pastetext, pasteword, 
//plugins : "inlinepopups, paste, table, fullscreen, preview, print, charmap, separator, ",

        return new IPF_Template_SafeString(
                       $out.sprintf('<textarea%s class="htmlEditor">%s</textarea>',
                               IPF_Form_Widget_Attrs($final_attrs),
                               htmlspecialchars($value, ENT_COMPAT, 'UTF-8')),
                       true);
    }
}