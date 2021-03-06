<?php

/* Defines constants. */

// Record type for courses in which students are enrolled.
define('RECORDTYPEID_COURSE', '012A0000000smdbIAA');

// Include the functions for handling Salesforce data.
chdir(dirname(__FILE__));

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
  /* Step 1. Query for the course registrations in Moodle DB. */
  $sql = 'select t.userid, u.firstname, u.lastname, t.courseid, c.fullname, c.shortname, 
          if(forum_submission_date > 0, 
          from_unixtime(forum_submission_date), 0) as forum_submission_date, 
          if(assignment_wk1_submission_date > 0, from_unixtime(assignment_wk1_submission_date), 0) as assignment_wk1_submission_date,
          if(assignment_wk2_submission_date > 0, from_unixtime(assignment_wk2_submission_date), 0) as assignment_wk2_submission_date,
          if(assignment_wk3_submission_date > 0, from_unixtime(assignment_wk3_submission_date), 0) as assignment_wk3_submission_date,
          if(assignment_wk4_submission_date > 0, from_unixtime(assignment_wk4_submission_date), 0) as assignment_wk4_submission_date,
          if(assignment_wk5_submission_date > 0, from_unixtime(assignment_wk5_submission_date), 0) as assignment_wk5_submission_date,
          if(assignment_wk6_submission_date > 0, from_unixtime(assignment_wk6_submission_date), 0) as assignment_wk6_submission_date,
          if(assignment_wk7_submission_date > 0, from_unixtime(assignment_wk7_submission_date), 0) as assignment_wk7_submission_date,
          if(assignment_wk8_submission_date > 0, from_unixtime(assignment_wk8_submission_date), 0) as assignment_wk8_submission_date,
          if(quiz_midterm_date > 0, from_unixtime(quiz_midterm_date), 0) as quiz_midterm_date, 
          if(quiz_final_date > 0, from_unixtime(quiz_final_date), 0) as quiz_final_date
          from tbl_student_course_data_v2 t
          join mdl_user u on t.userid = u.id
          join mdl_course c on t.courseid = c.id
          where c.fullname like "%Spring 2 2017%" or c.fullname like "%Spring 2 Term 2017%"';
  $results = db_query($sql);
  $course_regs = array();
  while($row = db_fetch_array($results)) {
    $course_name_parsed = parse_course_name($row['shortname']);
    $course_name_fmt = format_course_name($course_name_parsed);
	$row['course_name'] = !empty($course_name_parsed['name']) ? $course_name_parsed['name'] : '';
	$row['code'] = !empty($course_name_parsed['code']) ? $course_name_parsed['code'] : '';
	$row['term'] = !empty($course_name_fmt['term']) ? $course_name_fmt['term'] : '';
	$row['year'] = !empty($course_name_parsed['year']) ? $course_name_parsed['year'] : '';
	$course_regs[] = $row;
  }
  //print_r($course_regs);
  //die();

  /* Step 2. Do a SOQL query to fetch the ID's and info of all the course registrations for students. */
  $soql = "select id, name, term__c, year__c, student__r.id, student__r.firstname, 
           student__r.lastname, recordtypeid from City_Vision_Purchase__c
	   where recordtypeid = '" . RECORDTYPEID_COURSE . "'";
  // Run the query using the Salesforce API's facility for querying Salesforce.
  // This is dependent on the presence of the PHP toolkit, and a configured username/password/security token.
  //print_r($soql);
  //die();
  $results = salesforce_api_query($soql, array('queryMore' => TRUE));
  $sf_objects = array();
  foreach($results as $result) {
    $sf_object = flatten_sf_object($result);
    $sf_object->Code__a = get_course_code($sf_object->Name);
    $sf_objects[] = $sf_object;
  }
  echo "Count of sf_objects is " . count($sf_objects) . PHP_EOL;
  //print_r($sf_objects);
  //die();

  /* Step 3. Iterate over the SOQL results to match them to the Moodle results. */
  // Match on Student Firstname, Lastname + Course Name + Term + Year
  $match_idxs = array();
  $matches_count = 0;
  foreach($sf_objects as $sf_idx => $sf_object) {
    // Iterate over the course registrations.
    foreach($course_regs as $mdl_idx => $course_reg) {
      $firstname_match = (trim($sf_object->Student__r__FirstName) == trim($course_reg['firstname'])) ? TRUE : FALSE;
      $lastname_match = (trim($sf_object->Student__r__LastName) == trim($course_reg['lastname'])) ? TRUE : FALSE;
      $code_match = ($sf_object->Code__a == $course_reg['code']) ? TRUE : FALSE;
      $term_match = ($sf_object->Term__c == $course_reg['term']) ? TRUE : FALSE;
      $year_match = ($sf_object->Year__c == $course_reg['year']) ? TRUE : FALSE;
      $matches = array(
                'sf_idx' => $sf_idx, 
                'sf_name' => $sf_object->Student__r__FirstName . ' ' . $sf_object->Student__r__LastName . ': ' . $sf_object->Code__a . ' - ' . $sf_object->Term__c . ' ' . $sf_object->Year__c, 
                'mdl_idx' => $mdl_idx, 
                'mdl_name' => $course_reg['firstname'] . ' ' . $course_reg['lastname'] . ': ' . $course_reg['code'] . ' - ' . $course_reg['term'] . ' ' . $course_reg['year'], 
                'firstname' => $firstname_match, 
                'lastname' => $lastname_match, 
                'code' => $code_match, 
                'term' => $term_match, 
                'year' => $year_match
      );
      // var_dump($matches);
      // If there is a match, set the Salesforce id.
      if($firstname_match && $lastname_match && $code_match && $term_match && $year_match) {
        $course_regs[$mdl_idx]['Id'] = $sf_object->Id;
        $matches_count++;
      }
    }
  }
  echo "Count of matches is: " . $matches_count . PHP_EOL;
  // print_r($course_regs);

  /* Step 4. Send to Salesforce, in groups of 200. */
  $batch_counter = 0;
  $num_batches = 0;
  $num_updates = 0;
  $sf_objs = array();
  foreach($course_regs as $mdl_idx => $course_reg) {
    // Skip over ones that don't have a Salesforce id.
    if(!isset($course_reg['Id'])) {
      continue;
    }
    else {
      // Skip over ones that are already in the batch for some reason.
      if(in_array($course_reg['Id'], $batch_sfids)) {
        continue;
      }
      $batch_sfids[] = $course_reg['Id'];
      $sf_object = array('Id' => $course_reg['Id']);
      if($course_reg['forum_submission_date'] > 0) {
        $sf_object['Last_Forum_Submission_Date__c'] = sf_date_convert($course_reg['forum_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Last_Forum_Submission_Date__c';
      } */
      if($course_reg['assignment_wk1_submission_date'] > 0) {
        $sf_object['Progress_Week_1_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk1_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_1_Assignment_Date__c';
      } */
      if($course_reg['assignment_wk2_submission_date'] > 0) {
        $sf_object['Progress_Week_2_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk2_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_2_Assignment_Date__c';
      } */
      if($course_reg['assignment_wk3_submission_date'] > 0) {
        $sf_object['Progress_Week_3_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk3_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_3_Assignment_Date__c';
      } */
      if($course_reg['assignment_wk4_submission_date'] > 0) {
        $sf_object['Progress_Week_4_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk4_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_4_Assignment_Date__c';
      } */
      if($course_reg['assignment_wk5_submission_date'] > 0) {
        $sf_object['Progress_Week_5_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk5_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_5_Assignment_Date__c';
      } */
      if($course_reg['assignment_wk6_submission_date'] > 0) {
        $sf_object['Progress_Week_6_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk6_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_6_Assignment_Date__c';
      } */
      if($course_reg['assignment_wk7_submission_date'] > 0) {
        $sf_object['Progress_Week_7_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk7_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_7_Assignment_Date__c';
      } */
      if($course_reg['assignment_wk8_submission_date'] > 0) {
        $sf_object['Progress_Week_8_Assignment_Date__c'] = sf_date_convert($course_reg['assignment_wk8_submission_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Week_8_Assignment_Date__c';
      } */
      if($course_reg['quiz_midterm_date'] > 0) {
         $sf_object['Progress_Midterm_Exam_Date__c'] = sf_date_convert($course_reg['quiz_midterm_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Midterm_Exam_Date__c';
      } */
      if($course_reg['quiz_final_date'] > 0) {
         $sf_object['Progress_Final_Date__c'] = sf_date_convert($course_reg['quiz_final_date']);
      }
      /* else {
        $sf_object->fieldsToNull[] = 'Progress_Final_Date__c';
      } */
      $sf_object = (object) $sf_object;
      $sf_objs[] = $sf_object;
    }
  }
  $sf_batches = array_chunk($sf_objs, 200);
  foreach($sf_batches as $batch) {
    $results = salesforce_api_upsert($batch, 'City_Vision_Purchase__c');
    $num_batches++;
    $num_updates = count($results['updated']) + $num_updates;
    print_r($results);
  }
  echo "Total number of batches: " . $num_batches . PHP_EOL;
  echo "Total number updated: " . $num_updates . PHP_EOL;
  // @todo: Have some kind of error condition handling.
  return TRUE; 
}
