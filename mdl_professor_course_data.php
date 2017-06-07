<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Empty the table of professor course data.
$sql = 'DELETE FROM tbl_professor_course_data';
$results = db_query($sql);

// Query for all professors in courses.
$sql = 'SELECT distinct(mdl_user.id) AS userid ' .
       'FROM mdl_course, mdl_context, mdl_role_assignments, mdl_user ' .
       'WHERE mdl_context.instanceid = mdl_course.id ' .
       'AND mdl_role_assignments.contextid = mdl_context.id ' .
       'AND mdl_role_assignments.roleid = 3 ' .
       'AND mdl_role_assignments.userid = mdl_user.id ORDER BY mdl_user.id';
$results = db_query($sql);

// Initialize variables.
$course_data = array();
$num_inserts = 0;
// Iterate over users.
while($row = db_fetch_array($results)) {
  // Empty out the course data again.
  $course_data = array();
  $userid = $row['userid'];
  // Get Ids of Courses:
  $sql = 'select distinct(mdl_course.id) AS courseid ' .
       'FROM mdl_course, mdl_context, mdl_role_assignments, mdl_user ' .
       'WHERE mdl_context.instanceid = mdl_course.id ' .
       'AND mdl_role_assignments.contextid = mdl_context.id ' .
       'AND mdl_role_assignments.roleid = 3 ' .
	   'AND mdl_user.id = %d ' . 
       'AND mdl_role_assignments.userid = mdl_user.id ORDER BY mdl_course.id';
  $inner_results = db_query($sql, $userid);
  $courses = array();
  while($course_row = db_fetch_array($inner_results)) {
    $courses[] = $course_row['courseid'];
  }
  $courses = '(' . implode(',', $courses) . ')';
  
  // Get Professor Login Date:
  $sql = 'select courseid, timeaccess from mdl_user_lastaccess where userid = %d and courseid in ' . $courses;
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_login_date'] = $inner_row['timeaccess'];
	//print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Quiz Graded Date:
  $sql = 'SELECT ' .
    'mdl_course.shortname AS course_shortname, mdl_course.id as courseid, ' .
    'max(mdl_quiz_grades.timemodified) as newest_grade ' .
    'FROM mdl_quiz_grades ' .
	'JOIN mdl_quiz ON mdl_quiz_grades.quiz = mdl_quiz.id ' .
    'JOIN mdl_course ON mdl_quiz.course = mdl_course.id ' .
    'WHERE mdl_course.id in ' . $courses . ' ' .
    'GROUP BY mdl_course.id ' .
    'ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_quiz_graded_date'] = $inner_row['newest_grade'];
	//print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Graded Date:
  $sql = 'SELECT ' .
    'mdl_course.shortname AS course_shortname, mdl_course.id as courseid, ' .
    'max(mdl_assignment_submissions.timemarked) as newest_grade ' .
    'FROM mdl_assignment_submissions ' .
	'JOIN mdl_assignment ON mdl_assignment_submissions.assignment = mdl_assignment.id ' .
    'JOIN mdl_course ON mdl_assignment.course = mdl_course.id ' .
    'WHERE mdl_assignment_submissions.teacher = %d ' .
	//and mdl_course.id in ' . $courses . ' ' .
    'GROUP BY mdl_course.id ';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_assignment_graded_date'] = $inner_row['newest_grade'];
    //print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);
  
  //print_r($course_data);
  // Iterate over course data and set empty values.
  foreach($course_data as $courseid => $data) {
    if(!isset($data['last_login_date'])) {
      $data['last_login_date'] = 0;
    }
    if(!isset($data['last_quiz_graded_date'])) {
	  $data['last_quiz_graded_date'] = 0;
    }
    if(!isset($data['last_assignment_graded_date'])) {
	  $data['last_assignment_graded_date'] = 0;
    }
    $sql = 'insert into tbl_professor_course_data (userid, courseid, ' .
	       'last_login_date, last_assignment_graded_date, ' .
	       'last_quiz_graded_date) ' .
		   'values (%d, %d, %d, %d, %d)';
    $result = db_query($sql, $userid, $courseid, $data['last_login_date'], 
	  $data['last_assignment_graded_date'], $data['last_quiz_graded_date']);
    if($result == TRUE) {
      $num_inserts++;
    }
  }
}
echo 'Inserts: ' . $num_inserts . ' ';
