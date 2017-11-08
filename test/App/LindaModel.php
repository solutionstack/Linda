<?php

namespace solutionstack\Linda;

require_once \realpath(\dirname(__FILE__)) . "/" . 'Linda.php';
require_once \realpath(\dirname(__FILE__)) . "/" . 'LindaRowModel.php';

/*
 * @brief LindaModel is an Object Oriented Mapper (ORM) for PHP/MySql, providing a super simple interface,
 * when working with mysql tables
 *
 * @author Olubodun Agbalaya.
 */

class LindaModel extends Linda {

    protected $tableColumnSchema;
    protected $virtualModelCollection = array();
    protected $virtualModelPrimaryKey = null;
    protected $virtualModelPrimaryKeyColumnIndex;
    protected $modelName;

    /**
     * @param string $Model The name of the table we are to create an Object Map to
     * @param string $Key An optional key on the table, to be used for updates, defaults to the primary key if present
     *
     *
     */
    public function __construct($Model, $Key = null) {
        
        if(!$Model || empty($Model)){
            
            throw new \InvalidArgumentException("Class ".__CLASS__.": expects at least one string argument");
        }

        parent::__construct();

        if (null !== $Model) {
            $this->setTable($Model);//basically just the table name

            $this->tableColumnSchema = $this->parseModel();
            $this->modelName = $Model;
        }

        if ($this->defaultPrimaryKeyColumn || $Key !== null) {
            //store the primary key
            $this->virtualModelPrimaryKey = $Key ? $Key : $this->defaultPrimaryKeyColumn;

            //get the column index on the table for this primary key
            if (false === ($keyColumnIndex = \array_search($this->virtualModelPrimaryKey, $this->tableColumnSchema))) {
                throw new Exception('Primary Key ' . $Key . "  not found!");
            }

            $this->virtualModelPrimaryKeyColumnIndex = $keyColumnIndex;
        }
    }

    /**
     * Method to fetch all rows from the DB into memory, use #get to retrieve the fields as a collection of Models
     * @return $this
     */
    public function fetchAll() {
        $this->virtualModelCollection = array();

        $this->fetch("*");

        if( ($c = \count($this->resultObject))) {
            for ($i = 0; $i < $c; $i++) {

                $this->virtualModelCollection[] = new LindaRowModel($this->tableColumnSchema, $this->resultObject[$i]);
            }
        }


        return $this;
    }
    
     //+===================================================================================================
    /**
     *  Returns the sum of values on a field/column, this method executes a query directly on the table and doesn't
     *  work on the retrieved/stored data
     * @param string $columnName field to get the minimum value from
     *
     */
    public function sum($columnName)
    {

        $this->currentQuery = "SELECT SUM(" . $columnName . ") AS linda_sum_rows_on_column FROM " . $this->modelName;
        $this->runQuery();

        return $this->getAll()[0]['linda_sum_rows_on_column'];
    }

    /**
     * Returns first model representing an active record, or NULL if no records where matched
     * @return LindaRowModel
     * @deprecated since version 1.0.0
     */
    public function first() {

        return $this->virtualModelCollection && isset($this->virtualModelCollection[0]) ? $this->virtualModelCollection[0] : null;
    }

    /**
     * Returns the last model representing an active record, or NULL if no records where matched
     * @return LindaRowModel
     * @deprecated since version 1.0.0
     */
    public function last() {

        return $this->virtualModelCollection && isset($this->virtualModelCollection[0]) ? $this->virtualModelCollection[\count($this->virtualModelCollection) - 1] : null;
    }

     /**
     * Returns first model representing an active record, or NULL if no records where matched
     * @return LindaRowModel
     */
    public function peek() {
        return $this->first();
    }

   /**
     * Returns the last model representing an active record, or NULL if no records where matched
     * @return LindaRowModel
     */
    public function tail() {

        return $this->last();


    }

      //+===================================================================================================
    /**
     * Counts the total number of rows in the table
     * @return integer The total row count
     */
    public function count()
    {
        $this->currentQuery = "SELECT COUNT(*) AS linda_total_rows_counter FROM " . $this->modelName . ";";
        $this->runQuery();

        return $this->getAll()[0]['linda_total_rows_counter'];
    }
    
