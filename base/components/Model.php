<?php
namespace base\components;
/**
 * Class Model
 *
 * Basic parent class for other models
 *
 * @property integer $id
 *
 * @package \base\components
 */
class Model {
    const RULE_NONE = 0b00;
    const RULE_INSERT = 0b01;
    const RULE_UPDATE = 0b10;
    const RULE_ALL = 0b11;

    /* @var $_table string name of the corresponding table */
    static protected $_table = null;
    /* @var $_fields array field names */
    static protected $_fields = array();
    /* @var $_connection \mysqli handler for MySQL connection */
    static protected $_connection = null;
    /* @var $_values array model attributes */
    protected $_values = array();

    public function __construct() {
        foreach (static::$_fields as $field => $value) {
            if (!array_key_exists($field, $this->_values)){
                $this->_values[$field] = null;
            }
        }
    }

    /**
     * Magic method for getting param
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return (array_key_exists($name, static::$_fields)) ? $this->_values[$name] : null;
        /** maybe we don't need it? just doing nothing if there are no such field */
        //throw new Exception('No such field "'.$name.'" for '.get_called_class());
    }

    /**
     * Magic method for setting param
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        if (array_key_exists($name, static::$_fields)){
            $this->_values[$name] = $value;
        }
    }

    /**
     * Sets given mysqli database connection for this class
     * @param \mysqli $connection
     */
    static public function setConnection($connection) {
        static::$_connection = $connection;
    }

    /**
     * Returns mysqli database connection of current class
     * @return \mysqli|null
     */
    static public function getConnection() {
        return static::$_connection;
    }

    /**
     * Returns all attribute names and their parameters
     * @return array
     */
    static public function getAttributeNames() {
        return static::$_fields;
    }

    /**
     * Returns all attributes
     * @return array
     */
    public function getAttributes() {
        return $this->_values;
    }

    /**
     * Sets the attribute values in a massive way
     * @param array $attributes attributes to be set
     */
    public function setAttributes($attributes) {
        foreach($attributes as $field => &$value) {
            $this->$field = $value;
        }
    }

    /**
     * Generates string digest from array of attributes
     * @param array $params
     * @return string
     */
    protected static function digest($params) {
        $result = '';

        ksort($params);
        foreach($params as $attr => $value) {
            $result .= "$attr$value";
        }

        return $result;
    }

    /**
     * Caches current database record
     * @return boolean
     */
    public function cacheSet() {
        $result = false;

        if($this->id) {
            $result = CacheProvider::set(static::$_table . static::digest(['id' => $this->id]), $this);
        }

        return $result;
    }

    /**
     * Removes current database record from cache
     * @return boolean
     */
    public function cacheDelete() {
        $result = false;

        if($this->id) {
            $result = CacheProvider::delete(static::$_table . static::digest(['id' => $this->id]));
        }

        return $result;
    }

    /**
     * @param array $params array
     * @param integer $limit if set to zero than all records will be requested
     * @param integer $offset
     * @param string|null $orderBy
     * @param string $orderType
     * @return boolean|\mysqli_stmt
     */
    static protected function _bindParamsSelect($params, $limit = 1, $offset = 0, $orderBy = null, $orderType = 'ASC') {
        $bind_params = array('', '');
        $scope = '';
        $field_types = '';
        if(is_array($params)) {
            foreach ($params as $field => &$value) {
                $scope .= "$field = ? AND ";
                $field_types .= static::$_fields[$field]['type'];
                $bind_params[] = &$value;
            }
        }
        $scope = $scope ? rtrim($scope, 'AND ') : '1';

        $stmt = mysqli_prepare(static::$_connection,
            'SELECT * FROM ' . static::$_table .
            " WHERE $scope" . ($limit ? " LIMIT $offset, $limit" : '') .
            ($orderBy ? " ORDER BY `$orderBy` $orderType" : '')
        );

        if($stmt && $field_types) {
            $bind_params[0] = &$stmt;
            $bind_params[1] = &$field_types;
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
        }

        return $stmt;
    }

    /**
     * @return \mysqli_stmt
     */
    protected function _bindParamsInsert() {
        $bind_params = array('', '');
        $field_names = '';
        $field_params = '';
        $field_types = '';
        foreach ($this->_values as $field => &$value) {
            if(static::$_fields[$field]['rule'] & self::RULE_INSERT) {
                $field_names .= $field.', ';
                $field_params .= '?, ';
                $field_types .= static::$_fields[$field]['type'];
                $bind_params[] = &$value;
            }
        }
        $field_names = rtrim($field_names, ', ');
        $field_params = rtrim($field_params, ', ');

        $stmt = mysqli_prepare(static::$_connection,
            "INSERT INTO {$this::$_table}
            ($field_names)
            VALUES ($field_params)"
        );

        if($stmt) {
            $bind_params[0] = &$stmt;
            $bind_params[1] = &$field_types;
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
        }
        return $stmt;
    }

    /**
     * @return \mysqli_stmt
     */
    protected function _bindParamsUpdate() {
        $bind_params = array('', '');
        $field_names = '';
        $field_types = '';
        foreach ($this->_values as $field => &$value) {
            if(static::$_fields[$field]['rule'] & self::RULE_UPDATE) {
                $field_names .= $field.' = ?, ';
                $field_types .= static::$_fields[$field]['type'];
                $bind_params[] = &$value;
            }
        }
        $field_names = rtrim($field_names, ', ');
        $field_types .= static::$_fields['id']['type'];
        $bind_params[] = &$this->_values['id'];

        $stmt = mysqli_prepare(static::$_connection,
            "UPDATE {$this::$_table}
            SET $field_names
            WHERE id = ?"
        );
        if($stmt) {
            $bind_params[0] = &$stmt;
            $bind_params[1] = &$field_types;
            call_user_func_array('mysqli_stmt_bind_param', $bind_params);
        }

        return $stmt;
    }

    /**
     * @return \mysqli_stmt
     */
    protected function _bindParamsDelete() {
        $stmt = mysqli_prepare(static::$_connection,
            "DELETE FROM {$this::$_table}
            WHERE id = ?"
        );
        if($stmt) {
            mysqli_stmt_bind_param($stmt, 'i',
                $this->_values['id']
            );
        }

        return $stmt;
    }

    /**
     * Saves current object to the database.
     * INSERT if <b>id</b> is <b>null</b>,
     * UPDATE otherwise
     * @return bool <b>true</b> if success, <b>false</b> otherwise
     */
    public function save() {
        $result = false;
        $stmt = null;
        $is_editing = is_numeric($this->id);

        if (isset(static::$_fields['updated_at'])){
            $this->_values['updated_at'] = date('Y-m-d H:i:s');
        }

        if ($is_editing) {
            $stmt = $this->_bindParamsUpdate();
        } else {
            if (isset(static::$_fields['created_at'])) {
                $this->_values['created_at'] = date('Y-m-d H:i:s');
            }
            $stmt = $this->_bindParamsInsert();
        }

        if($stmt) {
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_affected_rows($stmt) === 1;
            } else {
                error_log(__CLASS__ . ', line #' . __LINE__ . ' error: ' . mysqli_error(static::$_connection));
            }
            if ($result) {
                $this->cacheSet();

                if(!$is_editing) {
                    $this->id = mysqli_stmt_insert_id($stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }

        return $result;
    }

    /**
     * Finds record with specified primary key (id by default). Shorthand for method getBy
     * @param integer $id Id of the entity to get
     * @param string $pk PK column name
     * @throws \Exception
     * @return mixed Instance of the object or null
     */
    static public function getByPk($id, $pk = 'id') {
        $result = CacheProvider::get(static::$_table . static::digest(['id' => $id]));

        if(!($result instanceof static)) {
            $result = static::getBy([$pk => $id]);
            if($result instanceof static) {
                $result->cacheSet();
            }
        }

        return $result;
    }

    /**
     * Finds all records that have the specified attribute values
     * @param array $params attributes
     * @param integer $limit limit
     * @param integer $offset offset
     * @param string|null $orderBy
     * @param string $orderType
     * @throws \Exception
     * @return array objects in array
     */
    static public function getAllBy($params, $limit = 0, $offset = 0, $orderBy = null, $orderType = 'ASC') {
        if(!isset(static::$_connection)) {
            throw new \Exception('MySQL error: connection was not configured');
        }
        $class = get_called_class();
        $entities = array();
        $result = false;

        $stmt = self::_bindParamsSelect($params, $limit, $offset, $orderBy, $orderType);

        if($stmt) {
            if (mysqli_stmt_execute($stmt)){
                $resultParams = array(&$stmt);
                $attributes = static::$_fields;
                foreach ($attributes as $field => &$value) {
                    $resultParams[$field] = &$value;
                }
                $result = call_user_func_array('mysqli_stmt_bind_result', $resultParams);
                while($result && mysqli_stmt_fetch($stmt)) {
                    $entity = new $class();
                    /* @var $entity Model */
                    $entity->setAttributes($attributes);
                    $entities[] = $entity;
                }
            }
            if (!$result){
                error_log(mysqli_error(static::$_connection));
            }
            mysqli_stmt_close($stmt);
        }

        return $entities;
    }

    /**
     * Finds a single record or create new
     * @param array $params
     * @throws \Exception
     * @return Model
     */
    static public function getOrCreate($params) {
        if (!isset(static::$_connection)) {
            throw new \Exception('MySQL error: connection was not configured');
        }
        $entity = static::getBy($params);
        if (!$entity) {
            $class = get_called_class();
            $entity =  new $class();
            $entity->setAttributes($params);
        }
        return $entity;
    }

    /**
     * Finds a single record that has specified attribute values
     * @param array $params
     * @throws \Exception
     * @return Model|null
     */
    static public function getBy($params) {
        if(!isset(static::$_connection)) {
            throw new \Exception('MySQL error: connection was not configured');
        }

        $entity = null;

        if(!isset($entity)) {
            $stmt = self::_bindParamsSelect($params);

            if($stmt) {
                $result = false;
                if (mysqli_stmt_execute($stmt)){
                    $resultParams = array(&$stmt);
                    $attributes = static::$_fields;
                    foreach ($attributes as $field => &$value) {
                        $resultParams[$field] = &$value;
                    }
                    $result = call_user_func_array('mysqli_stmt_bind_result', $resultParams);
                    if($result && $result = mysqli_stmt_fetch($stmt)) {
                        $class = get_called_class();
                        $entity = new $class();
                        /* @var $entity Model */
                        $entity->setAttributes($attributes);
                    }
                } else {
                    error_log(__CLASS__ . ', line #' . __LINE__ . ' error: ' . mysqli_error(static::$_connection));
                }
                mysqli_stmt_close($stmt);
            }
        }

        return $entity;
    }

    /**
     * Runs specified SQL query
     * @param string $sql
     * @throws \Exception
     * @return array
     */
    static public function hydrate($sql) {
        if(!isset(static::$_connection)) {
            throw new \Exception('MySQL error: connection was not configured');
        }
        $class = get_called_class();

        $rs = mysqli_query(static::$_connection, $sql);

        $entities = array();
        while ($rs && $obj = mysqli_fetch_object($rs, $class))
        {
            /*foreach ($obj->_fields as $field => $options) {
                if ($options['type'] == 'd'){
                    $obj->$field = (float)$obj->$field;
                }
                if ($options['type'] == 'i'){
                    $obj->$field = (int)$obj->$field;
                }
            }
             */
            $entities[] = $obj;
        }

        return $entities;
    }

    /**
     * Deletes current entity
     * @return boolean <b>true</b> upon success, <b>false</b> upon failure
     */
    public function delete() {
        $result = false;
        $stmt = $this->_bindParamsDelete();

        if($stmt) {
            if(mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_affected_rows($stmt) === 1;
                if($result) {
                    $this->cacheDelete();
                }
            } else {
                error_log(__CLASS__ . ', line #' . __LINE__ . ' error: ' . mysqli_error(static::$_connection));
            }
            mysqli_stmt_close($stmt);
        }

        return $result;
    }

    /**
     * Return object as an array. Used to express object in JSON.
     */
    public function toArray() {
        return $this->_values;
    }

    /**
     * Selects all rows from table
     * @param integer|null $is_hidden
     * @param string|null $orderBy
     * @param string $orderType
     * @param integer $limit
     * @param integer $offset
     * @throws \Exception
     * @return array of current class objects
     */
    static public function getAll($is_hidden = 0, $limit = 0, $offset = 0, $orderBy = null, $orderType = 'ASC') {
        $has_is_hidden = isset(static::$_fields['is_hidden']);
        $filter = isset($is_hidden) && $has_is_hidden ? ['is_hidden' => $is_hidden] : [];
        return static::getAllBy($filter, $limit, $offset, $orderBy, $orderType);
    }
}
