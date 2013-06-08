<?php

abstract class IPF_ORM_Configurable extends IPF_ORM_Locator_Injectable
{
    protected $attributes = array();
    protected $parent;
    protected $_impl = array();
    protected $_params = array();

    public function getAttributeFromString($stringAttributeName)
    {
      if (is_string($stringAttributeName)) {
          $upper = strtoupper($stringAttributeName);
          $const = 'IPF_ORM::ATTR_' . $upper; 
          if (defined($const)) {
              return constant($const);
          } else {
              throw new IPF_ORM_Exception('Unknown attribute: "' . $stringAttributeName . '"');
          }
      } else {
        return false;
      }
    }

    public function getAttributeValueFromString($stringAttributeName, $stringAttributeValueName)
    {
        $const = 'IPF_ORM::' . strtoupper($stringAttributeName) . '_' . strtoupper($stringAttributeValueName);
        if (defined($const)) {
            return constant($const);
        } else {
            throw new IPF_ORM_Exception('Unknown attribute value: "' . $value . '"');
        }
    }

    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute)) {
            $stringAttribute = $attribute;
            $attribute = $this->getAttributeFromString($attribute);
            $this->_state = $attribute;
        }

        if (is_string($value) && isset($stringAttribute)) {
            $value = $this->getAttributeValueFromString($stringAttribute, $value);
        }

        switch ($attribute) {
            case IPF_ORM::ATTR_COLL_KEY:
                if ( ! ($this instanceof IPF_ORM_Table)) {
                    throw new IPF_ORM_Exception("This attribute can only be set at table level.");
                }
                if ($value !== null && ! $this->hasField($value)) {
                    throw new IPF_ORM_Exception("Couldn't set collection key attribute. No such field '$value'.");
                }
                break;
            case IPF_ORM::ATTR_CACHE:
            case IPF_ORM::ATTR_RESULT_CACHE:
            case IPF_ORM::ATTR_QUERY_CACHE:
                if ($value !== null) {
                    if ( ! ($value instanceof IPF_ORM_Cache_Interface)) {
                        throw new IPF_ORM_Exception('Cache driver should implement IPF_ORM_Cache_Interface');
                    }
                }
                break;
            case IPF_ORM::ATTR_QUERY_LIMIT:
            case IPF_ORM::ATTR_QUOTE_IDENTIFIER:
            case IPF_ORM::ATTR_PORTABILITY:
            case IPF_ORM::ATTR_DEFAULT_TABLE_TYPE:
            case IPF_ORM::ATTR_EMULATE_DATABASE:
            case IPF_ORM::ATTR_USE_NATIVE_ENUM:
            case IPF_ORM::ATTR_DEFAULT_SEQUENCE:
            case IPF_ORM::ATTR_EXPORT:
            case IPF_ORM::ATTR_DECIMAL_PLACES:
            case IPF_ORM::ATTR_LOAD_REFERENCES:
            case IPF_ORM::ATTR_RECORD_LISTENER:
            case IPF_ORM::ATTR_THROW_EXCEPTIONS:
            case IPF_ORM::ATTR_DEFAULT_PARAM_NAMESPACE:
            case IPF_ORM::ATTR_AUTOLOAD_TABLE_CLASSES:
            case IPF_ORM::ATTR_MODEL_LOADING:
            case IPF_ORM::ATTR_RESULT_CACHE_LIFESPAN:
            case IPF_ORM::ATTR_QUERY_CACHE_LIFESPAN:
            case IPF_ORM::ATTR_RECURSIVE_MERGE_FIXTURES;
            case IPF_ORM::ATTR_SINGULARIZE_IMPORT;
            case IPF_ORM::ATTR_USE_DQL_CALLBACKS;

                break;
            case IPF_ORM::ATTR_SEQCOL_NAME:
                if ( ! is_string($value)) {
                    throw new IPF_ORM_Exception('Sequence column name attribute only accepts string values');
                }
                break;
            case IPF_ORM::ATTR_FIELD_CASE:
                if ($value != 0 && $value != CASE_LOWER && $value != CASE_UPPER)
                    throw new IPF_ORM_Exception('Field case attribute should be either 0, CASE_LOWER or CASE_UPPER constant.');
                break;
            case IPF_ORM::ATTR_SEQNAME_FORMAT:
            case IPF_ORM::ATTR_IDXNAME_FORMAT:
            case IPF_ORM::ATTR_TBLNAME_FORMAT:
                if ($this instanceof IPF_ORM_Table) {
                    throw new IPF_ORM_Exception('Sequence / index name format attributes cannot be set'
                                               . 'at table level (only at connection or global level).');
                }
                break;
            default:
                throw new IPF_ORM_Exception("Unknown attribute.");
        }

        $this->attributes[$attribute] = $value;
    }

    public function getParams($namespace = null)
    {
    	if ($namespace == null) {
    	    $namespace = $this->getAttribute(IPF_ORM::ATTR_DEFAULT_PARAM_NAMESPACE);
    	}
    	
    	if ( ! isset($this->_params[$namespace])) {
    	    return null;
    	}

        return $this->_params[$namespace];
    }
    
    public function getParamNamespaces()
    {
        return array_keys($this->_params);
    }

    public function setParam($name, $value, $namespace = null) 
    {
    	if ($namespace == null) {
    	    $namespace = $this->getAttribute(IPF_ORM::ATTR_DEFAULT_PARAM_NAMESPACE);
    	}
    	
    	$this->_params[$namespace][$name] = $value;
    	
    	return $this;
    }
    
    public function getParam($name, $namespace = null) 
    {
    	if ($namespace == null) {
    	    $namespace = $this->getAttribute(IPF_ORM::ATTR_DEFAULT_PARAM_NAMESPACE);
    	}
    	
        if ( ! isset($this->_params[$name])) {
            if (isset($this->parent)) {
                return $this->parent->getParam($name, $namespace);
            }
            return null;
        }
        
        return $this->_params[$namespace][$name];
    }

    public function setImpl($template, $class)
    {
        $this->_impl[$template] = $class;

        return $this;
    }

    public function getImpl($template)
    {
        if ( ! isset($this->_impl[$template])) {
            if (isset($this->parent)) {
                return $this->parent->getImpl($template);
            }
            return null;
        }
        return $this->_impl[$template];
    }
    
    
    public function hasImpl($template)
    {
        if ( ! isset($this->_impl[$template])) {
            if (isset($this->parent)) {
                return $this->parent->hasImpl($template);
            }
            return false;
        }
        return true;
    }

    public function addRecordListener($listener, $name = null)
    {
        if ( ! isset($this->attributes[IPF_ORM::ATTR_RECORD_LISTENER]) ||
             ! ($this->attributes[IPF_ORM::ATTR_RECORD_LISTENER] instanceof IPF_ORM_Record_Listener_Chain)) {

            $this->attributes[IPF_ORM::ATTR_RECORD_LISTENER] = new IPF_ORM_Record_Listener_Chain();
        }
        $this->attributes[IPF_ORM::ATTR_RECORD_LISTENER]->add($listener, $name);

        return $this;
    }

    public function getRecordListener()
    {
        if ( ! isset($this->attributes[IPF_ORM::ATTR_RECORD_LISTENER])) {
            if (isset($this->parent)) {
                return $this->parent->getRecordListener();
            }
            return null;
        }
        return $this->attributes[IPF_ORM::ATTR_RECORD_LISTENER];
    }

    public function setRecordListener($listener)
    {
        if ( ! ($listener instanceof IPF_ORM_Record_Listener_Interface)
            && ! ($listener instanceof IPF_ORM_Overloadable)
        ) {
            throw new IPF_ORM_Exception("Couldn't set eventlistener. Record listeners should implement either IPF_ORM_Record_Listener_Interface or IPF_ORM_Overloadable");
        }
        $this->attributes[IPF_ORM::ATTR_RECORD_LISTENER] = $listener;

        return $this;
    }

    public function getAttribute($attribute)
    {
        if (is_string($attribute)) {
            $upper = strtoupper($attribute);

            $const = 'IPF_ORM::ATTR_' . $upper; 

            if (defined($const)) {
                $attribute = constant($const);
                $this->_state = $attribute;
            } else {
                throw new IPF_ORM_Exception('Unknown attribute: "' . $attribute . '"');
            }
        }

        $attribute = (int) $attribute;

        if ($attribute < 0) {
            throw new IPF_ORM_Exception('Unknown attribute.');
        }

        if (isset($this->attributes[$attribute])) {
            return $this->attributes[$attribute];
        }
        
        if (isset($this->parent)) {
            return $this->parent->getAttribute($attribute);
        }
        return null;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setParent(IPF_ORM_Configurable $component)
    {
        $this->parent = $component;
    }

    public function getParent()
    {
        return $this->parent;
    }
}
