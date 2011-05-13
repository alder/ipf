<?php

class IPF_Form_Field_TreeModelChoice extends IPF_Form_Field_Choice{

    public $widget = 'IPF_Form_Widget_TreeSelectInput';
    protected $_models;

    function __construct($params=array()){
        parent::__construct($params);
        $this->_models = $params['models'];
        $choices = array('--------'=>'');
        $levels = $this->_getLevels();
        $this->_collectTreeRecursive($choices);
        $this->setChoices($choices);
        $this->widget->setLevels($levels);
    }    
    
    protected function _getLevels(){
        $levels = array();
        foreach($this->_models as &$m){
            $m['objects'] = IPF_ORM_Query::create()->from($m['model'])->orderby('ord')->execute();
            $levels[] = $m['field'];
        }
        return $levels;
    } 
    
    protected function _addObject($o, &$choices, $level, $valname, $name=null)
    {
        if (!$name)
            $name = str_repeat("-", $level).$o['name'];
        
        $choices[$name.' ('.$valname.$o->id.')'] = $valname.$o->id;
    }    

    protected function _collectTreeRecursive(&$choices,$level=0,$parent_id=null,$valname=''){
        foreach($this->_models[$level]['objects'] as $o){
            if ($parent_id){
                $foreign = $this->_models[$level]['foreign'];
                if ($parent_id!=$o->$foreign)
                    continue;
            }
            $this->_addObject($o, $choices, $level, $valname);
            if ($level<(count($this->_models)-1)){
                $this->_collectTreeRecursive($choices,$level+1,$o->id,$valname.$o->id.'.');
            }
        }
    }

    function LateClean($data, &$cleaned_data){
        foreach($this->_models as &$m){
            $cleaned_data[$m['field']] = $data[$m['field']];
        }
    }


    /*
    public function clean($value){
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            return null;
        }
        $o = $this->_model->getTable()->find($value);
        return $o;
    }
    */
}
