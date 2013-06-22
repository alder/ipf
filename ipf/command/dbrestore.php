<?php

class IPF_Command_DBRestore
{
    public $command = 'dbrestore';
    public $description = 'Restores database from a file';

    public function run($args=null)
    {
        $input = 'dump.sql';

        if (!in_array('--quiet', $args))
            print "Restoring database from file $input\n";

        $db = IPF_ORM_Manager::getInstance()->connectionParameters(IPF::get('database', IPF::get('dsn')));

        if ($db['scheme'] === 'mysql') {
            IPF_Shell::call('mysql',
                '-h'.$db['host'],
                '-u'.$db['username'],
                '-p'.$db['password'],
                '-e',
                'source '.$input,
                $db['database']);
        } else {
            print 'Do not know how to connect to "'.$db['scheme'].'" database.';
        }
    }
}