     //+===================================================================================================
    /**
     * Count the number of rows matching a value in a field, this method executes a query directly on the table and doesn't
     *  work on the retrieved/stored data 
     * @param string $columnName field to count values from
     * @param string $val value to be matched
     * @param string $operator Optional operator to be used default in matching defaults to equals (=), other candidates are ( <, > , <>)
     * @return integer The number of rows matching the value
     */
    public function countMatching($columnName, $val, $operator = "=")
    {
        $this->currentQuery = "SELECT COUNT(*) AS linda_matching_rows_counter " . $this->modelName . " WHERE " . $columnName . " " . $operator . " " . $val . ";";
        $this->runQuery();

        return $this->getAll()[0]['linda_matching_rows_counter'];
    }
    
    /**
     *  Returns even indexes  representing an active record objects, or NULL if no records where matched
     * @return array()
     */
    public function even() {
        $resultArray = array();
        if (($len = \count($this->virtualModelCollection))) {

            for ($i = 0; $i < $len; $i++) {
                if ($i % 2 === 0) {
                    $resultArray[] = $this->virtualModelCollection[$i];
                }
            }
        }
        return count($resultArray) ? $resultArray : null;
    }

    /**
     *  Returns odd indexes  representing an active record objects, or NULL if no records where matched
     * @return array()
     */
    public function odd() {

        $resultArray = array();
        if (($len = \count($this->virtualModelCollection))) {

            for ($i = 0; $i < $len; $i++) {
                if ($i & 2 !== 0) {
                    $resultArray[] = $this->virtualModelCollection[$i];
                }
            }
        }
        return \count($resultArray) ? $resultArray : null;
    }

    /**
     *  Returns the values in the internal result set as an array
     * @return array() | NULL
     */
    public function getValues() {

        if (\count($this->virtualModelCollection)) {
            for ($i = 0; $i < \count($this->virtualModelCollection); $i++) {

                $resultArray[] = $this->virtualModelCollection[$i]->getValues();
            }
            return $resultArray;
        }

        return null;
    }

    /**
     *  Returns the values in the internal result set as an atdClass object
     * @return stdClass() | NULL
     */
    public function getValuesAsObject() {

        if (\count($this->virtualModelCollection)) {
            for ($i = 0; $i < \count($this->virtualModelCollection); $i++) {

                $resultArray[] = $this->virtualModelCollection[$i]->getValuesAsObject();
            }
            return $resultArray;
        }

        return null;
    }

    /**
     *  Returns the collection of object row modelsin a random order, or NULL if no records are available
     * @return array()
     */
    public function random() {

        if (\count($this->virtualModelCollection)) {

            \shuffle($this->virtualModelCollection);
            return $this->virtualModelCollection;
        }
        return null;
    }

    /**
     *  Should the data retreival be unique
     *
     */
    public function distinct() {

        $this->distinctResult = true;
        return $this;
    }

    /**
     * Returns a collections of models representing each active record, retrieved from the last call to #get
     * Retruns NULL if no records are available
     * @return LindaRowModelCollection | null
     */
    public function collection() {

        return \count($this->virtualModelCollection) ? $this->virtualModelCollection : null;
    }

