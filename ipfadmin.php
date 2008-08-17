<?php
$ipf_path = dirname(__FILE__);
set_include_path($ipf_path);
require 'ipf.php';

print IPF_Version::$name;
