<?php

session_start();

// Will dynamically load all required class
require('../Autoloader.php');
require('../utils/debug.php');
    
Autoloader::$prefix = '../';
Autoloader::Autoload();

// Load html content
$html = file_get_contents("../html/retrieve-contract.html");

// Retrieving PDO object for database interaction 
$dbh = PDOSingleton::getPDOInstance();

try {
    
    // If id is set, send back a json of the contract
    if (isset($_GET["id"])) {
        
    } else {

        // Retrieve all contract not already stored in the ledger
        $query = "SELECT * FROM contract";
        $stmt = $dbh->prepare($query);

        if ($stmt->execute()) {

            if ($stmt->rowCount() > 0) {

                $rows = $stmt->fetchAll();
            
            } else throw new Exception("Aucun contrat à ajouter.");
        
        } else throw new Exception($dbh->errorInfo());

        // Loop through all contracts to create a row for each one of them
        // in the html table
        $contracts_list = '';
        
        foreach ($rows as $contract) {
            
            $contracts_list .= '<tr>
                                   <td id="Id" hidden>' . $contract->Id . '</td>
                                   <td id="ContractHeading">' . $contract->ContractHeading . '</td>
                                   <td id="StartingDate">' . $contract->StartingDate . '</td>
                                   <td id="EndingDate">' . $contract->EndingDate . '</td>';

            // The max function is a trick to retrieve only one row by signatory,
            // otherwise the query gets much bigger
            $query = "SELECT so.BusinessName, max(su.DateOfSignature) as 'Signed' 
                      FROM signatory so
                      JOIN is_party ip ON so.Id = ip.SignatoryId
                      LEFT OUTER JOIN signature su ON so.Id = su.SignatoryId
                      WHERE ip.ContractId = ?
                      GROUP BY so.BusinessName";
            
            $stmt = $dbh->prepare($query);
            $bindArray = array($contract->Id);

            // To align the signatories and signatures columns
            $tableOffset = '<tr>
                            <td></td>
                            <td></td>
                            <td></td>';
            
            if ($stmt->execute($bindArray)) {
                
                if ($stmt->rowCount() > 0) {
                    
                    $rows = $stmt->fetchAll();
                    $i = 0;

                    // Either display the button to send the contract to the blockchain, or display
                    // the message 'Contrat stocké dans la blockchain'
                    $contractStateMessage = ((!intval($contract->StoredInLedger)) ?
                                                     '<button type="button" onclick="addToBlockchain(this, ' . $contract->Id . ')">
                                                       Ajouter à la Blockchain
                                                      </button>' :
                                                     'Contrat stocké dans la blockchain');
                    
                    // Retrieving the signatories associated with the contract
                    // and the signature if one has been issued
                    foreach ($rows as $signatory) {
                        
                        if ($i == 0 && $signatory->Signed == NULL) {
                            $contracts_list .= '<td>' . $signatory->BusinessName . '</td>
                                                <td>Non</td>
                                                <td>' . $contractStateMessage . '</td>
                                                </tr>';
                            
                        } else if ($i == 0 && $signatory->Signed != NULL) {
                            $contracts_list .=   '<td>' . $signatory->BusinessName . '</td>
                                                  <td>Oui</td>
                                                  <td>' . $contractStateMessage . '</td>
                                                  </tr>';
                            
                        } else if ($i > 0 && $signatory->Signed == NULL) {
                            $contracts_list .= $tableOffset . '<td>' . $signatory->BusinessName . '</td>
                                                              <td>Non</td>
                                                              </tr>';
                            
                        } else {
                            $contracts_list .= $tableOffset . '<td>' . $signatory->BusinessName . '</td>
                                                              <td>Oui</td>
                                                              </tr>';
                        }

                        $i++;
                        
                    };
                    
                } else $contracts_list .= '<td>Aucun signataire</td>
                                          <td>Pas de signatures</td>                       
                                          <td> ' . $contractStateMessage . '</td>
                                          </tr>';
        
            } else throw new Exception($dbh->errorInfo());

        }

        $html = str_replace("{message_mode}", "none", $html);

        $html = str_replace("{contracts_list_mode}", "block", $html);
        $html = str_replace("{contracts_list}", $contracts_list, $html);

    }
    
} catch (Exception $e) {

    $html = str_replace("{contracts_list_mode}", "none", $html);
    $html = str_replace("{contracts_list}", "", $html);
    
    $html = str_replace("{message_mode}", "block", $html);
    $html = str_replace("{message}", $e->getMessage(), $html);

}

// Display the html content
echo $html;

?>
