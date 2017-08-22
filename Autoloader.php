<?php

class Autoloader {

    // Adapting the research path
    public static $prefix = '';

    /**
	 * Load classes automatically
	 * Usage : require('Autoloader.php');
	 *		   Autoloader::Autoload();
	 * @static
	 */
	static public function Autoload() {
		spl_autoload_register(array(__CLASS__, 'register'));
	}
    
	/**
	 * @param String $class The class to load
	 * @return bool True if class was found and then the class is imported, False if missing
	 * @static
	 */
	static public function register($class) {

        foreach (array("scripts/", "utils/") as $dir) {

            if(file_exists(self::$prefix . $dir . $class . '.php')){
                require  self::$prefix . $dir. $class . '.php';

                return true;
            }

        }
        
		return false;
	}
}

?>