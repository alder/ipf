<?php

class IPF_ORM_Template_Listener_Sluggable extends IPF_ORM_Record_Listener
{
    protected $_options = array();

    public function __construct(array $options)
    {
        $this->_options = $options;
    }

    public function preInsert(IPF_ORM_Event $event)
    {
        $name = $this->_options['name'];

        $record = $event->getInvoker();

        if ( ! $record->$name) {
            $record->$name = $this->buildSlug($record);
        }
    }

    public function preUpdate(IPF_ORM_Event $event)
    {
        if (false !== $this->_options['unique']) {
            $name = $this->_options['name'];
    
            $record = $event->getInvoker();

            if ( ! $record->$name ||
            (false !== $this->_options['canUpdate'] &&
            array_key_exists($name, $record->getModified()))) {
                $record->$name = $this->buildSlug($record);
            }
        }
    }

    protected function buildSlug($record)
    {
        if (empty($this->_options['fields'])) {
            if (method_exists($record, 'getUniqueSlug')) {
                $value = $record->getUniqueSlug($record);
            } else {
                $value = (string) $record;
            }
        } else {
            if ($this->_options['unique'] === true) {   
                $value = $this->getUniqueSlug($record);
            } else {  
                $value = '';
                foreach ($this->_options['fields'] as $field) {
                    $value .= $record->$field . ' ';
                } 
            }
        }

        $value =  call_user_func_array($this->_options['builder'], array($value, $record));

        return $value;
    }

    public function getUniqueSlug($record)
    {
        $name = $this->_options['name'];
        $slugFromFields = '';
        foreach ($this->_options['fields'] as $field) {
            $slugFromFields .= $record->$field . ' ';
        }

        $proposal = $record->$name ? $record->$name : $slugFromFields;
        $proposal =  call_user_func_array($this->_options['builder'], array($proposal, $record));
        $slug = $proposal;

        $whereString = 'r.' . $name . ' LIKE ?';
        $whereParams = array($proposal.'%');
        
        if ($record->exists()) {
            $identifier = $record->identifier();
            $whereString .= ' AND r.' . implode(' != ? AND r.', $record->getTable()->getIdentifierColumnNames()) . ' != ?';
            $whereParams = array_merge($whereParams, array_values($identifier));
        }

        foreach ($this->_options['uniqueBy'] as $uniqueBy) {
            if (is_null($record->$uniqueBy)) {
                $whereString .= ' AND r.'.$uniqueBy.' IS NULL';
            } else {
                $whereString .= ' AND r.'.$uniqueBy.' = ?';
                $whereParams[] =  $record->$uniqueBy;
            }
        }

        $query = IPF_ORM_Query::create()
        ->select('r.'.$name)
        ->from(get_class($record).' r')
        ->where($whereString , $whereParams)
        ->setHydrationMode(IPF_ORM::HYDRATE_ARRAY);

        $similarSlugResult = $query->execute();

        $similarSlugs = array();
        foreach ($similarSlugResult as $key => $value) {
            $similarSlugs[$key] = $value[$name];
        }

        $i = 1;
        while (in_array($slug, $similarSlugs)) {
            $slug = $proposal.'-'.$i;
            $i++;
        }

        return  $slug;
    }
}