# Linda is a Database Abstraction Layer and an ORM for PHP

The Database Abstraction part contained in Linda.php, simplifies data access, and building complex queries without the need to write complex SQL statements
Its built over PHP's PDO, so enabling multi data-server access, and safe transactions.

# Using Linda as an Abstraction Layer

Lets look at a basic select operation
<br/>
```php
 ` $l = new Linda();`<br/>
 `$l->setTable("some_db_table");`<br/>
 ` $l->fecthAll("*"); //fetch all rows in the Table`<br/>
 ` $result = $l->getAll(); //returns all rows as an associatove array or NULL if no rows where returned`
<br/><br/>

# More advanced selections with where clauses and joins

Apart from select, Linda offers for more basec CRUD methods: update, delete and put each taking similar arguments<br/>
Since most SQl statements support filtering results by Sub-queries, Where clauses, joins etc. <br/>
The basic CRUD methods take a second argiment, lets call it query-configuration where u can specify these, so as an example

`say we wanted to add a where clause to our sql execution statement, `<br/>
<code>
 $queryConfig = array( "whereGroup" => array(  
                            [
                                "actor_id"=>array("value"=>5, "operator"=>"="),
                                "last_name"=>array("value"=>"'%ER", "operator"=>"LIKE"),
                                "comparisonOp"=>"AND",              
                             ]
                          )                          
                       )

say we now do

 $l->fetch("*", $queryConfig);
 
 An SQl State ment similar to 
 
  SELECT * FROM `some_db-table` WHERE(actor_id=5 AND last_name LIKE '%ER');
 is executed
 
 The whereGroup as shown has each index also as an array where CLAUSES for a single where statement are specified
 the compatrisonOp, just defines what operator to use in camparing each parameter in the where group
 
</code>
