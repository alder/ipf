<?php

/**
 * This class has been auto-generated by the IPF_ORM Framework
 */
abstract class BaseUser extends IPF_ORM_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('auth_user');
    $this->hasColumn('username', 'string', 32, array('type' => 'string', 'notblank' => true, 'notnull' => true, 'unique' => true, 'length' => '32'));
    $this->hasColumn('password', 'string', 128, array('type' => 'string', 'notblank' => true, 'notnull' => true, 'length' => '128'));
    $this->hasColumn('first_name', 'string', 32, array('type' => 'string', 'length' => '32'));
    $this->hasColumn('last_name', 'string', 32, array('type' => 'string', 'length' => '32'));
    $this->hasColumn('email', 'string', 200, array('type' => 'string', 'email' => true, 'notnull' => true, 'notblank' => true, 'unique' => true, 'length' => '200'));
    $this->hasColumn('is_staff', 'boolean', null, array('type' => 'boolean', 'notnull' => true, 'default' => false));
    $this->hasColumn('is_active', 'boolean', null, array('type' => 'boolean', 'notnull' => true, 'default' => false));
    $this->hasColumn('is_superuser', 'boolean', null, array('type' => 'boolean', 'notnull' => true, 'default' => false));
    $this->hasColumn('last_login', 'timestamp', null, array('type' => 'timestamp'));

    $this->option('type', 'INNODB');
    $this->option('collate', 'utf8_unicode_ci');
    $this->option('charset', 'utf8');
  }

  public function setUp()
  {
    $this->hasMany('Role as Roles', array('refClass' => 'UserRole',
                                          'local' => 'user_id',
                                          'foreign' => 'role_id'));

    $this->hasMany('Permission as Permissions', array('refClass' => 'UserPermission',
                                                      'local' => 'user_id',
                                                      'foreign' => 'permission_id'));

    $this->hasMany('UserRole', array('local' => 'id',
                                     'foreign' => 'user_id'));

    $this->hasMany('UserPermission', array('local' => 'id',
                                           'foreign' => 'user_id'));

    $timestampable0 = new IPF_ORM_Template_Timestampable();
    $this->actAs($timestampable0);
  }
}