<?php
/**
 * @author sjoorm <sjoorm1@gmail.com>
 * date: 2014-01-16
 */
namespace base\components;
/**
 * Class App basic application class
 */
final class App {
    /* @var array application configurations parameters */
    private static $_config = [];
    /* @var array application logic parameters */
    private static $_params = [];
    /* @var array application initialization state(should be <b>TRUE</b> to run application) */
    private static $_init = false;
    /* @var Model application User model class name */
    private static $_userModel = 'base\\models\\User';
    /* @var string namespace for user-created controllers */
    private static $_namespace = '\\';
    /* @var Cache application Cache class name */
    //private static $_cacheClass = 'base\\models\\Cache';
    /* @var Cache application Cache class instance */
    //public static $cache;
    /* @var string application name */
    public static $applicationName = 'Application';
    /* @var boolean determines if application is running under PHP-FPM Fast-CGI module */
    public static $applicationFastCgi = false;
    /* @var string full site url */
    public static $siteUrl = '';
    /* @var string url to static files */
    public static $staticUrl = '';
    /* @var string url to application base */
    public static $baseUrl = '';
    /* @var string default uri prefix */
    public static $uriPrefix = '';
    /* @var string index.php working directory */
    public static $path = '';
    /* @var string base working directory */
    public static $basePath = '';
    /* @var string default controller name */
    protected static $defaultController = 'Site';
    /* @var array default include directories*/
    private static $defaultInclude = [
        'components/traits/*', //traits (independent)
        'components/*', //components
    ];

    /** application constants */
    const VIEW_ERROR_HTTP = 'errors/http';
    const LAYOUT_ERROR_HTTP = 'layouts/errorHttp';

    /**
     * Loads basic app configuration
     * @param array $config
     * @param array $params
     * @param string $cwd current working directory (index directory)
     */
    public static function init($config, $params, $cwd = __DIR__) {
        self::$_config = $config;
        self::$_params = $params;
        self::$path = preg_match('/.+\/$/', $cwd) ? $cwd : $cwd . DIRECTORY_SEPARATOR;
        self::$basePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

        self::$_userModel = isset($config['userModel']) ? $config['userModel'] : self::$_userModel;

        self::$siteUrl = self::$_config['siteUrl'];
        self::$staticUrl = self::$siteUrl . self::$_config['staticUrl'];
        self::$baseUrl = self::$siteUrl . self::$_config['baseUrl'];
        $site_url_options = parse_url(self::$siteUrl);
        self::$uriPrefix = $site_url_options['path'] . self::$_config['baseUrl'];
        self::$_namespace = preg_match('/.+\\$/', self::$_config['namespace']) ? self::$_config['namespace'] : self::$_config['namespace'] . '\\';

        self::$applicationName = isset(self::$_config['applicationName']) ? self::$_config['applicationName'] : self::$applicationName;
        self::$defaultController = isset(self::$_config['defaultController']) ? self::$_config['defaultController'] : self::$defaultController;

        self::loadClasses();
        CacheProvider::init(isset(self::$_config['cache']) ? self::$_config['cache'] : []);
        self::createDbConnection();

        self::$_init = true;
    }

