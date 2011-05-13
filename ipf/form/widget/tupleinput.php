<?php

class IPF_Form_Widget_TupleInput extends IPF_Form_Widget
{
    var $headers = array('');
    var $rows = 3;

    public function __construct($attrs=array()){
        
        parent::__construct($attrs);

        if (isset($attrs['headers']))
            $this->headers = $attrs['headers'];

        if (isset($attrs['rows']))
            $this->rows = $attrs['rows'];
    }
    
    protected function isHeadLabels(){
        foreach ($this->headers as &$h){
            if ($h=='')
                return false;
        }
        return true;
    }

    public function render($name, $value, $extra_attrs=array())
    {
        $data = array();
        $lines = explode("\n",$value);
        foreach ($lines as $line){
            $data[] = explode('|', $line);
        }
        if ($value === null) $value = '';
        $s = '<table class="tuplegrid">';
        if ($this->isHeadLabels()){
            $s .= '<tr>';
            foreach ($this->headers as &$h){
                $s .= '<th>'.$h.'</th>';
            }
            $s .= '</tr>';
        }
        for ($i=0; $i<$this->rows; $i++){
            $s .= '<tr>';
            for ($j=0; $j<count($this->headers); $j++){
                $v = @$data[$i][$j];
                $s .= '<td><input name="'.$name.'_'.$i.'_'.$j.'" value="'.$v.'"></td>';
            }
            $s .= '<tr>';
        }
        $s .= '</table>';
        return new IPF_Template_SafeString($s,true);
    }

    public function valueFromFormData($name, &$data)
    {
        $s = '';
        for ($i=0; $i<$this->rows; $i++){
            if ($i>0) $s .= "\n";
            for ($j=0; $j<count($this->headers); $j++){
                if ($j>0) $s.='|';
                $s .= @$data[$name.'_'.$i.'_'.$j];
            }
        }
        return $s;
    }
}

