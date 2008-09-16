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

    function getAddNum(){ return 4; }
    
    function getLegend(){
        return get_class($this->model);
    }

    function isValid(){
        foreach($this->formset as &$form){
            if (!$form->isValid()){
                if (!$form->isAdd){
                    return false;
                }
            }
        }
        return true;
    }
    
    function getFkName(){
        foreach($this->model->getTable()->getRelations() as $rel){
            if ($rel->getClass()==get_class($this->parentModel))
                return $rel->getAlias();
        } 
        throw new IPF_Exception(__('Cannot get fkName for '.$this->getModelName()));
    }

    function getFkLocal(){
        foreach($this->model->getTable()->getRelations() as $rel){
            if ($rel->getClass()==get_class($this->parentModel))
                return $rel->getLocal();
        } 
        throw new IPF_Exception(__('Cannot get fkLocal for '.$this->getModelName()));
    }

    function createFormSet(&$data){

        $this->formset = array();
        
        $first = true;
        
        if ($this->parentModel->exists()){
            
            $objects = IPF_ORM_Query::create()
                ->from(get_class($this->model))
                ->orderby('id')
                ->where($this->getFkLocal().'='.$this->parentModel->id)
                ->execute();
                
            foreach ($objects as $obj){
                $prefix = 'edit_'.get_class($this->model).'_'.$obj->id.'_';
                $d = array();
                foreach ($obj->getData() as $k=>$v)
                    $d[$prefix.$k] = $v;
                foreach ($data as $k=>$v){
                    if (strpos($k,$prefix)==0)
                        $d[$k] = $v;
                }
                $form = IPF_Shortcuts::GetFormForModel($obj, $d, 
                    array('exclude'=>array($this->getFkName(),$this->getFkLocal()))
                );
                $form->prefix = $prefix;
                $form->fields = array_merge(array(
                    new IPF_Form_Field_Boolean(array('label'=>'Del','name'=>'is_remove')),
                ),$form->fields);

                $form->isAdd = false;
                if ($first){
                    $form->isFirst = true;
                    $first = false;
                }
                else
                    $form->isFirst = false;
                $this->formset[] = $form;
            }
        }
        
        for($i=0; $i<$this->getAddNum(); $i++ ){
            $form = IPF_Shortcuts::GetFormForModel($this->model->copy(), null, array('exclude'=>array($this->getFkName(),$this->getFkLocal())));
            $form->fields = array_merge(array(new IPF_Form_Field_Boolean(array('label'=>'Del','name'=>'delete_', 'widget_attrs'=>array('disabled'=>'disabled')))),$form->fields);
            $form->prefix = 'add_'.get_class($this->model).'_'.$i.'_';
            $form->data = $data;
            $form->isAdd = true;
            if ($first){
                $form->isFirst = true;
                $first = false;
            }
            else
                $form->isFirst = false;
            $this->formset[] = $form;
        }
    }
    
    function save($parent_obj){
        
        $fk_name = $this->getFkName();
 
        if ($this->parentModel->exists()){
            $objects = IPF_ORM_Query::create()
                ->from(get_class($this->model))
                ->orderby('id')
                ->where($this->getFkLocal().'='.$this->parentModel->id)
                ->execute();
            foreach ($objects as $obj){
                foreach($this->formset as $form){
                    if ($form->isAdd)
                        continue;
                    @list($x1,$x2,$id,$x3) = @split('_',$form->prefix);
                    if ($id==$obj->id){
                        if ($form->cleaned_data[0]==true)
                            $obj->delete();
                        else{
                            unset($form->cleaned_data[0]);
                            foreach($form->fields as $fname=>$f){
                                if (is_a($f,'IPF_Form_Field_File')){
                                    if($form->cleaned_data[$fname]===null)
                                        continue;
                                    if($form->cleaned_data[$fname]=='')
                                        unset($form->cleaned_data[$fname]);
                                }
                            }
                            $obj->synchronizeWithArray($form->cleaned_data);
                            $obj->save();
                        }
                        break;
                    }
                }
            }
        }
 
        foreach($this->formset as $form){
            if ($form->isValid()){
                if ($form->isAdd){
                    unset($form->cleaned_data[0]);
                    $form->cleaned_data[$fk_name] = $parent_obj;
                    $form->save();
                }
            } 
        }
    }
}