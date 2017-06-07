<?php

// Maximum weeks in a term
define(MAX_WEEK, 8);

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Empty the table of last weeks active data.
$sql = 'delete from tbl_student_course_last_wk_active';
$results = db_query($sql);

// Query for last weeks active from aggregate table.
$sql = 'select * from tbl_student_course_wks_active';
$results = db_query($sql);

// Initialize variables.
$num_inserts = 0;
// Iterate over users.
while($row = db_fetch_array($results)) {
  /* Step 1: Determine the highest week in which there was participation. */
  $fieldname = '';
  $last_wk_num = 0;
  for($i = 1; $i <= MAX_WEEK; $i++) {
    $fieldname = 'wk' . $i . '_active';
    if($row[$fieldname] == 1) {
      $last_wk_num = $i;
    }
  } 

  /* Step 2: Determine if there are gaps in participation. */  
  $fieldname = '';
  $has_gaps = 0;
  for($i = 1; $i <= $last_wk_num ; $i++) {
    $fieldname = 'wk' . $i . '_active';
    if($row[$fieldname] == 0) {
      $has_gaps = 1;
    }
  }

  $sql = 'insert into tbl_student_course_last_wk_active (userid, courseid,
	       last_wk_num, 
               has_gaps)
               values (%d, %d, %d, %d)';
  $result = db_query($sql, $row['userid'], $row['courseid'], $last_wk_num, $has_gaps);
  if($result == TRUE) {
    $num_inserts++;
  }
}
echo 'Inserts: ' . $num_inserts . ' ';
