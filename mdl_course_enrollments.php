<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Run a test to confirm connection is working.
/* $results = db_query('select firstname, lastname from mdl_user limit 10');
while($row = db_fetch_array($results)) {
  echo $row['firstname'] . ' ' . $row['lastname'];
} */

$sql = 'DELETE FROM tbl_course_enrollments';
$result = db_query($sql);

// Query for all users in courses.
$sql = 'SELECT mdl_user.id AS userid, mdl_course.shortname
FROM mdl_course, mdl_context, mdl_role_assignments, mdl_user 
WHERE mdl_context.instanceid = mdl_course.id 
AND mdl_role_assignments.contextid = mdl_context.id 
AND mdl_role_assignments.roleid = 5 
AND mdl_role_assignments.userid = mdl_user.id ORDER BY mdl_user.id';
$results = db_query($sql);

$users_done = array();
$valid_terms = array('sp1', 'sp2', 'sum', 'fall1', 'fall2');
$num_inserts = 0;
$num_updates = 0;
// Update counts. Inserts everyone who is currently enrolled in the course, 
// regardless of their level of participation.
while($row = db_fetch_array($results)) {
  // Parse the term and year out.
  $course_parts = explode('-', $row['shortname']);
  $course_parts = explode('_', $course_parts[0]);
  $course_code = substr($course_parts[0], 4, 3);
  $term = strtolower($course_parts[1]);
  $year = $course_parts[2];
  // The field is used to actually update the aggregate table.
  $field = $term . '_' . $year;
  // Only update when the data to update is valid.
  if(!empty($term) && !empty($year) && in_array($term, $valid_terms)) {
    // Update the table if a row for this user is already inserted.
    if(in_array($row['userid'], $users_done)) {
      $sql = 'UPDATE tbl_course_enrollments SET ' . $field . ' = ' . $field . ' + 1 WHERE userid = %d';
	  echo sprintf($sql, $row['userid']) . '<br/>';
      $result = db_query($sql, $row['userid']);
	  if($result == TRUE) {
	    $num_updates++;
      }
    }
    // Otherwise, use this value to create the row.
    else {
	  $sql = 'INSERT INTO tbl_course_enrollments (userid, ' . $field . ') VALUES (%d, 1)';
	  echo sprintf($sql, $row['userid']) . '<br/>';
	  $result = db_query($sql, $row['userid']);
      $users_done[] = $row['userid'];
	  if($result == TRUE) {
	    $num_inserts++;
      }
    }
  }
  //print_r(array($course_code, $term, $year, $field));
}
echo 'Inserts: ' . $num_inserts . ' ';
echo 'Updates : ' . $num_updates;
