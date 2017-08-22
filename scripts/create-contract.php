<?php

session_start();

// Will dynamically load all required class
require('../Autoloader.php');
require('../utils/debug.php');
    
Autoloader::$prefix = '../';
Autoloader::Autoload();

// Load html content
$html = file_get_contents("../html/message.html");

// Retrieving PDO object for database interaction 
$dbh = PDOSingleton::getPDOInstance();

// Callback function to pass to array_map
$checkWellFormedGetArray = function ($value) {
    
    if (!isset($_GET[$value])) 
        throw new Exception("Erreur : Des éléments manquent pour l'enregistrement du contrat. Valeur manquante : " . $value);
    
};

try {

    // Check if all the exepected elements are set in $_GET array
    array_map($checkWellFormedGetArray, array("ContractHeading", "SignatoriesId", "SignatoryStatus", "StartingDate", "EndingDate"));
        
    // Avoid repeated inserts on page refreshing
    if (isset($_SESSION["submit"])) {
        header("Location: ../index.php");
        exit;
    }
    else $_SESSION["submit"] = 1;
        
    /************************************************************ 
     ********************** CONTRACT INSERTION ******************
     ************************************************************
           Insertion of a new row in the contract table
     ***********************************************************/
    
    // Retrieve values to insert from the $_GET array, and check if StartingDate is prior to EndingDate
    $contractHeading = $_GET["ContractHeading"];
    $startingDate = DateTime::createFromFormat('d-m-Y', $_GET["StartingDate"]);
    $endingDate = DateTime::createFromFormat('d-m-Y', $_GET["EndingDate"]);

    if ($startingDate >= $endingDate)
        throw new Exception("Erreur : La date de départ du contrat est inférieure ou égale à la date de fin. ");

    
    $query = "INSERT INTO contract(ContractHeading, StartingDate, EndingDate) VALUES (?, ?, ?)";
    $stmt = $dbh->prepare($query);

    $bindArray = array($contractHeading, $startingDate->format('Y-m-d'), $endingDate->format('Y-m-d'));
    
    // Raise exception if query aborts
    if (!$stmt->execute($bindArray)) {

        $failureMessage = "Erreur : Le contrat n'a pas pu être inséré dans la base. " . $dbh->errorInfo();
        throw new Exception($failureMessage);
                  
    }

    // Retrieve last inserted id, corresponding to the new contract
    $newContractId = $dbh->lastInsertId();
    
    // Add message to html content
    $contractInsertionMessage = "Un nouveau contrat n° " . $newContractId  . " a bien été inséré dans la base.";
    $html = str_replace("{contract_insertion_message}", $contractInsertionMessage, $html);

    /******************************************************************** 
     ********************** CONTRACT'S STATE INSERTION ****************** 
     ********************************************************************
          Insertion of the contract's first state in contract_state
          table. Heading is set to 0 corresponding to the 
          WAITING_FOR_SIGNATURE heading.
    *********************************************************************/
    $query = "INSERT INTO contract_state(Heading, StartingDate, ContractId) VALUES (?, ?, ?)";
    $stmt = $dbh->prepare($query);

    $bindArray = array('0', $startingDate->format('Y-m-d'), $newContractId);

    // Raise exception if query aborts
    if (!$stmt->execute($bindArray)) {

        $failureMessage = "Erreur : L'état du contrat n'a pas pu être inséré. " . $dbh->errorInfo();
        throw new Exception($failureMessage);
                  
    }

    // Add message to html content
    $contractStateInsertionMessage = "Un nouvel état d'id " . $dbh->lastInsertId()
                                   . " pour le contrat n° " . $newContractId  . " a bien été inséré dans la base.";
    $html = str_replace("{contract_state_insertion_message}", $contractStateInsertionMessage, $html);

    /****************************************************************** 
     ********************** PARTIES INSERTION ************************* 
     ******************************************************************
            Insertion of each signatory as a party of the contract.
    ******************************************************************/
    $partyInsertionMessage = "";
        
    for ($i = 0; $i < count($_GET["SignatoriesId"]); $i++) {

        $signatoryId = $_GET["SignatoriesId"][$i];
        $signatoryStatus = ($_GET["SignatoryStatus"][$i] == 'Client') ? 0 : 1; // Signatory status is an enum in the database

        $query = "INSERT INTO is_party(SignatoryId, ContractID, SignatoryStatus) VALUES (?, ?, ?)";
        $stmt = $dbh->prepare($query);

        $bindArray = array($signatoryId, $newContractId, $signatoryStatus);

        // Raise exception if query aborts
        if (!$stmt->execute($bindArray)) {

            $failureMessage = "Erreur : Le signataire " . $signatoryId . " n'a pas pu être inséré comme partie du contrat "
                            . $newContractId . " . " . $dbh->errorInfo();
            throw new Exception($failureMessage);
                  
        }

        // Add message to html content
        $partyInsertionMessage .= "La partie n° " . $signatoryId . " a été ajoutée au contrat n° " . $newContractId . PHP_EOL;
        
    }

    $html = str_replace("{party_insertion_message}", $partyInsertionMessage, $html);


    /****************************************************************** 
     ********************** SIGNATURE INSERTION *********************** 
     ******************************************************************
            Insertion of Berger-Levrault signature, the only one 
            available at contract's creation 
     ******************************************************************/
        
    $query = "INSERT INTO signature(ContractID, SignatoryId, DateOfSignature, SignatureDigest) VALUES (?, ?, ?, ?)";
    $stmt = $dbh->prepare($query);

    // Retrieving Berger-Levrault private key and digesting it
    $private_key = file_get_contents('../certificate/Admin@berger-levrault.com-cert.pem');
    $signatureDigest = hash("sha256", $private_key);
    
    $bindArray = array($newContractId, '1', $startingDate->format('Y-m-d'), $signatureDigest);
            
    // Raise exception if query aborts
    if (!$stmt->execute($bindArray)) {

        $failureMessage = "Erreur : Le signataire Berger-Levrault n'a pas pu apposer sa signture au contrat "
                        . $newContractId . " " . $dbh->errorInfo();
        throw new Exception($failureMessage);
                  
    }

    // Add message to html content
    $signatureInsertionMessage .= "Le signataire Berger-Levrault a apposé sa signature au contrat n° " . $newContractId;
    
    $html = str_replace("{signature_insertion_message}", $signatureInsertionMessage, $html);
    
    // Display success messages block and hide failure messages block
    $html = str_replace("{failure}", "none", $html);
    $html = str_replace("{success}", "block", $html);

    
} catch (Exception $e) {
    
    // Display failure messages block and hide success messages block
    $html = str_replace("{failure}", "block", $html);
    $html = str_replace("{success}", "none", $html);
    
    $html = str_replace("{failure_message}", $e->getMessage(), $html);
    
}

echo $html;


?>

