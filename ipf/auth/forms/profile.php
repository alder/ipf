<?php 

class IPF_Auth_Forms_Profile extends IPF_Form_Model{
    function __construct($data=null, $extra=array(), $label_suffix=null){
        $extra['model'] = new User();
        parent::__construct($data, $extra, $label_suffix);
    }
    
    function fields(){ 
        return array('username','email','first_name','last_name'); 
    }
    
    function addDBField($name,$col){
        parent::addDBField($name, $col);
        if ($name=='username')
            $this->fields['username']->help_text = 'Required. 32 characters or fewer. Alphanumeric characters only (letters, digits and underscores).';
    }
}
