<?php

abstract class IPF_Admin_ModelInline{

    var $model = null;
    var $parentModel = null;
    var $formset = null;

    function __construct($parentModel,$data){
        $this->parentModel = $parentModel;
        
        $modelName = $this->getModelName();
        $this->model = new $modelName();
        
        $this->createFormSet($data);
    }

    abstract function getModelName();

    function getAddNum(){ return 2; }
    
    function getLegend(){
        return get_class($this->model);
    }
    
    function isValid(){
        foreach($this->formset as &$form){
            if ($form->isValid()==false){
                return false;
            }
        }
        return true;
    }
    
    function getFkName(){
        foreach($this->model->getTable()->getRelations() as $rel){
            if ($rel->getClass()==get_class($this->parentModel))
                return $rel->getAlias();
        } 
        throw new IPF_Exception('Cannot get fkName for '.$this->getModelName());
    }

    function getFkLocal(){
        foreach($this->model->getTable()->getRelations() as $rel){
            if ($rel->getClass()==get_class($this->parentModel))
                return $rel->getLocal();
        } 
        throw new IPF_Exception('Cannot get fkLocal for '.$this->getModelName());
    }

    function createFormSet(&$data){
        $this->formset = array();
        for($i=0; $i<$this->getAddNum(); $i++ ){
            $form = IPF_Shortcuts::GetFormForModel($this->model, null, array('exclude'=>array($this->getFkName(),$this->getFkLocal())));
            $form->fields = array_merge(array(new IPF_Form_Field_Boolean(array('label'=>'Del','name'=>'delete_', 'widget_attrs'=>array('disabled'=>'disabled')))),$form->fields);
            $form->prefix = 'add_'.get_class($this->model).'_'.$i;
            $form->data = $data;
            if ($i==0)
                $form->isFirst = true;
            else
                $form->isFirst = false;
            //print_r($form->fields);
            $this->formset[] = $form;
        }
    }
    
    function save(){
        
    }
}