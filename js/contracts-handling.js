/*
 * Send a request to `add-contract-to-blockchain.php` which is in charge of 
 * querying the NodeJS web service enabling the interaction with the blockchain's ledger.
 * 
 * */
function addToBlockchain (button, contractId) {

  var parentTag = $( button ).parent('td');
  $( button ).remove();

  // Removing the button and replacing it with an wait message
  parentTag.text('Transaction en cours...');
  
  /* data is the response coming from the NodeJS web service, relayed by the PHP script.
   * data is a JSON-formatted object whether containing success and payload attributes in case of success,
   * or error attribute in case of failure.
   * */
  $.post("http://localhost/app-for-bl-operator/scripts/add-contract-to-blockchain.php?id=" + contractId, function(data) {
    
    var response = JSON.parse(data);

    // if the operation is a success, launch a http request 
    if (response.success) {
      
      parentTag.text('Contract stocké dans la blockchain');

    } else {

      // if the error message concerns the prior existence of the contract in the blockchain
      // replace the button by the mention 'Contract stocké dans la blockchain'
      if (response.error.match(new RegExp(/Asset\s[0-9]+\salready\sexists/))) {
        
        parentTag.text('Contract stocké dans la blockchain');

      } else {

        parentTag.append(button);
        alert(reponse.error);

      }

    }
    
  });
  
}