<?php
/**
 * @author sjoorm
 * date: 2013-12-222
 */
namespace base\components;
use base\components\traits\Validator;
/**
 * Class Form
 *
 * Basic parent class for all forms
 * @package \base\components
 */
class Form {
    /** Form has Validator trait */
    use Validator;

    /**
     * @var array $_names array of Form attributes names
     */
    protected static $_names = [];
    /**
     * @var array $_errors array of Form validation errors in <b>attrName => errorMsg</b> format
     */
    protected $_errors = [];
    /** @var string $class current form class name */
    public $class;

    /**
     * Default constructor
     */
    public function __construct() {
        $class = explode('\\', get_called_class());
        $this->class = array_pop($class);
    }

    /**
     * Default rules which can not be overridden
     */
    protected function _rules() {
        return [
            'csrfToken' => [
                'required' => 'true',
                'type' => 'string',
                'lengthMin' => 60,
                'lengthMax' => 60,
                'csrfToken' => true,
            ]
        ] + $this->rules();
    }

    /**
     * Returns array of declared rules for current form
     * @return array
     */
    protected function rules() {
        return [];
    }

    /**
     * Returns array of attribute labels
     * @return array
     */
    public function attributeLabels() {
        return [];
    }

    /**
     * Validates for according to declared <b>rules</b>
     * @return boolean
     */
    public function validate() {
        $result = true;
        $this->_errors = [];

        $attributeRules = $this->_rules();
        $names = $this->attributeNames();
        foreach($attributeRules as $name => $rules) {
            if(in_array($name, $names)) {
                $result = $this->validateAttribute($name, $rules) && $result;
            }
        }

        return $result;
    }

    /**
     * Validates specified attribute
     * @param string $attributeName
     * @param null $rules attribute validation rules
     * @return bool
     */
    protected function validateAttribute($attributeName, $rules = null) {
        $result = true;

        foreach($rules as $rule => $param) {
            if(!isset($this->$attributeName) && $rule !== 'required') {
                continue;
            }

            if(method_exists($this, $rule)) {
                $errorMsg = '';
                if(!static::$rule($this->$attributeName, $param, $errorMsg)) {
                    $this->_errors[$attributeName] = "$errorMsg";
                    $result = false;
                    break;
                }
            } else {
                $this->_errors[$attributeName] = "Rule \"$rule\" is incorrect.";//TODO: remove from $_errors?
                break;
            }
        }

        return $result;
    }

    /**
     * Gets an array with all available attributes(their names)
     * @return array
     */
    public function attributeNames() {
        $className = get_class($this);

        if(!isset(self::$_names[$className])) {
            $class = new \ReflectionClass($className);
            $names = [];
            foreach($class->getProperties() as $property) {
                $name = $property->getName();
                if($property->isPublic() && !$property->isStatic()) {
                    $names[] = $name;
                }
            }

            self::$_names[$className] = $names;
        }

        return self::$_names[$className];
    }

    /**
     * Gets all form attributes and their values as associative array
     * @return array
     */
    public function getAttributes() {
        $attributes = [];

        foreach($this->attributeNames() as $name) {
            $attributes[$name] = $this->$name;
        }

        return $attributes;
    }

    /**
     * Sets corresponding object attributes to given values
     * @param array $data
     * @param boolean $safeOnly determines if only attributes with declared rules should be set
     */
    public function setAttributes($data, $safeOnly = true) {
        if(is_array($data)) {
            $names = $this->attributeNames();
            $rules = $this->_rules();
            foreach($data as $name => $attr) {
                if(in_array($name, $names) &&
                    ((!$safeOnly) || ($safeOnly && isset($rules[$name])))) {
                    $this->setAttribute($name, $attr); //$this->$name = $attr; //TODO: apply validate rule?
                }
            }
        }
    }

    /**
     * Safely way to get attribute value
     * @param string $attributeName
     * @return mixed null if attribute was not set or attribute name is incorrect
     */
    public function getAttribute($attributeName) {
        $result = null;

        if(in_array($attributeName, $this->attributeNames())) {
            $result = $this->$attributeName;
        }

        return $result;
    }

    /**
     * Safely way to set attribute value
     * @param string $attributeName
     * @param mixed $value
     */
    public function setAttribute($attributeName, $value) {
        if(in_array($attributeName, $this->attributeNames())) {
            $this->$attributeName = $value;
        }
    }

    /**
     * Gets current form errors as associative array(field => errorMessage)
     * @return array
     */
    public function getErrors() {
        return $this->_errors;
    }

    /**
     * Gets error message for specified field
     * @param $attributeName
     * @return string|null string if error exists, null otherwise
     */
    public function getError($attributeName) {
        $result = null;

        if(in_array($attributeName, $this->attributeNames())) {
            $result = $this->_errors[$attributeName];
        }

        return $result;
    }
}
