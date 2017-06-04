<?php

require_once realpath(dirname(__FILE__)) . "/" . 'Linda.php';
require_once realpath(dirname(__FILE__)) . "/" . 'LindaRowModel.php';

/**

 * @brief LindaModel is an Active-record based ORM, 
 * it facilitates the creation and use of business 
 * objects whose data requires persistent storage to a database. 
 * It is an implementation of the Active Record pattern 
 * which itself is a description of an Object Relational Mapping system.
 * @author Olubodun Agbalaya.
 */
class LindaModel extends Linda {

    protected $tableColumnSchema;
    protected $virtualModelCollection = array();
    protected $virtualModelPrimaryKey;
    protected $virtualModelPrimaryKeyColumnIndex;
    protected $modelName;

    /**
     * @param string $Model The name of the table we are to create an Object Map to
     * @param string $primaryKey An optional primary key on the table, defaults to the first column
     * 
     * 
     */
    public function __construct($Model = NULL, $primaryKey = NULL) {

        parent::__construct();

        if (NULL !== $Model) {
            $this->setTable($Model);

            $this->tableColumnSchema = $this->parseModel();
            $this->modelName = $Model;
        }

        if ($primaryKey !== NULL) {
            $this->virtualModelPrimaryKey = $primaryKey; //store the primary key
            //get the column index on the table for this primary key
            $keyColumnIndex = array_search($primaryKey, $this->tableColumnSchema);

            if (FALSE === $keyColumnIndex)
                throw new Exception('Primary Key ' . $primaryKey . "  not found!");

            $this->virtualModelPrimaryKeyColumnIndex = $keyColumnIndex;
        }else {
            $this->virtualModelPrimaryKeyColumnIndex = 0; //default to colum 0 for primary key
        }
    }

    /**
     * Method to fetch all rows from the DB into memory, use #get to retrieve the fields as a collection of Models
     * @return $this
     */
    public function fetchAll() {
        $this->virtualModelCollection = array();

        $this->fetch("*");


        for ($i = 0; $i < count($this->resultObject); $i++) {

            $this->virtualModelCollection[] = new LindaRowModel($this->tableColumnSchema, $this->resultObject[$i]);
        }

        return $this;
    }

    /**
     * Returns first model representing an active record, or NULL if no records where matched
     * @return LindaRowModel
     */
    public function first() {


        return $this->virtualModelCollection && isset($this->virtualModelCollection[0]) ? $this->virtualModelCollection[0] : NULL;
    }

    /**
     * Returns the last model representing an active record, or NULL if no records where matched
     * @return LindaRowModel
     */
    public function last() {


        return $this->virtualModelCollection && isset($this->virtualModelCollection[0]) ? $this->virtualModelCollection[count($this->virtualModelCollection) - 1] : NULL;
    }

    /**
     *  Returns even indexes  representing an active record objects, or NULL if no records where matched
     * @return array()
     */
    public function even() {
        $resultArray = array();
        if (count($this->virtualModelCollection)) {

            for ($i = 0; $i < count($this->virtualModelCollection); $i++) {
                if ($i % 2 === 0) {
                    $resultArray[] = $this->virtualModelCollection[$i];
                }
            }
        }
        return count($resultArray) ? $resultArray : NULL;
    }

    /**
     *  Returns odd indexes  representing an active record objects, or NULL if no records where matched
     * @return array()
     */
    public function odd() {

        $resultArray = array();
        if (count($this->virtualModelCollection)) {

            for ($i = 0; $i < count($this->virtualModelCollection); $i++) {
                if ($i & 2 !== 0) {
                    $resultArray[] = $this->virtualModelCollection[$i];
                }
            }
        }
        return count($resultArray) ? $resultArray : NULL;
    }

    /**
     *  Returns the values in the internal relst set as an array
     * @return array() | NULL
     */
    public function getValues() {


        if (count($this->virtualModelCollection)) {
            for ($i = 0; $i < count($this->virtualModelCollection); $i++) {

                $resultArray[] = $this->virtualModelCollection[$i]->getValues();
            }
            return $resultArray;
        }

        return NULL;
    }

    /**
     *  Returns the values in the internal result set as an atdClass object
     * @return stdClass() | NULL
     */
    public function getValuesAsObject() {


        if (count($this->virtualModelCollection)) {
            for ($i = 0; $i < count($this->virtualModelCollection); $i++) {

                $resultArray[] = $this->virtualModelCollection[$i]->getValuesAsObject();
            }
            return $resultArray;
        }

        return NULL;
    }

    /**
     *  Returns the collection of object row modelsin a random order, or NULL if no records are available
     * @return array()
     */
    public function random() {


        if (count($this->virtualModelCollection)) {

            shuffle($this->virtualModelCollection);
            return $this->virtualModelCollection;
        }
        return NULL;
    }

    /**
     *  Should the data retreival be unique
     * 
     */
    public function distinct() {


        $this->DISTINCT = true;
        return $this;
        ;
    }

    /**
     * Returns a collections of models representing each active record, retrieved from the last call to #get
     * Retruns NULL if no records are available
     * @return LindaRowModelCollection | null
     */
    public function collection() {


        return count($this->virtualModelCollection) ? $this->virtualModelCollection : NULL;
    }

