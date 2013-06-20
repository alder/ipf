<?php

class IPF_Command_Routes
{
    public $command = 'routes';
    public $description = 'Displays all routes';

    public function run($args=null)
    {
        $rows = IPF_Router::describe();
        IPF_Shell::displayTwoColumns($rows);
    }
}

