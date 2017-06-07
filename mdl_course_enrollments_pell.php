<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Clear out old values from tables.
$sql = 'DELETE FROM tbl_course_enrollments_pell';
$results = db_query($sql);
$sql = 'DELETE FROM tbl_student_courses_for_pell';
$results = db_query($sql);

// Query for all users in courses.
$sql = 'SELECT mdl_user.id AS userid, mdl_course.id AS courseid, mdl_course.shortname
FROM mdl_course, mdl_context, mdl_role_assignments, mdl_user 
WHERE mdl_context.instanceid = mdl_course.id 
AND mdl_role_assignments.contextid = mdl_context.id 
AND mdl_role_assignments.roleid = 5 
AND mdl_role_assignments.userid = mdl_user.id ORDER BY mdl_user.id';
$results = db_query($sql);

// Initialize variables.
$users_done = array();
$forum_posts = 0;
$assignment_subs = 0;
$valid_terms = array('sp1', 'sp2', 'sum', 'fall1', 'fall2');
$num_inserts = 0;
$num_updates = 0;
$num_course_inserts = 0;
// Update counts.
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
  $assignment_subs = 0;
  $forum_posts = 0;
  $quiz_completions = 0;
  if(!empty($term) && !empty($year) && in_array($term, $valid_terms)) {
    // Query for count of assignment submissions.
    $sql = 'SELECT count(mdl_assign_submission.id) AS cnt
            FROM mdl_assign_submission
            JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
            JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
            JOIN mdl_course ON mdl_assign.course = mdl_course.id
            JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
            WHERE mdl_user.id = %d AND mdl_course.id = %d 
			AND mdl_assign_plugin_config.plugin != "offline"
			AND mdl_assign.grade > 0 AND mdl_assign_submission.grade > 0
            AND mdl_assign_submissions.timecreated > mdl_course.startdate + (60*60*24*34)';
    $assignment_subs = db_result(db_query($sql, $row['userid'], $row['courseid']));
	//echo sprintf($sql, $row['userid'], $row['courseid']) . '<br/>';	
	
	/* $sql = 'SELECT count(mdl_forum_posts.id) AS cnt
	        FROM mdl_forum_posts
            JOIN mdl_forum_discussions ON mdl_forum_posts.discussion = mdl_forum_discussions.id 
			JOIN mdl_forum ON mdl_forum_discussions.forum = mdl_forum.id 
			JOIN mdl_course ON mdl_forum_discussions.course = mdl_course.id 
			WHERE mdl_forum_posts.userid = %d AND mdl_course.id = %d
			AND mdl_forum_posts.created > mdl_course.startdate + (60*60*24*34)';
	$forum_posts = db_result(db_query($sql, $row['userid'], $row['courseid'])); */
	
	// Query for count of quiz completions.
	$sql = 'SELECT count(mdl_quiz_attempts.id) AS cnt
            FROM mdl_quiz_attempts
            JOIN mdl_user ON mdl_quiz_attempts.userid = mdl_user.id
            JOIN mdl_quiz ON mdl_quiz_attempts.quiz = mdl_quiz.id
            JOIN mdl_course ON mdl_quiz.course = mdl_course.id
            WHERE mdl_user.id = %d AND mdl_course.id = %d
			AND mdl_quiz_attempts.timefinish > mdl_course.startdate + (60*60*24*34)';
	$quiz_completions = db_result(db_query($sql, $row['userid'], $row['courseid']));
    //echo sprintf($sql, $row['userid'], $row['courseid']) . '<br/>';	
	
	// If user has completed at least one assignment or quiz after 34 days in, 
	// count as a participant for Pell purposes.
    if($quiz_completions > 0 || $assignment_subs > 0) {
      // Update the table if a row for this user is already inserted.
      if(in_array($row['userid'], $users_done)) {
        $sql = 'UPDATE tbl_course_enrollments_pell SET ' . $field . ' = ' . $field . ' + 1 WHERE userid = %d';
	    //echo sprintf($sql, $row['userid']) . '<br/>';
        $result = db_query($sql, $row['userid']);
	    if($result == TRUE) {
	      $num_updates++;
        }
      }
      // Otherwise, use this value to create the row.
      else {
	    $sql = 'INSERT INTO tbl_course_enrollments_pell (userid, ' . $field . ') VALUES (%d, 1)';
	    //echo sprintf($sql, $row['userid']) . '<br/>';
	    $result = db_query($sql, $row['userid']);
        $users_done[] = $row['userid'];
	    if($result == TRUE) {
	      $num_inserts++;
        }
      }
	  // Insert a row for the specific course.
	  $sql = 'INSERT INTO tbl_student_courses_for_pell(userid, courseid) VALUES (%d, %d)';
	  $result = db_query($sql, $row['userid'], $row['courseid']);
	  if($result == TRUE) {
	    $num_course_inserts++;
	  }
    }
  }
  //print_r(array($course_code, $term, $year, $field));
}
echo 'Inserts: ' . $num_inserts . ' ';
echo 'Updates : ' . $num_updates . ' ';
echo 'Course Inserts: ' . $num_course_inserts;
