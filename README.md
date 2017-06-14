# Linda is an Active-record based  ORM for PHP

Its built over PHP's PDO, so enabling multi data-server access, and safe transactions.


# Using Linda as an ORM
The first thing in setting up Linda to as an ORM is editing the Linda.inc file, this file contains database connection
parameters/constants.
The file contains the following constants, edit to your needs

```php
define('LINDA_DB_HOST', 'hostname');
define('LINDA_DB_TYPE', 'dbtype' ); //mysql, pgsql etc
define('LINDA_DB_NAME', 'dbname');
define('LINDA_DB_USER', 'user' );
define('LINDA_DB_PASSW', 'password' );

```

After this step, simply begin using the LindaModel class which contains the ORM interface.<br/>
For the examples here i'll be using the freely available sakila database.

My test file exists in the folder is the Linda classes, so'ill just include the LindaModel file
```php
require_once realpath(dirname(__FILE__)) ."/".'LindaModel.php';
```
Lets look at performing a select operation
```php
//First create a LindaModel instance and send the table name as its constructor argument
$l = new LindaModel("address");   //the address table is from the open-source sakila database

$l->fetchAll();  //this retrieves all rows from the database and stores them in memory
```
Each row returned is represented as an object with getter and setter features, as LindaModel implements an ActiveRecord interface
To work with this row models, you need to first retrve the models into a variable...

```php
//using the #collection method, i can do..
$rows = $l->collection(); //returns collection of models in an array, returns null if an empty set was returned from the DB

//and you could access them as maybe..
foreach($rows as $rows) echo $rows->address ."<br/>"; //this would print out the value of each address column

//that simple
//so if i wanted to change the address on each column in the DB table (since all columns where returned), i'lll simply
//set the new values on each row model (see note on primary keys below)
foreach($rows as $rows)$rows->address = "New Address to Set";

//when setting column values on a row model, its initially set only in Memory, you commit back into the table by using the save
//method
$l->save();   //by now all address columns in the address table, would have been updated


```
After using fetchAll() as above say we didnt want to retrieve the entire row models with #collection
<br/>other methods exists including... first(), last(), even(), odd(), random();  
```php
$l->first(); //retuns the object model for first row of the collection
$l->last(); //retuns the object model for last row of the collection
$l->even(); //retuns the collection of even rows object models
$l->odd(); //retuns the collection of odd rows object models
$l->random(); //like #collection but the row models are sorted in a random order

//all this methods return NULL if no row models are available

```

In the above examples we use fetchAll() to first retrieve all rows of the table as objects in memory<br/>
This isnt what you do in most cases as data retrieval from tables are usually filtered by clauses
like WHERE clauses, WHERE IN, JOINS etc, the LindaModel class provides for an increasing number of this clauses

# WHERE CLAUSE
```php
//in theaddress table we used above, lets apply some where clauses
$l = new LindaModel("address");   //the address table is from the open-source sakila database
$l->where("city_id", "<", 300); //this basically would apply an SQL where clause similar to ... WHERE(`city_id` < 300)

$l->get(); //after using a clause, use #get(), to fetch the matching row models into memory, after which you can use #collection, #first() etc to fetch the row-models u need

//#get also take an optional argument containing just the coulmns to retrieve instead of retrieving all columns
// get(["column1", "column2",...]);

//so say i wanted to update a column named foo on the address table for each row where the city_id column value < 300
//i'll do it this way
$l = new LindaModel("address");  
$l->where("city_id", "<", 300); 

$rows = $l->get()->collection();

foreach($rows as $rows) $row->foo = "new value";

//then commit changes to the table
$l->save(); //done

```
# A note on Updates and Deletes
Note that when updating or deleting columns, a unique index is usually needed to reference each column, usually this is the tables primary key.
You can specify the primary key as the second argument to the LindaModel constructor..like
 
```php
$l = new LindaModel("address","pri_key_column_name");
```
else <b>LindaModel</b> class uses the first column of the table as the primary key, which might not always be accurate, so always specify a primary key if update operations are going to be performed



#Multiple where clauses can be specified

```php
$l = new LindaModel("address");  
$l->where("city_id", "<", 300)
  ->where("address", "LIKE", "%street");


$rows = $l->get()->collection();
```
Multiple where clauses are related using AND, the ABOVE would translate to something like 

