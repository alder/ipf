<?php

class IPF_Form_Widget_FileInput extends IPF_Form_Widget_Input
{
    public $input_type = 'file';
    public $needs_multipart_form = true;
    public $allow_extended = true;
    public $allow_delete = true;

    protected function viewCurrentValue($filename)
    {
        if ($filename)
            return '<a target="_blank" href="'.IPF::getUploadUrl().$filename.'">view</a>';
        else
            return '';
    }

    protected function currentValue($filename)
    {
        if (!$filename)
            return '';

        if ($this->allow_extended) {
            $sim = 'Currently: <input name="'.$name.'_name" value="'.$filename.'" type="hidden" /><input name="'.$name.'_rename" value="'.$filename.'" id="id_'.$name.'_rename" type="text" style="width:150px;" /> ' .
                $this->viewCurrentValue($filename);
            if ($this->allow_delete)
                $sim .= '&nbsp;|&nbsp;<input name="'.$name.'_remove" value="1" id="id_'.$name.'_remove" type="checkbox" />&nbsp;<label class="file_remove" for="id_'.$name.'_remove">Remove</label>';
            $sim .= ' Change:';
            return $sim;
        } else {
            return 'Currently: <b>'.$filename.'</b><br> Change: ';
        }
    }

    public function render($name, $value, $extra_attrs=array())
    {
        if (isset($value['data']) && is_string($value['data']) && $value['data'])
            $sim = $this->currentValue($value['data']);
        else
            $sim = '';

        return $sim . parent::render($name, '', $extra_attrs);
    }

    public function valueFromFormData($name, &$data)
    {
        if (!isset($data[$name]))
            return null;
        $remove = isset($data[$name.'_remove']) && $data[$name.'_remove'] == 1;
        $res = array('data'=>$data[$name], 'remove'=>$remove);
        if (isset($data[$name.'_rename']))
            $res['rename'] = $data[$name.'_rename'];
        if (isset($data[$name.'_name']))
            $res['name'] = $data[$name.'_name'];
        return $res;
    }

    public function valueToFormData($name, $data)
    {
        if (!isset($data[$name]))
            return null;
        $remove = isset($data[$name.'_remove']) && $data[$name.'_remove'] == 1;
        return array('data'=>$data[$name], 'remove'=>$remove);
    }
}

