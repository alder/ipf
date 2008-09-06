<?php

class IPF_Cli{

    protected $commands;

    public function __construct(){
        $this->commands = array('help','sql','buildmodels','buildcontribmodels','syncdb', 'createsuperuser');
    }
    
    protected function usage(&$args){
        print "Type 'php index.php help' for usage.\n";
    }

    protected function main_help(&$args){
        print "php index.php <subcommand> [options] [args]\n";
        print "Available subcommands:\n";
        foreach ($this->commands as &$command)
            print "  $command\n";
    }
    
    protected function help($args){
        if (count($args)==2)
            $this->main_help($args);
    }
    
    protected function sql(&$args){
        print "Show All Sql DDL From Model Classes\n";
        print IPF_Project::getInstance()->generateSql();
    }

    protected function syncdb(&$args){
        print "Create Tables From Model Classes\n";
        IPF_Project::getInstance()->createTablesFromModels();
    }

    protected function buildmodels(&$args){
        print "Build All Model Classses\n";
        IPF_Project::getInstance()->generateModels();
    }

    protected function buildcontribmodels(&$args){
        print "Build All Contrib Model Classses\n";
        IPF_Project::getInstance()->generateContribModels();
    }


    protected function createSuperUser(&$args){
        print "Create SuperUser\n";

        IPF_Project::getInstance()->loadModels();

        $username = '';  while ($username==''){  print "  Username: "; $username = trim(fgets(STDIN)); };
        $password = '';  while ($password==''){  print "  Password: "; $password = trim(fgets(STDIN)); };
        $email = '';  while ($email==''){  print "  e-mail: "; $email = trim(fgets(STDIN)); };
        
        $su = new User();
        $su->username = $username;
        $su->email = $email;
        $su->is_staff = true;
        $su->is_active = true;
        $su->is_superuser = true;
        $su->setPassword($password);
        $su->save();
        print "Done\n";
    }

    public function run(){
        
        print "IPF command line tool. Version: ".IPF_Version::$name."\n";
        print "Project config: ".IPF::get('settings_file')."\n";
        
        $opt  = new IPF_Getopt();
        //$z = $opt->getopt2($opt->readPHPArgv(), array('s',)); //, array('s',));
        $args = $opt->readPHPArgv();
        if (count($args)==1)
        {
            $this->usage($args);
            return;
        }
            
        if (in_array($args[1],$this->commands))
        {
            eval('$this->'.$args[1].'($args);');
            return;
        }
        
        print "Unknown command: '".$args[1]."'\n";
        $this->usage($args);
    }
}