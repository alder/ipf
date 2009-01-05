<?php

class IPF_Form_BoundField
{
    public $form = null;
    public $field = null;
    public $name = null;
    public $html_name = null;
    public $label = null;
    public $help_text = null;
    public $errors = array();

    public function __construct($form, $field, $name)
    {
        $this->form = $form;
        $this->field = $field;
        $this->name = $name;
        $this->html_name = $this->form->addPrefix($name);
        if ($this->field->label == '') {
            $this->label = mb_ereg_replace('/\_/', '/ /', mb_ucfirst($name));
        } else {
            $this->label = $this->field->label;
        }
        $this->help_text = ($this->field->help_text) ? $this->field->help_text : '';
        if (isset($this->form->errors[$name])) {
            $this->errors = $this->form->errors[$name];
        }
    }

    public function render_w($widget=null, $attrs=array())
    {
        if ($widget === null) {
            $widget = $this->field->widget;
        }
        $id = $this->autoId();
        if ($id and !array_key_exists('id', $attrs)
            and !array_key_exists('id', $widget->attrs)) {
            $attrs['id'] = $id;
        }
        if (!$this->form->is_bound) {
            $data = $this->form->initial($this->name);
        } else {
            $data = $this->field->widget->valueToFormData($this->html_name, $this->form->data);
        }
        return $widget->render($this->html_name, $data, $attrs);
    }

    public function labelTag($contents=null, $attrs=array())
    {
        $contents = ($contents) ? $contents : htmlspecialchars($this->label);
        $widget = $this->field->widget;
        $id = (isset($widget->attrs['id'])) ? $widget->attrs['id'] : $this->autoId();
        $_tmp = array();
        $class_found = false;
        foreach ($attrs as $attr=>$val) {
            $_tmp[] = $attr.'="'.$val.'"';
            if ($attr=='class')
            	$class_found = true;
        }
        if ( (!$class_found) && ($this->field->required==1))
        	$_tmp[] = 'class="req"';
        if (count($_tmp)) {
            $attrs = ' '.implode(' ', $_tmp);
        } else {
            $attrs = '';
        }
        return new IPF_Template_SafeString(sprintf('<label for="%s"%s>%s</label>',
                                                    $widget->idForLabel($id), $attrs, $contents), true);
    }

    public function autoId()
    {
        $id_fields = $this->form->id_fields;
        if (false !== strpos($id_fields, '%s')) {
            return sprintf($id_fields, $this->html_name);
        } elseif ($id_fields) {
            return $this->html_name;
        }
        return '';
    }

    public function fieldErrors()
    {
        IPF::loadFunction('IPF_Form_renderErrorsAsHTML');
        return new IPF_Template_SafeString(IPF_Form_renderErrorsAsHTML($this->errors), true);
    }

    public function __get($prop)
    {
        if (!in_array($prop, array('labelTag', 'fieldErrors', 'render_w'))) {
            return $this->$prop;
        }
        return $this->$prop();
    }

    public function __toString()
    {
        return (string)$this->render_w();
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($str) {
        return mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1);
    }
}
