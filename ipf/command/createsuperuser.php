<?php

class IPF_Command_CreateSuperUser
{
    public $command = 'createsuperuser';
    public $description = 'Create superuser';

    public function run($args=null)
    {
        print "Create superuser\n";

        $username = $this->readString("  Username: ");
        $password = $this->readString("  Password: ");
        $email    = $this->readString("  E-mail: ");

        $project = IPF_Project::getInstance();

        $project->loadModels();

        $su = new User;
        $su->username     = $username;
        $su->email        = $email;
        $su->is_staff     = true;
        $su->is_active    = true;
        $su->is_superuser = true;
        $su->setPassword($password);
        $su->save();
        print "Done\n";
    }

    private function readString($prompt)
    {
        $value = '';
        while (!$value) {
            print $prompt;
            $value = trim(fgets(STDIN));
        }
        return $value;
    }
}

