<?php
// $Id$

/**
 * This file includes helper functions for creating reports for webform.module
 *
 * @author Nathan Haug <nate@lullabot.com>
 * @author Pontus Ullgren <ullgren@user.sourceforge.net>
 * @copyright Pontus Ullgren 2004
 */

/**
 * Retrieve lists of submissions for a given webform.
 */
function webform_results_submissions($node, $user_filter = FALSE) {
  global $user;

  $header = theme('webform_results_submissions_header', $node);
  if ($user_filter) {
    drupal_set_title(t('Submissions for %user', array('%user' => $user->name)));
    $submissions = webform_get_submissions($node->nid, $header, $user->uid);
  }
  else {
    $submissions = webform_get_submissions($node->nid, $header);
  }
  return theme('webform_results_submissions', $node, $submissions);
}

/**
 * Theme the header of the submissions table.
 *
 * This is done in it's own function so that webform can retrieve the header and
 * use it for sorting the results.
 */
function theme_webform_results_submissions_header($node) {
  $columns = array(
    array('data' => t('#'), 'field' => 'sid', 'sort' => 'asc'),
    array('data' => t('Submitted'), 'field' => 'submitted'),
  );
  if (user_access("access webform results")) {
    $columns[] = array('data' => t('User'), 'field' => 'name');
    $columns[] = array('data' => t('IP Address'), 'field' => 'remote_addr');
  }
  $columns[] = array('data' => t('Operations'), 'colspan' => 3);

  return $columns;
}

/**
 * Theme the submissions tab of the webform results page.
 *
 * @param $node
 *   The node whose results are being displayed.
 * @param $submissions
 *   An array of all submissions for this webform.
 */
function theme_webform_results_submissions($node, $submissions) {
  global $user;
  // This header has to be generated seperately so we can add the SQL necessary
  // to sort the results.
  $header = theme('webform_results_submissions_header', $node);

  $rows = array();
  foreach ($submissions as $sid => $submission) {
    $row = array(
      $sid,
      format_date($submission->submitted, 'small'),
    );
    if (user_access('access webform results')) {
      $row[] = theme('username', $submission);
      $row[] = $submission->remote_addr;
    }
    $row[] = l(t('View'), "node/$node->nid/submission/$sid");
    if ((user_access("edit own webform submissions") && ($user->uid == $submission->uid)) || user_access("edit webform submissions")) {
      $row[] = l(t('Edit'), "node/$node->nid/submission/$sid/edit");
      $row[] = l(t('Delete'), "node/$node->nid/submission/$sid/delete", NULL, drupal_get_destination());
    }
    else {
      $row[count($row) - 1] = array('data' => $row[count($row) - 1], 'colspan' => 3);
    }
    $rows[] = $row;
  }

  if (count($rows) == 0) {
    $rows[] = array(array('data' => t('There are no submissions for this form. <a href="!url">View this form</a>.', array('!url' => url('node/'. $node->nid))), 'colspan' => 7));
  }

  $output = theme('table', $header, $rows);
  if (arg(2) == 'submissions') {
    $output .= theme('links', array('webform' => array('title' => t('Go back to the form'), 'href' => 'node/'. $node->nid)));
  }
  return $output;
}

/**
 * Create a table containing all submitted values for a webform node.
 */
function webform_results_table($node) {
  // Load Components.
  webform_load_components();

  // Get all the submissions for the node.
  $header = theme('webform_results_table_header', $node);
  $submissions = webform_get_submissions($node->nid, $header);

  return theme('webform_results_table', $node, $node->webform['components'], $submissions);
}

function theme_webform_results_table_header($node) {
  return array(
    array('data' => t('#'), 'field' => 'sid', 'sort' => 'asc'),
    array('data' => t('Submitted'), 'field' => 'submitted'),
    array('data' => t('User'), 'field' => 'name'),
    array('data' => t('IP Address'), 'field' => 'remote_addr'),
  );
}

/**
 * Theme the results table displaying all the submissions for a particular node.
 *
 * @param $node
 *   The node whose results are being displayed.
 * @param $components
 *   An associative array of the components for this webform.
 * @param $submissions
 *   An array of all submissions for this webform.
 */
