#! /bin/bash

#
# Run all reports and sync to Salesforce.
#

echo "Script running"

/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/mdl_course_terms.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/mdl_course_enrollments.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/mdl_course_enrollments_pell.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/mdl_grade_activity_data.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/mdl_professor_course_data.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/mdl_student_course_data.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/sf_professor_sync.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/sf_student_sync.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/sf_sync_student_grades.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/sf_sync_student_project_grades.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/sf_sync_student_surveys.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/mdl_student_course_data_v2.php
/usr/bin/php /home/cvedu/public_html/drupal/mdl_custom_reports/sf_student_sync_v2.php
