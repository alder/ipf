<?php

// This is a index stub for a IPF Projects

$here = dirname(__FILE__);
$project_path = $here . '/../project';
require $project_path . '/vendor/andy128k/ipf/ipf.php';
return IPF::setUp($project_path, $here) && IPF_Project::getInstance()->run();

