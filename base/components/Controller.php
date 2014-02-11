<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-16
 */
namespace base\components;
use base\components\traits\AccessControl;
/**
 * Class Controller base class for all controllers
 * @package \base\components
 */
class Controller {

    /** Controller has AccessControl trait */
    use AccessControl;

    /* @var string id of this controller */
    public $id;
    /* @var string page title (for &lt;title&gt;) */
    public $title = '';
    /* @var string name of current action */
    protected $action;
    /* @var string default action for this controller */
    protected $defaultAction = 'index';
    /* @var string layout name using for rendering */
    protected $layout = '';
    /* @var array list of all available actions for this controller */
    private $_actions; //TODO: is it necessary?

    /** cosntants */
    const RULES_COMMON = '*';

    /**
     * Default constructor
     */
    public function __construct() {
        $this->id = get_called_class();

        $methods = get_class_methods($this);
        foreach($methods as $method) {
            $matches = [];
            if(preg_match('/^action(.+)/', $method, $matches)) {
                $this->_actions[ucfirst($matches[1])] = $method;
            }
        }
    }

    /**
     * Returns rules for all actions as action=>rulesArray
     * @return array
     */
    protected function accessRules() {
        return [];
    }

    /**
     * Performs access control check for selected action
     * @param string $action
     * @param string|null $errorMsg
     * @return boolean|callable
     */
    protected function accessControl($action, &$errorMsg = null) {
        $result = true;

        $actions = $this->accessRules();
        $rules = isset($actions[static::RULES_COMMON]) ? $actions[static::RULES_COMMON] : [];
        if(isset($actions[$action])) {
            $rules = array_merge($rules, $actions[$action]);
        }
        foreach($rules as $rule => $param) {
            if(method_exists($this, $rule)) {
                $result = static::$rule($param, $errorMsg);
                if(!$result || is_callable($result)) {
                    break;
                }
            } else {
                $result = false;
                $errorMsg = "Invalid rule name [$rule].";
                break;
            }
        }

        return $result;
    }

    /**
     * Default prequel for an action
     */
    protected function beforeAction() {
        return;
    }

    /**
     * @param array $params
     * @return array|boolean <b>array</b> of parameters if they are bound, <b>false</b> if not
     */
    private final function bindParameters($params = []) {
        $result = [];

        $method = new \ReflectionMethod($this, $this->action);
        if($method->getNumberOfParameters()) {
            foreach($method->getParameters() as $param) {
                /* @var \ReflectionParameter $param */
                $name = $param->getName();
                if(isset($params[$name])) {
                    if($param->isArray()) {
                        $result[] = is_array($params[$name]) ? $params[$name] : [$params[$name]];
                    } elseif(!is_array($params[$name])) {
                        $result[] = $params[$name];
                    } else {
                        return false;
                    }
                } elseif($param->isDefaultValueAvailable()) {
                    $result[] = $param->getDefaultValue();
                } else {
                    return false;
                }
            }
        }

        return $result;
    }

    /**
     * Internal method for running an action with prequel and sequel
     */
    private final function runAction() {
        $this->beforeAction();

        $params = App::requestGet();
        $args = $this->bindParameters($params);
        if(is_array($args)) {
            call_user_func_array([$this, $this->action], $args);
            if(App::$applicationFastCgi) {
                fastcgi_finish_request(); //PHP-FPM only
            }
        } else {
            App::error400('Request is invalid.');
        }

        $this->afterAction();
    }

    /**
     * Runs specified action
     * @param string $action selected action (or $defaultAction if empty)
     */
    public function run($action = '') {
        $action = ucfirst(empty($action) ? $this->defaultAction : $action );
        if(isset($this->_actions[$action])) {
            $this->action = $this->_actions[$action];
            $errorMsg = '';
            $access = $this->accessControl($action, $errorMsg);
            if(is_bool($access)) {
                if($access) {
                    $this->runAction();
                } else {
                    App::error400($errorMsg); //TODO: determine acceptable HTTP code
                }
            } elseif(is_callable($access)) {
                $access();
            } else {
                App::error500('Controller error.');
            }
        } else {
            App::error404('Requested action is invalid.');
        }
    }

    /**
     * Default sequel for an action
     */
    protected function afterAction() {
        return;
    }

    /**
     * Renders selected template
     * @param string $template template file name
     * @param array $context parameters to pass
     * @param boolean $layout <b>TRUE</b> if needed to output layout, <b>FALSE</b> otherwise
     * @param boolean $return <b>TRUE</b> if needed to return content as string, <b>FALSE</b> if output
     * @return null|string
     */
    protected function render($template, $context = [], $layout = true, $return = false) {
        return View::render($template, $context, $layout ? $this->layout : null, $return);
    }

    /**
     * Renders given array as JSON and adds Content-Type header if necessary
     * @param array $data
     * @param boolean $return if should be returned as string instead of output
     * @return string|null
     */
    protected function renderJson($data, $return = false) {
        return View::render(null, $data, null, $return, true);
    }
}
