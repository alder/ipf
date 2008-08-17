<?php

class IPF_Form_Widget_Input extends IPF_Form_Widget
{
    public function render($name, $value, $extra_attrs=array())
    {
        if ($value === null) $value = '';
        $final_attrs = $this->buildAttrs(array('name' => $name, 
                                               'type' => $this->input_type),
                                         $extra_attrs);
        if ($value !== '') {
            $value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
            $final_attrs['value'] = $value;
        }
        return new IPF_Template_SafeString('<input'.IPF_Form_Widget_Attrs($final_attrs).' />', true);
    }
}