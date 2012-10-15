<?php

/* SF API specialty functions. */

/**
 * Wraps SforceBaseClient::upsert. Upserts a record in Salesforce.
 * If there is an existing record in Salesforce with the same ID, that record is
 * updated. Otherwise, a new record is created.
 * @param array $records
 *  Either an array of arrays of Salesforce fields, to be converted to sObjects, of the specified
 *  type, or else an array of sObjects.
 * @param string $type
 *  The type of sObject to update. Must either be a core sObject, or a custom type defined in your
 *  WSDL. Custom types all end in "__c". Default type is "Contact".
 * @param string $key
 *  The Salesforce field, or external ID, on which to upsert. Default is "Id".
 * @param object $sf
 *  A currently-active Salesforce connection. If none is passed in, one will be created.
 * @return array
 *  An array containing an array of Salesforce IDs successfully upserted, and the number of failures,
 *  or FALSE if no Salesforce connection could be made or an exception was thrown.
*/
function salesforce_api_upsert(array $records, $type = 'Contact', $key = 'Id', $sf = NULL) {
  // Connects to Salesforce if no existing connection supplied.
  if(!is_object($sf)) {
    $sf = salesforce_api_connect();
    // Return FALSE if could not connect to Salesforce.
    if($sf == FALSE) {
      return FALSE;
    }
  }

  try {
    $results = $sf->client->upsert($key, $records, $type);
  }
  // Handle any exceptions.
  catch (Exception $e) {
    salesforce_api_log(SALESFORCE_LOG_SOME, 'The following exception occurred while attempting to upsert records: <pre>%e</pre>',
      array('%msg' => $e->getMessage(), '%e' => print_r($e, TRUE)), WATCHDOG_ERROR);
    return FALSE;
  }

  // Sets up the variables for the array of information about results of upsert operation.
  $success_ids = array();
  $failures = 0;
  $created_ids = array();
  $updated_ids = array();
  // $is_deleted_ids = array();

  // Iterate over the resultset.
  foreach ($results as $result) {
    // Handle any errors.
    // @todo: Log is_deleted errors separately, so they can be handled by unlink & upsert on
    // a successive call to this function.
    if (isset($result->errors) && is_array($result->errors)) {
      $err_msgs = array();
      $status_codes = array();
      // Log all errors to watchdog.
      // @todo: Present them more nicely than an array with print_r().
      foreach($result->errors as $error) {
        $err_msgs[] = $error->message;
        $status_codes[] = $error->statusCode;
        if($error->statusCode == 'ENTITY_IS_DELETED') {
          // @todo: Figure out a way to determine which one was deleted.
        }
      }
      salesforce_api_log(SALESFORCE_LOG_SOME, 'Errors occurred while attempting to upsert record: <pre>%msgs</pre>',
          array('%msgs' => print_r($err_msgs, TRUE), '%codes' => print_r($status_codes, TRUE)), WATCHDOG_ERROR);
      // Increment the number of failures.
      $failures++;
    }
    // Records ids of successes.
    elseif (isset($result->success) && $result->success == TRUE) {
      $success_ids[] = $result->id;
      // Separates successes into creates and updates.
      if (isset($result->created) && $result->created == TRUE) {
        $created_ids[] = $result->id;
      }
      else {
        $updated_ids[] = $result->id;
      }
    }
  }

  // Return the ids of results, grouped appropriately.
  $result_info = array(
    'successes' => $success_ids,
    'failures' => $failures,
    'created' => $created_ids,
    'updated' => $updated_ids,
    //'is_deleted' => $is_deleted_ids,
  );
  return $result_info;
}


