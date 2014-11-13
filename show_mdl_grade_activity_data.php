<?php

/* Drupal bootstrap - full for use of theme_table. */
chdir('..');
require_once('./includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

$sql = 'select firstname, lastname, course_shortname,' .
  'course_fullname, oldest_grade, newest_grade ' .
  'from tbl_grade_activity_denormalized';
$result = db_query($sql);

$output = '<h1>' . t('Moodle activities') . '</h1>';

$header = array(t('Student name'), t('Course shortname'), t('Course full name'), t('Oldest grade'), t('Newest grade'));
$rows = array();
while($course_info = db_fetch_object($result)) {
  $rows[] = array(
    $course_info->firstname . ' ' . $course_info->lastname,
    $course_info->course_shortname,
    $course_info->course_fullname,
    $course_info->oldest_grade . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp',
    $course_info->newest_grade
  );
}
$output .= theme('table', $header, $rows);

echo $output;
