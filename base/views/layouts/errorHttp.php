<?php
namespace base\views;
use base\components\App;
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-18
 */
/* @var string $content */
/* @var integer $code */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title><?= App::$applicationName ?> :: Error <?= $code ?></title>
</head>
<body>
    <?= $content ?>
</body>
</html>