function flatten_sf_object($sf_object) {
  // Start with an empty object.
  $flat_obj = new stdClass;
  if(is_object($sf_object)) {
    // Cast to array for ease of iteration.
    $sf_array = (array) $sf_object;
	foreach($sf_array as $idx => $sub_array) {
	  if(!is_array($sub_array) && !is_object($sub_array)) {
	    continue;
	  }
	  else {
	    if(is_object($sub_array)) {
	      $sub_array = (array) $sub_array;
		}
	    foreach($sub_array as $fieldname => $field) {
		  // If a regular field, flatten it up a level.
		  if(!is_array($field)) {
		    $sf_array[$idx . '__' . $fieldname] = $field;
		  }
		  // Don't operate down to the next level of depth.
		  else {
		    continue;
		  }
		}
	  }
	}
	// Cast back to object at end.
	$flat_obj = (object) $sf_array;
  }
  return $flat_obj;
}

function sf_date_convert($unix_ts) {
  $date = date_format(date_create_from_format('Y-m-d H:i:s', $unix_ts), 'c');
  return $date;
}

/* Utility functions. */
function get_course_code($course_name) {
  $code = '';
  $course_pos = strpos($course_name, 'Course');
  $code_str = substr($course_name, $course_pos + 7, 3);
  if(is_numeric($code_str)) {
    $code = $code_str;
  }
  return $code;
}
  
function parse_course_name($course_name, $sf_format = TRUE) {
  $course_name_parsed = array();
  // Parse the term and year out.
  $course_parts = explode('-', $course_name);
  $course_parts = explode('_', $course_parts[0]);
  $course_code = substr($course_parts[0], 3, 3);
  $term = strtolower($course_parts[1]);
  $year = $course_parts[2];
  if(!empty($course_code) && !empty($term) && !empty($year)) {
    $course_name_parsed = array('code' => $course_code, 'term' => $term, 'year' => $year);
	if($sf_format == TRUE) {
      $course_name_parsed = format_course_name($course_name_parsed);
    }
  }
  return $course_name_parsed;
}

function format_course_name($course_name_parsed) {
 $course_name_fmt = array();
 if(is_array($course_name_parsed)) {
   $course_name_fmt = $course_name_parsed;
   // Turn the code into the initial part of the name.
   if(!empty($course_name_parsed['code'])) {
     $course_names = get_course_names();
	 if(array_key_exists($course_name_parsed['code'], $course_names)) {
       $course_name_fmt['name'] = $course_names[$course_name_parsed['code']];
	 }
   }
   // Turn the short term name into the full term name.
   if(!empty($course_name_parsed['term'])) {
     $course_terms = get_course_terms();
	 if(array_key_exists($course_name_parsed['term'], $course_terms)) {
       $course_name_fmt['term'] = $course_terms[$course_name_parsed['term']];
	 }
   }
 }
 return $course_name_fmt;
}

function get_course_names() {
  // Don't get the full course names, only do a substring match.
  // Later, maybe just parse out the number and automatically prepend "Course".
  // The only advantage of this is it's a form of validation, but would require
  // list to be kept up to date.
  $course_names = array('301' => 'Course 301',
                        '302' => 'Course 302',
						'303' => 'Course 303',
						'304' => 'Course 304',
						'305' => 'Course 305',
						'306' => 'Course 306', // some just Course 306 - Residential Recovery Programs
						'307' => 'Course 307',
						'308' => 'Course 308',
						'309' => 'Course 309',
						'310' => 'Course 310',
						'311' => 'Course 311', // some just Course 311 - Theology & Strategies of Urban Missions
						'330' => 'Course 330',
						'331' => 'Course 331', 
						'401' => 'Course 401',
						'402' => 'Course 402',
						'403' => 'Course 403',
						'404' => 'Course 404',
						'405' => 'Course 405',
						'406' => 'Course 406',
						'407' => 'Course 407',
						'411' => 'Course 411',
						'412' => 'Course 413',
						'414' => 'Course 414',
						'415' => 'Course 415',
						'416' => 'Course 416',
						'417' => 'Course 417',
						'418' => 'Course 418',
						'419' => 'Course 419');
  return $course_names;
}

function get_course_terms() {
  $course_terms = array('sp1' => 'Spring 1 Term',
                        'sp2' => 'Spring 2 Term',
						'sum' => 'Summer Term',
						'fall1' => 'Fall 1 Term',
						'fall2' => 'Fall 2 Term');
  return $course_terms;
}