<?php

/**
 * @file
 * Contains \Drupal\webform\Controller\WebformController.
 */

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Webform routes.
 */
class WebformController extends ControllerBase {

  /**
   * Create an overview of webform content.
   */
  public function contentOverview() {
    $query = db_select('webform', 'w');
    $query->join('node', 'n', 'w.nid = n.nid');
    $query->fields('n');
    $nodes = $query->execute()->fetchAllAssoc('nid');
    module_load_include('inc', 'webform', 'includes/webform.admin');
    return _theme('webform_admin_content', array('nodes' => $nodes));
  }

}
