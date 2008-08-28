<?php

class IPF_Form implements Iterator
{
    public $fields = array();

    public $prefix = '';
    public $id_fields = 'id_%s';
    public $data = array();
    public $cleaned_data = array();
    public $errors = array();
    public $is_bound = false;
    public $f = null;
    public $label_suffix = ':';

    protected $is_valid = null;
    

    function __construct($data=null, $extra=array(), $label_suffix=null)
    {
        if ($data !== null) {
            $this->data = $data;
            $this->is_bound = true;
        }
        if ($label_suffix !== null) $this->label_suffix = $label_suffix;

        $this->initFields($extra);
        $this->f = new IPF_Form_FieldProxy($this);
    }

    function initFields($extra=array())
    {
        throw new IPF_Exception('Definition of the fields not implemented.');
    }

    function addPrefix($field_name)
    {
        if ('' !== $this->prefix) {
            return $this->prefix.'-'.$field_name;
        }
        return $field_name;
    }
    
    function hasFileField(){
        foreach($this->fields as $field){
            if (is_a($field,'IPF_Form_Field_File')){
                return true;
            }
        }
        return false;
    }

    function isValid()
    {
        if ($this->is_valid !== null) {
            return $this->is_valid;
        }
        $this->cleaned_data = array();
        $this->errors = array();
        $form_methods = get_class_methods($this);
        foreach ($this->fields as $name=>$field) {
            $value = $field->widget->valueFromFormData($this->addPrefix($name),
                                                       $this->data); 
            try {
                $value = $field->clean($value);
                $this->cleaned_data[$name] = $value;
                if (in_array('clean_'.$name, $form_methods)) {
                    $m = 'clean_'.$name;
                    $value = $this->$m();
                    $this->cleaned_data[$name] = $value;
                }                        
            } catch (IPF_Exception_Form $e) {
                if (!isset($this->errors[$name])) $this->errors[$name] = array();
                $this->errors[$name][] = $e->getMessage();
                if (isset($this->cleaned_data[$name])) {
                    unset($this->cleaned_data[$name]);
                }
            }
        }
        try {
            $this->cleaned_data = $this->clean();
        } catch (IPF_Exception_Form $e) {
            if (!isset($this->errors['__all__'])) $this->errors['__all__'] = array();
            $this->errors['__all__'][] = $e->getMessage();
        }
        if (empty($this->errors)) {
            $this->is_valid = true;
            return true;
        } 
        // as some errors, we do not have cleaned data available.
        $this->cleaned_data = array();
        $this->is_valid = false;
        return false;
    }

    public function clean()
    {
        return $this->cleaned_data;
    }

