<?php
// $Id$

/** 
 * This file includes helper functions for webform.module
 *
 *
 * @author Pontus Ullgren <ullgren@user.sourceforge.net>
 * @package module_webform
 * @copyright Pontus Ullgren 2004
 **/

/* Creates a list of all webforms avaliable on this site.
 */
function _webform_page() {
  $header = array(
    t('Title'), 
    array('data' => t('View'), 'colspan' => '4'),
    array('data' => t('Operations'), 'colspan' => '2')
  );

  $result = db_query("SELECT nid, title FROM {node} WHERE type='webform'");
   
  while ($node = db_fetch_object($result)) {
    $rows[] = array(
      l($node->title, 'node/'. $node->nid),
      l(t('submissions'), 'node/'. $node->nid .'/results'),
      l(t('analysis'), 'node/'. $node->nid .'/results/analysis'),
      l(t('table'), 'node/'. $node->nid .'/results/table'),
      l(t('download'), 'node/'. $node->nid .'/results/download'),
      l(t('edit'), 'node/'. $node->nid .'/edit'),
      l(t('clear'), 'node/'. $node->nid .'/results/clear'),
    );
  }
  
  $content = theme('table', $header, $rows);
  drupal_set_title($node->title);
  return $content;
} // end function _webform_page

/**
 * Delete all submission for a form.
 * @param $nid
 *   ID  of node for which to clear submissions.
 */
function _webform_results_clear($nid) {
  drupal_set_title(t("Clear Form Submissions"));
  
  $form = array();
  $form['nid'] = array('#type' => 'value', '#value' => $nid);
  $question = t("Are you sure you want to delete all submissions for this form?");
  
  return confirm_form($form, $question, 'node/'. $nid .'/results', NULL, t('Clear'), t('Cancel'));
} // end function _webform_results_clear

function _webform_results_clear_submit($form_id, $form_values) {
  db_query('DELETE FROM {webform_submitted_data} WHERE nid = %d', $form_values['nid']);
  db_query('DELETE FROM {webform_submissions} WHERE nid = %d', $form_values['nid']);

  $node = node_load(array('nid' => $form_values['nid']));
  $title = $node->title;

  $message = t('Webform %title entries cleared.', array('%title' => $title));
  drupal_set_message($message);
  watchdog('webform', $message, WATCHDOG_NOTICE);
  drupal_goto('admin/content/webform'); 
}

/**
 * Delete one form submission
 * @param $nid
 *   ID of node for which this webform was submitted
 * @param $sid
 *   ID  of submission to be deleted (from webform_submitted_data)
 */
function _webform_submission_delete($nid, $sid) {
  drupal_set_title(t("Delete Form Submission"));

  $form = array();
  $form['nid'] = array('#type' => 'value', '#value' => $nid);
  $form['sid'] = array('#type' => 'value', '#value' => $sid);
  $question = t("Are you sure you want to delete this submission?");

  return confirm_form($form, $question, 'node/'. $nid .'/results', NULL, t('Delete'), t('Cancel'));
} // end function _webform_submission_delete

function _webform_submission_delete_submit($form_id, $form_values) {
  $nid = $form_values['nid'];
  $sid = $form_values['sid'];

  db_query('DELETE FROM {webform_submitted_data} WHERE nid = %d AND sid = %d', $nid, $sid);
  db_query('DELETE FROM {webform_submissions} WHERE nid = %d AND sid = %d', $nid, $sid);

  drupal_set_message(t("Submission deleted"));
  drupal_goto('node/'. $nid .'/results');
} // end function _webform_submission_delete_submit

/**
 * Return all the submissions for a particular node
 * @param $nid
 *   The node ID for which submissions are being fetched.
 * @param $header
 *   If the results of this fetch will be used in a sortable table, pass the
 *   array header of the table.
 */
function _webform_fetch_submissions($nid, $header = NULL) {
  
  $query = 'SELECT s.sid, s.uid, s.submitted, s.remote_addr, sd.cid, sd.no, sd.data, u.name, u.mail, u.status '. 
           'FROM {webform_submissions} s '. 
           'LEFT JOIN {webform_submitted_data} sd ON sd.sid = s.sid '.
           'LEFT JOIN {users} u ON u.uid = s.uid '.
           'WHERE sd.nid = %d';
  
  if (is_array($header)) {
    $query .= tablesort_sql($header);
  }

  $res = db_query($query, $nid);
  $submissions = array();
  $previous = array();
  
  // Outer loop: iterate for each submission
  while ($row = db_fetch_object($res)) {
    if ($row->sid != $previous) {
      $submissions[$row->sid]->sid = $row->sid;
      $submissions[$row->sid]->submitted = $row->submitted;
      $submissions[$row->sid]->remote_addr = $row->remote_addr;
      $submissions[$row->sid]->uid = $row->uid;
      $submissions[$row->sid]->name = $row->name;
      $submissions[$row->sid]->status = $row->status;
    }
    $submissions[$row->sid]->data[$row->cid]['value'][$row->no] = $row->data;
    $previous = $row->sid;
  }
 
  return $submissions;
} // end function _webform_fetch_submissions

/** 
 * This function is used to fetch a specified submission.
 */
function _webform_fetch_submission($sid, $nid) {
  
  $submission = array();

  $query = 'SELECT sd.nid, sd.sid, s.submitted, sd.cid, sd.no, sd.data '. 
           'FROM {webform_submitted_data} as sd '. 
           'LEFT JOIN {webform_submissions} as s on (sd.sid = s.sid) '. 
           'WHERE sd.sid = %d AND s.nid = %d';

  $res = db_query($query, $sid, $nid);
  $recs = db_num_rows($res);
  if ($recs >= 1) {
    $row = db_fetch_array($res);
    $submission['nid'] = $row['nid'];
    $submission['sid'] = $row['sid'];
    $submission['submitted'] = $row['submitted'];

    while ($row) {
      $submission['data'][$row['cid']]['value'][$row['no']] = $row['data'];
      $row = db_fetch_array($res);
    }
    
  }
  return $submission;
} // end function _webform_fetch_submission


