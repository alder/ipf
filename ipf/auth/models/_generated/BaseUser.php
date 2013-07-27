<?php

/**
 * This class has been auto-generated by the IPF_ORM Framework.
 * Changes to this file may cause incorrect behavior
 * and will be lost if the code is regenerated.
 */

abstract class BaseUser extends IPF_ORM_Record
{
  public function setTableDefinition()
  {
    $table = $this->getTable();
    $table->setTableName('auth_user');
    $table->setColumn('username', 'string', 32, array('type' => 'string', 'notblank' => true, 'notnull' => true, 'unique' => true, 'length' => '32'));
    $table->setColumn('password', 'string', 128, array('type' => 'string', 'notblank' => true, 'notnull' => true, 'length' => '128'));
    $table->setColumn('first_name', 'string', 32, array('type' => 'string', 'length' => '32'));
    $table->setColumn('last_name', 'string', 32, array('type' => 'string', 'length' => '32'));
    $table->setColumn('email', 'string', 200, array('type' => 'string', 'email' => true, 'notnull' => true, 'notblank' => true, 'unique' => true, 'length' => '200'));
    $table->setColumn('is_staff', 'boolean', null, array('type' => 'boolean', 'notnull' => true, 'default' => false));
    $table->setColumn('is_active', 'boolean', null, array('type' => 'boolean', 'notnull' => true, 'default' => false));
    $table->setColumn('is_superuser', 'boolean', null, array('type' => 'boolean', 'notnull' => true, 'default' => false));
    $table->setColumn('last_login', 'timestamp', null, array('type' => 'timestamp'));
    $table->setOption('type', 'INNODB');
    $table->setOption('collate', 'utf8_unicode_ci');
    $table->setOption('charset', 'utf8');

  }

  public function setUp()
  {
    $table = $this->getTable();
    $table->hasMany('Role', 'Roles', array('refClass' => 'UserRole', 'local' => 'user_id', 'foreign' => 'role_id'));
    $table->hasMany('Permission', 'Permissions', array('refClass' => 'UserPermission', 'local' => 'user_id', 'foreign' => 'permission_id'));
    $table->hasMany('UserRole', '', array('local' => 'id', 'foreign' => 'user_id'));
    $table->hasMany('UserPermission', '', array('local' => 'id', 'foreign' => 'user_id'));
    $table->addTemplate(new IPF_ORM_Template_Timestampable());
  }

  public static function table()
  {
    return IPF_ORM::getTable('User');
  }

  public static function query($alias='')
  {
    return IPF_ORM::getTable('User')->createQuery($alias);
  }
}