<?php
// Show errors to screen.
//ini_set('display_errors', 1);

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Clear out table.
$sql = 'DELETE FROM tbl_grade_activity_denormalized';
$results = db_query($sql);

// Query for users' enrollments
$sql = "SELECT DISTINCT u.id AS userid, c.id AS courseid
FROM mdl_user u
JOIN mdl_user_enrolments ue ON ue.userid = u.id
JOIN mdl_enrol e ON e.id = ue.enrolid
JOIN mdl_role_assignments ra ON ra.userid = u.id
JOIN mdl_context ct ON ct.id = ra.contextid AND ct.contextlevel = 50
JOIN mdl_course c ON c.id = ct.instanceid AND e.courseid = c.id
JOIN mdl_role r ON r.id = ra.roleid AND r.shortname = 'student'
WHERE e.status = 0 AND u.suspended = 0 AND u.deleted = 0
  AND (ue.timeend = 0 OR ue.timeend > NOW()) AND ue.status = 0 order by u.lastname asc, u.firstname asc";
$results = db_query($sql);
$num_inserts = 0;

// Insert records for latest grades for each user.
while($row = db_fetch_array($results)) {
  // Query for course dates.
  $sql = 'SELECT
         mdl_user.username, mdl_user.firstname, mdl_user.lastname,
         mdl_course.shortname AS course_shortname, mdl_course.fullname AS course_fullname,
         FROM_UNIXTIME(min(mdl_grade_grades.timecreated)) as oldest_grade_created,
         FROM_UNIXTIME(max(mdl_grade_grades.timecreated)) as newest_grade_created,
         FROM_UNIXTIME(min(mdl_grade_grades.timemodified)) AS oldest_grade_modified,
         FROM_UNIXTIME(max(mdl_grade_grades.timemodified)) AS newest_grade_modified
         FROM mdl_grade_grades
         JOIN mdl_user ON mdl_grade_grades.userid = mdl_user.id
         JOIN mdl_grade_items ON mdl_grade_grades.itemid = mdl_grade_items.id
         JOIN mdl_course ON mdl_grade_items.courseid = mdl_course.id
         WHERE mdl_grade_items.itemmodule in ("assignment", "assign", "quiz")
         AND mdl_user.id = %d and mdl_course.id = %d
         ORDER BY mdl_user.lastname ASC, mdl_user.firstname ASC';
  $course_results = db_query($sql, $row['userid'], $row['courseid']);
  while($row = db_fetch_array($course_results)) {
    $sql = 'insert into tbl_grade_activity_denormalized (username, firstname, lastname, course_shortname, course_fullname, oldest_grade, newest_grade) values ("%s", "%s", "%s", "%s", "%s", "%s", "%s")';
    if(empty($row['oldest_grade_created']) && !empty($row['oldest_grade_modified'])) {
      $oldest_grade = $row['oldest_grade_modified'];
      $newest_grade = $row['newest_grade_modified'];
    }
    else {
      $oldest_grade = $row['oldest_grade_created'];
      $newest_grade = $row['newest_grade_created'];
    }
    $result = db_query($sql, $row['username'], $row['firstname'], $row['lastname'], $row['course_shortname'], $row['course_fullname'], $oldest_grade, $newest_grade);
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