function theme_webform_results_table($node, $components, $submissions) {
  $header = array();
  $rows = array();
  $cell = array();

  // This header has to be generated seperately so we can add the SQL necessary.
  // to sort the results.
  $header = theme('webform_results_table_header', $node);

  // Generate a row for each submission.
  foreach ($submissions as $sid => $submission) {
    $cell[] = l($sid, 'node/'. $node->nid .'/submission/'. $sid);
    $cell[] = format_date($submission->submitted, "small");
    $cell[] = theme('username', $submission);
    $cell[] = $submission->remote_addr;
    $component_headers = array();

    // Generate a cell for each component.
    foreach ($node->webform['components'] as $component) {
      $table_function = "_webform_table_data_". $component['type'];
      if (function_exists($table_function)) {
        $submission_output = $table_function($submission->data[$component['cid']], $component);
        if ($submission_output !== NULL) {
          $component_headers[] = $component['name'];
          $cell[] = $submission_output;
        }
      }
    }

    $rows[] = $cell;
    unset($cell);
  }
  if (!empty($component_headers)) {
    $header = array_merge($header, $component_headers);
  }

  if (count($rows) == 0) {
    $rows[] = array(array('data' => t('There are no submissions for this form. <a href="!url">View this form</a>.', array('!url' => url('node/'. $node->nid))), 'colspan' => 4));
  }

  return theme('table', $header, $rows);
}

/**
 * Delete all submissions for a node.
 *
 * @param $nid
 *   The node id whose submissions will be deleted.
 */
function webform_results_clear($nid) {
  $node = node_load($nid);
  $submissions = webform_get_submissions($nid);
  foreach ($submissions as $submission) {
    webform_submission_delete($node, $submission);
  }
}

/**
 * Confirmation form to delete all submissions for a node.
 *
 * @param $nid
 *   ID of node for which to clear submissions.
 */
function webform_results_clear_form($node) {
  drupal_set_title(t("Clear Form Submissions"));

  $form = array();
  $form['nid'] = array('#type' => 'value', '#value' => $node->nid);
  $question = t("Are you sure you want to delete all submissions for this form?");

  return confirm_form($form, $question, 'node/'. $node->nid .'/results', NULL, t('Clear'), t('Cancel'));
}

function webform_results_clear_form_submit($form_id, $form_values) {
  webform_results_clear($form_values['nid']);
  $node = node_load(array('nid' => $form_values['nid']));
  $title = $node->title;

  $message = t('Webform %title entries cleared.', array('%title' => $title));
  drupal_set_message($message);
  watchdog('webform', $message, WATCHDOG_NOTICE);
  return 'node/'. $form_values['nid'] .'/results';
}

/**
 * Form to configure the download of CSV files.
 */
function webform_results_download_form($node) {
  include_once(drupal_get_path('module', 'webform') .'/webform_export.inc');

  $form = array();

  $form['node'] = array(
    '#type' => 'value',
    '#value' => $node,
  );

  $form['format'] = array(
    '#type' => 'radios',
    '#title' => t('Export format'),
    '#options' => webform_export_list(),
    '#default_value' => variable_get('webform_export_format', 'delimited'),
  );

  $form['delimiter'] = array(
    '#type' => 'select',
    '#title' => t('Delimited text format'),
    '#description' => t('This is the delimiter used in the CSV/TSV file when downloading Webform results. Using tabs in the export is the most reliable method for preserving non-latin characters. You may want to change this to another character depending on the program with which you anticipate importing results.'),
    '#default_value' => variable_get('webform_csv_delimiter', '\t'),
    '#options' => array(
      ','  => t('Comma (,)'),
      '\t' => t('Tab (\t)'),
      ';'  => t('Semicolon (;)'),
      ':'  => t('Colon (:)'),
      '|'  => t('Pipe (|)'),
      '.'  => t('Period (.)'),
      ' '  => t('Space ( )'),
    ),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Download'),
  );

  return $form;
}

function webform_results_download_form_submit($form_id, $form_values) {
  webform_results_download($form_values['node'], $form_values['format'], $form_values);
}

/**
 * Generate a Excel-readable CSV file containing all submissions for a Webform.
 *
 * The CSV requires that the data be presented in a flat file.  In order
 * to maximize usability to the Excel community and minimize subsequent
 * stats or spreadsheet programming this program extracts data from the
 * various records for a given session and presents them as a single file
 * where each row represents a single record.
 * The structure of the file is:
 *   Heading Line 1: Gives group overviews padded by empty cells to the
 *                   next group.  A group may be a question and corresponds
 *                   to a component in the webform philosophy. Each group
 *                   overview will have a fixed number of columns beneath it.
 *   Heading line 2: gives column headings
 *   Data line 1 .....
 *   Data line 2 .....
 *
 * An example of this format is given below.  Note the columns have had spaces
 * added so the columns line up.  This is not the case with actual file where
 * a column may be null.  Note also, that multiple choice questions as produced
 * by checkboxes or radio buttons have been presented as "yes" or "no" and the
 * actual choice text is retained only in the header line 2.
 * Data from text boxes and input fields are written out in the body of the table.
 *
 *   Submission Details,    ,   ,      ,Question 1,        ,        ,..,        ,Question 2,        ,        ,..,        ,Question n
 *   timestamp         ,time,SID,userid,Choice 1  ,Choice 2,Choice 3,..,Choice n,Choice 1  ,Choice 2,Choice 3,..,Choice n,Comment
 *   21 Feb 2005       ,1835,23 ,34    ,X         ,        ,        ,..,       ,X          ,X       ,X       ,..,X       ,My comment
 *   23 Feb 2005       ,1125,24 ,89    ,X         ,X       ,        ,..,       ,X          ,X       ,X       ,..,X       ,Hello
 *   .................................................................................................................................
 *   27 Feb 2005       ,1035,56 ,212   ,X         ,        ,        ,..,       ,X          ,X       ,X       ,..,X       ,How is this?
 *
 */
