<?php

require_once realpath(dirname(__FILE__)) . "/" . 'Linda.inc';

/**
 * 
 * @brief Linda is a Database Abstraction Layer for PHP Built on top PDO.
 * @author Olubodun Agbalaya.
 */
class Linda {

    public $LINDA_ERROR = NULL;
    protected $DB_LINK;
    protected $TABLE_MODEL;
    public $CURRENT_QUERY;
    protected $MODEL_SCHEMA = array();
    protected $DEFAULT_LIMIT = 1000;
    protected $DEFAULT_START_INDEX = 0;
    protected $DISTINCT = FALSE;
    protected $resultObject = NULL;
    protected $lastAffectedRowCount = 0;
    protected $queryConfig = array();

    const Linda_DB_HOST = LINDA_DB_HOST;
    const Linda_DB_NAME = LINDA_DB_NAME;
    const Linda_DB_TYPE = LINDA_DB_TYPE;
    const Linda_DB_USER = LINDA_DB_USER;
    const Linda_DB_PASSW = LINDA_DB_PASSW;

    public function __construct() {

        $this->initConnnection();
    }

    /**
     * @ignore
     * @internal  
     */
    public function setTable($tableName) {


        if (is_string($tableName)) {
            $this->TABLE_MODEL = $tableName;
        }
    }

    /**
     * @ignore
     * @internal 
     */
    //+========================================================================================
    protected function parseModel() {

        $this->MODEL_SCHEMA = []; //always reset;

        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = :table";
        try {
            $core = $this->DB_LINK;
            $stmt = $core->prepare($sql);
            $stmt->bindValue(':table', $this->TABLE_MODEL, PDO::PARAM_STR);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->MODEL_SCHEMA[] = $row['COLUMN_NAME'];
            }
            return $this->MODEL_SCHEMA;
        } catch (PDOException $pe) {
            trigger_error('Could not connect to MySQL database. ' . $pe->getMessage(), E_USER_ERROR);
        }
    }

//+=========================================================================================================
    /**
     * @ignore
     * @internal 
     */
    private function initConnnection() {
        try {
            $this->DB_LINK = new PDO(self::Linda_DB_TYPE . ':host=' . self::Linda_DB_HOST . ';dbname=' . self::Linda_DB_NAME, self::Linda_DB_USER, self::Linda_DB_PASSW, array(PDO::ATTR_PERSISTENT => true));

            $this->DB_LINK->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return TRUE;
        } catch (PDOException $pe) {
            $this->LINDA_ERROR = "DB_CONNECT_ERROR";
            throw $pe;
        }
    }