    /**
     * Saves data back into the table
     * @return LindaRowModel
     *
     * @param type $new_flag An optional primary key parameter, to use in identifying the row to save data into
     * @return \LindaRowModel|$this
     */
    public function save() {

        if (!$this->virtualModelPrimaryKey) {
            throw new Exception('No Key found, cannot update table -> (' . $this->modelName . "). ");
        }

        for ($i = 0; $i < \count($this->virtualModelCollection); $i++) {

            $fieldsData = $this->virtualModelCollection[$i]->getValues();
            $fieldsName = $this->tableColumnSchema;

            $tableData = \array_combine($fieldsName, $fieldsData);

            $this->update($tableData, array(
                "whereGroup" => array(
                    [
                        $this->virtualModelPrimaryKey => array("value" => $fieldsData[$this->virtualModelPrimaryKeyColumnIndex]),
                    ],
                ),
            ));
        }

        return $this;
    }

//
    /**
     * Update/set properties on the row model, this sets data on the database columns.<br/>
     * The string NOW() and TIME() can be used to insert PHP date values, the formats used are <br/>
     * <code> date("Y-m-d")</code> and <code> date("Y-m-d H:i:s")</code> respectively 
     * call #save when done to commit the data.
     * <pre>
     *   <code>
     *     the format of the argument, is an array with name-value pairs representing column names and values
     *     //eg
     *     $data = array( "age"=>2, update_timestamp=>"TIME()");
     *    </code>
     * 
     * 
     * </pre>
     * @param type $data array containing data to set on the row-model
     * @return $this
     */
    public function set($data = array()) {
        for ($i = 0; $i < \count($this->virtualModelCollection); $i++) {

            for ($j = 0; $j < \count($data); $j++) {

                $this->virtualModelCollection[$i]->{\array_keys($data)[$j]} = \array_values($data)[$j];
            }
        }

        return $this;
    }

    public function where($col, $op, $val) {
        $this->queryConfig["whereGroup"][] = [$col => ["value" => $val, "operator" => $op], "nextOp" => "AND"];
        return $this;
    }

    public function whereOr($col, $op, $val) {
        $this->queryConfig["whereGroup"][] = [$col => ["value" => $val, "operator" => $op], "nextOp" => "OR"];
        return $this;
    }

    public function whereInOr($col, $val) {
        if (0 === \stripos(\trim($val), "select")) {
            //they want to perform a sub-query
            $this->queryConfig["where_in"] = ["fieldName" => $col, "query" => $val, "operator" => "OR"];
        } else {
            $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => "" . \implode(",", $val) . "", "operator" => "OR"];
        }

        return $this;
    }

    public function whereIn($col, $val) {

        if (0 === \stripos(\trim($val), "select")) {
            //they want to perform a sub-query
            $this->queryConfig["where_in"] = ["fieldName" => $col, "query" => $val, "operator" => "AND"];
        } else {
            $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => "" . \implode(",", $val) . "", "operator" => "AND"];
        }
        return $this;
    }

    public function innerJoin($table, $conditional_column_a, $conditional_column_b) {
        $this->queryConfig["innerJoinGroup"][] = ["table" => $table, "conditional_column_a" => $conditional_column_a, "conditional_column_b" => $conditional_column_b];

        return $this;
    }

    /**
     * Fetches data from the table based on the CLAUSES set, and stores them in memory as distinct object models of each row/record
     * @param $columns array Optional columns to fetch daata from
     * @return $this
     */
    public function get($columns = "*") {

        $this->virtualModelCollection = [];

        if ($columns !== "*") {
            $this->tableColumnSchema = $columns; //they sent custom column schema to fetch
            //if we are getting a custom column list,
            // always get the PRIMARY/ID column along
            if ($this->virtualModelPrimaryKey && false === \array_search($this->virtualModelPrimaryKey, $this->tableColumnSchema)) {

                $this->virtualModelPrimaryKeyColumnIndex = 0; //we'll be adding the key to the top of the array

                \array_unshift($this->tableColumnSchema, $this->virtualModelPrimaryKey);
                \array_unshift($columns, $this->virtualModelPrimaryKey);
            }
        }
        $this->fetch($columns, $this->queryConfig);

        if (!$this->resultObject) {

            $this->tableColumnSchema = \array_merge([], $this->modelSchema); //reset
            return $this;
        }

        //if we have results, populate the virtualModelCollection

        for ($i = 0; $i < \count($this->resultObject); $i++) {

            $this->virtualModelCollection[] = new LindaRowModel($this->tableColumnSchema, $this->resultObject[$i]);
        }

        //$this->tableColumnSchema = array_merge([], $this->modelSchema); //reset
        return $this;
    }

