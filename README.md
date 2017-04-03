# Linda is a Database Abstraction Layer and an ORM for PHP

The Database Abstraction part contained in Linda.php, simplifies data access, and building complex queries without the need to write complex SQL statements
Its built over PHP's PDO, so enabling multi data-server access, and safe transactions.

# Using Linda as an Abstraction Layer

Lets look at a basic select operation
<br/>
```php
  $l = new Linda();>
 $l->setTable("some_db_table");
  $l->fecthAll("*"); //fetch all rows in the Table
  $result = $l->getAll(); //returns all rows as an associatove array or NULL if no rows where returned
```

# More advanced selections with where clauses and joins

Apart from select, Linda offers for more basec CRUD methods: update, delete and put each taking similar arguments<br/>
Since most SQl statements support filtering results by Sub-queries, Where clauses, joins etc. <br/>
The basic CRUD methods take a second argiment, lets call it query-configuration where u can specify these, so as an example

```php
say we wanted to add a where clause to our sql execution statement, 

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
 ```
 The whereGroup as shown has each index also as an array where CLAUSES for a single where statement are specified
 the compatrisonOp, just defines what operator to use in camparing each parameter in the where group
 
 If we had the query configure array as
 
 ```php
  $queryConfig = array( "whereGroup" => array(  
                            [
                                "actor_id"=>array("value"=>5, "operator"=>"="),
                                "last_name"=>array("value"=>"'%ER", "operator"=>"LIKE"),
                                "comparisonOp"=>"AND", 
                                "nextOp"=>"OR
                             ],
                                   [
                                    "last_name"=>array("value"=>"foo", "operator"=>"LIKE")
    
                                 ] 
                          )                          
                       )
 
```
The executed SQL statement would be
```mysql
SELECT * FROM `some_db-table` WHERE(actor_id=5 AND last_name LIKE '%ER') OR (actor_id LIKE 'foo');
```
As you can see multiple where clauses can be specified in a whereGroupm, for each distict index in the whereGroup array,
specifying a nextOp key, tells how you want the current where CLAUSE to compare to the next (usuall AND or OR)

also for WHERE IN clauses

```php
  $queryConfig = array( "whereGroup" => array(  
                            [
                                "actor_id"=>array("value"=>5, "operator"=>"="),
                                "last_name"=>array("value"=>"'%ER", "operator"=>"LIKE"),
                                "comparisonOp"=>"AND", 
                                "nextOp"=>"OR
                             ],
                                   [
                                    "last_name"=>array("value"=>"foo", "operator"=>"LIKE")
    
                                 ] 
                          ),
                          
                            "where_in"=>array(
                                  "fieldName" => "id",
                                  "options" => " 10, 15, 22,
                                    "query" => "",
                                   "operator" = > "AND"
      
                               ),
                       )
   //and then we do
   $l->fetch("*", $queryConfig);
  ```
  The executed SQL statement would be
```mysql
SELECT * FROM `some_db-table` WHERE(actor_id=5 AND last_name LIKE '%ER') OR (actor_id LIKE 'foo') AND id IN (10,15,20);
```
There can be multiple where_in parameters, and a sub-query can be specified in the query sub-index, instead of specifying static values.

