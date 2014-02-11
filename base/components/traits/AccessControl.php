<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-17
 */
namespace base\components\traits;
use base\components\App;
use base\components\UserIdentity;
/**
 * Class AccessControl
 * Static trait for accessRules
 * @package \base\components\traits
 */
trait AccessControl {

    /**
     * Checks if user is authorized, redirects him to login page if not (or sends error message about auth)
     * @param string $param login page URL/URI
     * @param string|null $errorMsg
     * @return boolean|callable
     */
    protected static function authorized($param, &$errorMsg = null) {
        $result = null;

        $isLoggedIn = $param ? UserIdentity::login(App::getUserModel())/*UserIdentity::isLoggedIn()*/ : false;
        if(is_bool($param)) {
            $result = $isLoggedIn === $param;
        } elseif(is_callable($param)) {
            $result = $isLoggedIn ? true : $param;
        }
        if(!$result) {
            $result = function() use($param) {
                App::error403(
                    'You must be ' . ($param ? '' : 'not ') . 'authorized to perform this action'
                );
            };
        }

        return $result;
    }

    /**
     * Checks if request is AJAX or not
     * @param boolean $param which type of request is acceptable: <b>TRUE</b> for AJAX,
     * <b>FALSE</b> otherwise
     * @param string|null $errorMsg
     * @return boolean
     */
    protected static function ajax($param, &$errorMsg = null) {
        $result = App::requestIsAjax() === $param;

        if(!$result && isset($errorMsg)) {
            $errorMsg = 'Only ' . ($param ? '' : 'not-') . 'AJAX request acceptable.';
        }

        return $result;
    }

    protected static function method($param, &$errorMsg = null) {
        $result = App::requestMethod($param);

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Only $param request method acceptable.";
        }

        return $result;
    }
}
