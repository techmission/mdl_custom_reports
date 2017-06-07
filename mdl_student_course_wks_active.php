<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Empty the table of last weeks active data.
$sql = 'DELETE FROM tbl_student_course_wks_active';
$results = db_query($sql);

// Query for last weeks active.
$sql = 'select u.id as userid, c.id as courseid,
          if(t.assignment_wk1_submission_date > 0 OR t2.wk1_date > 0 OR t3.wk1_date > 0, 1, 0) as wk1_active,
	  if(t.assignment_wk2_submission_date > 0 OR t2.wk2_date > 0 OR t3.wk2_date > 0, 1, 0) as wk2_active,
	  if(t.assignment_wk3_submission_date > 0 OR t2.wk3_date > 0 OR t3.wk3_date > 0, 1, 0) as wk3_active,
	  if(t.assignment_wk4_submission_date > 0 OR t2.wk4_date > 0 OR t3.wk4_date > 0, 1, 0) as wk4_active,
	  if(t.assignment_wk5_submission_date > 0 OR t2.wk5_date > 0 OR t3.wk5_date > 0, 1, 0) as wk5_active,
	  if(t.assignment_wk6_submission_date > 0 OR t2.wk6_date > 0 OR t3.wk6_date > 0, 1, 0) as wk6_active,
	  if(t.assignment_wk7_submission_date > 0 OR t2.wk7_date > 0 OR t3.wk7_date > 0, 1, 0) as wk7_active,
	  if(t.assignment_wk8_submission_date > 0 OR t2.wk8_date > 0 OR t3.wk8_date > 0, 1, 0) as wk8_active
          from tbl_student_course_data_v2 t
	  left join tbl_student_course_data_forums t2 on t.userid = t2.userid and t.courseid = t2.courseid
	  left join tbl_student_course_data_quiz t3 on t.userid = t3.userid and t.courseid = t3.courseid
          join mdl_user u on t.userid = u.id
          join mdl_course c on t.courseid = c.id
          where c.fullname not like "New Student Orientation%"';
$results = db_query($sql);

// Initialize variables.
$num_inserts = 0;
// Iterate over users.
while($row = db_fetch_array($results)) {
  $sql = 'insert into tbl_student_course_wks_active (userid, courseid,
	       wk1_active, 
               wk2_active,
               wk3_active,
               wk4_active,
               wk5_active,
               wk6_active,
               wk7_active,
               wk8_active) 
               values (%d, %d, %d, %d, %d, %d, %d, %d, %d, %d)';
  $result = db_query($sql, $row['userid'], $row['courseid'], $row['wk1_active'], $row['wk2_active'], $row['wk3_active'], $row['wk4_active'], $row['wk5_active'], $row['wk6_active'], $row['wk7_active'], $row['wk8_active']);
  if($result == TRUE) {
    $num_inserts++;
  }
}
echo 'Inserts: ' . $num_inserts . ' ';
