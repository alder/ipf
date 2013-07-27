<?php

/**
 * This class has been auto-generated by the IPF_ORM Framework.
 * Changes to this file may cause incorrect behavior
 * and will be lost if the code is regenerated.
 */

abstract class BaseRolePermission extends IPF_ORM_Record
{
  public function setTableDefinition()
  {
    $table = $this->getTable();
    $table->setTableName('auth_role_permission');
    $table->setColumn('role_id', 'integer', null, array('type' => 'integer', 'primary' => true));
    $table->setColumn('permission_id', 'integer', null, array('type' => 'integer', 'primary' => true));
    $table->setOption('type', 'INNODB');
    $table->setOption('collate', 'utf8_unicode_ci');
    $table->setOption('charset', 'utf8');

  }

  public function setUp()
  {
    $this->hasOne('Role', array('local' => 'role_id',
                                'foreign' => 'id',
                                'onDelete' => 'CASCADE'));

    $this->hasOne('Permission', array('local' => 'permission_id',
                                      'foreign' => 'id',
                                      'onDelete' => 'CASCADE'));
  }

  public static function table()
  {
    return IPF_ORM::getTable('RolePermission');
  }

  public static function query($alias='')
  {
    return IPF_ORM::getTable('RolePermission')->createQuery($alias);
  }
}