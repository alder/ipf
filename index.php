<?php

// This is a index stub for a IPF Projects

$here = dirname(__FILE__);
$ipf_path = $here.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'ipf';
$project_path = $here.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'project';
set_include_path(get_include_path() . PATH_SEPARATOR . $ipf_path . PATH_SEPARATOR . $project_path);
require 'ipf.php';
return IPF::boot($ipf_path, $project_path, $here) && IPF_Project::getInstance()->run();

