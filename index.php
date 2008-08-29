<?php

// This is a index stub for a IPF Projects

$ipf_path = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'ipf';
$project_path = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'project';
set_include_path($ipf_path.PATH_SEPARATOR.$project_path);
require 'ipf.php';
IPF::boot($ipf_path, $project_path);
IPF_Project::getInstance()->run();
