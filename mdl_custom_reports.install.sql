CREATE TABLE `tbl_course_enrollments` (
	`ceid` INT(11) NOT NULL AUTO_INCREMENT,
	`userid` INT(11) NOT NULL,
	`sp1_2009` INT(11) NOT NULL DEFAULT '0',
	`sp2_2009` INT(11) NOT NULL DEFAULT '0',
	`sum_2009` INT(11) NOT NULL DEFAULT '0',
	`fall1_2009` INT(11) NOT NULL DEFAULT '0',
	`fall2_2009` INT(11) NOT NULL DEFAULT '0',
	`sp1_2010` INT(11) NOT NULL DEFAULT '0',
	`sp2_2010` INT(11) NOT NULL DEFAULT '0',
	`sum_2010` INT(11) NOT NULL DEFAULT '0',
	`fall1_2010` INT(11) NOT NULL DEFAULT '0',
	`fall2_2010` INT(11) NOT NULL DEFAULT '0',
	`sp1_2011` INT(11) NOT NULL DEFAULT '0',
	`sp2_2011` INT(11) NOT NULL DEFAULT '0',
	`sum_2011` INT(11) NOT NULL DEFAULT '0',
	`fall1_2011` INT(11) NOT NULL DEFAULT '0',
	`fall2_2011` INT(11) NOT NULL DEFAULT '0',
	`sp1_2012` INT(11) NOT NULL DEFAULT '0',
	`sp2_2012` INT(11) NOT NULL DEFAULT '0',
	`sum_2012` INT(11) NOT NULL DEFAULT '0',
	`fall1_2012` INT(11) NOT NULL DEFAULT '0',
	`fall2_2012` INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`ceid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0;

CREATE TABLE `tbl_course_enrollments_pell` (
	`ceid` INT(11) NOT NULL AUTO_INCREMENT,
	`userid` INT(11) NOT NULL,
	`sp1_2009` INT(11) NOT NULL DEFAULT '0',
	`sp2_2009` INT(11) NOT NULL DEFAULT '0',
	`sum_2009` INT(11) NOT NULL DEFAULT '0',
	`fall1_2009` INT(11) NOT NULL DEFAULT '0',
	`fall2_2009` INT(11) NOT NULL DEFAULT '0',
	`sp1_2010` INT(11) NOT NULL DEFAULT '0',
	`sp2_2010` INT(11) NOT NULL DEFAULT '0',
	`sum_2010` INT(11) NOT NULL DEFAULT '0',
	`fall1_2010` INT(11) NOT NULL DEFAULT '0',
	`fall2_2010` INT(11) NOT NULL DEFAULT '0',
	`sp1_2011` INT(11) NOT NULL DEFAULT '0',
	`sp2_2011` INT(11) NOT NULL DEFAULT '0',
	`sum_2011` INT(11) NOT NULL DEFAULT '0',
	`fall1_2011` INT(11) NOT NULL DEFAULT '0',
	`fall2_2011` INT(11) NOT NULL DEFAULT '0',
	`sp1_2012` INT(11) NOT NULL DEFAULT '0',
	`sp2_2012` INT(11) NOT NULL DEFAULT '0',
	`sum_2012` INT(11) NOT NULL DEFAULT '0',
	`fall1_2012` INT(11) NOT NULL DEFAULT '0',
	`fall2_2012` INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`ceid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0;

CREATE TABLE `tbl_course_terms` (
	`ctid` INT(11) NOT NULL AUTO_INCREMENT,
	`courseid` INT(11) NOT NULL,
	`term` VARCHAR(4) NULL DEFAULT NULL,
	`year` VARCHAR(4) NULL DEFAULT NULL,
	PRIMARY KEY (`ctid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
AUTO_INCREMENT=0;

CREATE TABLE `tbl_grade_activity_denormalized` (
	`username` VARCHAR(25) NOT NULL,
	`course_shortname` VARCHAR(25) NOT NULL,
	`oldest_grade` DATETIME NOT NULL,
	`newest_grade` DATETIME NOT NULL,
	PRIMARY KEY (`username`, `course_shortname`),
	INDEX `username` (`username`),
	INDEX `course_shortname` (`course_shortname`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

CREATE TABLE `tbl_pell_students` (
	`userid` INT(11) NOT NULL,
	`year` VARCHAR(20) NULL DEFAULT NULL,
	PRIMARY KEY (`userid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

CREATE TABLE `tbl_professor_course_data` (
	`pcdid` INT(11) NOT NULL AUTO_INCREMENT,
	`userid` INT(11) NOT NULL DEFAULT '0',
	`courseid` INT(11) NOT NULL DEFAULT '0',
	`last_login_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_assignment_graded_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_quiz_graded_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`pcdid`),
	INDEX `userid` (`userid`),
	INDEX `courseid` (`courseid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

CREATE TABLE `tbl_student_course_data` (
	`scdid` INT(11) NOT NULL AUTO_INCREMENT,
	`userid` INT(11) NOT NULL DEFAULT '0',
	`courseid` INT(11) NOT NULL DEFAULT '0',
	`last_login_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_forum_submission_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_assignment_submission_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_assignment_graded_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_quiz_completion_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_quiz_graded_date` BIGINT(10) UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (`scdid`),
	INDEX `userid` (`userid`),
	INDEX `courseid` (`courseid`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

CREATE TABLE `tbl_submission_activity_denormalized` (
	`username` VARCHAR(25) NOT NULL,
	`course_shortname` VARCHAR(25) NOT NULL,
	`oldest_submission` DATETIME NOT NULL,
	`newest_submission` DATETIME NOT NULL,
	PRIMARY KEY (`username`, `course_shortname`),
	INDEX `username` (`username`),
	INDEX `course_shortname` (`course_shortname`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

CREATE TABLE `tbl_submission_file_denormalized` (
	`username` VARCHAR(25) NOT NULL,
	`course_shortname` VARCHAR(25) NOT NULL,
	`oldest_submission` DATETIME NOT NULL,
	`newest_submission` DATETIME NOT NULL,
	PRIMARY KEY (`username`, `course_shortname`),
	INDEX `username` (`username`),
	INDEX `course_shortname` (`course_shortname`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM;

create view view_due_date_table as
select `mdl_course`.`id` AS `courseid`,`mdl_assignment`.`id` AS `activity_id`,'assignment' AS `activity_type`,`mdl_assignment`.`timedue` AS `moodle_deadline`,(`mdl_assignment`.`timedue` + (((60 * 60) * 24) * 3)) AS `soft_deadline`,(`mdl_assignment`.`timedue` + (((60 * 60) * 24) * 7)) AS `soft_deadline_end`,(`mdl_assignment`.`timedue` + (((60 * 60) * 24) * 14)) AS `hard_deadline` from (`mdl_assignment` join `mdl_course` on((`mdl_assignment`.`course` = `mdl_course`.`id`))) union select `mdl_course`.`id` AS `courseid`,`mdl_quiz`.`id` AS `activity_id`,'quiz' AS `activity_type`,`mdl_quiz`.`timeclose` AS `moodle_deadline`,(`mdl_quiz`.`timeclose` + (((60 * 60) * 24) * 3)) AS `soft_deadline`,(`mdl_quiz`.`timeclose` + (((60 * 60) * 24) * 7)) AS `soft_deadline_end`,(`mdl_quiz`.`timeclose` + (((60 * 60) * 24) * 14)) AS `hard_deadline` from (`mdl_quiz` join `mdl_course` on((`mdl_quiz`.`course` = `mdl_course`.`id`)))