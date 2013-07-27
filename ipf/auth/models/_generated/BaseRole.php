<?php

/**
 * This class has been auto-generated by the IPF_ORM Framework.
 * Changes to this file may cause incorrect behavior
 * and will be lost if the code is regenerated.
 */

abstract class BaseRole extends IPF_ORM_Record
{
  public function setTableDefinition()
  {
    $table = $this->getTable();
    $table->setTableName('auth_role');
    $this->getTable()->setColumn('name', 'string', 255, array('unique' => true, 'type' => 'string', 'notblank' => true, 'length' => '255'));
    $table->setOption('type', 'INNODB');
    $table->setOption('collate', 'utf8_unicode_ci');
    $table->setOption('charset', 'utf8');

  }

  public function setUp()
  {
    $this->hasMany('Permission as Permissions', array('refClass' => 'RolePermission',
                                                      'local' => 'role_id',
                                                      'foreign' => 'permission_id'));

    $this->hasMany('User as Users', array('refClass' => 'UserRole',
                                          'local' => 'role_id',
                                          'foreign' => 'user_id'));

    $this->hasMany('RolePermission', array('local' => 'id',
                                           'foreign' => 'role_id'));

    $this->hasMany('UserRole', array('local' => 'id',
                                     'foreign' => 'role_id'));
  }

  public static function table()
  {
    return IPF_ORM::getTable('Role');
  }

  public static function query($alias='')
  {
    return IPF_ORM::getTable('Role')->createQuery($alias);
  }
}