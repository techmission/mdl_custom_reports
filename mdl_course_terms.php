<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Query for all courses.
$sql = 'SELECT id, shortname FROM mdl_course';
$results = db_query($sql);

// Empty the course terms tables.
$sql = 'DELETE FROM tbl_course_terms';
$result = db_query($sql);

$courses_done = array();
$valid_terms = array('sp1', 'sp2', 'sum', 'fall1', 'fall2');
$num_inserts = 0;
// Update counts.
while($row = db_fetch_array($results)) {
  // Parse the term and year out.
  $course_parts = explode('-', $row['shortname']);
  $course_parts = explode('_', $course_parts[0]);
  $course_code = substr($course_parts[0], 4, 3);
  $term = strtolower($course_parts[1]);
  $year = $course_parts[2];
  // Only update when the data to update is valid.
  if(!empty($term) && !empty($year) && in_array($term, $valid_terms)) {
	$sql = 'INSERT INTO tbl_course_terms (courseid, term, year) VALUES (%d, "%s", "%s")';
	// echo sprintf($sql, $row['id'], $term, $year) . '<br/>';
	$result = db_query($sql, $row['id'], $term, $year);
	if($result == TRUE) {
	  $num_inserts++;
    }
  }
  else {
    print_r(array('invalid name' => $row['shortname'], 'code' => $course_code, 'term' => $term, 'year' => $year));
  }
}
echo 'Inserts: ' . $num_inserts;
