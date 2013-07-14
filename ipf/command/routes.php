<?php

class IPF_Command_Routes
{
    public $command = 'routes';
    public $description = 'Displays all routes';

    public function run($args=null)
    {
        $rows = IPF_Project::getInstance()->router->describe();
        IPF_Shell::displayTwoColumns($rows);
    }
}

