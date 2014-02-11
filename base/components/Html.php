<?php
/**
 * @author sjoorm
 * date: 2013-12-23
 */
namespace base\components;
/**
 * Class Html for rendering Form components
 * @package \base\components
 */
class Html {
    /**
     * @var Form $_form current form instance
     */
    protected static $_form;

    /**
     * Prints array of <b>attr => value</b> into string that can be inserted in HTML code
     * @param array $attributes attr => value
     * @param array $except array of attributes that should be excluded from result
     * @return string HTML code
     */
    protected static function htmlAttributes($attributes, $except = []) {
        $result = '';

        foreach($attributes as $attr => $value) {
            if(!in_array($attr, $except)) {
                $result .= "$attr=\"$value\" ";
            }
        }

        return $result;
    }

    /**
     * Returns HTML code for specified <b>form</b> beginning
     * @param Form $form model
     * @param string $action action URL
     * @param string $method request type
     * @param array $htmlAttributes additional HTML attributes
     * @param array $htmlAttributesToken additional HTML attributes for csrfToken
     * @return string
     */
    public static function beginForm($form, $action = '', $method = 'post', $htmlAttributes = [], $htmlAttributesToken = []) {
        $result = '';
        if($form instanceof Form) {
            static::$_form = $form;
            $className = static::$_form->class;
            $htmlAttributes['id'] = isset($htmlAttributes['id']) ? $htmlAttributes['id'] : $className;
            $htmlAttributes['name'] = isset($htmlAttributes['name']) ? $htmlAttributes['name'] : $className;
            $attributes = static::htmlAttributes($htmlAttributes);
            $attributesToken = static::htmlAttributes($htmlAttributesToken);
            $csrfToken = App::csrfTokenGet();
            $csrfTokenName = $className . '[csrfToken]';

            $result = "<form action=\"$action\" method=\"$method\" $attributes>\n
                        <div style=\"display: none\"><input type=\"hidden\" name=\"$csrfTokenName\" value=\"$csrfToken\" $attributesToken /></div>";
        }

        return $result;
    }

    /**
     * Returns HTML code for specified <b>form</b> ending
     * @return string
     */
    public static function endForm() {
        static::$_form = null;

        return '</form>';
    }

    /**
     * Returns HTML code for label which points to chosen attribute element
     * @param $attributeName
     * @param string|null $text custom text for label
     * @param array $htmlAttributes
     * @return string
     */
    public static function label($attributeName, $text = null, $htmlAttributes = []) {
        $result = '';

        if(static::$_form instanceof Form) {
            $label = $text;
            if(!isset($label)) {
                $labels = static::$_form->attributeLabels();
                $label = isset($labels[$attributeName]) ? $labels[$attributeName] : $attributeName;
            }
            $className = get_class(static::$_form);
            $htmlAttributes['for'] = isset($htmlAttributes['for']) ? $htmlAttributes['for'] : $className.'['.$attributeName.']';
            $attributes = static::htmlAttributes($htmlAttributes);
            $for = isset($for) ? $for : $className.'['.$attributeName.']';
            $result = "<label $attributes>$label</label>";
        }

        return $result;
    }


    /**
     * Returns HTML code for input element
     * @param string $attributeName
     * @param string $type input element <b>type</b> attribute
     * @param array $htmlAttributes
     * @return string
     */
    public static function input($attributeName, $type, $htmlAttributes = []) {
        $result = '';

        if(static::$_form instanceof Form) {
            $attribute = static::$_form->getAttribute($attributeName);
            $htmlAttributes['id'] = isset($htmlAttributes['id']) ? $htmlAttributes['id'] : static::$_form->class.'['.$attributeName.']';
            $htmlAttributes['name'] = isset($htmlAttributes['name']) ? $htmlAttributes['name'] : static::$_form->class.'['.$attributeName.']';
            $htmlAttributes['value'] = isset($htmlAttributes['value']) ? $htmlAttributes['value'] : $attribute;
            /** specific cases begin */
            switch($type) {
                case 'radio':
                    if($htmlAttributes['value'] == $attribute) {
                        $htmlAttributes['checked'] = '';
                    }
                    break;
                case 'checkbox':
                    if($attribute) {
                        $htmlAttributes['checked'] = '';
                    }
                    if(isset($htmlAttributes['value'])) {
                        unset($htmlAttributes['value']);
                    }
                    break;
                default:
                    break;
            }
            /** specific cases end */
            $attributes = static::htmlAttributes($htmlAttributes);

            $result = "<input type=\"$type\" $attributes />";
        }

        return $result;
    }

