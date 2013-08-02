<?php

class IPF_Cli
{
    protected $commands;

    public function __construct()
    {
        $this->commands = array(
            new IPF_Command_DebugServer,
            new IPF_Command_Routes,
            new IPF_Command_BuildModels,
            new IPF_Command_BuildContribModels,
            new IPF_Command_Sql,
            new IPF_Command_SyncDB,
            new IPF_Command_Fixtures,
            new IPF_Command_DB,
            new IPF_Command_DBDump,
            new IPF_Command_DBRestore,
            new IPF_Command_CollectStatic,
            new IPF_Command_Pack,
            new IPF_Command_Unpack,
            new IPF_Command_CreateSuperUser,
            new IPF_Command_SyncPerms,
        );
        
        foreach (IPF::get('commands', array()) as $cmd) {
            if (is_string($cmd))
                $cmd = new $cmd;
            $this->commands[] = $cmd;
        }
    }

    protected function usage()
    {
        print "Usage: php index.php <subcommand> [options] [args]\n\n";
        print "Available subcommands:\n";

        $rows = array();
        foreach ($this->commands as $command)
            $rows[] = array(
                '    ' . $command->command,
                $command->description,
            );

        IPF_Shell::displayTwoColumns($rows);

        print "\n";
    }

    private function getArgs()
    {
        global $argv;
        if (is_array($argv))
            return $argv;
        if (@is_array($_SERVER['argv']))
            return $_SERVER['argv'];
        if (@is_array($GLOBALS['HTTP_SERVER_VARS']['argv']))
            return $GLOBALS['HTTP_SERVER_VARS']['argv'];
        throw new IPF_Exception("IPF_Cli: Could not read command arguments (register_argc_argv=Off?)");
    }

    public function run()
    {
        print "IPF command line tool. Version: ".IPF_Version::$name."\n";
        print "Project config: ".IPF::get('settings_file')."\n\n";

        $args = $this->getArgs();

        if (count($args) < 2) {
            $this->usage();
            return;
        }

        foreach ($this->commands as $command) {
            if ($command->command === $args[1]) {
                $command->run(array_slice($args, 2));
                return;
            }
        }

        print "Unknown command: '".$args[1]."'\n\n";
        $this->usage();
    }
}

