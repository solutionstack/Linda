<?php

namespace solutionstack\LindaTest;

include_once 'smartloader.php';

use PHPUnit\Framework\TestCase;
use solutionstack\Linda\LindaModel;

class LindaTest extends TestCase {

    static $db_obj = null;


    protected function setUp()
    {
        echo 1;
    }

    protected function tearDown()
    {
        
    }

    public static function setUpBeforeClass()
    {
        
        echo "loading sample database data...\n";
        \shell_exec("mysql -uroot -padmin < App/test-db/employees.sql");
        
        echo "done loading data\n";
       
    }

    public static function tearDownAfterClass()
    {
        $sql = mysqli_connect('localhost', 'root', 'admin', '');
//        mysqli_query($sql, "drop database employees;");
        mysqli_close($sql);
    }

}