    /**
     * Returns HTML code for text input
     * @param string $attributeName
     * @param array $htmlAttributes
     * @return string
     */
    public static function textField($attributeName, $htmlAttributes = []) {
        return static::input($attributeName, 'text', $htmlAttributes);
    }

    /**
     * Returns HTML code for password input
     * @param string $attributeName
     * @param array $htmlAttributes
     * @return string
     */
    public static function passwordField($attributeName, $htmlAttributes = []) {
        return static::input($attributeName, 'password', $htmlAttributes);
    }

    /**
     * Returns HTML code for hidden input
     * @param string $attributeName
     * @param array $htmlAttributes
     * @return string
     */
    public static function hiddenField($attributeName, $htmlAttributes = []) {
        return static::input($attributeName, 'hidden', $htmlAttributes);
    }

    /**
     * Returns HTML code for radio input
     * @param string $attributeName
     * @param array $htmlAttributes
     * @return string
     */
    public static function radioField($attributeName, $htmlAttributes = []) {
        return static::input($attributeName, 'radio', $htmlAttributes);
    }

    /**
     * Returns HTML code for checkbox input
     * @param string $attributeName
     * @param array $htmlAttributes
     * @return string
     */
    public static function checkboxField($attributeName, $htmlAttributes = []) {
        return static::input($attributeName, 'checkbox', $htmlAttributes);
    }

    /**
     * Returns HTML code for submit button
     * @param string $text button text
     * @param array $htmlAttributes
     * @return string
     */
    public static function submitButton($text = 'Submit', $htmlAttributes = []) {
        $htmlAttributes['value'] = isset($htmlAttributes['value']) ? $htmlAttributes['value'] : $text;
        $attributes = static::htmlAttributes($htmlAttributes);

        return "<input type=\"submit\" $attributes />";
    }

    /**
     * Returns HTML code for select dropdown list
     * @param string $attributeName
     * @param array|callable $options array of options(or callable generating it)
     * in <b>"text" => value</b> format
     * @param array $htmlAttributes
     * @return string
     */
    public static function dropdownSelect($attributeName, $options, $htmlAttributes = []) {
        $result = '';

        if(static::$_form instanceof Form) {
            $attribute = static::$_form->getAttribute($attributeName);
            $htmlAttributes['id'] = isset($htmlAttributes['id']) ? $htmlAttributes['id'] : static::$_form->class.'['.$attributeName.']';
            $htmlAttributes['name'] = isset($htmlAttributes['name']) ? $htmlAttributes['name'] : static::$_form->class.'['.$attributeName.']';
            $htmlAttributes['value'] = isset($htmlAttributes['value']) ? $htmlAttributes['value'] : $attribute;
            $attributes = static::htmlAttributes($htmlAttributes, ['value']);

            $result .= "<select $attributes>";
            $htmlOptions = '';
            if(is_callable($options)) {
                $options = $options();
            }
            if(is_array($options)) {
                foreach($options as $value => $option) {
                    if(is_array($option)) {
                        $optionAttributes = static::htmlAttributes($option);
                        $htmlOptions .= "<option $optionAttributes>$value</option>";
                    } else {
                        $htmlOptions .= "<option value=\"$option\">$value</option>";
                    }
                }
            }
            $result .= $htmlOptions;
            $result .= "</select>";
        }

        return $result;
    }
}
