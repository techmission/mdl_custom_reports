<?php

/* Defines constants. */

// Record type for courses in which students are enrolled.
define('RECORDTYPEID_COURSE', '012A0000000smdbIAA');

// Include the functions for handling Salesforce data.
require_once('sf_libs.inc');
/*
/* Drupal bootstrap - full in order to use the Salesforce toolkit. */
// This script requires the latest version of the Salesforce API module for Drupal 6.
chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Execute the main logic.
run_sync();

function run_sync() {
  /* Step 1. Query for the course grades in Moodle DB. */
  $sql = 'select * from view_course_final_grades';
  $results = db_query($sql);
  $course_regs = array();
  while($row = db_fetch_array($results)) {
    $course_name_parsed = parse_course_name($row['course_shortname']);
	$row['course_name'] = !empty($course_name_parsed['name']) ? $course_name_parsed['name'] : '';
	$row['code'] = !empty($course_name_parsed['code']) ? $course_name_parsed['code'] : '';
	$row['term'] = !empty($course_name_parsed['term']) ? $course_name_parsed['term'] : '';
	$row['year'] = !empty($course_name_parsed['year']) ? $course_name_parsed['year'] : '';
	$course_regs[] = $row;
  }
  //print_r($course_regs);

  /* Step 2. Do a SOQL query to fetch the ID's and info of all the course registrations for students. */
  $soql = "select id, name, term__c, year__c, student__r.id, student__r.firstname, 
           student__r.lastname, recordtypeid from City_Vision_Purchase__c
		   where recordtypeid = '" . RECORDTYPEID_COURSE . "'";
  // Run the query using the Salesforce API's facility for querying Salesforce.
  // This is dependent on the presence of the PHP toolkit, and a configured username/password/security token.
  $results = salesforce_api_query($soql);
  $sf_objects = array();
  foreach($results as $result) {
    $sf_object = flatten_sf_object($result);
	$sf_object->Code__a = get_course_code($sf_object->Name);
	$sf_objects[] = $sf_object;
  }
  //print_r($sf_objects);
  
  /* Step 3. Iterate over the SOQL results to match them to the Moodle results. */
  // Match on Student Firstname, Lastname + Course Name + Term + Year
  $match_idxs = array();
  foreach($sf_objects as $sf_idx => $sf_object) {
    // Iterate over the course registrations.
	foreach($course_regs as $mdl_idx => $course_reg) {
	  $firstname_match = (trim($sf_object->Student__r__FirstName) == trim($course_reg['firstname'])) ? TRUE : FALSE;
	  $lastname_match = (trim($sf_object->Student__r__LastName) == trim($course_reg['lastname'])) ? TRUE : FALSE;
	  $code_match = ($sf_object->Code__a == $course_reg['code']) ? TRUE : FALSE;
	  $term_match = ($sf_object->Term__c == $course_reg['term']) ? TRUE : FALSE;
	  $year_match = ($sf_object->Year__c == $course_reg['year']) ? TRUE : FALSE;
	  $matches = array('sf_idx' => $sf_idx, 'mdl_idx' => $mdl_idx, 'firstname' => $firstname_match, 'lastname' => $lastname_match, 'code' => $code_match, 'term' => $term_match, 'year' => $year_match);
	  //var_dump($matches);
	  // If there is a match, set the Salesforce id.
	  if($firstname_match && $lastname_match && $code_match && $term_match && $year_match) {
	    $course_regs[$mdl_idx]['Id'] = $sf_object->Id;
	  }
	}
  }
  //print_r($course_regs);

  /* Step 4. Send to Salesforce, in groups of 200. */
  $batch_counter = 0;
  // Highest possible index is equal to all registrations minus one.
  $max_idx = count($course_regs) - 1;
  $batch = array();
  foreach($course_regs as $mdl_idx => $course_reg) {
    // Skip over ones that don't have a Salesforce id.
    if(!isset($course_reg['Id'])) {
	  continue;
	}
	else {
	  $sf_object = array('Id' => $course_reg['Id']);
	  if(!empty($course_reg['letter_grade']) {
	    $sf_object['Current_Grade__c'] = $course_reg['letter_grade'];
	  }
	  if($course_reg['grade_percent'] != null) {
	    $sf_object['Current_Grade_Percent__c'] = $course_reg['grade_percent'];
	  }
	  $sf_object = (object) $sf_object;
	  $batch[] = $sf_object;
	  $batch_counter++;
	}
	// If you have a batch of 200 or if you have processed all records,
	// send them over, reset the counter, and empty the batch.
    if($batch_counter = 200 || $mdl_idx == $max_idx) {
	  //print_r($batch);
	  $results = salesforce_api_upsert($batch, 'City_Vision_Purchase__c');
	  $batch_counter = 0;
	  $batch = array();
	}
  }
  //print_r($results);
  // @todo: Have some kind of error condition handling.
  return TRUE; 
}