//+=========================================================================================================

    /**
     * The get method fetches data from the table
     * @param array() | string $feilds An associative array containing fields to get from the table, or the string *
     * @param array() $queryConfig A configuration array, that contains option for the get operation
     * 
     * @ 
     *  $queryConfig = array(
     *                      
     *                     "whereGroup" => array(  
     *                       [
     *                           "actor_id"=>array("value"=>5, "operator"=>"="),
     *                          "last_name"=>array("value"=>"'%ER", "operator"=>"LIKE"),
     *                          "comparisonOp"=>"AND",
     *                          "nextOp"=>"OR"
     *                        ],
     *
     *                     [
     *           "actor_id"=>array("value"=>"last_name", "operator"=>"LIKE")
     *
     *                     ] ),
     *
     *                     
     *
     * 
     *                      "where_in"=>array(
     *                             fieldName => "id",
     *                             options => " 10, 15, 22,
     *                               query => "",
     *                              operator = > "AND"
     * 
     *                          ),
     *                    
     *                     "innerJoinGroup" => array(
     *                       [
     *                       table => "table_name",
     *                       conditional_column_a => "column name" 
     *                       conditional_column_b => "column name" 
     *                       ],
     *                       [
     *                     table => "table_name",
     *                       conditional_column => "column name" 
     *                       ]
     *       
     * 
     *                     ) ,
     *                          
     *                      "limit" =>[
     *                               index => 2,
     *                               count =>18
     *                              
     *                             ],
     * 
     *                )
     */
    public function fetch($fields, $queryConfig = array()) {

        $this->resultObject = null;
        $this->queryBuilder("select", $fields, $queryConfig);

        $this->runQuery();
        return $this;
    }

    //+=========================================================================================================

    /**
     * Update fields in the DB

     * @param Array $fields is an associative array containing values to set
     * @example 
     * $fields = array(
     *                        id= "1",
     *                        email = "example.example.org
     *                    )
     *  using NOW() or TIME() as values, inserts the date/time
     * @param type $queryConfig see #insert for structure of the queryConfig parameter

     *
     */
    public function update($fields, $queryConfig) {
        $this->resultObject = NULL;

        $this->queryBuilder("update", $fields, $queryConfig);
        $this->runQuery();

        return $this;
    }

    //+=========================================================================================================
    /**
     *  put inserts data into the table
     * @param Array $fields An associative array, containing column names that matches the actual table
     * @param Array $values An associative array, values to be inserted per column, using NOW() or TIME(), inserts the date/time
     * in either YMD format or YMD H:m:s format
     * @param type $fields
     * @param type $queryConfig
     */
    public function put($fields, $values) {

        $this->resultObject = NULL;
        $this->createInsert($fields, $values);

        $this->runQuery();
        return $this;
    }

    //+=========================================================================================================
    /**
     *  Deletes data from a table

     * @param type $queryConfig, see #insert for structure of the queryConfig parameter
     * 
     */
    public function delete($queryConfig) {
        $this->resultObject = null;
        $this->queryBuilder("delete", null, $queryConfig);

        $this->runQuery();



        return $this;
    }

    //+======================================================+
    /**
     * 
     * @internal 
     * @ignore
     */
    protected function sanitize($inp) {//escape values for database query
        if (is_array($inp))
            return array_map(__METHOD__, $inp);


        if (!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', '\\"', '\\Z'), $inp);
        }

        return trim(strip_tags(stripslashes(htmlentities($inp, ENT_QUOTES, 'UTF-8'))));
    }

    //+======================================================+
