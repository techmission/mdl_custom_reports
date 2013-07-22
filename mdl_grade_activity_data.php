<?php
// Show errors to screen.
ini_set('display_errors', 1);

/* Drupal bootstrap - full so use of watchdog. */
chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Clear out table.
$sql = 'DELETE FROM tbl_grade_activity_denormalized';
$results = db_query($sql);

// Query for users' names.
$sql = 'SELECT distinct(username) FROM mdl_user JOIN mdl_grade_grades ON mdl_user.id = mdl_grade_grades.userid ORDER BY username ASC';
$results = db_query($sql);
$num_inserts = 0;
// Insert records for latest grades for each user.
while($row = db_fetch_array($results)) {
  echo "Username: " . $row['username'];
  // Query for course dates.
  $sql = 'SELECT ' .
    'mdl_user.username,' .
    'mdl_course.shortname AS course_shortname, ' .
    'FROM_UNIXTIME(min(mdl_grade_grades.timecreated)) as oldest_grade, ' .
    'FROM_UNIXTIME(max(mdl_grade_grades.timecreated)) as newest_grade ' .
    'FROM mdl_grade_grades ' .
    'JOIN mdl_user ON mdl_grade_grades.userid = mdl_user.id ' .
    'JOIN mdl_grade_items ON mdl_grade_grades.itemid = mdl_grade_items.id ' .
    'JOIN mdl_course ON mdl_grade_items.courseid = mdl_course.id ' .
    'WHERE mdl_grade_items.itemmodule in ("assignment", "quiz") and mdl_user.username = "%s" ' .
    'GROUP BY mdl_course.shortname ' .
    'ORDER BY mdl_course.shortname asc';
  $course_results = db_query($sql, $row['username']);
  while($row = db_fetch_array($course_results)) {
    $sql = 'insert into tbl_grade_activity_denormalized (username, course_shortname, oldest_grade, newest_grade) values ("%s", "%s", "%s", "%s")';
    $result = db_query($sql, $row['username'], $row['course_shortname'], $row['oldest_grade'], $row['newest_grade']);
    if($result == TRUE) {
      $num_inserts++;
    }
  }
}
// Log a successful insert to watchdog.
if(isset($num_inserts)) {
  echo "Inserted " . $num_inserts . " records";
  watchdog('Moodle reporting', 'Inserted %count student grades into tbl_grade_activity_denormalized.', array('%count' => $num_inserts), WATCHDOG_NOTICE);
}
