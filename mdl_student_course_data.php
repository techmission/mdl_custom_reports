<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Empty the table of student course data.
$sql = 'DELETE FROM tbl_student_course_data';
$results = db_query($sql);

// Query for all students in courses.
$sql = 'SELECT distinct(mdl_user.id) AS userid ' .
       'FROM mdl_course, mdl_context, mdl_role_assignments, mdl_user ' .
       'WHERE mdl_context.instanceid = mdl_course.id ' .
       'AND mdl_role_assignments.contextid = mdl_context.id ' .
       'AND mdl_role_assignments.roleid = 5 ' .
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
  // Get Student Login Date:
  $sql = 'select courseid, timeaccess from mdl_user_lastaccess where userid = %d';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_login_date'] = $inner_row['timeaccess'];
	//print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date:
  $sql = 'select mdl_course.id as courseid, max(mdl_forum_posts.created) as last_forum_submission_date ' . 
         'from mdl_forum_posts ' .
         'join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id ' .
         'join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id ' .
         'join mdl_course on mdl_forum_discussions.course = mdl_course.id ' .
         'where mdl_forum_posts.userid = %d ' .
         'group by mdl_course.id ' .
         'order by mdl_course.id';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_forum_submission_date'] = $inner_row['last_forum_submission_date'];
	//print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, mdl_course.shortname AS course_shortname, ' .
         'max(mdl_assignment_submissions.timemodified) as last_assignment_submission_date ' .
         'FROM mdl_assignment_submissions ' .
         'JOIN mdl_user ON mdl_assignment_submissions.userid = mdl_user.id ' .
         'JOIN mdl_assignment ON mdl_assignment_submissions.assignment = mdl_assignment.id ' .
         'JOIN mdl_course ON mdl_assignment.course = mdl_course.id ' .
         'WHERE mdl_user.id = %d and mdl_assignment.assignmenttype != "offline" ' .
		 'and mdl_assignment.grade > 0 ' .
         'GROUP BY mdl_course.id ' .
         'ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_assignment_submission_date'] = $inner_row['last_assignment_submission_date'];
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
    'WHERE mdl_assignment_submissions.userid = %d ' .
	//and mdl_course.id in ' . $courses . ' ' .
    'GROUP BY mdl_course.id ';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_assignment_graded_date'] = $inner_row['newest_grade'];
		//print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);
  
  // Get Last Quiz Submission Date:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, mdl_course.shortname AS course_shortname, ' .
         'max(mdl_quiz_attempts.timefinish) as last_quiz_completion_date ' .
         'FROM mdl_quiz_attempts ' .
         'JOIN mdl_user ON mdl_quiz_attempts.userid = mdl_user.id ' .
         'JOIN mdl_quiz ON mdl_quiz_attempts.quiz = mdl_quiz.id ' .
         'JOIN mdl_course ON mdl_quiz.course = mdl_course.id ' .
		 'WHERE mdl_user.id = %d ' .
         'GROUP BY mdl_course.id ' .
         'ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_quiz_completion_date'] = $inner_row['last_quiz_completion_date'];
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
    'WHERE mdl_quiz_grades.userid = %d ' .
    'GROUP BY mdl_course.id ' .
    'ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_quiz_graded_date'] = $inner_row['newest_grade'];
    //print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);
  
  // Get Whether Each Student is Active for Pell Purposes (4.8 weeks of participation):
  $sql = 'SELECT t.courseid FROM tbl_student_courses_for_pell t ' .
    'WHERE t.userid = %d ' .
    'ORDER BY t.courseid asc';
  $inner_results = db_query($sql, $userid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['active_for_pell'] = 1;
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
    if(!isset($data['last_forum_submission_date'])) {
	  $data['last_forum_submission_date'] = 0;
    }
    if(!isset($data['last_assignment_submission_date'])) {
	  $data['last_assignment_submission_date'] = 0;
    }
    if(!isset($data['last_assignment_graded_date'])) {
	  $data['last_assignment_graded_date'] = 0;
    }
    if(!isset($data['last_quiz_completion_date'])) {
	  $data['last_quiz_completion_date'] = 0;
    }
    if(!isset($data['last_quiz_graded_date'])) {
	  $data['last_quiz_graded_date'] = 0;
    }
	if(!isset($data['active_for_pell'])) {
	  $data['active_for_pell'] = 0;
	}
    $sql = 'insert into tbl_student_course_data (userid, courseid, ' .
	       'last_login_date, last_forum_submission_date, ' .
	       'last_assignment_submission_date, last_assignment_graded_date, ' .
	       'last_quiz_completion_date, last_quiz_graded_date, active_for_pell) ' .
		   'values (%d, %d, %d, %d, %d, %d, %d, %d, %d)';
    $result = db_query($sql, $userid, $courseid, $data['last_login_date'], 
	  $data['last_forum_submission_date'], $data['last_assignment_submission_date'],
	  $data['last_assignment_graded_date'], $data['last_quiz_completion_date'],
	  $data['last_quiz_graded_date'], $data['active_for_pell']);
    if($result == TRUE) {
      $num_inserts++;
    }
  }
}
echo 'Inserts: ' . $num_inserts . ' ';