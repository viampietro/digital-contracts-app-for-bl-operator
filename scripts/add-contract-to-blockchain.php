<?php

session_start();

// Will dynamically load all required class
require('../Autoloader.php');
require('../utils/debug.php');
    
Autoloader::$prefix = '../';
Autoloader::Autoload();


// Retrieving PDO object for database interaction 
$dbh = PDOSingleton::getPDOInstance();

try {
    
    if (isset($_GET['id'])) {
        
        /*---------------------------------------------------------------
          -                                                              -
          -        BUILDING JSON OBJECT REPRESENTING THE CONTRACT        -
          -                                                              -
          ---------------------------------------------------------------*/
        
        
        /*
         * 
         *  RETRIEVE CONTRACT AND ALL CONTRACT'S STATES
         *
         */
        $query = "SELECT c.ContractHeading, c.StartingDate, c.EndingDate, cs.Heading, 
                         cs.StartingDate as 'StateStartingDate', cs.EndingDate as 'StateEndingDate'               
                  FROM contract c 
                  JOIN contract_state cs ON c.Id = cs.ContractId
                  WHERE c.Id = ?";

        // Preparing and executing the query
        $stmt = $dbh->prepare($query);
        
        if ($stmt->execute(array($_GET['id']))) {

            if ($stmt->rowCount() > 0) {

                $rows = $stmt->fetchAll();
            
            } else throw new Exception("Aucun contrat à ajouter.");
        
        } else throw new Exception($dbh->errorInfo());

        // Creating contract's structure
        $contract = (object) array(
            'ContractHeading' => $rows[0]->ContractHeading,
            'StartingDate'    => $rows[0]->StartingDate,
            'EndingDate'      => $rows[0]->EndingDate,
            'StateRecords'    => [],
            'PaymentRecords'  => [],
            'Signatories'     => [],
            'Signatures'      => []
        );

        /* The following steps aim the retrieval of contract's
         *  state records, payment records, signatories and signatures
         *  to complete the contract's structure
         */
        
        // Building state records' array
        $stateRecords = [];
        foreach ($rows as $stateRecord) {

            // Adding an stdClass object to stateRecords array
            $stateRecords[] = (object) array(
                'Heading'      => intval($stateRecord->Heading),
                'StartingDate' => $stateRecord->StateStartingDate,
                'EndingDate'   => $stateRecord->StateEndingDate
            );
        }

        // Assigning stateRecords array to contract's structure
        $contract->StateRecords = $stateRecords;


        /*
         * 
         *  RETRIEVE CONTRACT'S SIGNATORIES
         *
         */
        $query = "SELECT s.Id, s.BusinessName, s.HeadQuarters, s.Holder, s.RegistrationNumber,                
                         ip.SignatoryStatus                         
                  FROM signatory s 
                  JOIN is_party ip ON s.Id = ip.SignatoryId
                  WHERE ip.ContractId = ?";

        // Preparing and executing the query
        $stmt = $dbh->prepare($query);
        
        if ($stmt->execute(array($_GET['id']))) {

            if ($stmt->rowCount() > 0) {

                $rows = $stmt->fetchAll();
            
            } else $rows = [];
        
        } else throw new Exception($dbh->errorInfo());

        // Building signatories array to fill the contract's structure with
        $signatories = [];
        $signatoriesWithId = []; // facilitate building of signature structure
        
        foreach ($rows as $signatory) {
            
            $signatoryObject = (object) array(
                'BusinessName'       => $signatory->BusinessName,
                'HeadQuarters'       => $signatory->HeadQuarters,
                'Holder'             => $signatory->Holder,
                'RegistrationNumber' => $signatory->RegistrationNumber,
                'Status'             => intval($signatory->SignatoryStatus)
            );

            $signatories[] = $signatory;
            $signatoriesWithId[$signatory->Id] = $signatoryObject;
        }

        $contract->Signatories = $signatories;

        /*
         * 
         *  RETRIEVE CONTRACT'S SIGNATURES
         *
         */
        $query = "SELECT su.SignatoryId, su.DateOfSignature, su.SignatureDigest                          
                  FROM signature su
                  WHERE su.ContractId = ?";

        // Preparing and executing the query
        $stmt = $dbh->prepare($query);
        
        if ($stmt->execute(array($_GET['id']))) {

            if ($stmt->rowCount() > 0) {

                $rows = $stmt->fetchAll();
            
            } else $rows = [];
        
        } else throw new Exception($dbh->errorInfo());

        // Building signatories array to fill the contract's structure with
        $signatures = [];
        foreach ($rows as $signature) {
            
            $signatures[] = (object) array(
            
                // Thanks to the id, retrieve the issuer from the $signatoriesWithId array
                'Issuer'          => $signatoriesWithId[$signature->SignatoryId], 
                'DateOfSignature' => $signature->DateOfSignature,
                'SignatureDigest' => $signature->SignatureDigest
                
            );
        }

        $contract->Signatures = $signatures;

        /*
         * 
         *  RETRIEVE CONTRACT'S PAYMENTS
         *
         */
        $query = "SELECT py.SignatoryId, py.DateOfIssuance, py.Amount                          
                  FROM payment py
                  WHERE py.ContractId = ?";

        // Preparing and executing the query
        $stmt = $dbh->prepare($query);
        
        if ($stmt->execute(array($_GET['id']))) {

            if ($stmt->rowCount() > 0) {

                $rows = $stmt->fetchAll();
            
            } else $rows = [];
        
        } else throw new Exception($dbh->errorInfo());

        // Building signatories array to fill the contract's structure with
        $payments = [];
        foreach ($rows as $payment) {
            
            $payments[] = (object) array(
            
                // Thanks to the id, retrieve the issuer from the $signatoriesWithId array
                'Issuer'         => $signatoriesWithId[$payment->SignatoryId], 
                'DateOfIssuance' => $payment->DateOfIssuance,
                'Amount'         => $payment->Amount
                
            );
        }

        $contract->PaymentRecords = $payments;

        /*------------------------------------------------------------
          -                                                           -
          -        SENDING HTTP POST QUERY TO DIGITAL CONTRACTS'      -
          -        NODEJS WEB SERVICE                                 -
          -                                                           -
          ------------------------------------------------------------*/

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, "http://localhost:8081/addContract");
        
        // enabling post request
        curl_setopt($ch, CURLOPT_POST, 1 ); 
        
        // passing data to post request's body
        $postData = json_encode(array('key' => $_GET['id'], 'value' => $contract));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        // return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($postData))                                                                       
        );
        
        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch); 

        $chaincodeResponse = (object) json_decode($output);

        // if request is successful or contract already exists in the ledger,
        // set the StoredInLedger contract's attribute value to TRUE in the MySQL database
        if ($chaincodeResponse->success
            || preg_match('/Asset\s[0-9]+\salready\sexists/', $chaincodeResponse->error)) {

            $query = "UPDATE contract SET StoredInLedger = TRUE WHERE Id = ?";
            // Preparing and executing the query
            $stmt = $dbh->prepare($query);
        
            if ($stmt->execute(array($_GET['id']))) {

                if ($stmt->rowCount() <= 0)
                    throw new Exception('Unable to update the StoredInLedger attribute for contract ' . $_GET['id']);
        
            } else throw new Exception($dbh->errorInfo());
            
        } 
        
        // send json back to javascript program, the output is already json-encoded
        echo $output;
        
    } else throw new Exception('Contract id is missing');

} catch (Exception $e) {
    
    echo json_encode(array('error' => $e->getMessage()));

}