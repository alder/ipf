<?php

// This is a index stub for a IPF Projects

$ipf_path = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
$project_path = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'project';
set_include_path(get_include_path() . PATH_SEPARATOR . $ipf_path . PATH_SEPARATOR . $project_path);
require 'ipf.php';
IPF::boot($ipf_path, $project_path);
// return IPF_Project::getInstance()->run();
