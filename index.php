<?php

session_start();

require('utils/debug.php');
require('Autoloader.php');
Autoloader::Autoload();

$html = file_get_contents("html/index.html");

// Enable contract form sending
if (isset($_SESSION["submit"]))
    unset($_SESSION["submit"]);

echo $html;                    

?>