//+===================================================================================================
    /**
     * Fetches row(s) containing the maximum value of a particular column.
     * As usual use #collection, #first, #last etc. to get the returned row models
     * @param string $fieldName field to get the maximum value from
     * @return array()
     */
    public function maxRow($fieldName) {

        $this->virtualModelCollection = array();
        parent::maxRow($fieldName);

        //if we have results, populate the virtualModelCollection

        for ($i = 0; $i < \count($this->resultObject); $i++) {

            $this->virtualModelCollection[] = new LindaRowModel($this->tableColumnSchema, $this->resultObject[$i]);
        }
    }

//+===================================================================================================
    /**
     *  Fetches row(s) containing the maximum value of a particular column;
     *  As usual use #collection, #first, #last etc. to get the returned row models
     *  @param string $fieldName field to get the maximum value from
     * @return array()
     */
    public function minRow($fieldName) {

        $this->virtualModelCollection = array();
        parent::minRow($fieldName);

        //if we have results, populate the virtualModelCollection

        for ($i = 0; $i < \count($this->resultObject); $i++) {

            $this->virtualModelCollection[] = new LindaRowModel($this->tableColumnSchema, $this->resultObject[$i]);
        }
    }

//+===================================================================================================
    /**
     *  Returns the minimum value on a field, this method executes a query directly on the table and doesn't
     *  work on the retrieved/stored data
     * @param string $columnName field to get the minimum value from
     *  @return integer | NULL
     */
    public function minInColumn($columnName) {

        return parent::minInColumn($columnName) || null;
    }

//+===================================================================================================
    /**
     *  Returns the minimum value on a field, this method executes a query directly on the table and doesn't
     *  work on the retrieved/stored data 
     * @param string $columnName field to get the minimum value from
     *  @return integer | NULL
     */
    public function maxInColumn($columnName) {

        return parent::maxInColumn($columnName) || null;
    }

//+===================================================================================================

    /**
     *
     * @param type $num integer number of rows to skip
     * @return $this
     */
    public function skip($num = 0) {

        $this->defaultStartIndex = (int) $num;

        return $this;
    }

    //+===================================================================================================
    /**
     *
     * @param type $num integer number of rows to take
     * @return $this
     */
    public function take($num = 1000) {

        $this->defaultLimit = (int) $num;
        return $this;
    }

//+===================================================================================================
    public function create($values) {

        $values = \array_map(array($this, "sanitize"), \array_values($values));
        $values = \array_map(array($this, "string_or_int"), \array_values($values));
        //  $keys = array_keys($inserts);

        $this->currentQuery = "INSERT INTO `" . $this->modelName . "` (`" . \implode('`,`', $this->tableColumnSchema) . "`) VALUES (" . \implode(", ", $values) . ")";

        $this->runQuery();
        $this->currentQuery = "";

        return $this;
    }

//+===================================================================================================
    /**
     * Removes rows from the table, this method removes rows that where returned during the last #fetchAll or #get operation
     * @return $this
     */
    public function remove() {

        if (!$this->virtualModelPrimaryKey) {
            throw new Exception('No Key found, cannot update table -> (' . $this->modelName . "). ");
        }

        for ($i = 0; $i < \count($this->virtualModelCollection); $i++) {

            $fieldsData = $this->virtualModelCollection[$i]->getValues();
            // $filedsName = $this->tableColumnSchema;

            $this->delete(array(
                "whereGroup" => array(
                    [
                        $this->virtualModelPrimaryKey => array("value" => $fieldsData[$this->virtualModelPrimaryKeyColumnIndex]),
                    ],
                ),
            ));
        }
    }

    /**
     * Returns true if the last query raised an error/exception
     * @return boolean
     */
    public function hasErrors() {
        return ($this->lindaError && \strpos("SQLSTATE[HY000]: General error", $this->lindaError) === false);
    }

    /**
     * Returns the last error or null
     */
    public function getLastError() {
        return $this->hasErrors() ? $this->lindaError : null;
    }

    /**
     * Returns the last executed query 
     */
    public function getLastQuery() {
        return $this->currentQuery;
    }

}

