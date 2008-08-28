<?php

abstract class IPF_Admin_ModelInline{

    var $model = null;
    var $parentModel = null;
    var $formset = null;
    var $parentInstance = null;
    
    function __construct(&$parentModel,$parentInstance=null){
        $this->parentModel = $parentModel;
        $this->parentInstance = $parentInstance;
        
        $modelName = $this->getModelName();
        $this->model = new $modelName();
        
        $this->createFormSet();
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

    function createFormSet(){
        $this->formset = array();
        for($i=0; $i<$this->getAddNum(); $i++ ){
            $form = IPF_Shortcuts::GetFormForModel($this->model, null, array('exclude'=>array($this->getFkName(),$this->getFkLocal())));
            $form->prefix = "add-$i";
            if ($i==0)
                $form->isFirst = true;
            else
                $form->isFirst = false;
            //print_r($form->fields);
            $this->formset[] = $form;
        }
    }
}