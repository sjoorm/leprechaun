<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-18
 */
namespace base\components;
/**
 * Class View static class used for view files rendering
 * @package base\components
 */
class View {

    /**
     * Selects proper view and layout template files and renders it
     * @param string $template view template file name
     * @param array $data parameters to pass
     * @param string $layout layout file name
     * @param boolean $return <b>TRUE</b> if needed to return content as string, <b>FALSE</b> if output
     * @param boolean $json if just JSON data with correct header needed
     * @return null|string
     */
    public static function render($template, $data = array(), $layout = null, $return = false, $json = false) {
        if($json) {
            $json = json_encode($data);

            if($return) {
                return $json;
            } else {
                header('Content-Type: application/json');
                echo $json;
                return null;
            }
        } else {
            // Define template variables
            foreach ($data as $param_name => $value) {
                $$param_name = $value;
            }

            // Determine if user view exists
            $template = file_exists(App::$path . "views/$template.php") ?
                App::$path . "views/$template.php" :
                App::$basePath . "views/$template.php";
            //TODO: add file not found exception and other exception classes

            ob_start();
            require($template);
            $content = ob_get_clean();

            $result = null;
            if(isset($layout) && $layout) {
                // Determine if user layout exists
                $layout = file_exists(App::$path . "views/$layout.php") ?
                    App::$path . "views/$layout.php" :
                    App::$basePath . "views/$layout.php";
                ob_start();
                require($layout);
                $result = ob_get_clean();
            } else {
                $result = &$content;
            }

            if($return) {
                return $result;
            } else {
                echo $result;
                return null;
            }
        }
    }
}