//*argument $inserts is an associative array of column names and value-data 
    protected function createInsert($fields, $inserts) {
        //call our custom escape function on the data-values

        $values = array_map(array($this, "sanitize"), array_values($inserts));
        $values = array_map(array($this, "string_or_int"), array_values($inserts));
        $keys = array_keys($inserts);

        $this->CURRENT_QUERY = "INSERT INTO `" . $this->TABLE_MODEL . "` (" . implode(',', $fields) . ") VALUES (" . implode(", ", $values) . ")";
    }

    //+==================================================================+
    //this method checks if a value is a string and quotes it if it is
    protected function string_or_int($val) {

        if (!is_numeric($val) || "+" === substr($val, 0, 1)) {

            if ($val !== NULL)
                return "'" . $val . "'"; //add quotes
        }

        return $val;
    }

    //+=========================================================================================================
    /**
     * 
     * @internal 
     * @ignore
     */
    protected function queryBuilder($mode, $fields, $queryConfig) {

        switch ($mode) {

            case "select":

                $inner_join_prefix = ""; //to be set fot table 1 if we have a join


                $this->CURRENT_QUERY .= " SELECT " . ($this->DISTINCT ? "DISTINCT " : "") . ( is_string($fields) ? "* " : implode(",", $fields)) . " FROM `" . $this->TABLE_MODEL . "` ";

                //handle joins  
                $join_count = 0; //
                foreach ($queryConfig as $index => $item) {//loop through each inner join array argument
                    if (FALSE !== strpos($index, "innerJoinGroup")) {

                        $this->CURRENT_QUERY .= " AS T" . ( ++$join_count);

                        foreach ($item as $key => $val) {
                            $this->CURRENT_QUERY .= " INNER JOIN `" . $val['table'] . "` AS T" . ( ++$join_count) . " ON T1." . $val['conditional_column_a'] . " = T" . ($join_count) . "." . $val['conditional_column_b'] . " ";
                        }
                    }
                }

                //handle where clause
                $where_clause_counter = 1;
                $in_where_clause = 1;
                $inner_join_prefix_where = "T1."; //table prefix:  if we have table joins, columns in where clauses are always prefixed with the main table i.e T1

                foreach ($queryConfig as $index => $item) {//loop through each where Groups
                    if (FALSE !== strpos($index, "whereGroup")) { //HANDLE WHERE CLUASE 
                        if ($where_clause_counter++ === 1)
                            $this->CURRENT_QUERY .= " WHERE(";

                        foreach ($item as $key => $whereGroupIndex) {//loop through each where Groups
                            $nextComparisonOp = isset($whereGroupIndex["nextOp"]) ? $whereGroupIndex["nextOp"] : "AND";
                            if ($in_where_clause > 1) {
                                $in_where_clause = 1;
                            }

                            foreach ($whereGroupIndex as $key2 => $val2) {//loop through each whereGroups
                                if ($key2 !== "comparisonOp" && $key2 !== "nextOp" && $key2 !== "join_index") {
                                    if ($in_where_clause++ > 1)
                                        $this->CURRENT_QUERY .= isset($whereGroupIndex['comparisonOp']) ? " " . $whereGroupIndex['comparisonOp'] . " " : " AND ";

                                    if ($key2 !== "operator") //this key shouldnt be added as a value
                                        $this->CURRENT_QUERY .= " "

                                                //if we have inner joins, we check if they have set a joined table index to use as the where column prefix
                                                . ($join_count ? ($whereGroupIndex['join_index'] !== NULL ? "T" . $whereGroupIndex['join_index'] . "." : $inner_join_prefix_where) : "")


                                                //add the column name and comparison operator
                                                . $key2 . " " . (isset($val2['operator']) ? $val2['operator'] : "=") . " "

                                                //add the column value we are comparing with
                                                . $this->sanitize($this->string_or_int($val2 ['value']));
                                };
                            }

                            $this->CURRENT_QUERY .= " )";
                            if (next($item))
                                $this->CURRENT_QUERY .= " " . $nextComparisonOp . " (";
                        }
                    }
                }


                //handle where_in_*
                if (isset($queryConfig['where_in'])) {

                    $where_operator = isset($queryConfig['where_in']['operator']) ? " " . $queryConfig['where_in']['operator'] . " " : " AND ";


                    if ($where_clause_counter++ > 1) {

                        $this->CURRENT_QUERY .= $where_operator . " " . $queryConfig['where_in']['fieldName'] . " IN (" .
                                (isset($queryConfig['where_in']['query']) ? $queryConfig['where_in']['query'] : $queryConfig['where_in']['options']) . ")";
                    }
                    else {

                        $this->CURRENT_QUERY .= " WHERE " . $queryConfig['where_in']['fieldName'] . " IN (" .
                                (isset($queryConfig['where_in']['query']) ? $queryConfig['where_in']['query'] : $queryConfig['where_in']['options']) . ")";
                    }
                }



                if (isset($queryConfig['limit'])) {
                    $this->CURRENT_QUERY .= " LIMIT " . $queryConfig['limit']['index'];
                    $this->CURRENT_QUERY .= ",  " . $queryConfig['limit']['count'];
                }
                else {
                    $this->CURRENT_QUERY .= " LIMIT " . $this->DEFAULT_START_INDEX;
                    $this->CURRENT_QUERY .= ", " . $this->DEFAULT_LIMIT;
                }

                $this->CURRENT_QUERY .= ";";

                break;

            case "update":

                $update_column_count = 1;
                $this->CURRENT_QUERY = "UPDATE `" . $this->TABLE_MODEL . "` SET `";


                foreach ($fields as $key => $val) {

                    if ($update_column_count++ > 1)
                        $this->CURRENT_QUERY .= ",`"; //seperate the next row feild/value

                    if (is_array($val))
                        $this->CURRENT_QUERY .= $key . "` = (" . $val[0] . " )";
                    else
                        $this->CURRENT_QUERY .= $key . "` = " . $this->sanitize($this->string_or_int(($val))) . " ";
                }


                //handle where clause
                $where_clause_counter = 1;
                $in_where_clause = 1;
                foreach ($queryConfig as $index => $item) {//loop through each where Groups
                    if (FALSE !== strpos($index, "whereGroup")) { //HANDLE WHERE CLUASE 
                        if ($where_clause_counter++ === 1)
                            $this->CURRENT_QUERY .= " WHERE(";

                        foreach ($item as $key => $whereGroupIndex) {//loop through each where Groups
                            $nextComparisonOp = isset($whereGroupIndex["nextOp"]) ? $whereGroupIndex["nextOp"] : "AND";
                            if ($in_where_clause > 1) {
                                $in_where_clause = 1;
                            }

                            foreach ($whereGroupIndex as $key2 => $val2) {//loop through each whereGroups
                                if ($key2 !== "comparisonOp" && $key2 !== "nextOp") {
                                    if ($in_where_clause++ > 1)
                                        $this->CURRENT_QUERY .= isset($whereGroupIndex['comparisonOp']) ? " " . $whereGroupIndex['comparisonOp'] . " " : " AND ";

                                    if ($key2 !== "operator") //this key shouldnt be added as a value
                                        $this->CURRENT_QUERY .= " `" . $key2 . "` " . (isset($val2['operator']) ? $val2['operator'] : "=") . " " . $this->sanitize($this->string_or_int($val2 ['value']));
                                };
                            }

                            $this->CURRENT_QUERY .= " )";
                            if (next($item))
                                $this->CURRENT_QUERY .= " " . $nextComparisonOp . " (";
                        }
                    }
                }

                //handle where_in_*
                if (isset($queryConfig['where_in'])) {
                    $where_operator = isset($queryConfig['where_in']['operator']) ? " " . $queryConfig['where_in']['operator'] . " " : " AND ";


                    if ($where_clause_counter++ > 1) {

                        $this->CURRENT_QUERY .= $where_operator . " " . $queryConfig['where_in']['fieldName'] . " IN (" .
                                ($queryConfig['where_in']['query'] ? $queryConfig['where_in']['query'] : $queryConfig['where_in']['options']) . ")";
                    }
                    else {

                        $this->CURRENT_QUERY .= " WHERE " . $queryConfig['where_in']['fieldName'] . " IN (" .
                                ($queryConfig['where_in']['query'] ? $queryConfig['where_in']['query'] : $queryConfig['where_in']['options']) . ")";
                    }
                }

                $this->CURRENT_QUERY .= " ;";





                break;
            case "delete":

                $delete_column_count = 1;
                $this->CURRENT_QUERY = "DELETE FROM `" . $this->TABLE_MODEL . "` ";
                $this->CURRENT_QUERY .= "";

                //handle where clause
                $where_clause_counter = 1;
                $in_where_clause = 1;
                foreach ($queryConfig as $index => $item) {//loop through each where Groups
                    if (FALSE !== strpos($index, "whereGroup")) { //HANDLE WHERE CLUASE 
                        if ($where_clause_counter++ === 1) {
                            $this->CURRENT_QUERY .= " WHERE(";
                        }

                        foreach ($item as $key => $whereGroupIndex) {//loop through each where Groups
                            $nextComparisonOp = isset($whereGroupIndex["nextOp"]) ? $whereGroupIndex["nextOp"] : "AND";
                            if ($in_where_clause > 1) {
                                $in_where_clause = 1;
                            }

                            foreach ($whereGroupIndex as $key2 => $val2) {//loop through each whereGroups
                                if ($key2 !== "comparisonOp" && $key2 !== "nextOp") {
                                    if ($in_where_clause++ > 1) {
                                        $this->CURRENT_QUERY .= isset($whereGroupIndex['comparisonOp']) ? " " . $whereGroupIndex['comparisonOp'] . " " : " AND ";
                                    }

                                    if ($key2 !== "operator") { //this key shouldnt be added as a value
                                        $this->CURRENT_QUERY .= " `" . $key2 . "` " . (isset($val2['operator']) ? $val2['operator'] : "=") . " " . $this->sanitize($this->string_or_int($val2 ['value']));
                                    }
                                }
                            }

                            $this->CURRENT_QUERY .= " )";
                            if (next($item)) {
                                $this->CURRENT_QUERY .= " " . $nextComparisonOp . " (";
                            }
                        }
                    }
                }

                //handle where_in_*
                if (isset($queryConfig['where_in'])) {

                    $where_operator = isset($queryConfig['where_in']['operator']) ? $queryConfig['where_in']['operator'] : " AND ";


                    if ($where_clause_counter++ > 1) {

                        $this->CURRENT_QUERY .= $where_operator . " " . $queryConfig['where_in']['fieldName'] . " IN (" .
                                ($queryConfig['where_in']['query'] ? $queryConfig['where_in']['query'] : $queryConfig['where_in']['options']) . ")";
                    }
                    else {

                        $this->CURRENT_QUERY .= " WHERE " . $queryConfig['where_in']['fieldName'] . " IN (" .
                                ($queryConfig['where_in']['query'] ? $queryConfig['where_in']['query'] : $queryConfig['where_in']['options']) . ")";
                    }
                }

                if (isset($queryConfig['LIMIT'])) {

                    $this->CURRENT_QUERY .= " LIMIT " . (int) $queryConfig['LIMIT'];
                }
                $this->CURRENT_QUERY .= ";";

                break;
        }
        return $this;
    }

    //+===========================================================================================
    //run the query and set the return object
    /**
     * @internal 
     * @ignore
     * @return $this
     */
    public function runQuery() {
        $this->CURRENT_QUERY = str_replace("NOW()", date("Y-m-d"), $this->CURRENT_QUERY);
        $this->CURRENT_QUERY = str_replace("TIME()", date("Y-m-d H:i:s"), $this->CURRENT_QUERY);
        $this->LINDA_ERROR = "";
        $this->lastAffectedRowCount = 0;

// echo "<br/><br/>".$this->CURRENT_QUERY."<br/><br/>";
        $stmnt;


        try {
            if (($stmnt = $this->DB_LINK->prepare($this->CURRENT_QUERY))) {

                if (!$stmnt->execute()) {
                    $this->LINDA_ERROR = "ERROR_EXECUTING_QUERY";
                    $this->resultObject = NULL;
                }

                if ($stmnt->rowCount()) {
                    $this->lastAffectedRowCount = $stmnt->rowCount();
                }


                $this->resultObject = $stmnt->fetchAll(PDO::FETCH_ASSOC);


                if (FALSE === $this->resultObject) {
                    $this->LINDA_ERROR = "ERROR_EXECUTING_QUERY";
                    $this->resultObject = NULL;
                }

                //set the number of rows returned for select ops
                if (!$this->lastAffectedRowCount && count($this->resultObject)) {
                    $this->lastAffectedRowCount = count($this->resultObject);
                }

                if (count($this->resultObject) || $this->lastAffectedRowCount) {
                    
                }
                else {
                    $this->resultObject = NULL;
                }
            }
        } catch (PDOException $e) {

            $this->LINDA_ERROR = $e->getMessage();
            $this->resultObject = NULL;
            // echo $this->LINDA_ERROR;
        }
        $this->DEFAULT_LIMIT = 1000;
        $this->DEFAULT_START_INDEX = 0;
        $this->DISTINCT = FALSE;
        return $this;
    }

    //+===================================================================================================
    /**
     * Counts the total number of rows in the table
     * @return integer The total row count
     */
    public function count() {
        $this->CURRENT_QUERY = "SELECT COUNT(*) AS DAL_counter FROM " . $this->TABLE_MODEL . ";";
        $this->runQuery();


        return $this->getAll()[0]['DAL_counter'];
    }

    //+===================================================================================================
    /**
     * Count the number of rows matching a value in a field, this method executes a query directly on the table and doesn't
     *  work on the retrieved/stored data - so you must have set the table using the #setTable method, prior to calling
     * @param string $columnName field to count values from 
     * @param string $val value to be matched
     * @param string $operator Optional operator to be used default in matching defaults to equals (=), other candidates are ( <, > , <>) 
     * @return integer The number of rows matching the value
     */
    public function countMatching($columnName, $val, $operator = "=") {
        $this->CURRENT_QUERY = "SELECT COUNT(*) AS DAL_counter FROM " . $this->TABLE_MODEL . " WHERE " . $columnName . " " . $operator . " " . $val . ";";
        $this->runQuery();


        return $this->getAll()[0]['DAL_counter'];
    }

    //+===================================================================================================
    /**
     *  Returns the maximum value on a field, this method executes a query directly on the table and doesnt
     *  work on the retrieved/stored data - so you must have set the table using the #setTable method, prior to calling
     * @param string $columnName field to get the minimum value from 
     *  @return integer
     */
    public function maxInColumn($columnName) {

        $this->CURRENT_QUERY = "SELECT MAX(" . $columnName . ") AS DAL_max FROM " . $this->TABLE_MODEL;
        $this->runQuery();


        return $this->getAll()[0]['DAL_max'];
    }

    //+===================================================================================================
    /**
     *  Returns row containing the maximum value of a particular column, this method executes a query directly on the table and doesnt
     *  work on the retrieved/stored data - so you must have set the table using the #setTable method, prior to calling
     * @param string $columnName field to get the maximum value from    
     * @return array() 
     */
    public function maxRow($fieldName) {

        $this->CURRENT_QUERY = "SELECT * FROM " . $this->TABLE_MODEL . " WHERE " . $fieldName . " = (SELECT MAX(" . $fieldName . ") FROM " . $this->TABLE_MODEL . ")";
        $this->runQuery();

        return $this->getAll()[0];
    }

    //+===================================================================================================
    /**
     *  Returns the minimum value on a field, this method executes a query directly on the table and doesnt
     *  work on the retrieved/stored data - so you must have set the table using the #setTable method, prior to calling
     * @param string $columnName field to get the minimum value from 
     *  @return integer
     */
    public function min_in_column($columnName) {

        $this->CURRENT_QUERY = "SELECT MIN(" . $columnName . ") AS Linda_min FROM " . $this->TABLE_MODEL;
        $this->runQuery();

        return $this->getAll()[0]['Linda_min'];
    }

    //+===================================================================================================
    /**
     *  Returns row containing the maximum value of a particular column,, this method executes a query directly on the table and doesnt
     *  work on the retrieved/stored data - so you must have set the table using the #setTable method, prior to calling
     * @param string $columnName field to get the minimum value from 
     * @return array()
     */
    public function minRow($columnName) {

        $this->CURRENT_QUERY = "SELECT * FROM " . $this->TABLE_MODEL . " WHERE " . $columnName . " = (SELECT MIN(" . $columnName . ") FROM " . $this->TABLE_MODEL . ")";
        $this->runQuery();

        return $this->getAll()[0];
    }

    //+===================================================================================================
    /**
     *  Returns the sum of values on a field/column, this method executes a query directly on the table and doesnt
     *  work on the retrieved/stored data - so you must have set the table using the #setTable method, prior to calling
     * @param string $columnName field to get the minimum value from 
     * 
     */
    public function sum($columnName) {

        $this->CURRENT_QUERY = "SELECT SUM(" . $columnName . ") AS DAL_sum FROM " . $this->TABLE_MODEL;
        $this->runQuery();

        return $this->getAll()[0]['DAL_sum'];
    }

    //+===================================================================================================
    /**
     *  Returns the entire result set as an array
     *  @retun array()
     */
    public function getAll() {

        if (count($this->resultObject)) {


            return $this->resultObject;
        }
        return NULL;
    }

    //+===================================================================================================
    /**
     *  Returns the row at a particular index in the result set
     * @return integer
     */
    public function getRowAtIndex($index) {

        if ($this->resultObject && count($this->resultObject) >= $index) {
            return $this->resultObject[$index];
        }
    }

    /**
     *  Returns the total number of rows in the result set, or the numbers of rows affected by the last update/delete operation
     * @return integer
     */
    public function numRows() {

        return $this->lastAffectedRowCount;
    }

    /**
     *  Returns the first row in the result set
     * @return array()
     */
    public function peek() {

        if (count($this->resultObject)) {
            return $this->resultObject(0);
        }
    }

    /**
     *  Returns the last row in the result set
     * @return array()
     */
    public function tail() {

        if (count($this->resultObject)) {
            return $this->resultObject(count($this->resultObject) - 1);
        }
    }

    /**
     *  Returns even indexes from the stored result set
     * @return array()
     */
    public function even() {
        $resultArray = array();
        if (count($this->resultObject)) {

            for ($i = 0; $i < count($this->resultObject); $i++) {
                if ($i % 2 === 0) {
                    $resultArray[] = $this->resultObject[$i];
                }
            }
        }
        return $resultArray;
    }

    /**
     *  Returns odd indexes from the stored result set
     * @return array()
     */
    public function odd() {

        $resultArray = array();
        if ($this->resultObject) {

            for ($i = 0; $i < count($this->resultObject); $i++) {
                if ($i & 2 !== 0) {
                    $resultArray[] = $this->resultObject[$i];
                }
            }
        }
        return $resultArray;
    }

    public function hasErrors() {
        return ($this->LINDA_ERROR && strpos("SQLSTATE[HY000]: General error", $this->LINDA_ERROR) === FALSE);
    }

    public function __destruct() {
        
    }

}
