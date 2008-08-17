<?php

class IPF_Form_Widget_TextareaInput extends IPF_Form_Widget
{

    public function __construct($attrs=array())
    {
        $this->attrs = array_merge(array('cols' => '40', 'rows' => '10'), 
                                   $attrs);
    }

    public function render($name, $value, $extra_attrs=array())
    {
        if ($value === null) $value = '';
        $final_attrs = $this->buildAttrs(array('name' => $name),
                                         $extra_attrs);
        return new IPF_Template_SafeString(
                       sprintf('<textarea%s>%s</textarea>',
                               IPF_Form_Widget_Attrs($final_attrs),
                               htmlspecialchars($value, ENT_COMPAT, 'UTF-8')),
                       true);
    }
}
