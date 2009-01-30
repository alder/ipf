<?php

class IPF_Form_Widget_HTMLInput extends IPF_Form_Widget
{
    public $mode = 'textareas';
    public $theme = 'simple';
    public $include_tinymce = true;

    static $js_include = False;

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
        $final_attrs = $this->buildAttrs(array('name' => $name), $extra_attrs);
        $out = '';
        if (!IPF_Form_Widget_HTMLInput::$js_include){
            IPF_Form_Widget_HTMLInput::$js_include = true;
                    $out .= '<script language="javascript" type="text/javascript" src="'.IPF::get('tiny_mce_url').'tiny_mce.js"></script>'."\n";
                    $out .='<script language="javascript" type="text/javascript">
					function kfm_for_tiny_mce(field_name, url, type, win){
					  window.SetUrl=function(url,width,height,caption){
					   win.document.forms[0].elements[field_name].value = url;
					   if(caption){
					    win.document.forms[0].elements["alt"].value=caption;
					    win.document.forms[0].elements["title"].value=caption;
					   }
					  }
					  window.open("/media/tiny_mce/plugins/kfm/index.php?mode=selector&lang=en&type="+type,"kfm","modal,width=800,height=600");
					}
            	tinyMCE.init({
                    theme_advanced_buttons1 : "bold, italic, underline, separator, undo, redo, separator, bullist, numlist, outdent, indent, separator, justifyleft, justifycenter, justifyright, separator, link, unlink, forecolor, backcolor, sub, sup, separator, preview",
                    theme_advanced_buttons2 : "code, fullscreen, image, link, charmap, separator, pastetext, pasteword, selectall, removeformat, separator, formatselect, fontselect, fontsizeselect, separator, tablecontrols",
                    theme_advanced_buttons3 : "",
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align: "left",
                    mode : "specific_textareas",
                    editor_selector : "htmlEditor",
                 	theme : "advanced",
                 	convert_urls:"false",
                 	plugins : "inlinepopups, charmap, paste, table, fullscreen, preview, print, advlink, advimage",
                    button_tile_map : true,
                    fix_list_elements : true,
                    gecko_spellcheck : true,
                    verify_html : true,
                    dialog_type : "modal",
                    width : "80%",
                    height : "350",
                    relative_urls : false,
                    remove_script_host : true,
                    content_css : "/media/tiny_mce/themes/advanced/skins/default/content.css",
                    file_browser_callback : "kfm_for_tiny_mce"
            	});
            </script>';
        }
        return new IPF_Template_SafeString(
                       $out.sprintf('<textarea%s class="htmlEditor">%s</textarea>',
                               IPF_Form_Widget_Attrs($final_attrs),
                               htmlspecialchars($value, ENT_COMPAT, 'UTF-8')),
                       true);
    }
}