    /**
     * Internal method for loading all necessary include files(given on 'include' key from $_config array)
     */
    private static final function loadClasses() {
        if(isset(self::$_config['include']) && is_array(self::$_config['include'])) {
            self::$_config['include'] = array_merge(self::$defaultInclude, self::$_config['include']);
            $defaultIncludeCount = count(self::$defaultInclude);
            foreach(self::$_config['include'] as $key => $include) {
                /* @var string $include */
                $include = ($key < $defaultIncludeCount) ? self::$basePath . $include : self::$path . $include;
                $matches = [];
                if(preg_match('/(.*)\/?\*$/', $include, $matches)) {
                    if(is_dir($matches[1])) {
                        $files = scandir($matches[1]);
                        foreach ($files as $file) {
                            /* @var string $file */
                            if($file === '.' || $file === '..' || is_dir($matches[1] . $file)) {
                                continue;
                            }
                            if (preg_match('/^.+\.php$/', $file)) {  // include only php files
                                require_once($matches[1] . $file);
                            }
                        }
                    } else {
                        static::error500("Wrong config parameter in <strong>include</strong>:
                        {$matches[1]} is not a directory");
                    }
                } else {
                    if(file_exists($include)) {
                        if(!is_dir($include)) {
                            require_once($include);
                        } else {
                            static::error500("Wrong config parameter in <strong>include</strong>:
                            $include is a directory");
                        }
                    } else {
                        static::error500("Wrong config parameter in <strong>include</strong>:
                        $include is not exists.");
                    }
                }
            }
        } else {
            static::error500('Include is missing from config.');
        }
    }

    /**
     * Initiates new DB connection
     */
    protected static function createDbConnection() {
        if(isset(self::$_config['db']) &&
            isset(self::$_config['db']['host']) &&
            isset(self::$_config['db']['user']) &&
            isset(self::$_config['db']['password']) &&
            isset(self::$_config['db']['database'])) {
            $db = mysqli_connect(
                self::$_config['db']['host'],
                self::$_config['db']['user'],
                self::$_config['db']['password'],
                self::$_config['db']['database']
            );

            if(mysqli_connect_errno()) {
                static::error500('DB error.', 'DB error: ' . mysqli_connect_errno());
            }

            if(!mysqli_set_charset($db, 'utf8')) {
                static::error500('DB error.', 'DB error: ' . mysqli_error($db));
            }

            Model::setConnection($db);
        } else {
            static::error500('Database settings are missing from config.');
        }
    }

    /**
     * Internal method for parsing request url
     * @return array [controller, action]
     */
    protected static function requestRoute() {
        $route = [];

        $currentUrlOptions = parse_url($_SERVER['REQUEST_URI']);
        $fullUri = $currentUrlOptions['path'];
        $prefixLen = strlen(self::$uriPrefix);
        if ($fullUri == self::$uriPrefix){ //all default
            $route['controller'] = '';
            $route['action'] = '';
        } elseif (substr($fullUri, 0, $prefixLen) === self::$uriPrefix) {
            $uri = substr($fullUri, $prefixLen);
            $parts = explode('/', rtrim($uri, '/'));
            $route['controller'] = ucfirst($parts[0]);
            $route['action'] = isset($parts[1]) ? ucfirst($parts[1]) : '';
        } else {
            //do nothing
        }

        return $route;
    }

    /**
     * Checks if given class name corresponds to real controller's name and returns an entity on success
     * @param string $name
     * @return Controller|null
     */
    protected static function createController($name) {
        $result = null;

        $name = self::$_namespace . ($name ? $name : self::$defaultController) . 'Controller';
        if(class_exists($name)) {
            $controllerClass = new \ReflectionClass($name);
            if($controllerClass->hasMethod('run')) {
                $result = new $name();
            }
        }

        return $result;
    }

    public static function isLoggedIn() {
        return UserIdentity::isLoggedIn();
    }

    /**
     * Runs entire application, main method
     */
    public static function run() {
        if(self::$_init) {
            $route = static::requestRoute();
            if($route && $controller = static::createController($route['controller'])) {
                /** session started */
                App::sessionStart();
                /** running application */
                try {
                    /* @var Controller $controller */
                    $controller->run($route['action']);
                } catch (\Exception $e) {
                    static::error500('', "Exception: [{$e->getMessage()}] in {$e->getFile()} at #{$e->getLine()}.");
                }
                /** on success */
                static::end();
            } else {
                static::error404('Requested invalid resource.');
            }
        } else {
            static::error500('Application was not set up.');
        }
    }

    /**
     * Gets specified parameter from Application $_parameters array
     * @param string $name
     * @return mixed <b>null</b> if is not set
     */
    public static function getParam($name) {
        return isset(static::$_params[$name]) ? static::$_params[$name] : null;
    }

    /**
     * Application exit handler
     * @param integer $code exit() return code
     */
    public static function end($code = 0) {
        $db = Model::getConnection();
        if($db) {
            $db->close();
        }
        exit($code);
    }

    /**
     * Generates and outputs correct redirect header
     * @param string $url URL or URI (if needs to be built)
     * @param array $params GET parameters
     * @param boolean $built if was already built
     */
    public static function redirect($url, $params = [], $built = false) {
        $url = $built ? $url : static::urlFor($url, $params);
        header("Location: $url");
        //app::end() would be called in app:run() function later
    }

    /**
     * Outputs error message with HTTP 404 header
     * @@param string $text text to be printed. Omitted if default
     */
    public static function error404($text = '') {
        header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
        View::render(static::VIEW_ERROR_HTTP, [
            'code' => 404,
            'msg' => $text ? $text : 'Page not found.',
        ], static::LAYOUT_ERROR_HTTP, false, App::requestIsAjax());
        static::end(44);
    }

    /**
     * Outputs error message with HTTP 400 header
     * @param string $text text to be printed. Omitted if default
     */
    public static function error400($text = '') {
        header("{$_SERVER['SERVER_PROTOCOL']} 400 Bad Request");
        View::render(static::VIEW_ERROR_HTTP, [
            'code' => 400,
            'msg' => $text ? $text : 'Bad request.',
        ], static::LAYOUT_ERROR_HTTP, false, App::requestIsAjax());
        static::end(40);
    }

    /**
     * Outputs error message with HTTP 403 header
     * @param string $text text to be printed. Omitted if default
     */
    public static function error403($text = '') {
        header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");
        View::render(static::VIEW_ERROR_HTTP, [
            'code' => 403,
            'msg' => $text ? $text : 'You are not authorized to perform this action.',
        ], static::LAYOUT_ERROR_HTTP, false, App::requestIsAjax());
        static::end(43);
    }

    /**
     * Outputs error message with HTTP 500 header
     * @param string $text text to be printed. Omitted if default
     * @param string $logMessage message that will be sent in error_log
     */
    public static function error500($text = '', $logMessage = '') {
        header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
        View::render(static::VIEW_ERROR_HTTP, [
            'code' => 500,
            'msg' => $text ? $text : 'Internal server error.',
        ], static::LAYOUT_ERROR_HTTP, false, App::requestIsAjax());

        if($logMessage) {
            error_log($logMessage);
        }

        static::end(50);
    }

    /**
     * Gets specified argument (or all of them) from GET params
     * @param string $name omitted if all GET params are needed as Array
     * @param mixed $default
     * @param boolean $urlDecode <b>TRUE</b> if string should be decoded, <b>FALSE</b> otherwise
     * @return mixed
     */
    public static function requestGet($name = null, $default = null, $urlDecode = true) {
        $result = null;

        if(isset($name)) {
            $result = isset($_GET[$name]) ? ($urlDecode ? urldecode($_GET[$name]) : $_GET[$name]) : $default;
        } else {
            if($urlDecode) {
                $result = [];
                foreach($_GET as $key => $value) {
                    $result[$key] = urldecode($value);
                }
            } else {
                $result = $_GET;
            }
        }

        return $result;
    }

    /**
     * Gets specified argument (or all of them) from POST params
     * @param string $name omitted if all POST params are needed as Array
     * @param mixed $default
     * @return mixed
     */
    public static function requestPost($name = null, $default = null) {
        $result = null;

        if(isset($name)) {
            $result = isset($_POST[$name]) ? $_POST[$name] : $default;
        } else {
            $result = $_POST;
        }

        return $result;
    }

    /**
     * Parses request payload JSON data
     * @return array
     */
    public static function requestPayload() {
        return json_decode(file_get_contents('php://input'), true);
    }

    /**
     * Checks if current request is AJAX
     * @return boolean
     */
    public static function requestIsAjax() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * Returns request method if called without parameter, compares it otherwise
     * @param string $method
     * @return string
     */
    public static function requestMethod($method = null) {
        return isset($method) ? ($_SERVER['REQUEST_METHOD'] === $method) : ($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Returns URL string for specified resource, adding GET query params (if they were passed)
     * @param string $uri
     * @param array $params
     * @return string
     */
    public static function urlFor($uri, $params = []) {
        $uri = (!$uri || (substr($uri, -1) === '/')) ? $uri : "$uri/";
        $query = http_build_query($params);
        return self::$baseUrl . $uri . ($query ? "?$query" : '');
    }

    /**
     * Starts user session
     * @param boolean $isUserActivity if user activity timer should be updated
     * @param string $prefix additional session prefix
     * @return boolean
     */
    public static function sessionStart($isUserActivity = true, $prefix = null) {
        $sessionLifetime = 2592000; //30 days
        //$idLifetime = 60;

        if(session_id()) {
            return true;
        }

        $sessionName = str_replace(['=',',',';',' ',"\t","\r","\n","\013","\014"], '', self::$applicationName);
        session_name($sessionName . ($prefix ? "_$prefix" : ''));
        ini_set('session.cookie_lifetime', 0);
        if(!session_start()) {
            return false;
        }

        $t = time();

        if($sessionLifetime) {
            if(isset($_SESSION['lastActivity']) && ($t - $_SESSION['lastActivity']) >= $sessionLifetime){
                static::sessionDestroy();
                return false;
            } else {
                if($isUserActivity) {
                    $_SESSION['lastActivity'] = $t;
                }
            }
        }

        return true;
    }

    /**
     * Destroys session for current user
     */
    public static function sessionDestroy() {
        if(session_id()) {
            session_unset();
            setcookie(session_name(), session_id(), time() - 86400, '/');
            session_destroy();
        }
    }

    /**
     * Checks if CSRF token passed as POST parameter is valid
     * @param string $csrfToken if token should be passed as parameter, POST content will be used otherwise
     * @return boolean
     */
    public static function csrfTokenCheck($csrfToken = null) {
        $csrfToken = isset($csrfToken) ? $csrfToken : (isset($_POST['csrfToken']) ? $_POST['csrfToken'] : null);
        return (isset($csrfToken) && isset($_SESSION['csrfToken']) && $csrfToken === $_SESSION['csrfToken']);
    }

    /**
     * Generates CSRF token and puts it into session variable
     * @return string generated token string
     */
    public static function csrfTokenGet() {
        $csrfToken = null;

        if(!isset($_SESSION['csrfTokenCreatedAt']) || ($_SESSION['csrfTokenCreatedAt'] - time() > 21600)) { //1hr
            $_SESSION['csrfToken'] = password_hash(uniqid(self::$applicationName), PASSWORD_BCRYPT);
            $_SESSION['csrfTokenCreatedAt'] = time();
        }
        $csrfToken = $_SESSION['csrfToken'];

        return $csrfToken;
    }

    /**
     * Sends an email
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $from
     * @param string $replyTo
     */
    public static function mail($to, $subject, $message, $from='noreply@ats.com', $replyTo = 'support@ats.com') {
        $headers = "From: $from\r\n" .
            "Reply-To: $replyTo\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/html; charset=utf-8\r\n";
        mail($to, $subject, $message, $headers);
    }

    /**
     * Gets configured User model
     * @return Model
     */
    public static function getUserModel() {
        return self::$_userModel;
    }
}
