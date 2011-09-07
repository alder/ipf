<?php

class IPF_Form_Widget_RadioInput extends IPF_Form_Widget
{
    public $choices = array();

    public function __construct($attrs=array())
    {
        if (isset($attrs['choices'])){
            $this->choices = $attrs['choices'];
            unset($attrs['choices']);
        }
        parent::__construct($attrs);
    }

    public function render($name, $value, $extra_attrs=array(), 
                           $choices=array())
    {
        $output = array();
        if ($value === null) {
            $value = '';
        }
        // $final_attrs = $this->buildAttrs($extra_attrs);
        $output[] = '<ul>'; // '.IPF_Form_Widget_Attrs($final_attrs).'
        $choices = $this->choices + $choices;
        $index = 1;
        foreach ($choices as $option_label=>$option_value) {
            $selected = ($option_value == $value) ? ' checked="checked"':'';
            $output[] = sprintf('<input type="radio" name="%s" id="id_%s-%s" value="%s"%s/><label for="id_%s-%s">%s</label>',
                                $name,
                                $name,
                                $index,
                                htmlspecialchars($option_value, ENT_COMPAT, 'UTF-8'),
                                $selected,
                                $name,
                                $index,
                                htmlspecialchars($option_label, ENT_COMPAT, 'UTF-8'));
            $index++;
        }
        $output[] = '</ul>';
        return new IPF_Template_SafeString(implode("\n", $output), true);
    }
}

