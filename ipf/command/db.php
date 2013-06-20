<?php

class IPF_Command_DB
{
    public $command = 'db';
    public $description = 'Database console';

    public function run($args=null)
    {
        $db = IPF_ORM_Manager::getInstance()->connectionParameters(IPF::get('database', IPF::get('dsn')));

        if ($db['scheme'] === 'mysql') {
            IPF_Shell::call('mysql',
                '-h'.$db['host'],
                '-u'.$db['username'],
                '-p'.$db['password'],
                $db['database']);
        } else {
            print 'Do not know how to connect to "'.$db['scheme'].'" database.';
        }
    }
}

