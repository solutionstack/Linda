# Linda is a Database Abstraction Layer and an ORM for PHP

The Database Abstraction part contained in Linda.php, simplifies data access, and building complex queries without the need to write complex SQL statements
Its built over PHP's PDO, so enabling multi data-server access, and safe transactions.

# Using Linda

Lets look at a basic select operation
<br/>
 ` $l = new Linda();`<br/>
 `$l->setTable("some_db_table");`<br/>
 ` $l->fecthAll("*"); //fetch all rows in the Table`<br/>
 ` $result = $l->getAll(); //returns all rows as an associatove array or NULL if no rows where returned`
<br/><br/>