```sql

... WHERE (`city_id` < 300) AND (`address` LIKE '%street)...
```
If u need to relate multiple <b>WHERE</b> clauses OR wise, use

```php

$l = new LindaModel("address");  
$l->where_or("city_id", "<", 300)
  ->where("address", "LIKE", "%street"); //if theres another where clause after this, it would be related AND- wise, or u can use #where_or
  ```
  
  # WHERE IN CLAUSE
  the <b>where_in</b> and <b>where_in_or</b> methods provides means to apply WHERE IN clauses to your table operations
  
  ```php
//in theaddress table we used above, lets apply some where clauses
$l = new LindaModel("address");   //the address table is from the open-source sakila database
$l->where_in("city_id", ["300, 400, 500"]); //wher the city_id is within that range

```
<b>where_in_or</b> differs significantly, for <b>where_or</b><br/>
For where_in_or you are specifying that an OR comes before the where_in CLAUSE, if multiple clauses exixts

<br/><br/>so as an example

```php
$l = new LindaModel("address");   //the address table is from the open-source sakila database
$l->where_in_or("city_id", ["300", "400", "500"]) //wher the city_id is within that range)
  ->where("city_id","<" ,100); //wher the city_id is within that range)

$rows = $l->get()->collection();
```

would translate to

```sql
SELECT * FROM `address` WHERE( city_id < 100 ) OR city_id IN ('300', '400', '500') LIMIT 0, 1000;
```
You'll notice that even though the <b>where_in</b> clause came first, the <b> where clause</b> was processed first<br/>
This is a design decision, as thus all WHERE clauses would be processed before WHERE IN's, irrespective of the order the methods where called, you can also pass a sub-query string as the second value of a <b> where_in</b> method call
  

# INNER JOIN CLAUSE
Inner Joins are a common way to retrieve related data from multiple table and the <b> LindaModel</b> class provides a convinient method
 to perform such joins<br/>
 The INNER JOIn method signature is
 <b> inner_join($table, $conditional_column_a, $conditional_column_b)</b><br/>
 where <b>$table</b> is the table you want to JOIN with<br/>
      <b> $conditional_column_a</b> is column on the current table you are operating on<br/>
      <b> $conditional_column_b</b> is the column to match on the JOIN'ed table<br/>
      
```php
$l = new LindaModel("address");  
$l->where("city_id","<" ,100) //wher the city_id is within that range)
 ->inner_join("city", "city_id", "city_id");
 
$rows = $l->get()->collection();
```
In the above an SQL query sililar to
```sql
SELECT * FROM `address` AS T1 INNER JOIN `city` AS T2 ON T1.city_id = T2.city_id WHERE( T1.city_id < 100 ) LIMIT 0, 1000;
```
would be executed

# PAGINATION
LindaModel supports two methods take() and skip() for paginating reslts
```php
$l = new LindaModel("address");   
$l->where_in_or("city_id", ["300", "400", "500"]) //wher the city_id is within that range)
  ->where("city_id","<" ,100) //wher the city_id is within that range)
  ->take(10)
  ->skip(4);
$rows = $l->get()->collection();
```
<br/>
Would execute the Statement
```sql
SELECT * FROM `address` WHERE( city_id < 100 ) OR city_id IN (300,400,500) LIMIT 4, 10;
```

# Inserting rows
To insert new rows use the create method, but make sure the argument count matches the number of columns

```php
$l = new LindaModel("address");   
$l->create(["val1", "val2",...]);
```

To insert column data that takes a MySql <b>DATE</b> or <b>DATETIME</b> use the string <b>NOW()</b> or <b>TIME()</b>
```php
$l = new LindaModel("address");   
$l->create(["NOW()", "TIME()",...]);
```


# Removing rows
To remove rows from the table after fetching the object models using either <b>fetchAll()</b> or <b>get()</b>
simply call <b> remove()</b> to delete those rows from a table

<br/>

# DISTINCT rows
To ensure the returned result set/models contains unique, values for the columns, use 

```php
$l = new LindaModel("address");   
$l->where("city_id","<" ,100) //wher the city_id is within that range)
  ->take(10)
  ->skip(4)
  ->distinct() //ensure rows column values are unique
  ->get(["address"]); //we are getting just this column data
```

<br/>
<br/>

