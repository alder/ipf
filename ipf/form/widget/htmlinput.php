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
        $final_attrs = $this->buildAttrs(array('name' => $name), $extra_attrs);
        return new IPF_Template_SafeString('<textarea'.IPF_Form_Widget_Attrs($final_attrs).' class="htmlEditor">'.htmlspecialchars($value, ENT_COMPAT, 'UTF-8').'</textarea>', true);
    }

    public function extra_js()
    {
        return array(
            '<script type="text/javascript" src="'.IPF::get('tiny_mce_url').'tiny_mce.js"></script>',
            '<script type="text/javascript">
function ipf_filebrowser(field_name, url, type, win) {
  var cmsURL = "/admin/filebrowser/";
  tinyMCE.activeEditor.windowManager.open({
    file: cmsURL,
    title: "IPF File Browser",
    width: 800,
    height: 600,
    resizable: "yes",
    inline: "yes",
    close_previous: "no"
  }, {
    window: win,
    input: field_name
  });
  return false;
}
tinyMCE.init({
  theme_advanced_buttons1: "bold, italic, underline, separator, undo, redo, separator, bullist, numlist, outdent, indent, separator, justifyleft, justifycenter, justifyright, justifyfull, separator, link, unlink, forecolor, backcolor, sub, sup, separator, preview",
  theme_advanced_buttons2: "code, fullscreen, image, charmap, separator, pastetext, pasteword, selectall, removeformat, separator, formatselect, fontselect, fontsizeselect",
  theme_advanced_buttons3: "tablecontrols",
  theme_advanced_toolbar_location: "top",
  theme_advanced_toolbar_align: "left",
  extended_valid_elements: "span[class|style],code[class],iframe[src|width|height|name|align|frameborder|scrolling]",
  mode: "specific_textareas",
  editor_selector: "htmlEditor",
  theme: "advanced",
  convert_urls: "false",
  plugins: "inlinepopups, charmap, paste, table, fullscreen, preview, print, advlink, advimage",
  button_tile_map: true,
  fix_list_elements: true,
  gecko_spellcheck: true,
  verify_html: true,
  dialog_type: "modal",
  width: "80%",
  height: "350",
  relative_urls: false,
  remove_script_host: true,
  content_css: "/media/tiny_mce/themes/advanced/skins/default/content.css",
  file_browser_callback: "ipf_filebrowser"
});
</script>',
        );
    }
}

