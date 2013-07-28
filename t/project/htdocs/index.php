<?php

$here = dirname(__FILE__);
$ipf_path     = $here . '/../../..';
$project_path = $here . '/../project';

require $ipf_path . '/ipf.php';
return IPF::setUp($project_path, $here, $ipf_path . '/vendor'); // && IPF_Project::getInstance()->run();

