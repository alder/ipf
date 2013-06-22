<?php

class IPF_Command_DBDump
{
    public $command = 'dbdump';
    public $description = 'Dumps database to a file';

    public function run($args=null)
    {
        $output = 'dump.sql';

        if (!in_array('quiet', $args))
            print "Dumping database to file $output\n";

        $db = IPF_ORM_Manager::getInstance()->connectionParameters(IPF::get('database', IPF::get('dsn')));

        if ($db['scheme'] === 'mysql') {
            IPF_Shell::call('mysqldump',
                '-h'.$db['host'],
                '-u'.$db['username'],
                '-p'.$db['password'],
                '-r'.$output,
                $db['database']);
        } else {
            print 'Do not know how to connect to "'.$db['scheme'].'" database.';
        }
    }
}

