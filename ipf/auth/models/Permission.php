<?php

class Permission extends BasePermission
{
    public function __toString()
    {
        return IPF_Auth_App::GetHumanNameOfPermission($this->name);
    }
}
