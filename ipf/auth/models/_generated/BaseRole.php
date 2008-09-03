<?php

/**
 * This class has been auto-generated by the IPF_ORM Framework
 */
abstract class BaseRole extends IPF_ORM_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('auth_role');
    $this->hasColumn('name', 'string', 255, array('unique' => true, 'type' => 'string', 'length' => '255'));

    $this->option('type', 'INNODB');
    $this->option('collate', 'utf8_unicode_ci');
    $this->option('charset', 'utf8');
  }

  public function setUp()
  {
    $this->hasMany('User as Users', array('refClass' => 'UserRole',
                                          'local' => 'role_id',
                                          'foreign' => 'user_id'));

    $this->hasMany('RolePermission', array('local' => 'id',
                                           'foreign' => 'role_id'));

    $this->hasMany('UserRole', array('local' => 'id',
                                     'foreign' => 'role_id'));
  }
}