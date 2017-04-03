<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/process/Linda/Linda.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/process/Linda/LindaRowModel.php';

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
    protected $modelName;

    /**
     * @param string $Model The name of the table we are to create an Object Map to
     * @param string $primaryKey An optional primary key on the table, defaults to the first column
     * 
     * 
     */
    public function __construct($Model, $primaryKey = NULL) {

        parent::__construct();

        if (NULL !== $Model) {
            $this->setTable($Model);

            $this->tableColumnSchema = $this->parseModel();
            $this->modelName = $Model;
        }

        if ($primaryKey)
            $this->virtualModelPrimaryKey = $primaryKey;
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


        return isset($this->virtualModelCollection[0]) ? $this->virtualModelCollection[0]: NULL;
    }
    /**
     * Returns a collections of models representing each active record, retrieved from the last call to #get
	 * Retruns NULL if no records are available
     * @return LindaRowModelCollection | null
     */
    public function collection() {


        return count($this->virtualModelCollection) ? $this->virtualModelCollection: NULL;
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
                        $PK => array("value" => $fieldsData[0])
                    ]
                )
            ));
        }

        return $this;
    }

    public function set($data = array()) {
        for ($i = 0; $i < count($this->virtualModelCollection); $i++) {

            for ($j = 0; $j < count($data); $j++)
                $this->virtualModelCollection[$i]->{array_keys($data)[$j]} = array_values($data)[$j];
        }

        return $this;
    }

    public function where($col, $op, $val) {
        $this->queryConfig["whereGroup"][] = [$col => ["value" => $val, "operator" => $op], "nextOp" => "AND"];
        return $this;
    }

    public function where_or($col, $op, $val) {
        $this->queryConfig["whereGroup"][] = [$col => ["value" => $val, "operator" => $op], "nextOp" => "OR"];
        return $this;
    }

    public function where_in_or($col, $val) {
        $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => "'" . implode("','", $val) . "'", "operator" => "OR"];

        return $this;
    }

    public function where_in($col, $val) {
        $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => "'" . implode("','", $val) . "'", "operator" => "AND"];

        return $this;
    }

    public function where_in_numeric_or($col, $val) {
        $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => implode(",", $val), "operator" => "OR"];

        return $this;
    }

    public function where_in_numeric($col, $val) {
        $this->queryConfig["where_in"] = ["fieldName" => $col, "options" => implode(",", $val), "operator" => "AND"];

        return $this;
    }

    public function inner_join($table, $conditional_column_a, $conditional_column_b) {
        $this->queryConfig["innerJoinGroup"][] = ["table" => $table, "conditional_column_a" => $conditional_column_a, "conditional_column_b" => $conditional_column_b];

        return $this;
    }

    /**
     * Fetches data from the table based on the CLAUSES set, and stores them in memory as distinct object models of each row/record
     * @return $this
     */
    public function get() {
        $this->virtualModelCollection = [];
        $this->fetch("*", $this->queryConfig);

      
        for ($i = 0; $i < count($this->resultObject); $i++) {

            $this->virtualModelCollection[] = new LindaRowModel($this->tableColumnSchema, $this->resultObject[$i]);
        }

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

        $this->CURRENT_QUERY = "INSERT INTO `" . $this->TABLE_MODEL . "` (" . implode(',', $this->tableColumnSchema) . ") VALUES (" . implode(", ", $values) . ")";


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
                        $PK => array("value" => $fieldsData[0])
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