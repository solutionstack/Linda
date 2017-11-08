<?php
/**
 * smartload.php
 *
 * Autoloading Class
 *
 * @author     Olubodun Agbalaya
 * @copyright  2017
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    GIT: 1.0.0

 * @link       https://github.com/solutionstack/smartloader 
 */


//A simple file to auto-load namespaced and non-namespaced classes in your project
//You'll only need to include this file in all your project files to use autoloaded classes	
//simply change the $classes_root variable to point to the folder where your classes reside
//this autoloader expects your files end in .php.
//this utility doesnt however stop you from properly decaring namespacs as declared in classes your are using (of course)!


\spl_autoload_register(function($class_name) {

    $classes_root = __DIR__."/App"; //change to reflect your classes root folder, i normally just use App/
    //see if we have a chache and the queried class file path is already inc cache
    if (\function_exists('apcu_add')) {


        if (\apcu_exists($class_name) && \file_exists(\apcu_fetch($class_name))) {

            require_once \apcu_fetch($class_name);
            return;
        }
    }

    $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($classes_root));


// Filter directories, i dont need the result, so cast to NULL
    (unset) $files = new \CallbackFilterIterator($dir, function ($current, $key, $iterator) use ($class_name) {

        if ($current->isFile()) {

            //here we remove namespace prefix(es) , leaving only the base class name
            if (\strrpos($class_name, "\\") !== FALSE) {
                $cur_file = \substr($class_name, \strrpos($class_name, "\\") + 1);
            } else {
                $cur_file = $class_name; //else just leave the class name as-is
            }

      
            if ($current->getBasename('.php') === \trim($cur_file)) {//stript out .php and compare file name to required class
                if (\function_exists('apcu_add')) {
            
                    \apcu_store($class_name, $current->getRealPath());
                }

                require_once $current->getRealPath();
            }
        }
    });




    foreach ($files as $c)
        ; //note the trick, just dummy iterating makes the function (the FilterIterator Above) 
	  //yeild required file names else the file names never gets generated/included
});
