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
  /* Step 1. Query for the course survey responses in Moodle DB. */
  $sql = 'select * from view_questionnaire_answers"';
  $results = db_query($sql);
  $course_regs = array();
  while($row = db_fetch_array($results)) {
    $course_name_parsed = parse_course_name($row['course_shortname']);
    $row['course_name'] = !empty($course_name_parsed['name']) ? $course_name_parsed['name'] : $row['course_name'];
    $row['code'] = !empty($course_name_parsed['code']) ? $course_name_parsed['code'] : '';
    $row['term'] = !empty($course_name_parsed['term']) ? $course_name_parsed['term'] : '';
    $row['year'] = !empty($course_name_parsed['year']) ? $course_name_parsed['year'] : '';
    $row['answer'] = '';
    if(!empty($row['bool_choice'])) {
      $row['answer'] = $row['bool_choice'];
    }
    elseif(!empty($row['choice_value'])) {
      $row['answer'] = $row['choice_value'];
    }
    elseif(!empty($row['text_value'])) {
      $row['answer'] = $row['text_value'];
    }
    if(!empty($row['answer'])) {
      // Set the initial values used for matching.
      $rowname = $row['userid'] . '_' . $row['courseid'];
      if(!isset($course_regs[$rowname])) {
        $course_regs[$rowname] = 
           array(
	    'firstname' => $row['firstname'],
	    'lastname' => $row['lastname'],
	    'code' => $row['code'],
	    'term' => $row['term'],
	    'year' => $row['year'],
	   );
      }
      // Add in the question response.
      $question_mdl_names = get_question_mdl_names(TRUE);
      $fieldnames = get_question_sf_fieldnames();
      $fieldname = '';
      if(array_key_exists($row['question'], $question_mdl_names)) {
        $question_id = $question_mdl_names[$row['question']];
      }
      if(array_key_exists($question_id, $fieldnames)) {
        $fieldname = $fieldnames[$question_id];
      }
      if(!empty($fieldname)) {
        $course_regs[$rowname][$fieldname] = $row['answer'];
      }
    }
  }
  print_r($course_regs);
  exit();
  /* Step 2. Do a SOQL query to fetch the ID's and info of all the course registrations for students. */
  $soql = "select id, name, term__c, year__c, student__r.id, student__r.firstname, 
           student__r.lastname, recordtypeid from City_Vision_Purchase__c
		   where recordtypeid = '" . RECORDTYPEID_COURSE . "'";
  // Run the query using the Salesforce API's facility for querying Salesforce.
  // This is dependent on the presence of the PHP toolkit, and a configured username/password/security token.
  $results = salesforce_api_query($soql, array('queryMore' => TRUE));
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
	    //echo "Match on Moodle " . $mdl_idx . " and Salesforce " . $sf_object->Id;
	    $course_regs[$mdl_idx]['Id'] = $sf_object->Id;
	  }
	}
  }
  // print_r($course_regs);

  /* Step 4. Send to Salesforce, in groups of 200. */
  $batch = array();
  $batch_sfids = array();
  $num_batches = 0;
  $num_updates = 0;
  $sf_objs = array();
  foreach($course_regs as $mdl_idx => $course_reg) {
    // Skip over ones that don't have a Salesforce id.
    if(!isset($course_reg['Id'])) {
	  continue;
    }
    else {
      $sf_object = array('Id' => $course_reg['Id']);
      $fieldnames = get_question_sf_fieldnames();
      foreach($course_reg as $fieldname => $value) {
         if(in_array($fieldname, $fieldnames)) {
 	   $sf_object[$fieldname] = $value;
       	 }
       }
       $sf_object = (object) $sf_object;
       $sf_objs[] = $sf_object;
    }
  }
  $sf_batches = array_chunk($sf_objs, 200);
  $sf_batches = array_chunk($sf_objs, 200);
  foreach($sf_batches as $batch) {
    $results = salesforce_api_upsert($batch, 'City_Vision_Purchase__c');
    $num_batches++;
    $num_updates = count($results['updated']) + $num_updates;
    print_r($results);
  }
  echo "Total number of batches: " . $num_batches . PHP_EOL;
  echo "Total number updated: " . $num_updates . PHP_EOL;
  //print_r($results);
  // @todo: Have some kind of error condition handling.
  return $results; 
}

function get_question_mdl_names($flip = FALSE) {
  $question_mdl_names = array(
    'Q1' => 'Did you achieve, or will you have achieved upon completing your studies, the goals you had when you started this course?',
    'Q2' => 'Would you recommend this course to a friend?',
    'Q3' => 'All things considered, were/are you satisfied with your studies with City Vision College?',
  );
  if($flip == TRUE) {
    $question_mdl_names = array_flip($question_mdl_names);
  }
  return $question_mdl_names;
}

function get_question_sf_fieldnames($flip = FALSE) {
  $question_sf_fieldnames = array(
    'Q1' => 'Q1_Achieved_Goals__c',
    'Q3' => 'Q2_Satisfied_with_Studies__c',
    'Q2' => 'Q3_Would_Recommend_to_Friend__c',
  );
  if($flip == TRUE) {
    $question_sf_fieldnames = array_flip($question_sf_fieldnames);
  }
  return $question_sf_fieldnames;
}

function get_question_sf_labels($flip = FALSE) {
  $question_sf_labels = array(
    'Q1' => 'Q1: Achieved Goals?',
    'Q3' => 'Q2: Satisfied with Studies?',
    'Q2' => 'Q3: Would Recommend to Friend?',
  );
  if($flip == TRUE) {
    $question_sf_labels = array_flip($question_sf_labels);
  }
  return $question_sf_labels;
}
