<?php

class IPF_ORM_Exception_Validator extends IPF_ORM_Exception
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

    private function generateMessage()
    {
        $message = "";
        foreach ($this->invalid as $record) {
            $errors = array();
            foreach ($record->getErrors() as $field => $validators)
                $errors[] = 'Field "' . $field . '" failed following validators: ' . implode(', ', $validators) . '.';
            $message .= "Validaton error in class " . get_class($record) . ' (' . implode(' ', $errors) . ') ';
        }
        return $message;
    }
}

