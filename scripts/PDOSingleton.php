<?php


class PDOSingleton
{
	private static $SERVERNAME = "localhost";
	private static $USERNAME = "root";
	private static $PASSWORD = "bc@bl2017";
	private static $PDO_INSTANCE = NULL;

	static public function getPDOInstance() {

        if (self::$PDO_INSTANCE == NULL) {
            try {

                // connect with set names utf-8
                self::$PDO_INSTANCE = new PDO("mysql:host=" . self::$SERVERNAME  . ";dbname=digital_contracts;charset=utf8",
                                              self::$USERNAME,
                                              self::$PASSWORD);

                // fetch method returns standard objects instead of array with numeric indexes 
                self::$PDO_INSTANCE->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

                // set the PDO error mode to exception
                self::$PDO_INSTANCE->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            } catch(PDOException $e) {
		  
                echo "Connection failed: " . $e->getMessage();

            }
        }
	       
        return self::$PDO_INSTANCE; 	       
	}
	
}

?>