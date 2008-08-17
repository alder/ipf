<?php

class IPF_Form_Widget_HTMLInput extends IPF_Form_Widget
{
    public $tiny_mceurl = IPF::get('media_url').'/js/editor/tiny_mce.js';
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
            $out .= '<script language="javascript" type="text/javascript" src="'.$this->tinymce_url.'"></script>'."\n";
        }
        $out .='<script language="javascript" type="text/javascript">
	tinyMCE.init({
		mode : "'.$this->mode.'",
		theme : "'.$this->theme.'"'.$extra_config.'
	});
</script>';
        return new IPF_Template_SafeString(
                       $out.sprintf('<textarea%s>%s</textarea>',
                               IPF_Form_Widget_Attrs($final_attrs),
                               htmlspecialchars($value, ENT_COMPAT, 'UTF-8')),
                       true);
    }
}