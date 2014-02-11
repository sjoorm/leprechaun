<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-18
 */
namespace base\components;
/**
 * Class UserIdentity
 * Static class for manipulating with User identity
 * @package base\components
 */
class UserIdentity {
    /* @var Model $_identity */
    protected static $_identity = null;

    /**
     * No construct available - static class
     */
    private final function __construct() {
        //prevent constructing
    }

    /**
     * No clone available - static class
     */
    public final function __clone() {
        trigger_error('Clone is not allowed for ' . __CLASS__, E_USER_ERROR);
    }

    /**
     * Sets user identity to specified User object
     * @param $identity Model
     */
    static protected final function setIdentity($identity) {
        static::$_identity = $identity;
    }

    /**
     * Gets current User identity
     * Should be overridden if code complete is necessary
     * @return Model
     */
    static public final function getIdentity() {
        return static::$_identity;
    }

    /**
     * Checks if user was logged in (isset($_identity))
     * @return boolean
     */
    static public final function isLoggedIn() {
        return isset(static::$_identity);
    }

    /**
     * Checks if user have correct userId and token in cookies
     * Sets the <b>identity</b> field to correct User entry if success
     * @param Model $userModel class name of User Model
     * @return boolean <b>true</b> if User is logged in, <b>false</b> otherwise
     */
    static public function login($userModel) {
        $result = false;

        if(class_exists($userModel) && session_status() === PHP_SESSION_ACTIVE) { //TODO: throw exception if class does not exists
            if(isset ($_COOKIE['userId']) && isset($_COOKIE['userToken'])) {
                $user = $userModel::getByPk($_COOKIE['userId']);
                if($user instanceof $userModel && $user->checkSessionKey($_COOKIE['userToken'], $_SERVER['HTTP_USER_AGENT'])) {
                    $result = true;
                    self::setIdentity($user);
                }

                if(!$result) {
                    $expire = time() - 2592000; //30 days
                    setcookie('userId', '', $expire, '/');
                    setcookie('userToken', '', $expire, '/');
                    setcookie(App::$applicationName, '', $expire, '/');
                }
            }
            if(!$result) {
                App::sessionDestroy();
            }
        }

        return $result;
    }

    /**
     * Logout action for current user
     */
    static public function logout() {
        $expire = time() - 2592000;//30 days
        setcookie('userId', '', $expire, '/');
        setcookie('userToken', '', $expire, '/');
        setcookie(App::$applicationName, '', $expire, '/');
        App::sessionDestroy();
    }
}
