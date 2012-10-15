<?php

// Include the functions for handling Salesforce data.
require_once('sf_libs.inc');

/* Drupal bootstrap - full in order to use the Salesforce toolkit. */
// This script requires the latest version of the Salesforce API module for Drupal 6.
chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

  /* Step 2. Do a SOQL query to fetch the ID's and info of all the course registrations for professors. */
  $soql = "select id, name, term__c, year__c, student__r.id, student__r.firstname, 
           student__r.lastname, recordtypeid from City_Vision_Purchase__c";
  // Run the query using the Salesforce API's facility for querying Salesforce.
  // This is dependent on the presence of the PHP toolkit, and a configured username/password/security token.
  $results = salesforce_api_query($soql);
  print_r($results);