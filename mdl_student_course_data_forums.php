<?php

/* Drupal bootstrap - full so use of watchdog. */
chdir(dirname(__FILE__));

chdir('..');
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Switch to the Moodle database.
db_set_active('cvedu_moodle');

// Empty the table of student course data.
$sql = 'DELETE FROM tbl_student_course_data_forums';
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

  // Get Last Forum Submission Date for Week 1
  $sql = 'select mdl_course.id as courseid, 
          max(mdl_forum_posts.created) as wk1_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 1%" or mdl_forum.name like "Forum 1%" or mdl_forum.name like "1.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk1_date'] = $inner_row['wk1_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date for Week 2
  $sql = 'select mdl_course.id as courseid,
          max(mdl_forum_posts.created) as wk2_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 2%"
         or mdl_forum.name like "Forum #2%"
         or mdl_forum.name like "Forum 2%"
         or mdl_forum.name like "Period 1%" or mdl_forum.name like "2.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk2_date'] = $inner_row['wk2_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date for Week 3
  $sql = 'select mdl_course.id as courseid,
          max(mdl_forum_posts.created) as wk3_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 3%" or mdl_forum.name like "Forum 3%" or mdl_forum.name like "3.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk3_date'] = $inner_row['wk3_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date for Week 4
  $sql = 'select mdl_course.id as courseid,
          max(mdl_forum_posts.created) as wk4_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 4%"
         or mdl_forum.name like "Forum #4%"
         or mdl_forum.name like "Forum 4%"
         or mdl_forum.name like "Period 2%" or mdl_forum.name like "4.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk4_date'] = $inner_row['wk4_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date for Week 5
  $sql = 'select mdl_course.id as courseid,
          max(mdl_forum_posts.created) as wk5_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 5%" or mdl_forum.name like "Forum 5%" or mdl_forum.name like "5.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk5_date'] = $inner_row['wk5_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date for Week 6
  $sql = 'select mdl_course.id as courseid,
          max(mdl_forum_posts.created) as wk6_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 6%"
         or mdl_forum.name like "Forum #6%"
         or mdl_forum.name like "Forum 6%"
         or mdl_forum.name like "Period 3%" or mdl_forum.name like "6.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk6_date'] = $inner_row['wk6_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date for Week 7
  $sql = 'select mdl_course.id as courseid,
          max(mdl_forum_posts.created) as wk7_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 7%" or mdl_forum.name like "Forum 7%" or mdl_forum.name like "7.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk7_date'] = $inner_row['wk7_date'];
  }
  unset($inner_results);
  unset($inner_row);

  // Get Last Forum Submission Date for Week 8
  $sql = 'select mdl_course.id as courseid,
          max(mdl_forum_posts.created) as wk8_date
         from mdl_forum_posts
         join mdl_forum_discussions on mdl_forum_posts.discussion = mdl_forum_discussions.id
         join mdl_forum on mdl_forum_discussions.forum = mdl_forum.id
         join mdl_course on mdl_forum_discussions.course = mdl_course.id
         where mdl_forum_posts.userid = %d and mdl_course.id = %d
         and (mdl_forum.name like "%Week 8%"
         or mdl_forum.name like "Forum #8%"
         or mdl_forum.name like "Forum 8%"
         or mdl_forum.name like "Period 4%" or mdl_forum.name like "8.%")
         order by mdl_course.id';
  $inner_results = db_query($sql, $userid, $courseid);
  while($inner_row = db_fetch_array($inner_results)) {
    $course_data[$inner_row['courseid']]['wk8_date'] = $inner_row['wk8_date'];
  }
  unset($inner_results);
  unset($inner_row);


  // Iterate over course data and set empty values.
  foreach($course_data as $courseid => $data) {
    if(!isset($data['wk1_date'])) {
      $data['wk1_date'] = 0;
    }
    if(!isset($data['wk2_date'])) {
      $data['wk2_date'] = 0;
    }
    if(!isset($data['wk3_date'])) {
      $data['wk3_date'] = 0;
    }
    if(!isset($data['wk4_date'])) {
      $data['wk4_date'] = 0;
    }
    if(!isset($data['wk5_date'])) {
      $data['wk5_date'] = 0;
    }
    if(!isset($data['wk6_date'])) {
      $data['wk6_date'] = 0;
    }
    if(!isset($data['wk7_date'])) {
      $data['wk7_date'] = 0;
    }
    if(!isset($data['wk8_date'])) {
      $data['wk8_date'] = 0;
    }
    $sql = 'insert into tbl_student_course_data_forums (userid, courseid, wk1_date, wk2_date, wk3_date, wk4_date, wk5_date, wk6_date, wk7_date, wk8_date) values (%d, %d, %d, %d, %d, %d, %d, %d, %d, %d)';
    /* echo printf($sql, $userid, $courseid, $data['wk1_date'],
      $data['wk2_date'], $data['wk3_date'], $data['wk4_date'],
      $data['wk5_date'], $data['wk6_date'], $data['wk7_date'],
      $data['wk8_date']); */
    $result = db_query($sql, $userid, $courseid, $data['wk1_date'],
      $data['wk2_date'], $data['wk3_date'], $data['wk4_date'],
      $data['wk5_date'], $data['wk6_date'], $data['wk7_date'],
      $data['wk8_date']);
    if($result == TRUE) {
      $num_inserts++;
    }
  }
}

// print_r($course_data);

echo 'Inserts: ' . $num_inserts . ' ';
