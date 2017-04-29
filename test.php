<?php



require_once realpath(dirname(__FILE__)) ."/".'LindaModel.php';

$l = new LindaModel("address");   //the address table is from the open-source sakila database
$l->where_in_or("city_id", ["300", "400", "500"]) //wher the city_id is within that range)
  ->where("city_id","<" ,100) //wher the city_id is within that range)
  ->take(10)
  ->skip(4);
 
$rows = $l->get()->collection();


//and you could access them as maybe..
foreach($rows as $rows) echo $rows->address ."<br/>"; //this would print out the value of each address column