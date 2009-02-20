<?php

class IPF_Form_Widget_FileInput extends IPF_Form_Widget_Input
{
    public $input_type = 'file';
    public $needs_multipart_form = true;
    public $allow_extended = true;

    public $additional_params = array();

    public function render($name, $value, $extra_attrs=array())
    {
        $sim = '';
        if (isset($value['data'])){
            $value = $value['data'];
            if (is_string($value) && $value!=''){
				if ($this->allow_extended)
	                $sim = '<nobr>Currently: <a target="_blank" href="'.IPF::getUploadUrl($this->additional_params).$value.'">'.$value.'</a>&nbsp;|&nbsp;<input name="'.$name.'_remove" value="1" id="id_'.$name.'_remove" type="checkbox" />&nbsp;<label class="file_remove" for="id_'.$name.'_remove">Remove</label></nobr>Change:';
				else
	                $sim = '<nobr>Currently: <b>'.$value.'</b><br> Change: ';
            }
        }
        $value = '';
        return $sim.parent::render($name, $value, $extra_attrs);
    }

    public function valueFromFormData($name, $data){
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

    public function valueToFormData($name, $data){
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