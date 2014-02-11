<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-02-11
 */
use base\components\App;
use base\components\Controller;
/* @var string $content */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <link href="<?= App::$staticUrl ?>css/main.css"
          rel="stylesheet"/>
    <script src="<?= App::$staticUrl ?>js/main.js">
    </script>
</head>
<body>
<?= $content ?>
</body>
</html>
