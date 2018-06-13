# Linda is a lightweight  ORM for PHP
It features a simple and fluent interface that can easily handle DB related tasks . It has an intentional no fuss setup with nearly zero configuration, so you can get up and running literally in a minute
#### v1.1

Features
--------

* Makes simple queries and simple CRUD operations completely painless.
* Gets out of the way when more complex SQL is required.
* Built on top of [PDO](http://php.net/pdo).
* Uses [prepared statements](http://uk.php.net/manual/en/pdo.prepared-statements.php) throughout to protect against [SQL injection](http://en.wikipedia.org/wiki/SQL_injection) attacks.
* Requires no model classes, no XML configuration and no code generation.
* Supports collections of models with method chaining to filter or apply actions to multiple results at once.
* Fast and Small footprint

# Using Linda
The first thing in setting up Linda is editing the Linda.inc file, this file contains database connection
parameters/constants.
The file contains the following constants, edit to your needs

```php
define('LINDA_DB_HOST', 'hostname');
define('LINDA_DB_TYPE', 'dbtype' ); 
define('LINDA_DB_NAME', 'dbname');
define('LINDA_DB_USER', 'user' );
define('LINDA_DB_PASSW', 'password' );
```


## next
Autoload the  LindaModel.php file or require/include it

# \#API 
Lets look at performing a select operation
First create a LindaModel instance which accepts the table name as its constructor argument, the phpunit test files use the open source employee database dump files, available here [Employee database](https://dev.mysql.com/doc/employee/en/)

## All records
```php
use solutionstack\Linda\LindaModel;

$l = new LindaModel("`employees");   
$l->fetchAll();  //this retrieves all rows from the database and stores them in memory
```
The above fetches all rows and stors them as row-mapped objects in memory ready for access and updating

Each row returned is represented as an object with getter and setter features, as LindaModel implements an ActiveRecord interface

# Working with live data
Once the records are avialable in memory we can retrieve them into variables

```php
//using the #collection method, i can do..
$rows = $l->collection(); //returns collection of all row objects in an array, returns null if an empty set was returned from the DB
```
## accesing row data
```php
foreach($rows as $rows) echo $rows->address ."<br/>"; //this would print out the value of each address column
```
## updating row data
```php
//now lets change the gender column on all rows to 'M' 
foreach($rows as $rows)$rows->address = "New Address to Set";
```
 After updating column values on a row object, its initially set only in Memory, you commit back into the table by calling the save method
```php
$l->save();   //by now all address columns in the address table, would have been updated
```
### More updates..
Updates can alsobe performed on selected colums using the **#set** method

```php
use solutionstack\Linda\LindaModel;

$l = new LindaModel("employees");    database
$l->where("emp_no", ">", 20000
  ->get()
  ->set([
      'gender' => "M",
      'last_name' => "Bar"
      ])
      ->save(); //updates the gender and last name columns for the retrieved rows
```
## Methods for retrieving row objects
In the above say we didnt want to retrieve the entire row models into a variable with #collection
other methods exists including... first(), last(), even(), odd(), random();  
They all return null if no results where retrieved from the table
```php
$l->first(); //retuns the object model for first row of the collection
$l->last(); //retuns the object model for last row of the collection
$l->even(); //retuns the collection of even rows object models
$l->odd(); //retuns the collection of odd rows object models
$l->random(); //like #collection but the row models are sorted in a random order
```
## And other utility methods
```php
$l->count(); //count all rows on the table (retrieved or not)
$l->numRows(); // indicating the number of rows retrieved or affected by the last operation
$l->hasErrors(); // if the last operation raised an Exception
$l->getLastError();//get the last error string if any
$l-> getLastQuery(); //get the last executed query
```

# CLAUSES
In the above examples we use #fetchAll() to first retrieve all rows of the table as objects in memory.
This isnt what you do in most cases as data retrieval from tables are usually filtered by clauses
like **WHERE** clauses, **WHERE IN**, **JOINS** etc, the LindaModel class provides for an increasing number of this clauses

### WHERE CLAUSE
```php
use solutionstack\Linda\LindaModel;

$l = new LindaModel("employees");    database
$l->where("emp_no", ">", 20000); //this basically would apply an SQL where clause similar to ... WHERE(`emp_no` < 300)
```
####
After using a **CLAUSE** the #get method is used to retrieve the matched rows into memory, as opposed to #fetchAll which just loads in all the rows

so the full example for the where clause would be 
```php
use solutionstack\Linda\LindaModel;

$l = new LindaModel("employees");   
$rows = $l->where("emp_no", ">", 20000)
        ->get()                          //fetch rows into memory
        ->collection();                  // get row objects that where fetched as a collection

```

The second parameter in a **where** method call takes any standard MySQL operator
* =
* \>
* LIKE
etc

# Multiple clauses and more..
Multiple calls to a #where method would get AND'ed togethere, as in the following example
```php
use solutionstack\Linda\LindaModel;

$l = new LindaModel("employees");   
$rows = $l->where("emp_no", ">", 20000)
        ->where("gender","=", "F")
        ->get()                          
        ->collection();  
```
The above would generate/execute the following SQL statement
```sql
SELECT * FROM `employees` WHERE( `emp_no` > 20000 ) AND ( `gender` = 'F' ) LIMIT 0, 1000;
```
#### OR'ed where clauses
To Compare **WHERE** clauses OR' wise
use the #where_or method, this method ensures that the next CLAUSE is comopared OR' wise
```php
use solutionstack\Linda\LindaModel;

$l = new LindaModel("employees");   
$rows = $l->where_or("emp_no", ">", 10001)
        ->where("emp_no", "<", 10010)
        ->get()                          
        ->collection();  
```
##### That would execute the query
```sql
 SELECT * FROM `employees` WHERE( `emp_no` = 10011 ) OR ( `emp_no` = 10010 ) LIMIT 0, 1000;
 ```
 **Linda** also supports **where_in** clauses
 ```php
 use solutionstack\Linda\LindaModel;
 
 $l = new LindaModel("employees");   
$rows = $l->whereOr("emp_no", "=", 10011)
        ->whereIn("emp_no", [10010,10013,10024])
        ->get()                          
        ->collection();  
```
would generate
```sql
SELECT * FROM `employees` WHERE( `emp_no` = 10011 ) AND `emp_no` IN (10010,10013,10024) LIMIT 0, 1000;
```
 ##### which should return an empty set        
 # Note*
 **whereIn** are compared AND'wise independent of whether one uses **#whereOr** previously (as seen above)
 To get **whereIn** to compare OR'wise use **#whereInOr** as the following example illustrates 
  ```php
  use solutionstack\Linda\LindaModel;
  
 $l = new LindaModel("employees");   
$rows = $l->where("emp_no", "=", 10011)
        ->whereInOr("emp_no", [10010,10013,10024])
        ->get()                          
        ->collection();  
```
would execute the SQL statement...
```sql
SELECT * FROM `employees` WHERE( `emp_no` = 10011 ) OR `emp_no` IN (10010,10013,10024) LIMIT 0, 1000;
```
There are also complimentary **#whereNotIn** and **#whereNotInOr** methods. eg
illustrates 
  ```php
  use solutionstack\Linda\LindaModel;
  
 $l = new LindaModel("employees");   
$rows = $l->whereNotIn("emp_no", "select `emp_no` from `employees` where `emp_no` < 10010")      //yes sub-queries are allowed
        ->get()                          
        ->collection();   
```
Would generate the following SQL
```sql
SELECT * FROM `employees` WHERE `emp_no` NOT IN (select `emp_no` from `employees` where `emp_no` < 10010) 
```

# A note on Updates and Deletes
Linda automatically detects the **PRIMARY_KEY** on the table if one is avaialable.
If not you'l need to specify what colum, to use as a key before updates would suceed.
The key is specified as the second argument to the constructor **only when a default PRI_KEY doesn't exists else it's ignored**
 
```php
$l = new LindaModel("address","unique_key_column_name");
```

 

# INNER JOIN
Inner Joins are a common way to retrieve related data from multiple table and the  LindaModel class provides a convinient method to perform such joins
 The INNER JOIn method signature is
 ` innerJoin($table, $conditional_column_a, $conditional_column_b)`
 where **$table** is the table you want to JOIN with.
      **$conditional_column_a** is column on the current table you are operating on
      **$conditional_column_b** is the column to match on the JOIN'ed table
      
Using the **#innerJoin** method      
```php
 $l = new LindaModel("employees");   
$rows = $l->innerJoin("salaries", "emp_no", "emp_no")
        ->whereIn("T1.emp_no", "select `emp_no` from `employees` where `emp_no` < 10010")
        ->get(["T2.salary"])                          
        ->collection();  

```
### The above would generate and execute the following
```sql
SELECT T1.emp_no,T2.salary FROM `employees` AS T1 INNER JOIN `salaries` AS T2 ON T1.emp_no = T2.emp_no WHERE T1.emp_no IN (select `emp_no` from `employees` where `emp_no` < 10010) LIMIT 0, 1000;
```
It is important to note that joined table are aliased as **T.x** starting from **T1** representing the main table.
The above examplealso showcases an important feature where we can fetch only values from specific columns as seen in the **#get** method call.
When fetching specific columns the PRIMARY_KEY is always fetchedalsong side custom columns



# PAGINATION
LindaModel supports two methods **#take()** and **#skip()** for paginating reslts
```php
use solutionstack\Linda\LindaModel;

$l = new LindaModel("salaries");   
$l->whereIn("salary", [60117, 603317, 30127]) 
  ->where("emp_no","<" ,10031) 
  ->take(10)
  ->skip(4);
$rows = $l->get()->collection();
```
<br/>
Would execute the Statement
```sql
SELECT * FROM `salaries` WHERE ( `emp_no` < 10031 ) AND `salary` IN (60117,603317,30127) LIMIT 4, 10;
```

# Inserting rows
To insert new rows use the create method.

```php
$l = new LindaModel("address");   
$l->create(array(
    ["val1", "val2",...] //colum data for a row
    )
    );
```
### Inserting multiple rows
```php
$l = new LindaModel("address");   
$l->create(array(
    ["val1", "val2",...], 
    ["val1", "val2",...],
    ["val1", "val2",...])
    );
```

### Inserting data on custom columns (i.e The DB would fill in default values for others)
```php
$l = new LindaModel("address");   
$l->create(array(
    ["val1", "val2"], 
    ["val1", "val2"],
    ["val1", "val2"]
    ),['column1_name','column2_name']
    );
```

To insert column data that takes a MySql **DATE** or **DATETIME** use the string **NOW()** or **TIME()**
```php
$l = new LindaModel("address");   
$l->create(["NOW()", "TIME()",...]);
```


# Removing rows
To remove rows from the table after fetching the object models using either **#fetchAll()** or **#get()**
simply call **#remove()** to delete those rows from a table
```php
$l = new LindaModel("employees");   
$rows = $l->where("emp_no", ">", 20000)
        ->get()                          //fetch rows into memory
        ->remove();                     //remove rows from table    

```

# DISTINCT rows
To ensure the returned result set/models contains unique, values for the columns, use #distinct

```php
$l = new LindaModel("address");   
$l->where("city_id","<" ,100) //wher the city_id is within that range)
  ->take(10)
  ->skip(4)
  ->distinct() //ensure rows column values are unique
  ->get(["address"]); //we are getting just this column data
```

