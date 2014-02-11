<?php
$path = __DIR__ . DIRECTORY_SEPARATOR;
require_once($path.'base/components/App.php');
$config = require_once($path.'config/config.php');
$configLocal = require_once($path.'config/config.local.php');
$configDB = require_once($path.'config/db.php');
$config = array_merge($config, $configDB, $configLocal);
$parameters = require_once($path.'config/params.php');

base\components\App::init($config, $parameters, $path);
base\components\App::run();
