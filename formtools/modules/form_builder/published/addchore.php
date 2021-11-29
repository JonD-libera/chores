<?php

/**
 * This file was created by the Form Tools Form Builder module.
 */
require_once('/var/www/html/formtools/global/library.php');
use FormTools\Core;
Core::init(array("auto_logout" => false));
$root_dir = Core::getRootDir();
$published_form_id = 1;
$filename  = "addchore.php";
require_once("$root_dir/modules/form_builder/form.php");