<?php

/**
 * Class SQL_Query
 * 
 * sql_query.class.php
 * 
 * Miroslav Stoev
 * 
 * micro-framework
 */
class SQL_Query
{
    # few public options
    public $query_debug   = false; // print query
    public $show_num_rows = false; // print number of rows
    public $die           = false; // stop the script
    
    protected $_conn                = false;	// the connection object
    protected $pdo_params_arr       = array();	// parameters for the query to be escaped
    protected $table_fields         = array();	// used table fields
    protected $query_results        = 0;		// the number of results
    
    private $_mysql_compare_operators = [
        '>', '>=', '<', '<=', '<>', '!=', '=', '<=>',
        'LIKE', 'NOT LIKE', 'IS NOT NULL', 'IS NOT', 'IS NULL', 'IS', 'IN'
    ];
    private $_password_field_names  = array(); // these fields must be set in config file
    private $_query_elements        = array(
        'select' => '',
        'update' => '',
        'insert' => '',
        'from' => '',
        'left_join' => '',
        'where' => '',
        'group_by' => '',
        'order' => '',
        'limit' => '',
        'is_delete_query' => '',
    );
    private $_memcache;

    /**
     * Function connect
     * This function connect us to DB.
     * We will not use __construct function,
     * this is the most important and base function here and will define some variables here.
     *
     * @param (string) $host - host name
     * @param (string) $user - user name
     * @param (string) $pwd - user password
     * @param (string) $db - db name
     */
    public final function connect($host, $user, $pwd, $db)
    {
        if (!$this->_conn) {
            try {
                $options = array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => true,
                );
                
                $dsn = "mysql:host={$host};dbname={$db};charset=utf8";

                $this->_conn = new PDO($dsn, $user, $pwd, $options);

            //    $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->_conn->exec("set names utf8");
            }
            catch (Exception $e) {
                Text::create_error_log('Connection Exception: ' . $e->getMessage());
                die('Connection Exception.');
            }
            
            if (defined('PASS_FIELDS_NAMES') and PASS_FIELDS_NAMES) {
				$pas_fields = PASS_FIELDS_NAMES;
				
				if (is_string(PASS_FIELDS_NAMES)) {
					$pas_fields = explode(',', PASS_FIELDS_NAMES);
                }
				
				if(is_array($pas_fields)) {
                    $this->_password_field_names = $pas_fields;
                }
            }
        }
    }

    /**
     * Function insert_or_update
     * Try to insert data, if we have duplicate unique key, update.
     *
     * @param array $assoc_array - associative array with fields names and their values
     * @param string $table
     *
     * @return (object) $this
	 * 
	 * @deprecated - secure the input or remove the method
     */
    public final function insert_or_update(array $assoc_array, $table = '')
    {
        $fields_string = '';
        $values_string = '';
        $update_string = '';

        if(empty($table)) {
            $table = $this->table;
        }

        $table_fields = $this->get_table_fields($table);
        if(!$table_fields) {
            return false;
        }

        // new way, needs unique keys, or something like that :)
        foreach ($assoc_array as $field => $val) {
            if(in_array($field, $table_fields)) {
                // check for password field, if find encript the value of the password
                // if the incoming password is empty we will not change the old password
                if (!empty($val) and in_array($field, $this->_password_field_names)) {
                    $encr_pass = $this->encript_pass($val);

                    $update_string .= "{$field} = '{$encr_pass}',";
                    $values_string .= "'{$encr_pass}',";
                }
                else {
                    // check for increment into the $val - we will search for "+"
                    $plus_pos = strpos($val, "+");
                    $un_key = uniqid();
                    $this->pdo_params_arr[':' . $un_key] = $val;

                    // in case there is a "+" sign
                    if ($plus_pos > -1 and $plus_pos < 50) {
                        $update_string .= "{$field}` = :{$un_key},";
                    }
                    // in case we set NULL for value
                    elseif (strtolower($val) === 'null') {
                        $update_string .= "{$field} = NULL,";
                    }
                    // in case value is a string
                    else {
                        $update_string .= "{$field} = :{$un_key},";
                    }

                    $values_string .= ":{$un_key},";
                }

            $fields_string .= "{$field},";
            }
        }

        $values_string = rtrim($values_string, ',');
        $fields_string = rtrim($fields_string, ',');
        $update_string = rtrim($update_string, ',');

        $query = "INSERT INTO {$table} (".$fields_string.") VALUES (". $values_string .") "
            . "ON DUPLICATE KEY UPDATE " . $update_string;

        return $this->query($query);
    }

    /**
     * Function get_last_added_id
     * Return the last added ID from a query
     */
    public final function get_last_added_id()
    {
        return $this->_conn->lastInsertId();
    }

    /**
     * Function select
     * Here we put fields we want to select with their alias if we need
     * 
     * @param (array) $fields - contain arrays with name - alias pairs
     *      example: [ ['field', 'field AS alias'] ]
     * @return $this
     */
    public final function select(array $fields, $table = '')
    {
        if (empty($fields)) {
            Text::create_error_log("Wrong select parameters for SQL select() !!!");
        }

        if(!empty($table)) {
            $this->table = $table;
        }

        $select = [];

        // when we select all with "*" and have join, the id field will be overwrite
        // with the id of the last joined table, same for the other fields with duplicate names
        if (count($fields) == 1 and $fields[0] == "*") {
            $this->_query_elements['select'] = "*";
            return $this;
        }

        $this->_query_elements['select'] = implode(', ', $fields);
        return $this;
    }

    /**
     * Function update
     *
     * @param (string) $table
     * @param (array) $fields - field => values pairs
     *
     * @return (object) $this
     */
    public final function update($table, array $fields)
    {
        $this->table_fields = $this->get_table_fields($table);
        if(!$this->table_fields) {
            return $this;
        }

        $str = "UPDATE {$table} SET ";
        // keep values here
        $sets_arr = [];

        foreach ($fields as $key => $val) {
            if(in_array($key, $this->table_fields)) {
                // check for password field, if find encript the value of the password
                if (!empty($val) and in_array($key, $this->_password_field_names)) {
                    // if the incoming password is empty we will not change the old password
                    $sets_arr[] = $key." = '{$this->encript_pass($val)}'";
                }
                else {
                    $un_key = uniqid();

                    $sets_arr[] = $key." = :{$un_key}";

                    $this->pdo_params_arr[":{$un_key}"] = $val;
                }
            }
        }

        $str .= implode(', ', $sets_arr);
        $str .= " ";
        $this->_query_elements['update'] = $str;

        return $this;
    }

    /**
     * Function insert
     *
     * @param (string) $table
     * @param (array) $fields - field => values pairs
     * 
     * @return (object) $this
     */
    public final function insert($table, array $fields)
    {
        $str = "INSERT IGNORE INTO {$table} (";

        $this->table_fields = $this->get_table_fields($table);
        
		if(!$this->table_fields) {
            return $this;
        }

        $keys_arr = array_keys($fields);

		$f_values	= array();
		$f_names	= array();

        // check if passed fields are real table fields
        foreach ($keys_arr as $key => $val) {
            if(in_array($val, $this->table_fields)) {
				// check for password field
				if(
					is_array($this->_password_field_names)
					and in_array($val, $this->_password_field_names)
					and $fields[$val] != ''
                ) {
					$f_names[] = $val; // fields array
					$f_values[] = ":" . $val; // values array
					$this->pdo_params_arr[":" . $val] = $this->encript_pass($fields[$val]);
				}
				// all other fields
				else {
					$f_names[] = $val; // fields array
					$f_values[] = ":" . $val; // values array

					$this->pdo_params_arr[":" . $val] = $fields[$val];
				}
            }
        }

        $keys_str = implode(',', $f_names);

        $str .= $keys_str.") VALUES (";
        $str .= implode(",", $f_values) . ")";

		$this->_query_elements['insert'] = $str;

        return $this;
    }

    /**
     * Function delete
     * Here main part is for where clause,
     * with delete() we only confirm the query
     */
    public final function delete()
    {
        $this->_query_elements['is_delete_query'] = true;
        return $this;
    }

    /**
     * Function from
     * Define table from we want to get data. The escape by default is false
     * because most of the time table is added manual, not from parameter.
     * 
     * @param (string) $table - table name with alias if need
     * @param (bool) $do_escape - do we want to escape;
     * @return $this
     */
    public final function from($table)
    {
        $this->_query_elements['from'] = $table;
        return $this;
    }

    /**
     * Function left_join
     * Describe a table for left join
     *
     * @param (string) $t_name - table name
     * @param (string) $fields - relation fields;
     *
     * @return $this
     */
    public final function left_join($table, $fields)
    {
        if (empty(trim($table)) or empty(trim($fields))) {
            Text::create_error_log("First two parameters can not be empty !!!");
        }

        $this->_query_elements['left_join'] .= " LEFT JOIN {$table} ON {$fields}";

        return $this;
    }

    /**
     * Function where
     * Generate WHERE clause in the query
     *
     * @param (string) $field - field name
     * @param (string) $operand - logical operator
     * @param (string) $val - another field or some value, can be empty
     *
     * @return (class) $this
     */
    public final function where($field, $operand, $val)
    {
        // empty where clause
        if (empty($field) or empty($operand)) {
            Text::create_error_log("Empty parameters !!!");
        }

        if ($operand and ! in_array(trim($operand), $this->_mysql_compare_operators)) {
            Text::create_error_log("Please check allowed logical operator !!!: '".print_r($operand)."'");
        }

		$un_key = uniqid();

		$this->_query_elements['where'] .= $field." ".$operand;
		// with IN we no need quotes
		$this->_query_elements['where'] .= trim($operand) == 'IN' ? " ".$val." " : " :{$un_key} ";

		$this->pdo_params_arr[':' . $un_key] = $val;

        return $this;
    }

    /**
     * Function and_where
     * Add 'AND some_clause' to the where
     *
     * @param string $field
     * @param string $operand
     * @param string $val
     *
     * @return object
     */
    public final function and_where($field, $operand, $val)
    {
        if ($this->_query_elements['where'] != '') {
            $this->_query_elements['where'] .= "AND ";
        }

        $this->where($field, $operand, $val);
        return $this;
    }

    /**
     * Function or_where
     * Add 'OR some_clause' to the where
     *
     * @param string $field
     * @param string $operand
     * @param string $val
     *
     * @return object
     */
    public final function or_where($field, $operand, $val)
    {
        if ($this->_query_elements['where'] != '') {
            $this->_query_elements['where'] .= "OR ";
        }

        $this->where($field, $operand, $val);
        return $this;
    }

    /**
     * Function group_by
     * Generates GROUP BY part of the query. By default escape is false,
     * because most of the times the data do not come from input.
     *
     * @param (string) $field - field to group by
     *
     * @return (class) $this
     */
    public final function group_by($field)
    {
        if (!$field) {
            Text::create_error_log("Please put group by condition !!!");
        }

        if(!empty($this->table_fields) and !in_array($field, $this->table_fields)) {
            return $this;
        }

        $this->_query_elements['group_by'] = "GROUP BY {$field} ";
        return $this;
    }

    /**
     * Function order_by
     * Generates ORDER BY part of the query
     *
     * @param (array) $conds - conditions
     * @param (string) $order - ASC or DESC
     *
     * @return (class) $this
     */
    public final function order_by(array $conds, $order = 'ASC')
    {
        if (count($conds) < 1) {
            Text::create_error_log("Please put order by condition !!!");
        }

        if(empty($this->table_fields)) {
            return $this;
        }

        // check if all fields are real
        foreach($conds as $key => $val) {
            if(!in_array($val, $this->table_fields)) {
                unset($conds[$key]);
            }
        }

        $this->_query_elements['order'] = " ORDER BY ";
        $c = implode(', ', $conds);

        $this->_query_elements['order'] .= $c;
        $this->_query_elements['order'] .= ' '.$order." ";
    //    Text::debug($this->_query_elements['order']."");
        return $this;
    }

    /**
     * Function limit
     * Set results limit for a query
     * 
     * @param (string) $limit
     * @return (class) $this
     */
    public final function limit($limit)
    {
        $this->_query_elements['limit'] = "LIMIT :limit ";
        $this->pdo_params_arr[':limit'] = $limit;

        return $this;
    }

    /**
     * Function exec_query
     * Execute the generated query string.
     * 
     * @param (bool) $field_as_key - do we want field value to be used as key in array
     * @param (string) $ids_col - the name of the above field
     * @param (bool) $debug - debug query
     * @param (bool) $die - die
     * 
     * @return (bools)
     */
    public final function exec_query($field_as_key = false, $ids_col = 'id', $debug = false, $die = false)
    {
        $query = '';

        // in case we have SELECT
        if ($this->_query_elements['select'] != '') {
            $query = "SELECT ".$this->_query_elements['select']." ";
        }
        // in case we have INSERT
        elseif ($this->_query_elements['insert'] != '') {
            $query .= $this->_query_elements['insert'];
        }
        // in case we have UPDATE
        elseif ($this->_query_elements['update'] != '') {
            $query .= $this->_query_elements['update'];
        }
        // in case we have DELETE
        elseif ($this->_query_elements['is_delete_query'] === true) {
            $query = "DELETE ";
        }

        // FROM, when update the syntax is different
        if ($this->_query_elements['update'] == '' and $this->_query_elements['insert']
            == '') {
            $query .= "FROM ".($this->_query_elements['from'] == '' ? $this->table
                    : $this->_query_elements['from'])." ";
        }

        // LEFT JOIN
        if ($this->_query_elements['left_join'] != '' and ! $this->_query_elements['is_delete_query']) {
            $query .= $this->_query_elements['left_join']." ";
        }

        // WHERE
        if ($this->_query_elements['where'] != '') {
            $query .= "WHERE ".$this->_query_elements['where']." ";
        }

        if ($this->_query_elements['group_by']) {
            $query .= $this->_query_elements['group_by'];
        }

        if ($this->_query_elements['update'] == '' and $this->_query_elements['insert']
            == '') {
            // ORDER BY
            if ($this->_query_elements['order'] != '') {
                $query .= $this->_query_elements['order']." ";
            }

            // LIMIT
            if ($this->_query_elements['limit'] != '') {
                $query .= $this->_query_elements['limit'];
            }
        }

        // unset help variables
        foreach ($this->_query_elements as $key => $el) {
            $this->_query_elements[$key] = '';
        }

        if ($debug) {
            Text::debug($query,false);
        }

        if ($die) {
            die('exec query');
        }

        return $this->query($query, $field_as_key, $ids_col);
    }

    /**
     * Function delete_by_id
     * The function delete a record by ID
     *
     * @param (int) $id
     * @param (string) $table
     * @return (bool) the result
     */
    public final function delete_by_id($id, $table = '')
    {
        $tab   = $table == '' ? $this->table : $table;
        $query = "DELETE FROM {$tab} WHERE id = ".intval($id);

        return $this->query($query);
    }

    /**
     * Function mem_get_records
     * Get memcached keys by names in passed array
     * 
     * @param array $keys - name of keys
     * @return array $results - assocciative array with results
     */
    public final function mem_get_records(array $keys)
    {
        $results = array();
        $mem = $this->_get_memcached();
        
        if(count($keys) > 1){
            foreach($keys as $k) {
                $results[$k] = $mem->get($k);
            }
        }
        elseif(count($keys) == 1) {
            $results = $mem->get($keys[0]);
        }
        
        return $results;
    }
    
    /**
     * Function mem_set_records
     * Set memcached keys
     * 
     * @param string $key
     * @param mixed $val
     * @param int $exp_time - in miliseconds, 0 for not expired
     * 
     * @return bool - Returns TRUE on success or FALSE on failure
     */
    public final function mem_set_records($key, $val, $exp_time = 0)
    {
        $mem = $this->_get_memcached();
        return $mem->set($key, $val, $exp_time);
    }

    /**
     * Function mem_del_records
     * Delete memcached keys
     * 
     * @param array $keys - name of keys
     * @return bool - Returns TRUE on success or FALSE on failure
     */
    protected final function mem_del_records(array $keys)
    {
        $mem = $this->_get_memcached();
        
        foreach($keys as $k) {
            if(!$mem->delete($k)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Function query
     *
     * Execute user query and return results.
     * If this is SELECT query:
     * 		If query result is more than 1 row we return multidimensional array. If result have
     * 		`id` column its values will be used for keys in array.
     * 		If the result is only 1, we return simple associative array.
     * If this is other query:
     * 		Return bool.
     * If there is no results we return FALSE
     *
     * @param (string) $query - MySQL query
     * @param (bool) $id_as_key - do I use id col as key of result array
     * @param (string) $ids_col - the name of ids column if it is different then 'id'
     *
     * @return (array)$result_data, TRUE or FALSE
     */
    protected final function query($query, $id_as_key = true, $ids_col = 'id')
    {
        if ($this->query_debug) {
            Text::debug($query);
        }

        if ($this->die) {
            die("Script was stopped from user!");
        }

        try {
            $stmt = $this->_conn->prepare($query);

            if(!empty($this->pdo_params_arr)) {
                $results = $stmt->execute($this->pdo_params_arr);
            }
            else {
                $results = $stmt->execute();
            }

            // reset the arrays
            $this->pdo_params_arr = array();
            $this->table_fields = array();
        }
        catch (Exception $ex) {
            $error_report = "\n".date("Y-m-d H:i:s").
                "\n SQL Error: ". $ex->getMessage() ."\n".
                "\n".'Your query:<br/>'."\n".$query."\n";

            Text::create_error_log($error_report);
        }
        
        $query_to_lower = strtolower($query);
        $this->query_results = $stmt->rowCount();

        // on UPDATE, INSERT or DELETE
        if (
            strpos($query_to_lower, 'update') !== false
            or strpos($query_to_lower, 'insert') !== false
            or strpos($query_to_lower, 'delete') !== false
        ) {
            return $results;
        }
        // on SELECT and others
        else {
            $stmt->setFetchMode(PDO::FETCH_ASSOC); // boolean

            $results    = $stmt->fetchAll();
            $errors     = $this->get_error($stmt);

            if ($this->show_num_rows) {
                Text::debug($stmt->rowCount());
            }

            if ($this->query_results > 0) {
                $result_data = [];

                // by this way we do this check once
                if ($id_as_key) {
                    foreach($results as $row) {
                        if (isset($row[$ids_col])) {
                            $result_data[$row[$ids_col]] = $row;
                        }
                        else {
                            $result_data[] = $row;
                        }
                    }
                }
                else {
                    $result_data = $results;
                }

                // for single result, return one dimensional array
                if ($stmt->rowCount() == 1 and ! $id_as_key) {
                    return current($result_data);
                }

                return $result_data;
            }
            else {
                return false;
            }
        }
    }

    /**
     * Function get_error
     * Get mysql error and number and return them.
     * 
     * @return (array)
     */
    protected final function get_error($stmt = null)
    {
        return array(
            'error_number' => $stmt->errorCode(),
            'error_msg' => $stmt->errorInfo()
        );
    }

    /**
     * Function disconnect
     * This function disconnect us from the DB
     */
    protected final function disconnect()
    {
        $this->_conn = null;
    }

    /**
     * Function get_table_fields
     * Get list of the fields in the table we work on.
     *
     * @param string $table
     * @return boolean|array
     */
    private function get_table_fields($table = '')
    {
        if(empty($table)) {
            $table = $this->table;
        }

        $stmt = $this->_conn->prepare("DESCRIBE {$table}");
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt->fetchAll();

        if($results) {
            return array_column($results, 'Field');
        }

        return false;
    }

    /**
     * Function _get_memcached
     * Create and/or return memcache object
     * 
     * @return (object) memcache object
     */
    private function _get_memcached()
    {
        if(!$this->_memcache) {
            try {
                $this->_memcache = new Memcached();
                $this->_memcache->setOptions(array(
                    Memcached::OPT_PREFIX_KEY => MEM_KEY_PREFIX,
                    Memcached::OPT_COMPRESSION => TRUE
                ));
                $this->_memcache->addServer(MEM_HOST, MEM_PORT);
            }
            catch (Exception $ex) {
                Text::create_error_log('Error when try to create Memcached object, error: ' . $ex->getMessage());
            }
        }
        
        return $this->_memcache;
    }
}