<?php
namespace leprechaun;
use base\components\Model;
/**
 * Class User implements `ats_users` record
 *
 * property id is already set in Parent class Model
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $created_at
 * @property string $updated_at
 * @property string $lockout
 * @property string $auth_key
 * @property integer $is_hidden
 *
 * @package \ats
 */
class User extends Model
{
    const AUTH_KEY_SIZE = 32;
    static protected $_table = 'ats_users';
    static protected $_fields = array(
        'id' => array(
            'type' => 'i',
            'rule' => self::RULE_NONE
        ),
        'username' => array(
            'type' => 's',
            'rule' => self::RULE_INSERT
        ),
        'email' => array(
            'type' => 's',
            'rule' => self::RULE_ALL
        ),
        'password' => array(
            'type' => 's',
            'rule' => self::RULE_ALL
        ),
        'created_at' => array(
            'type' => 's',
            'rule' => self::RULE_INSERT
        ),
        'updated_at' => array(
            'type' => 's',
            'rule' => self::RULE_ALL
        ),
        'lockout' => array(
            'type' => 'i',
            'rule' => self::RULE_ALL
        ),
        'auth_key' => array(
            'type' => 's',
            'rule' => self::RULE_ALL
        ),
        'is_hidden' => array(
            'type' => 'i',
            'rule' => self::RULE_ALL
        ),
    );

    /**
     * Specific string conversion for generating authentication keys
     * @param boolean $addUniqueId if unique id should be added
     * @param boolean $addAuthKey if auth_key field should be added
     * @return string
     */
    private function toString($addUniqueId = false, $addAuthKey = false) {
        $result = '';
        foreach($this->_values as $field => &$attr) {
            if((($field === 'auth_key' && $addAuthKey) || $field !== 'auth_key') && isset($attr)) {
                $result .= $attr;
            }
        }
        if($addUniqueId) {
            $result .= uniqid();
        }
        return $result;
    }

    /**
     * Generates key for user-session authentication with cookies
     * @param string $additionalParams USER_AGENT string or something to make authentication more specific
     * @return boolean|string
     */
    public function getSessionKey($additionalParams = '') {
        return password_hash($this->toString() . $additionalParams, PASSWORD_BCRYPT);
    }

    /**
     * Checks if given session key is correct for this user
     * @param $key
     * @param string $additionalParams USER_AGENT string or something to make authentication more specific
     * @return boolean
     */
    public function checkSessionKey($key, $additionalParams = '') {
        return password_verify($this->toString() . $additionalParams, $key);
    }

    /**
     * Generates authentication key for single-time actions like password reset etc.
     * Has `auth_key` field size (AUTH_KEY_SIZE)
     * Has random component - could be verified only with '==='.
     */
    public function generateAuthKey() {
        $this->auth_key = substr(password_hash($this->toString(true), PASSWORD_BCRYPT), -static::AUTH_KEY_SIZE);
    }
}
