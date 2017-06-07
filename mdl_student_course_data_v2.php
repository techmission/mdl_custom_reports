<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Empty the table of student course data.
$sql = 'DELETE FROM tbl_student_course_data_v2';
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

  // Get Last Assignment Submission Date for Week 1:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
           mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified)
           as assignment_wk1_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status` = "submitted"
	 AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 1)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk1_submission_date'] = $inner_row['assignment_wk1_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date for Week 2:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
           mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified) 
           as assignment_wk2_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status`
 = "submitted"
         AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 2)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk2_submission_date'] = $inner_row['assignment_wk2_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date for Week 3:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
            mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified) 
            as assignment_wk3_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status`
 = "submitted"
         AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 3)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk3_submission_date'] = $inner_row['assignment_wk3_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date for Week 4:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
            mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified) 
            as assignment_wk4_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status`
 = "submitted"
         AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 4)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk4_submission_date'] = $inner_row['assignment_wk4_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date for Week 5:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
           mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified) 
           as assignment_wk5_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status`
 = "submitted"
         AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 5)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk5_submission_date'] = $inner_row['assignment_wk5_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date for Week 6:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
           mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified) 
           as assignment_wk6_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status`
 = "submitted"
         AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 6)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk6_submission_date'] = $inner_row['assignment_wk6_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date for Week 7:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
           mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified) 
           as assignment_wk7_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status`
 = "submitted"
         AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 7)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk7_submission_date'] = $inner_row['assignment_wk7_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Assignment Submission Date for Week 8:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
            mdl_course.shortname AS course_shortname,
         min(mdl_assign_submission.timemodified) 
            as assignment_wk8_submission_date
         FROM mdl_assign_submission
         JOIN mdl_user ON mdl_assign_submission.userid = mdl_user.id
         JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
         JOIN mdl_course ON mdl_assign.course = mdl_course.id
         JOIN mdl_assign_plugin_config ON mdl_assign.id = mdl_assign_plugin_config.assignment
         WHERE mdl_user.id = %d AND mdl_assign_plugin_config.plugin != "offline"
         AND mdl_assign_submission.latest = 1 AND mdl_assign_submission.`status`
 = "submitted"
         AND mdl_assign.grade > 0 AND mdl_course.id = %d
         AND (ceiling((mdl_assign.duedate - mdl_course.startdate) / (60 * 60 * 24 * 7)) = 8)
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['assignment_wk8_submission_date'] = $inner_row['assignment_wk8_submission_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Mid-Term Submission Date:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid, 
         mdl_course.shortname AS course_shortname,
         max(mdl_quiz_attempts.timefinish) as last_midterm_completion_date
         FROM mdl_quiz_attempts
         JOIN mdl_user ON mdl_quiz_attempts.userid = mdl_user.id
         JOIN mdl_quiz ON mdl_quiz_attempts.quiz = mdl_quiz.id
         JOIN mdl_course ON mdl_quiz.course = mdl_course.id
         WHERE mdl_user.id = %d AND mdl_course.id = %d
         AND mdl_quiz.name like "%Mid%"
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_midterm_completion_date'] = $inner_row['last_midterm_completion_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Final Submission Date:
  $sql = 'SELECT mdl_user.username, mdl_course.id as courseid,
         mdl_course.shortname AS course_shortname,
         max(mdl_quiz_attempts.timefinish) as last_final_completion_date
         FROM mdl_quiz_attempts
         JOIN mdl_user ON mdl_quiz_attempts.userid = mdl_user.id
         JOIN mdl_quiz ON mdl_quiz_attempts.quiz = mdl_quiz.id
         JOIN mdl_course ON mdl_quiz.course = mdl_course.id
         WHERE mdl_user.id = %d AND mdl_course.id = %d
         AND mdl_quiz.name like "%Final%"
         ORDER BY mdl_course.id asc';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['last_final_completion_date'] = $inner_row['last_final_completion_date'];
  }
  unset($inner_results);
  unset($inner_row);

  
  // Iterate over course data and set empty values.
  foreach($course_data as $courseid => $data) {
    if(!isset($data['last_forum_submission_date'])) {
      $data['last_forum_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk1_submission_date'])) {
      $data['assignment_wk1_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk2_submission_date'])) {
      $data['assignment_wk2_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk3_submission_date'])) {
      $data['assignment_wk3_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk4_submission_date'])) {
      $data['assignment_wk4_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk5_submission_date'])) {
      $data['assignment_wk5_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk6_submission_date'])) {
      $data['assignment_wk6_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk7_submission_date'])) {
      $data['assignment_wk7_submission_date'] = 0;
    }
    if(!isset($data['assignment_wk8_submission_date'])) {
      $data['assignment_wk8_submission_date'] = 0;
    }
    if(!isset($data['last_midterm_completion_date'])) {
      $data['last_midterm_completion_date'] = 0;
    }
    if(!isset($data['last_final_completion_date'])) {
      $data['last_final_completion_date'] = 0;
    }
    $sql = 'insert into tbl_student_course_data_v2 (userid, courseid,
	       forum_submission_date,
	       assignment_wk1_submission_date, 
               assignment_wk2_submission_date,
               assignment_wk3_submission_date,
               assignment_wk4_submission_date,
               assignment_wk5_submission_date,
               assignment_wk6_submission_date,
               assignment_wk7_submission_date,
               assignment_wk8_submission_date,
               quiz_midterm_date, quiz_final_date) 
               values (%d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d)';
    $result = db_query($sql, $userid, $courseid, $data['last_forum_submission_date'], $data['assignment_wk1_submission_date'],  $data['assignment_wk2_submission_date'], $data['assignment_wk3_submission_date'], $data['assignment_wk4_submission_date'], $data['assignment_wk5_submission_date'], $data['assignment_wk6_submission_date'], $data['assignment_wk7_submission_date'], $data['assignment_wk8_submission_date'], $data['last_midterm_completion_date'], $data['last_final_completion_date']);
    if($result == TRUE) {
      $num_inserts++;
    }
  }
}
echo 'Inserts: ' . $num_inserts . ' ';
