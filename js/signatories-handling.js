// Launch appendBusinessNamesOnLastRow function when the DOM is ready
$().ready(appendBusinessNamesOnLastRow);

/*
 * Send a Http Get query to retrieve-signatory.php script in order to retrieve
 * all the business names of the signatories entities stored in the database
 * Business Names will populate the select field in the last row of the table
 * in the create-contract.html page
 * */
function appendBusinessNamesOnLastRow() {

  // Calling retrieve-signatory.php script to retrieve signatories' business names
  // and populate the "Siège social" field in the last row of the table
  $.get("http://vincent-linux/app-for-bl-operator/scripts/retrieve-signatory.php", function(data) {

    console.log(data);

    // Getting back a string from http request
    var signatoriesArray = JSON.parse(data);

    // Get the select tag which will be populated with the data retrieved
    var businessNameSelectTag = $( '#LastSignatoryRow > #BusinessNameContainer > #BusinessName' );

    var index = 0;

    // Adding a option tag for each business names
    signatoriesArray.forEach(function(signatoryObject) {

      // To retrieve the first option afterwise
      if (index == 0)
        businessNameSelectTag.append('<option id="firstOption" onclick="completeSignatoryRow(this, ' + signatoryObject["Id"]  +')">'
                                    + signatoryObject["BusinessName"] + '</option>');
      else businessNameSelectTag.append('<option onclick="completeSignatoryRow(this, ' + signatoryObject["Id"]  +')">'
                                       + signatoryObject["BusinessName"] + '</option>');

      index++;

    });

    // Trigger completeSignatoryRow function on the firstOption tag
    $( '#firstOption' ).trigger( "click" );

    // Delete the id make it available for future rows
    $( '#firstOption' ).attr('id', '');
    
  });

}

/*
 * Complete signatory row with values associated with the signatory object 
 * identified by signatoryId
 */
function completeSignatoryRow(clickedTag, signatoryId) {

  $.get("http://vincent-linux/app-for-bl-operator/scripts/retrieve-signatory.php?Id=" + signatoryId, function(data) {

    var retrievedSignatory = JSON.parse(data)[0];
    var signatoryRowTag = $( clickedTag ).parents('tr');

    // Complete the input fields with the new values
    $.each(retrievedSignatory, function(key, value) {

      if (key != 'BusinessName') 
        signatoryRowTag.find('#' + key).attr("value", value);
      
    });
    
  });

}

/*
 * Function called when the "Ajouter une ligne" button is clicked
 * Add a row to the #SignatoriesEntries table
 */
function addSignatoryRow() {

  $( '#LastSignatoryRow' ).attr('id', '');
  $( '#SignatoriesEntries' ).append('<tr id="LastSignatoryRow">' + 
	                            '<td hidden><input type="text" id="Id" name="SignatoriesId[]" hidden></td>' +
	                            '<td><input type="text" id="SignatoryStatus" name="SignatoryStatus[]" value="Client" readonly></td>' +
	                            '<td id="BusinessNameContainer">' +
	                            '<select id="BusinessName">' +
	                            '</select>' +
	                            '</td>' +
	                            '<td><input type="text" id="HeadQuarters" readonly></td>' +
	                            '<td><input type="text" id="Holder" readonly></td>' +
	                            '<td><input type="text" id="RegistrationNumber" readonly></td>' +
                                    '<td><input type="button" onclick="deleteSignatoryRow(this)" value="Supprimer"></td>' +                          
	                            '</tr>');
  
  appendBusinessNamesOnLastRow();
  
}

/*
 * Remove the tr tag parent to the clickedTag 
 */
function deleteSignatoryRow(clickedTag) {
  $( clickedTag ).parents('tr').remove();
}