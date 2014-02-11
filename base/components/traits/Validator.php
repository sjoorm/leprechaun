<?php
/**
 * @author sjoorm
 * date: 2013-12-22
 */
namespace base\components\traits;
use base\components\App;
/**
 * Class Validator
 *
 * Static trait for validation purposes - contains methods for specific cases
 * @package \base\components\traits
 */
trait Validator {

    /**
     * Checks if attribute was set
     * @param mixed $attribute
     * @param mixed $param not used here
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function required($attribute, $param, &$errorMsg = null) {
        $result = isset($attribute);

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Field is required.";
        }

        return $result;
    }

    /**
     * Checks if attribute was starts with given string
     * @param mixed $attribute
     * @param string $param must start with this string
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function startsWith(&$attribute, $param, &$errorMsg = null) {
        $result = !$attribute || $param === '' || strpos($attribute, $param) === 0;

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Field must be starts with '$param'.";
        }
        return  $result;
    }

    /**
     * Checks if attribute was set
     * @param mixed $attribute will be converted if correct
     * @param mixed $param type
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function type(&$attribute, $param, &$errorMsg = null) {
        $result = null;

        switch($param) {
            case 'bool':
                $attribute = isset($attribute);
                $result = $attribute;
                break;
            case 'int':
                $intVal = intval($attribute);
                $floatVal = floatval($attribute);
                $result = (is_int($attribute)) || (is_numeric($attribute) && ($intVal == $floatVal));
                $attribute = $result ? intval($attribute) : $attribute;
                break;
            case 'float':
                $intVal = intval($attribute);
                $floatVal = floatval($attribute);
                $result = (is_float($attribute)) || (is_numeric($attribute) && ($intVal != $floatVal));
                $attribute = $result ? floatval($attribute) : $attribute;
                break;
            case 'numeric':
                $intVal = intval($attribute);
                $floatVal = floatval($attribute);
                $result = is_numeric($attribute);
                $attribute = $result ? (($floatVal == $intVal) ? $intVal : $floatVal) : $attribute;
                break;
            default:
                $result = call_user_func("is_$param", $attribute);
                break;
        }

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Field must be $param.";
        }

        return $result;
    }

    /**
     * Checks if given attribute is a valid email address
     * @param string $attribute
     * @param mixed $param not used here
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function email($attribute, $param, &$errorMsg = null) {
        $result = (gettype($attribute) === 'string') ? filter_var($attribute, FILTER_VALIDATE_EMAIL) : false;

        if(!$result && isset($errorMsg)) {
            $errorMsg = 'Is not a correct email address.';
        }

        return $result;
    }

    /**
     * Checks if given attribute is not less than declared
     * @param string $attribute
     * @param mixed $param minimal allowed length
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function lengthMin($attribute, $param, &$errorMsg = null) {
        $result = strlen((string)$attribute) >= $param;

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Minimal length is $param symbols.";
        }

        return $result;
    }

    /**
     * Checks if given attribute is not longer than declared
     * @param string $attribute
     * @param mixed $param maximal allowed length
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function lengthMax($attribute, $param, &$errorMsg = null) {
        $result = strlen((string)$attribute) <= $param;

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Maximal length is $param symbols.";
        }

        return $result;
    }

    /**
     * Checks if given attribute is greater than or equals declared value
     * @param string $attribute
     * @param mixed $param minimal allowed value
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function valueMin($attribute, $param, &$errorMsg = null) {
        $result = false;

        $type = gettype($param);
        if($type === 'integer') {
            $result = (int)$attribute >= $param;
        } elseif ($type === 'float' || $type === 'double') {
            $result = (float)$attribute >= $param;
        }

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Minimal allowed value is $param.";
        }

        return $result;
    }

    /**
     * Checks if given attribute is less than or equals declared value
     * @param string $attribute
     * @param mixed $param maximal allowed value
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function valueMax($attribute, $param, &$errorMsg = null) {
        $result = false;

        $type = gettype($param);
        if($type === 'integer') {
            $result = (int)$attribute <= $param;
        } elseif ($type === 'float' || $type === 'double') {
            $result = (float)$attribute <= $param;
        }

        if(!$result && isset($errorMsg)) {
            $errorMsg = "Maximal allowed value is $param.";
        }

        return $result;
    }

    /**
     * Checks if given attribute is in allowed <b>variants</b> set
     * @param string $attribute
     * @param array $param allowed variants
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function variants($attribute, $param, &$errorMsg = null) {
        $result = in_array($attribute, $param);

        if(!$result && isset($errorMsg)) {

            $errorMsg = 'Only limited attribute values enabled: [' . implode(',', $param) . '].';
        }

        return $result;
    }

    /**
     * Checks url is valid.
     * @param mixed $attribute
     * @param mixed $param rule parameters
     * @param string $errorMsg error message returns here
     * @return boolean
     */
    protected static function url($attribute, $param, &$errorMsg = null) {
        $result = filter_var($attribute, FILTER_VALIDATE_URL);

        if(!$result && isset($errorMsg)) {
            $errorMsg = 'URL is not valid.';
        }

        return $result;
    }

    /**
     * Checks if given CSRF token is valid
     * @param string $attribute
     * @param null $param
     * @param string $errorMsg
     * @return boolean
     */
    protected static function csrfToken($attribute, $param, &$errorMsg = null) {
        $result = App::csrfTokenCheck($attribute);

        if(!$result && isset($errorMsg)) {
            $errorMsg = 'CSRF token is not valid.';
        }

        return $result;
    }

    /**
     * Checks if given string matches specified regular expression
     * @param string $attribute
     * @param string $param
     * @param string $errorMsg
     * @return boolean
     */
    protected static function pregMatch($attribute, $param, &$errorMsg = null) {
        $result = preg_match($param, $attribute);

        if(!$result && isset($errorMsg)) {
            $errorMsg = 'Wrong format.';
        }

        return $result;
    }

    /**
     * Checks if given string equals to specified string
     * @param string $attribute
     * @param string $param
     * @param string $errorMsg
     * @return boolean
     */
    protected static function equals($attribute, $param, &$errorMsg = null) {
        $result = $attribute === $param;

        if(!$result && isset($errorMsg)) {
            $errorMsg = 'Input value is incorrect.';
        }

        return $result;
    }

    /**
     * Checks if given string has only [a-z\-_0-9]
     * @param string $attribute
     * @param null $param
     * @param string $errorMsg
     * @return boolean
     */
    protected static function uriString($attribute, $param, &$errorMsg = null) {
        $result = preg_match('/^[a-zA-Z0-9_-\s]+$/', $attribute);

            if(!$result && isset($errorMsg)) {
                $errorMsg = 'Only alphabetic chars, digits, dash and underscore are enabled.';
            }

        return $result;
    }
}
