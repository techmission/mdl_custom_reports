<?php

/* Drupal bootstrap - full for use of theme_table. */
chdir('..');
require_once('./includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Run the script to update the Moodle activity table.
// Backticks are execution operation in PHP.
//$execute = `/usr/bin/php /home/cvedu/public_html/drupal/courseinfo_ac3s/mdl_activity_data.php`;

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

$sql = 'select a.username, c.shortname, ' .
  'a.oldest_grade, a.newest_grade ' .
  'from tbl_grade_activity_denormalized a ' .
  'join mdl_course c on a.course_shortname = c.shortname where ' .
  'a.course_shortname != ""';
$result = db_query($sql);

$output = '<h1>' . t('Moodle activities') . '</h1>';

$header = array(t('Student name'), t('Course name'), t('Oldest grade'), t('Newest grade'));
$rows = array();
while($course_info = db_fetch_object($result)) {
  $rows[] = array(
    $course_info->username,
    $course_info->shortname,
    $course_info->oldest_grade . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp',
    $course_info->newest_grade
  );
}
$output .= theme('table', $header, $rows);

echo $output;
