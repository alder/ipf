<?php

class IPF_Form_Widget_Image extends IPF_Form_Widget_FileInput
{
    public function render($name, $value, $extra_attrs=array())
    {
        if ($value!='')
            $sim = 'Currently: <a target="_blank" href="'.IPF::get('upload_url').$value.'">'.$value.'</a><br />Change:';
        $value = '';
        return $sim.parent::render($name, $value, $extra_attrs);
    }
}