<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Empty the table of student course data.
$sql = 'DELETE FROM tbl_student_course_data';
$results = db_query($sql);

// Query for all students in courses.
$sql = "SELECT DISTINCT u.id AS userid, c.id AS courseid
        FROM mdl_user u
        JOIN mdl_user_enrolments ue ON ue.userid = u.id
        JOIN mdl_enrol e ON e.id = ue.enrolid
        JOIN mdl_role_assignments ra ON ra.userid = u.id
        JOIN mdl_context ct ON ct.id = ra.contextid AND ct.contextlevel = 50
        JOIN mdl_course c ON c.id = ct.instanceid AND e.courseid = c.id
        JOIN mdl_role r ON r.id = ra.roleid AND r.shortname = 'student'
        WHERE e.status = 0 AND u.suspended = 0 AND u.deleted = 0
        AND (ue.timeend = 0 OR ue.timeend > NOW())
        AND ue.status = 0 order by u.lastname asc, u.firstname asc";
$results = db_query($sql);

// Initialize variables.
$course_data = array();
$num_inserts = 0;
// Iterate over users.
while($row = db_fetch_array($results)) {
  // Empty out the course data again.
  $course_data = array();
  $userid = $row['userid'];
  $courseid = $row['courseid'];
  // Get Student Login Date:
  $sql = 'select courseid, timeaccess from mdl_user_lastaccess where userid = %d and courseid = %d';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_login_date'] = $inner_row['timeaccess'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date:
  $sql = 'select mdl_course.id as courseid, 
          max(mdl_forum_posts.created) as last_forum_submission_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_forum_submission_date'] = $inner_row['last_forum_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, mdl_course.shortname AS course_shortname,
         max(mdl_assign_submission.timemodified) as last_assignment_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d and mdl_assign_plugin_config.plugin != "offline"
	 and mdl_assign.grade > 0 and mdl_course.id = %d
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_assignment_submission_date'] = $inner_row['last_assignment_submission_date'];
    //print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Graded Date:
  $sql = "SELECT mdl_course.id as courseid,
         max(mdl_grade_grades.timemodified) AS newest_grade 
         FROM mdl_grade_grades
         JOIN mdl_user ON mdl_grade_grades.userid = mdl_user.id
         JOIN mdl_grade_items ON mdl_grade_grades.itemid = mdl_grade_items.id
         JOIN mdl_course ON mdl_grade_items.courseid = mdl_course.id
         WHERE mdl_grade_items.itemmodule in ('assignment', 'assign')
         AND mdl_user.id = %d and mdl_course.id = %d";
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_assignment_graded_date'] = $inner_row['newest_grade'];
  }
  unset($inner_results);
  unset($inner_row);
  
  // Get Last Quiz Submission Date:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
         mdl_course.shortname AS course_shortname,
         max(mdl_quiz_attempts.timefinish) as last_quiz_completion_date
         FROM mdl_quiz_attempts
         JOIN mdl_user ON mdl_quiz_attempts.userid = mdl_user.id
         JOIN mdl_quiz ON mdl_quiz_attempts.quiz = mdl_quiz.id
         JOIN mdl_course ON mdl_quiz.course = mdl_course.id
         WHERE mdl_user.id = %d AND mdl_course.id = %d
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_quiz_completion_date'] = $inner_row['last_quiz_completion_date'];
  }
  unset($inner_results);
  unset($inner_row);
  
  // Get Last Quiz Graded Date:
  $sql = 'SELECT mdl_course.shortname AS course_shortname, 
    mdl_course.id as courseid,
    max(mdl_quiz_grades.timemodified) as newest_grade
    FROM mdl_quiz_grades
    JOIN mdl_quiz ON mdl_quiz_grades.quiz = mdl_quiz.id
    JOIN mdl_course ON mdl_quiz.course = mdl_course.id
    WHERE mdl_quiz_grades.userid = %d AND mdl_course.id = %d
    ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_quiz_graded_date'] = $inner_row['newest_grade'];
    //print_r($inner_row);
  }
  unset($inner_results);
  unset($inner_row);
  
  // Get Whether Each Student is Active for Pell Purposes (4.8 weeks of participation):
  $sql = 'SELECT t.courseid FROM tbl_student_courses_for_pell t
          WHERE t.userid = %d AND t.courseid = %d
          ORDER BY t.courseid asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['active_for_pell'] = 1;
  }
  unset($inner_results);
  unset($inner_row);
  
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
    $sql = 'insert into tbl_student_course_data (userid, courseid,
	       last_login_date, last_forum_submission_date,
	       last_assignment_submission_date, last_assignment_graded_date,
	       last_quiz_completion_date, last_quiz_graded_date, active_for_pell) 
               values (%d, %d, %d, %d, %d, %d, %d, %d, %d)';
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