    /**
     * Saves data back into the table
     * @return LindaRowModel
     * 
     * @param type $new_flag An optional primary key parameter, to use in Identfying the row to save data into
     * @return \LindaRowModel|$this
     */
    public function save($new_flag = NULL) {

        //the primary we
        //use when inserting
        $PK = NULL === $this->virtualModelPrimaryKey ? $this->tableColumnSchema[0] : $this->virtualModelPrimaryKey;


        for ($i = 0; $i < count($this->virtualModelCollection); $i++) {

            $fieldsData = $this->virtualModelCollection[$i]->getValues();
            $filedsName = $this->tableColumnSchema;

            $tableData = array_combine($filedsName, $fieldsData);


            $this->update($tableData, array(
                "whereGroup" => array(
                    [
                        $PK => array("value" => $fieldsData[$this->virtualModelPrimaryKeyColumnIndex])
                    ]
                )
            ));
        }

        return $this;
    }

    //update properties on the row model
    public function set($data = array()) {
        for ($i = 0; $i < count($this->virtualModelCollection); $i++) {

            for ($j = 0; $j < count($data); $j++)
                $this->virtualModelCollection[$i]->{array_keys($data)[$j]} = array_values($data)[$j];
        }

        return $this;
    }

    public function where($col, $op, $val, $join_table_index = NULL) {
        $this->queryConfig["whereGroup"][] = [$col => ["value" => $val, "operator" => $op], "nextOp" => "AND", "join_index" => $join_table_index];
        return $this;
    }

    public function where_or($col, $op, $val, $join_table_index = NULL) {
        $this->queryConfig["whereGroup"][] = [$col => ["value" => $val, "operator" => $op], "nextOp" => "OR", "join_index" => $join_table_index];
        return $this;
    }

    public function where_in_or($col, $val) {
        $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => "" . implode(",", $val) . "", "operator" => "OR"];

        return $this;
    }

    public function where_in($col, $val) {

        if (0 === stripos(trim($val), "select")) { //they want to perform a sub-query
            $this->queryConfig["where_in"] = ["fieldName" => $col, "query" => $val, "operator" => "AND"];
        }
        else {
            $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => "" . implode(",", $val) . "", "operator" => "AND"];
        }
        return $this;
    }

    public function inner_join($table, $conditional_column_a, $conditional_column_b) {
        $this->queryConfig["innerJoinGroup"][] = ["table" => $table, "conditional_column_a" => $conditional_column_a, "conditional_column_b" => $conditional_column_b];

        return $this;
    }

    /**
     * Reset the internal Schema, after performing operations that JOINS with other, the internal table schema is modified to
     * accomodate columns from the joined tables this method therefore restores the schema to the original table
     * specified in the constructor this is necessary if, new updates to the table are to be sucesfull, and to avoid
     * ambigous  column errors
     */
    public function resetSchema(){
       
        $this->tableColumnSchema = $this->parseModel();
        
    }
    /**
     * Fetches data from the table based on the CLAUSES set, and stores them in memory as distinct object models of each row/record
     * @param $columns array Optional columns to fetch daata from
     * @return $this
     */
    public function get($columns = "*") {

        $this->virtualModelCollection = [];

        if($columns !== "*"){
            $this->tableColumnSchema = $columns; //they sent custom column schema to fetch
        }
        $this->fetch($columns, $this->queryConfig);

        if (!$this->resultObject)
            return $this;

        //after getting the results from the DB before we create row-models, we need to check if the DB operation had Joins
        //in which case we need to alter the table schema to include the joined columns
        if (array_key_exists("innerJoinGroup", $this->queryConfig)) {
            $this->tableColumnSchema = array_keys($this->resultObject[0]);
        }

        //if we have results, populate the virtualModelCollection

        for ($i = 0; $i < count($this->resultObject); $i++) {

            $this->virtualModelCollection[] = new LindaRowModel($this->tableColumnSchema, $this->resultObject[$i]);
        };

        


        return $this;
    }

    public function skip($param) {

        $this->DEFAULT_START_INDEX = (int) $param;

        return $this;
    }

    public function take($param) {

        $this->DEFAULT_LIMIT = (int) $param;
        return $this;
    }

    public function create($values) {


        $values = array_map(array($this, "sanitize"), array_values($values));
        $values = array_map(array($this, "string_or_int"), array_values($values));
        //  $keys = array_keys($inserts);

        $this->CURRENT_QUERY = "INSERT INTO `" . $this->TABLE_MODEL . "` (`" . implode('`,`', $this->tableColumnSchema) . "`) VALUES (" . implode(", ", $values) . ")";


        $this->runQuery();
        $this->CURRENT_QUERY = "";

        return $this;
    }

    public function remove() {

        //the primary we
        //use when inserting
        $PK = NULL === $this->virtualModelPrimaryKey ? $this->tableColumnSchema[0] : $this->virtualModelPrimaryKey;


        for ($i = 0; $i < count($this->virtualModelCollection); $i++) {

            $fieldsData = $this->virtualModelCollection[$i]->getValues();
            $filedsName = $this->tableColumnSchema;

            $tableData = array_combine($filedsName, $fieldsData);


            $this->delete(array(
                "whereGroup" => array(
                    [
                        $PK => array("value" => $fieldsData[$this->virtualModelPrimaryKeyColumnIndex])
                    ]
                )
            ));
        }

        return $this;
    }

}

//$m = new LindaModel("actor");
//$m->fetchAll();
//


//$m->where("last_name","=","bar")
//        //->where_in("first_name", ["bar", "JOHNNY"])
//       // ->where_in_numeric("first_name", [1, 2])
//        ->inner_join("address", "actor_id", "address_id")
//        ->skip(5)
//        ->take(50)
//        ->get()
//        ->remove();
//print_r($m->collection());


//drop
//new
//skip
//take