function webform_results_download($node, $format = 'delimiter', $options = array()) {
  include_once(drupal_get_path('module', 'webform') .'/webform_export.inc');

  if (empty($options)) {
    $options = array(
      'delimiter' => variable_get('webform_csv_delimiter', '\t'),
      'format' => variable_get('webform_export_format', 'delimited'),
    );
  }

  // Open a new Webform exporter object.
  $exporter = webform_export_create_handler($format, $options);

  $file_name = tempnam(variable_get('file_directory_temp', FILE_DIRECTORY_TEMP), 'webform');
  $handle = @fopen($file_name, 'w'); // The @ suppresses errors.
  $exporter->bof($handle);

  $header[0] = array($node->title, '', '', '', '', '');
  $header[1] = array(t("Submission Details"), '', '', '', '', '');
  $header[2] = array(t('Serial'), t('SID'), t('Time'), t('IP Address'), t('UID'), t('Username'));

  // Compile header information.
  webform_load_components(); // Load all components.
  foreach ($node->webform['components'] as $cid => $component) {
    $csv_header_function = "_webform_csv_headers_". $component['type'];
    if (function_exists($csv_header_function)) {
      // Let each component determine its headers.
      $component_header = $csv_header_function($component);
      $header[0] = array_merge($header[0], (array)$component_header[0]);
      $header[1] = array_merge($header[1], (array)$component_header[1]);
      $header[2] = array_merge($header[2], (array)$component_header[2]);
    }
  }

  // Add headers to the file.
  foreach ($header as $row) {
    $exporter->add_row($handle, $row);
  }

  // Get all the submissions for the node.
  $submissions = webform_get_submissions($node->nid);

  // Generate a row for each submission.
  $row_count = 0;
  foreach ($submissions as $sid => $submission) {
    $row = array();
    $row[] = ++$row_count;
    $row[] = $sid;
    $row[] = format_date($submission->submitted, 'small');
    $row[] =  $submission->remote_addr;
    $row[] = $submission->uid;
    $row[] = $submission->name;
    foreach ($node->webform['components'] as $cid => $component) {
      $csv_data_function   = "_webform_csv_data_". $component['type'];
      if (function_exists($csv_data_function)) {
        // Let each component add its data.
        $data = $csv_data_function($submission->data[$cid], $component);
        if (is_array($data)) {
          $row = array_merge($row, array_values($data));
        }
        else {
          $row[] = $data;
        }
      }
    }
    // Write data from submissions.
    $data = $exporter->add_row($handle, $row);
  }

  // Add the closing bytes.
  $exporter->eof($handle);

  // Close the file.
  @fclose($handle);

  $export_name = _webform_safe_name($node->title);
  $exporter->set_headers($export_name);
  @readfile($file_name);  // The @ makes it silent.
  @unlink($file_name);  // Clean up, the @ makes it silent.
  exit();
}

/**
 * Provides a simple analysis of all submissions to a webform.
 */
function webform_results_analysis($node) {
  $rows = array();
  $question_number = 0;

  $headers = array(
    t('Q'),
    array('data' => t('responses'), 'colspan' => '10')
  );

  webform_load_components(); // Load all component types.
  foreach ($node->webform['components'] as $component) {
    $question_number++;

    // Do component specific call.
    $analysis_function = "_webform_analysis_rows_". $component['type'];
    if (function_exists($analysis_function)) {
      $crows = $analysis_function($component);
      if (is_array($crows)) {
        $row[0] = array('data' => '<strong>'. $question_number .'</strong>', 'rowspan' => count($crows) + 1, 'valign' => 'top');
        $row[1] = array('data' => '<strong>'. $component['name'] .'</strong>', 'colspan' => '10');
        $rows = array_merge($rows, array_merge(array($row), $crows));
      }
    }
  }

  if (count($rows) == 0) {
    $rows[] = array(array('data' => t('There are no submissions for this form. <a href="!url">View this form</a>.', array('!url' => url('node/'. $node->nid))), 'colspan' => 20));
  }

  return theme('table', $headers, $rows);
}
