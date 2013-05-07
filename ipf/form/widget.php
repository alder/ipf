<?php

class IPF_Form_Widget
{
    public $is_hidden = false;
    public $needs_multipart_form = false;
    public $input_type = '';
    public $attrs = array();

    public function __construct($attrs=array())
    {
        $this->attrs = $attrs;
    }

    public function render($name, $value, $extra_attrs=array())
    {
        throw new IPF_Exception('Not Implemented.');
    }

    public function extra_js()
    {
        return array();
    }

    protected function buildAttrs($attrs, $extra_attrs=array())
    {
        return array_merge($this->attrs, $attrs, $extra_attrs);
    }

    public function valueFromFormData($name, &$data)
    {
        if (isset($data[$name])) {
            return $data[$name];
        }
        return null;
    }

    public function valueToFormData($name, $data)
    {
        if (isset($data[$name])) {
            return $data[$name];
        }
        return null;
    }

    public function idForLabel($id)
    {
        return $id;
    }
}

function IPF_Form_Widget_Attrs($attrs)
{
    $_tmp = array();
    foreach ($attrs as $attr=>$val) {
        $_tmp[] = $attr.'="'.$val.'"';
    }
    return ' '.implode(' ', $_tmp);
}

