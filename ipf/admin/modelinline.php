<?php

abstract class IPF_Admin_ModelInline
{
    var $model = null;
    var $parentModel = null;
    var $formset = null;

    var $orderby = 'id';

    function __construct($parentModel,$data=null)
    {
        $this->parentModel = $parentModel;

        $modelName = $this->getModelName();
        $this->model = new $modelName();
        $this->createFormSet($data);
    }

    abstract function getModelName();

    public function getApplication()
    {
        return IPF_Utils::appByModel($this->getModelName());
    }

    function getAddNum()
    {
        return 4;
    }

    function getLegend()
    {
        return get_class($this->model);
    }

    function isValid()
    {
        foreach ($this->formset as &$form) {
            if (!$form->isValid()) {
                if (!$form->isAdd) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function _getForm($model_obj, $data, $extra)
    {
        return IPF_Shortcuts::GetFormForModel($model_obj, $data, $extra);
    }

    function getFkName()
    {
        foreach($this->model->getTable()->getRelations() as $rel) {
            if ($rel->getClass()==get_class($this->parentModel))
                return $rel->getAlias();
        }
        throw new IPF_Exception(__('Cannot get fkName for '.$this->getModelName()));
    }

    function getFkLocal()
    {
        foreach($this->model->getTable()->getRelations() as $rel) {
            if ($rel->getClass() == get_class($this->parentModel))
                return $rel->getLocal();
        }
        throw new IPF_Exception(__('Cannot get fkLocal for '.$this->getModelName()));
    }

    function createFormSet($data)
    {
        $this->formset = array();

        $o = $this->_orderableColumn();

        $form_extra = array(
            'exclude' => array(
                $this->getFkName(),
                $this->getFkLocal(),
            ),
        );
        if ($o)
            $form_extra['exclude'][] = $o;

        $first = true;

        if ($this->parentModel->exists()) {
            $query = IPF_ORM_Query::create()
                ->from(get_class($this->model))
                ->where($this->getFkLocal().'='.$this->parentModel->id);

            if ($o)
                $objects = $query->orderby($o)->execute();
            else
                $objects = $query->orderby($this->orderby)->execute();

            foreach ($objects as $obj) {
                $prefix = 'edit_'.get_class($this->model).'_'.$obj->id.'_';
                $d = array();

                if ($data===null) {
                    foreach ($obj->getData() as $k=>$v) {
                        $d[$prefix.$k] = $v;
                    }
                } else {
                    foreach ($data as $k=>$v) {
                        if (strpos($k,$prefix) == 0)
                            $d[$k] = $v;
                    }
                }

                $form = $this->_getForm($obj, $d, $form_extra);

                $form->prefix = $prefix;
                $form->fields = array_merge(array(
                    new IPF_Form_Field_Boolean(array('label'=>'Del','name'=>'is_remove')),
                ), $form->fields);

                $form->isAdd = false;
                if ($first) {
                    $form->isFirst = true;
                    $first = false;
                } else {
                    $form->isFirst = false;
                }
                $this->formset[] = $form;
            }
        }

        $n_addnum = $this->getAddNum();
        for ($i = 0; $i < $n_addnum; $i++) {
            $form = $this->_getForm($this->model->copy(), null, $form_extra);
            $form->fields = array_merge(array(new IPF_Form_Field_Boolean(array('label'=>'Del','name'=>'delete_', 'widget_attrs'=>array('disabled'=>'disabled')))),$form->fields);
            $form->prefix = 'add_'.get_class($this->model).'_'.$i.'_';
            $form->data = $data;
            $form->isAdd = true;
            if ($first) {
                $form->isFirst = true;
                $first = false;
            } else {
                $form->isFirst = false;
            }
            $this->formset[] = $form;
        }
    }

    function save($parent_obj)
    {
        if (!$this->isValid())
            throw new IPF_Exception_Form(__('Cannot save models from an invalid formset.'));

        if ($this->parentModel->exists()) {
            $objects = IPF_ORM_Query::create()
                ->from(get_class($this->model))
                ->orderby('id')
                ->where($this->getFkLocal().'='.$this->parentModel->id)
                ->execute();
            foreach ($objects as $obj) {
                foreach ($this->formset as $form) {
                    if ($form->isAdd)
                        continue;

                    @list($x1,$x2,$id,$x3) = @explode('_',$form->prefix);
                    if ($id == $obj->id) {
                        if ($form->cleaned_data[0]==true) {
                            $obj->delete();
                        } else {
                            unset($form->cleaned_data[0]);
                            foreach($form->fields as $fname=>$f) {
                                if (is_a($f,'IPF_Form_Field_File')) {
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

        $fk_local = $this->getFkLocal();
        foreach ($this->formset as $form) {
            if ($form->isValid()) {
                if ($form->isAdd) {
                    unset($form->cleaned_data[0]);
                    $form->cleaned_data[$fk_local] = $parent_obj->id;
                    $form->save();
                }
            }
        }
    }

    public function _orderable()
    {
        return $this->_orderableColumn() !== null;
    }

    public function _orderableColumn()
    {
        if ($this->model->getTable()->hasTemplate('IPF_ORM_Template_Orderable'))
            return $this->model->getTable()->getTemplate('IPF_ORM_Template_Orderable')->getColumnName();
        else
            return null;
    }
}

