<?php

$project_root = dirname(__FILE__).DIRECTORY_SEPARATOR.'..';

$set = array();
$set['dsn'] = 'mysql://fake:fake@localhost/fake';

$set['tmp'] = $project_root . '/tmp';

$set['upload_url'] = '/media/upload/';
$set['upload_path'] = $project_root . '/htdocs/media/upload/';

$set['secret_key'] = '123456';

$set['debug'] = true;

$set['applications'] = array(
    'IPF_Session',
    'IPF_Auth',
    'IPF_Admin',
);

$set['middlewares'] = array(
    'IPF_Middleware_Common',
    'IPF_Session_Middleware',
);

$set['urls'] = array();

return $set;

