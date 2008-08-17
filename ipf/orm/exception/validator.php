<?php

class IPF_ORM_Exception_Validator extends IPF_ORM_Exception implements Countable, IteratorAggregate
{
    private $invalid = array();

    public function __construct(array $invalid)
    {
        $this->invalid = $invalid;
        parent::__construct($this->generateMessage());
    }

    public function getInvalidRecords()
    {
        return $this->invalid;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->invalid);
    }

    public function count()
    {
        return count($this->invalid);
    }

    public function __toString()
    {

        return parent::__toString();
    }

    private function generateMessage()
    {
        $message = "";
        foreach ($this->invalid as $record) {
           $message .= "Validaton error in class " . get_class($record) . " ";
        }
        return $message;
    }

    public function inspect($function)
    {
        foreach ($this->invalid as $record) {
            call_user_func($function, $record->getErrorStack());
        }
    }
}