    public function initial($name)
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name]->initial;
        }
        return '';
    }

    public function render_top_errors()
    {
        $top_errors = (isset($this->errors['__all__'])) ? $this->errors['__all__'] : array();
        array_walk($top_errors, 'IPF_Form_htmlspecialcharsArray');
        return new IPF_Template_SafeString(IPF_Form_renderErrorsAsHTML($top_errors), true);
    }

    public function get_top_errors()
    {
        return (isset($this->errors['__all__'])) ? $this->errors['__all__'] : array();
    }

    protected function htmlOutput($normal_row, $error_row, $row_ender, 
                                  $help_text_html, $errors_on_separate_row)
    {
        $top_errors = (isset($this->errors['__all__'])) ? $this->errors['__all__'] : array();
        array_walk($top_errors, 'IPF_Form_htmlspecialcharsArray');
        $output = array();
        $hidden_fields = array();
        foreach ($this->fields as $name=>$field) {
            $bf = new IPF_Form_BoundField($this, $field, $name);
            $bf_errors = $bf->errors;
            array_walk($bf_errors, 'IPF_Form_htmlspecialcharsArray');
            if ($field->widget->is_hidden) {
                foreach ($bf_errors as $_e) {
                    $top_errors[] = sprintf(__('(Hidden field %1$s) %2$s'),
                                            $name, $_e);
                }
                $hidden_fields[] = $bf; // Not rendered
            } else {
                if ($errors_on_separate_row and count($bf_errors)) {
                    $output[] = sprintf($error_row, IPF_Form_renderErrorsAsHTML($bf_errors));
                }
                if (strlen($bf->label) > 0) {
                    $label = htmlspecialchars($bf->label, ENT_COMPAT, 'UTF-8');
                    if ($this->label_suffix) {
                        if (!in_array(mb_substr($label, -1, 1), 
                                      array(':','?','.','!'))) {
                            $label .= $this->label_suffix;
                        }
                    }
                    if ($field->required)
                        $label_attrs = array('class'=>'required');
                    else
                        $label_attrs = array();
                    $label = $bf->labelTag($label,$label_attrs);
                } else {
                    $label = '';
                }
                if ($bf->help_text) {
                    // $bf->help_text can contains HTML and is not
                    // escaped.
                    $help_text = sprintf($help_text_html, $bf->help_text);
                } else {
                    $help_text = '';
                }
                $errors = '';
                if (!$errors_on_separate_row and count($bf_errors)) {
                    $errors = IPF_Form_renderErrorsAsHTML($bf_errors);
                }
                $output[] = sprintf($normal_row, $errors, $label, 
                                    $bf->render_w(), $help_text);
            }
        }
        if (count($top_errors)) {
            $errors = sprintf($error_row, 
                              IPF_Form_renderErrorsAsHTML($top_errors));
            array_unshift($output, $errors);
        }
        if (count($hidden_fields)) {
            $_tmp = '';
            foreach ($hidden_fields as $hd) {
                $_tmp .= $hd->render_w();
            }
            if (count($output)) {
                $last_row = array_pop($output);
                $last_row = substr($last_row, 0, -strlen($row_ender)).$_tmp
                    .$row_ender;
                $output[] = $last_row;
            } else {
                $output[] = $_tmp;
            }
        }
        return new IPF_Template_SafeString(implode("\n", $output), true);
    }

    public function render_p()
    {
        return $this->htmlOutput('<p>%1$s%2$s %3$s%4$s</p>', '%s', '</p>', 
                                 ' %s', true);
    }

    public function render_ul()
    {
        return $this->htmlOutput('<li>%1$s%2$s %3$s%4$s</li>', '<li>%s</li>', 
                                 '</li>', ' %s', false);
    }

    public function render_table()
    {
        return $this->htmlOutput('<tr><th>%2$s</th><td>%1$s%3$s%4$s</td></tr>',
                                 '<tr><td colspan="2">%s</td></tr>', 
                                 '</td></tr>', '<br /><span class="helptext">%s</span>', false);
    }

    function __get($prop)
    {
        if (!in_array($prop, array('render_p', 'render_ul', 'render_table', 'render_top_errors', 'get_top_errors'))) {
            return $this->$prop;
        }
        return $this->$prop();
    }

    public function field($key)
    {
        return new IPF_Form_BoundField($this, $this->fields[$key], $key);
    }

 	public function current()
    {
        $field = current($this->fields);
        $name = key($this->fields);
        return new IPF_Form_BoundField($this, $field, $name);
    }

 	public function key()
    {
        return key($this->fields);
    }

 	public function next()
    {
        next($this->fields);
    }

 	public function rewind()
    {
        reset($this->fields);
    }

 	public function valid()
    {
        return (false !== current($this->fields));
    }

}

function IPF_Form_htmlspecialcharsArray(&$item, $key)
{
    $item = htmlspecialchars($item, ENT_COMPAT, 'UTF-8');
}

function IPF_Form_renderErrorsAsHTML($errors)
{
    $tmp = array();
    foreach ($errors as $err) {
        $tmp[] = '<li>'.$err.'</li>';
    }
    return '<ul class="errorlist">'.implode("\n", $tmp).'</ul>';
}
