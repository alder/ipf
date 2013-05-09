<?php

class IPF_Form_Widget_SelectMultipleInputCheckbox extends IPF_Form_Widget_SelectMultipleInput
{
    public function render($name, $value, $extra_attrs=array(), $choices=array())
    {
        $output = array();
        if ($value === null || $value == '')
            $value = array();
        $final_attrs = $this->buildAttrs($extra_attrs);
        $output[] = '<ul>';
        $choices = array_merge($this->choices, $choices);
        $i=0;
        $base_id = $final_attrs['id'];
        foreach ($choices as $option_label=>$option_value) {
            $final_attrs['id'] = $base_id.'_'.$i;
            $final_attrs['value'] = htmlspecialchars($option_value, ENT_COMPAT, 'UTF-8');
            $checkbox = new IPF_Form_Widget_CheckboxInput($final_attrs);
            $rendered = $checkbox->render($name.'[]', in_array($option_value, $value), array('value'=>$option_value));
            $output[] = sprintf('<li><label>%s %s</label></li>',
                                $rendered,
                                htmlspecialchars($option_label, ENT_COMPAT, 'UTF-8'));
            $i++;
        }
        $output[] = '</ul>';
        return new IPF_Template_SafeString(implode("\n", $output), true);
    }

    public function idForLabel($id)
    {
        if ($id) {
            $id += '_0';
        }
        return $id;
    }
}

