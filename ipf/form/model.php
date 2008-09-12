<?php

class IPF_Form_Model extends IPF_Form
{
    public $model = null;
    public $user_fields = null;

    function initFields($extra=array())
    {
        if (isset($extra['model']))
            $this->model = $extra['model'];
        else
            throw new IPF_Exception_Form(__('Unknown model for form'));
        
        if (isset($extra['user_fields']))
            $this->user_fields = $extra['user_fields'];
        
        $user_fields = $this->fields();
        $db_columns = $this->model->getTable()->getColumns();
        $db_relations = $this->model->getTable()->getRelations();
        
        if ($user_fields===null){

            if (isset($extra['exclude'])) 
                $exclude = $extra['exclude'];
            else
                $exclude = array();
                
            foreach($db_columns as $name=>$col){
                if (array_search($name,$exclude)!==false)
                    continue;
                $this->addDBField($name,$col);
            }
            foreach($db_relations as $name => $relation){
                if (array_search($name,$exclude)!==false)
                    continue;
                $this->addDBRelation($name,$relation);
		    }
        }
        else{
            foreach($user_fields as $uname){
                $add_method = 'add__'.$uname.'__field';
                if (method_exists($this,$add_method)){
                    $this->$add_method();
                    continue;
                }
                if (array_key_exists($uname,$db_columns))
                    $this->addDBField($uname,$db_columns[$uname]);
                elseif (array_key_exists($uname,$db_relations))
                    $this->addDBRelation($uname,$db_relations[$uname]);
            }
        }
    }
    
    function addDBField($name,$col){
        if ($name==$this->model->getTable()->getIdentifier())
            return;
            
        $defaults = array('blank' => true, 'verbose' => $name, 'help_text' => '', 'editable' => true);
        $type = $col['type'];
        
        if (isset($col['notblank']))
            if ($col['notblank'])
                $defaults['blank'] = false;
            else
                $defaults['blank'] = true;
        if (isset($col['length']))
            $defaults['max_length'] = (int)($col['length']);
        if (isset($col['email']))
            $type = 'email';

        if (isset($col['file']))
            $type = 'file';

        if (isset($col['image']))
            $type = 'image';

        $cn = 'IPF_Form_DB_'.$type;
        
        $db_field = new $cn('', $name);
        //echo $name;
        //print_r($defaults);

        
        if (null !== ($form_field=$db_field->formField($defaults))) {
            $this->fields[$name] = $form_field;
        }
    }

    function addDBRelation($name,$relation){
        if ($relation->getType()==IPF_ORM_Relation::ONE_AGGREGATE){
            $name .= "_id";
            $db_field = new IPF_Form_DB_Foreignkey('',$name);
            $defaults = array('blank' => true, 'verbose' => $name, 'help_text' => '', 'editable' => true, 'model'=>$relation->getClass());
            $form_field = $db_field->formField($defaults);
            $this->fields[$name] = $form_field;
        }
    }

    function fields(){ return $this->user_fields; }

    function save($commit=true)
    {
        if ($this->isValid()) {
            $this->model->SetFromFormData($this->cleaned_data);
            try{
                $this->model->save();
                return $this->model;
            } catch(IPF_ORM_Exception_Validator $e) {
                $erecords = $e->getInvalidRecords();
                $errors = $erecords[0]->getErrorStack();
                foreach($errors as $k=>$v){
                    print($k);
                }
            }
        }
        throw new IPF_Exception_Form(__('Cannot save the model from an invalid form.'));
    }
}
