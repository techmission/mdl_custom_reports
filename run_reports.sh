#!/bin/bash
#
# Run all reports and sync to Salesforce.
#

/usr/bin/php mdl_course_terms.php
/usr/bin/php mdl_course_enrollments.php
/usr/bin/php mdl_course_enrollments_pell.php
/usr/bin/php mdl_grade_activity_data.php
/usr/bin/php mdl_professor_course_data.php
/usr/bin/php mdl_student_course_data.php
/usr/bin/php sf_professor_sync.php
/usr/bin/php sf_student_sync.php
/usr/bin/php sf_sync_student_grades.php
/usr/bin/php sf_sync_student_surveys.php