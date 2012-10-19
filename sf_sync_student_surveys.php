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
  $sql = 'select * from view_questionnaire_answers';
  // @totest: Test with a limit of 50 for inserts into test SF.
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
	  if(!isset($course_regs[$userid . '_' . $courseid])) {
	    $course_regs[$userid . '_' . $courseid] = 
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
	    $course_regs[$userid . '_' . $courseid][$fieldname] = $row['answer'];
	  }
	}
  }
  print_r($course_regs);

  /* Step 2. Do a SOQL query to fetch the ID's and info of all the course registrations for students. */
  /* $soql = "select id, name, term__c, year__c, student__r.id, student__r.firstname, 
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
  } */
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
	  $fieldnames = get_question_sf_fieldnames();
	  foreach($course_reg as $fieldname => $value) {
	    if(in_array($fieldname, $fieldnames)) {
		  $sf_object[$fieldname] = $value;
		}
	  }
	  $sf_object = (object) $sf_object;
	  $batch[] = $sf_object;
	  $batch_counter++;
	}
	// If you have a batch of 200 or if you have processed all records,
	// send them over, reset the counter, and empty the batch.
    if($batch_counter = 200 || $mdl_idx == $max_idx) {
	  print_r($batch);
	  /* $results = salesforce_api_upsert($batch, 'City_Vision_Purchase__c');
	  $batch_counter = 0;
	  $batch = array(); */
	}
  }
  //print_r($results);
  // @todo: Have some kind of error condition handling.
  return TRUE; 
}

function get_question_mdl_names($flip = FALSE) {
  $question_mdl_names = array(
    'Q1' => 'Did you achieve the goals you had when you started the course?',
	'Q2' => 'All things considered, are you satisfied with your studies with City Vision College?',
	'Q3' => 'Would you recommend this course to a friend?',
	'Q4' => 'Have you previously taken a course over the Internet?',
	'Q5' => 'How do you feel about the level of interaction with the instructor?',
	'Q6' => 'How do you feel about the level of interaction you had with your classmates?',
	'Q7' => 'How do you feel about the level of assistance provided by the college support staff?',
	'Q8' => 'What did you find least valuable about the course?',
	'Q9' => 'What did you find most valuable about the course?',
	'Q10' => 'How do you feel about the content of the course?',
	'Q11' => 'What is your opinion of the amount of work required for the course?',
	'Q12' => 'How many hours did you spend on the readings and other assignments for the course?',
	'Q13' => 'I found this course to be...',
	'Q14' => 'Tell us about the first time you decided to enroll in a course(s)  through City Vision. (1) How did you hear about City Vision? (2) What  alternatives to City Vision did you consider? (3) What made you decide  to enroll in the course(s)?',
	'Q15' => 'How do you intend to use your education from this course in the future?',
  );
  if($flip == TRUE) {
    $question_mdl_names = array_flip($question_mdl_names);
  }
  return $question_mdl_names;
}

function get_question_sf_fieldnames($flip = FALSE) {
  $question_sf_fieldnames = array(
    'Q1' => 'Q1_Achieved_Goals__c',
	'Q2' => 'Q2_Satisfied_with_Studies__c',
	'Q3' => 'Q3_Would_Recommend_to_Friend__c',
	'Q4' => 'Q4_Previously_Taken_Internet_Course__c',
	'Q5' => 'Q5_Level_of_Interaction_w_Instructor__c',
	'Q6' => 'Q6_Level_of_Interaction_w_Classmates__c',
	'Q7' => 'Q7_Level_of_Assistance_By_Support_Staff__c',
	'Q8' => 'Q8_What_Did_You_Find_Least_Valuable__c',
	'Q9' => 'Q9_What_Did_You_Find_Most_Valuable__c',
	'Q10' => 'Q10_How_Do_You_Feel_About_Course_Conten__c',
	'Q11' => 'Q11_Opinion_on_Amt_of_Work_Required__c',
	'Q12' => 'Q12_Hrs_Spent_on_Readings_Assignments__c',
	'Q13' => 'Q13_I_Found_This_Course_to_Be__c',
	'Q14' => 'Q14_Reason_for_Enrollment__c',
	'Q15' => 'Q15_How_Intend_to_Use_Education__c',
  );
  if($flip == TRUE) {
    $question_sf_fieldnames = array_flip($question_sf_fieldnames);
  }
  return $question_sf_fieldnames;
}

function get_question_sf_labels($flip = FALSE) {
  $question_sf_labels = array(
    'Q1' => 'Q1: Achieved Goals?',
	'Q2' => 'Q2: Satisfied with Studies?',
	'Q3' => 'Q3: Would Recommend to Friend?',
	'Q4' => 'Q4: Previously Taken Internet Course?',
	'Q5' => 'Q5: Level of Interaction w/Instructor',
	'Q6' => 'Q6: Level of Interaction w/Classmates',
	'Q7' => 'Q7: Level of Assistance By Support Staff',
	'Q8' => 'Q8: What Did You Find Least Valuable?',
	'Q9' => 'Q9: What Did You Find Most Valuable?',
	'Q10' => 'Q10: How Do You Feel Abt Course Content',
	'Q11' => 'Q11: Opinion on Amt of Work Required',
	'Q12' => 'Q12: Hrs Spent on Readings & Assignments',
	'Q13' => 'Q13: I Found This Course to Be...',
	'Q14' => 'Q14: What Made You Enroll in Course?',
	'Q15' => 'Q15: How Intend to Use Education',
  );
  if($flip == TRUE) {
    $question_sf_labels = array_flip($question_sf_labels);
  }
  return $question_sf_labels;
}