function theme_webform_create_mailmessage($form_values, $node, $sid) {
  global $user, $baseurl;
  $message .=  t('Submitted on') .' '. format_date(time(), 'small') ."\n";
  $ip_address = $_SERVER['REMOTE_ADDR'];
  
  if ($user->uid) {
    $message .= t('Submitted by user') .": $user->name [$ip_address]\n";
  }
  else {
    $message .= t('Submitted by anonymous user') .": [$ip_address]\n";
  }

  $message .= "\n";
  $message .= t('Submitted values are');
  $message .= theme('webform_mail_fields', '', $form_values['submitted_tree'], $node);
  
  $message .= "\n\n";
  $message .= t("The results of this submission may be viewed at:\n");
  $message .= "http://". $_SERVER['HTTP_HOST'] . $baseurl . url('node/'.$node->nid, "sid=". $sid);
  
  
  if (variable_get('webform_debug', 0) == 2) {
    $message .= "\n";
    $message .= "DEBUG INFO\n";
    $message .= "==========\n";
    $message .= "\$form_values are\n";
    $message .= print_r($form_values, true);
    $message .= "\$node is\n";
    $message .= print_r($node, true);
    $message .= "\$_SERVER is\n";
    $message .= print_r($_SERVER, true);
    $message .= "\n";
    $message .= "\$_POST is\n";
    $message .= print_r($_POST, true);
  }

  return $message;
}

function theme_webform_mail_fields($key, $value, $node, $indent = "") {
  // First check for component-level themes.
  $themed_output = theme("webform_mail_". $node->webformcomponents[$key]['type'], $value, $node->webformcomponents[$key]);
  if ($themed_output) {
    // Indent the output and add to message.
    $message .= $indent;
    $themed_output = rtrim($themed_output, "\n");
    $message .= str_replace("\n", "\n". $indent, $themed_output);
    $message .= "\n";
  }
  // Generic output for single values.
  elseif (!is_array($value)) {
    // Note that newlines cannot be preceeded by spaces to display properly in some clients.
    if ($node->webformcomponents[$key]['name']) {
      // If text is more than 60 characters, put it on a new line with space after.
      $long = (strlen($indent . $node->webformcomponents[$key]['name'] . $value)) > 60;
      $message .= $indent . $node->webformcomponents[$key]['name'] .":". (empty($value) ? "\n" : ($long ? "\n$value\n\n" : " $value\n"));
    }
  }
  // Else use a generic output for arrays.
  else {
    $message .= $indent . $node->webformcomponents[$key]['name'] .":\n";
    foreach ($value as $k => $v) {
      $message .= theme('webform_mail_fields', $k, $v, $node, $indent ."  ");
    }
  }
  return ($message);
}


function _webform_submission_spam_check($to, $subject, $message, $from, $headers = array()) {
  $headers = implode('\n', $headers);
  // Check if they are attempting to spam using a bcc or content type hack
  if (preg_match('/(b?cc\s?:)|(content\-type:)/i', $to ."\n". $subject ."\n". $from ."\n". $headers)) {
    return true; // Possible spam attempt
  }  
  return false; // Not spam
}

function _webform_submission_limit_check($node, $form_values) {
  global $user, $db_type;

  // check if submission limiting is enabled
  if ($node->submit_limit == '-1') {
    return false; // No check enabled
  }
  
  // Retrieve submission data for this IP address or username from the database
  $query = "SELECT submitted, uid, remote_addr ". 
           "FROM {webform_submissions} ". 
           "WHERE (( 0 = %d AND remote_addr = '%s') OR uid = %d )". 
           "AND submitted > %d AND nid = %d";
  
  // Fetch all the entries from the database within the submit interval with this username and IP
  $result = db_query($query, $user->uid, $_SERVER['REMOTE_ADDR'], $user->uid, time() - $node->submit_interval, $node->nid);
  $num_submissions_database = db_num_rows($result);
  
  // Double check the submission history from the users machine using cookies
  if (variable_get("webform_use_cookies", 0)) {
    $cookie_name = 'webform-'. $node->nid;
    
    if (isset($_COOKIE[$cookie_name]) && is_array($_COOKIE[$cookie_name])) {
      foreach ($_COOKIE[$cookie_name] as $key => $timestamp) {
        if ($timestamp <= time() - $node->submit_interval) {
          // Remove the cookie if past the required time interval
          setcookie($cookie_name ."[". $key ."]", "", 0);
        }
      }
      // Count the number of submissions recorded in cookies
      $num_submissions_cookie = count($_COOKIE[$cookie_name]);
    }
    else {
      $num_submissions_cookie = 0;
    }
  }
  
  if ($num_submissions_database >= $node->submit_limit || $num_submissions_cookie >= $node->submit_limit) {
    // Limit exceeded
    return $num_submissions_database; 
  }
  else {
    // Increment a cookie for triple recording of the submission
    if (variable_get("webform_use_cookies", 0)) {
      $attempted_key = 0;
      if ($num_submissions_cookie > 0) {
        while (array_key_exists($attempted_key, $_COOKIE[$cookie_name])) {
          $attempted_key++;
        }
      }
      // Set a cookie including the server's submission time
      // The cookie expires in the length of the interval plus a day to compensate for different timezones
      setcookie($cookie_name ."[". $attempted_key ."]", time(), time() + $node->submit_interval + 86400);
    }
    
    // Limit not exceeded
    return false;
  }
}
