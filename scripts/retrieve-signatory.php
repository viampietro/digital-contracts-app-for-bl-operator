<?php

// Will dynamically load all required class
require('../Autoloader.php');

Autoloader::$prefix = '../';
Autoloader::Autoload();

if(class_exists("PDOSingleton")) {
    
    // Retrieving PDO object for database interaction 
    $dbh = PDOSingleton::getPDOInstance();

} else {
    
    echo "PDOSingleton est introuvable.";
    die();
}

if (isset($_GET["Id"])) {

    // Prepare the query to retrieve all fields for a given signatory id
    $query = 'SELECT * FROM signatory WHERE Id = ?';
    $stmt = $dbh->prepare($query);

    $bindArray = array($_GET["Id"]);
    
} else {
    
    // Prepare the query to retrieve all rows from signatory table
    $query = "SELECT Id, BusinessName FROM signatory WHERE Id != 1";
    $stmt = $dbh->prepare($query);
    $bindArray = array();
    
}

// Executing query and returning results as JSON object
if ($stmt->execute($bindArray)) {

    // the query execution returned at least one row
    if ($stmt->rowCount() > 0) {
        
        if($json = json_encode($stmt->fetchAll()))
            echo $json;
        else
            echo "Erreur de parsing de l'objet JSON";
           
    } else {
        // if no rows, return empty array
        echo json_encode(array());
    }
    
    http_response_code(200);
    
} else {
    
    echo $dbh->errorInfo(); 
    http_response_code(500);
}


?>