<?php

class IPF_Form_Widget_FileInput extends IPF_Form_Widget_Input
{
    public $input_type = 'file';
    public $needs_multipart_form = true;

    public function render($name, $value, $extra_attrs=array())
    {
        $sim = '';
        if (isset($value['data'])){
            $value = $value['data'];
            if (is_string($value) && $value!=''){
                $sim = 'Currently: <a target="_blank" href="'.IPF::get('upload_url').$value.'">'.$value.'</a> | <input name="'.$name.'_remove" value="1" id="id_'.$name.'_remove" type="checkbox" /> <label for="id_'.$name.'_remove">Remove</label><br />Change:';
            }
        }
        $value = '';
        return $sim.parent::render($name, $value, $extra_attrs);
    }
    
    public function valueFromFormData($name, $data)
    {
        if (isset($data[$name])) {
            $remove = false;
            if (isset($data[$name.'_remove']))
                if ($data[$name.'_remove']==1)
                    $remove = true;
            $res = array('data'=>$data[$name], 'remove'=>$remove);
            return $res;
        }
        return null;
    }
    

}