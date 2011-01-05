<?php

class IPF_Form_Extra_Widget_CheckboxGroupInput extends IPF_Form_Widget_SelectMultipleInputCheckbox
{
    public function render($name, $value, $extra_attrs=array(), $choices=array())
    {
        $output = array();
        if ($value === null || $value == '')
            $value = array();
        $final_attrs = $this->buildAttrs($extra_attrs);
        $output[] = '<div><ul style="float:left;">';
        $choices = array_merge($this->choices, $choices);
        $i=0;
        $base_id = $final_attrs['id'];
        foreach ($choices as $option_label=>$option_value)
        {
            $final_attrs['id'] = $base_id.'_'.$i;
            $final_attrs['value'] = htmlspecialchars($option_value, ENT_COMPAT, 'UTF-8');
            $field = new IPF_Form_Extra_Widget_CheckboxInput($final_attrs);
            $rendered = $field->render($name.'[]', in_array($option_value, $value), array('value'=>$option_value));
            $output[] = sprintf(
                '<li style="list-style-type:none;"><label style="width:600px;">%s %s</label></li><br/>',
                $rendered, htmlspecialchars($option_label, ENT_COMPAT, 'UTF-8')
            );
            $i++;
        }
        $output[] = '</ul><div style="clear:both; height:0px;"></div></div>';
        return new IPF_Template_SafeString(implode("\n", $output), true);
    }
}
