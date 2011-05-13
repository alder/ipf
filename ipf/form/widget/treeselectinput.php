<?php

class IPF_Form_Widget_TreeSelectInput extends IPF_Form_Widget_SelectInput
{
    protected $_levels = null;

    public function setLevels($levels){
        $this->_levels = $levels;
    }

    public function valueToFormData($name, $data)
    {
        $val = null;
        foreach($this->_levels as $l){
            if ( (!isset($data[$l])) || ($data[$l]=='')){
                return $val;
            }
            if ($val==null)
                $val = '';
            else
                $val .= '.';
            $val .= $data[$l];
        }
        return $val;
    }

    public function valueFromFormData($name, &$data)
    {
        if (isset($data[$name])) {
            $vals = explode(".",(string)$data[$name]);
            for($i=0; $i<count($this->_levels); $i++){
                if ( ($i<count($vals)) && ($data[$name]!=''))
                    $data[$this->_levels[$i]] = $vals[$i];
                else
                    $data[$this->_levels[$i]] = null;
            }
            return $data[$name];
        }
        return null;
    }
}

