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

        if ($user_fields === null) {
            if (isset($extra['exclude']))
                $exclude = $extra['exclude'];
            else
                $exclude = array();

            foreach ($db_columns as $name => $col) {
                if (array_search($name, $exclude) !== false)
                    continue;
                if (isset($col['exclude']) && $col['exclude'])
                    continue;
                $this->addDBField($name, $col);
            }

            foreach ($db_relations as $name => $relation) {
                if (array_search($name, $exclude) !== false)
                    continue;
                if (isset($relation['exclude']) && $relation['exclude'])
                    continue;
                $this->addDBRelation($name, $relation, $col); // FIXME: $col is undefined
            }
        } else {
            foreach ($user_fields as $uname) {
                $add_method = 'add__'.$uname.'__field';
                if (method_exists($this, $add_method)) {
                    $this->$add_method();
                    continue;
                }
                if (array_key_exists($uname,$db_columns)) {
                    $this->addDBField($uname,$db_columns[$uname]);
                } elseif (array_key_exists($uname,$db_relations)) {
                    $lfn = $db_relations[$uname]->getLocalFieldName();
                    if (isset($db_columns[$lfn]))
                        $col = $db_columns[$lfn];
                    else
                        $col = array();
                    $this->addDBRelation($uname,$db_relations[$uname],$col);
                }
            }
        }
    }

    function addDBField($name, $col)
    {
        if ($name == $this->model->getTable()->getIdentifier())
            return;

        $defaults = array(
            'blank'     => true,
            'help_text' => '',
            'editable'  => true,
            'verbose'   => isset($col['verbose']) ? $col['verbose'] : $name,
        );

        $type = $col['type'];

        if (isset($col['notblank'])) {
            if ($col['notblank'])
                $defaults['blank'] = false;
            else
                $defaults['blank'] = true;
        }
        if (isset($col['length']))
            $defaults['max_length'] = (int)($col['length']);

        if (isset($col['email']))
            $type = 'email';

        if (isset($col['file']))
            $type = 'file';

        if (isset($col['image']))
            $type = 'image';

        if (isset($col['html']))
            $type = 'html';

        $cn = 'IPF_Form_DB_'.ucfirst($type);

        $db_field = new $cn('', $name, $col);

        $form_field = $db_field->formField($defaults);
        if ($form_field !== null)
            $this->fields[$name] = $form_field;
    }

    function addDBRelation($name, $relation, $col)
    {
        $rt = $relation->getType();
        if ($rt !== IPF_ORM_Relation::ONE_AGGREGATE && $rt !== IPF_ORM_Relation::MANY_AGGREGATE)
            return;

        $defaults = array(
            'blank'     => !isset($col['notblank']),
            'help_text' => '',
            'editable'  => true,
            'model'     => $relation->getClass(),
            'verbose'   => isset($col['verbose']) ? $col['verbose'] : $name,
        );

        if ($rt === IPF_ORM_Relation::ONE_AGGREGATE) {
            $name .= "_id";
            $db_field = new IPF_Form_DB_Foreignkey('',$name);
            $this->fields[$name] = $db_field->formField($defaults);
        } else if ($rt === IPF_ORM_Relation::MANY_AGGREGATE) {
            $db_field = new IPF_Form_DB_Manytomany('',$name);
            $this->fields[$name] = $db_field->formField($defaults);
        }
    }

    function fields()
    {
        return $this->user_fields;
    }

    function save($commit=true)
    {
        if (!$this->isValid())
            throw new IPF_Exception_Form(__('Cannot save the model from an invalid form.'));

        $this->model->SetFromFormData($this->cleaned_data);
        $this->model->save();
        $rels = $this->model->getTable()->getRelations();
        foreach ($rels as $rname => $rel) {
            if (isset($this->cleaned_data[$rname])) {
                $this->model->unlink($rel->getAlias());
                if (is_array($this->cleaned_data[$rname])) {
                    $this->model->link($rel->getAlias(),$this->cleaned_data[$rname]);
                }
            }
        }
        return $this->model;
    }
}

