<?php

require_once 'dedupe.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function dedupe_civicrm_config(&$config) {
  _dedupe_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function dedupe_civicrm_xmlMenu(&$files) {
  _dedupe_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function dedupe_civicrm_install() {
  return _dedupe_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function dedupe_civicrm_uninstall() {
  return _dedupe_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function dedupe_civicrm_enable() {
  return _dedupe_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function dedupe_civicrm_disable() {
  return _dedupe_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function dedupe_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _dedupe_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function dedupe_civicrm_managed(&$entities) {
  return _dedupe_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function dedupe_civicrm_caseTypes(&$caseTypes) {
  _dedupe_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function dedupe_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _dedupe_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_merge
 */
function dedupe_civicrm_merge($type, &$data, $mainId, $otherId, $tables) {
  if ($type == 'flip') {
    $doSwap = FALSE;
    $query  = "SELECT count(*) FROM civicrm_contribution WHERE contact_id = %1";
    $SrcCount = CRM_Core_DAO::singleValueQuery($query, array(1 => array($data['srcID'], 'Integer')));
    $dstCount = CRM_Core_DAO::singleValueQuery($query, array(1 => array($data['dstID'], 'Integer')));
    if ($SrcCount > $dstCount) {
      // keep contact with max contributions as destination (contact to be retained)
      $doSwap = TRUE;
    } else if ($data['dstID'] > $data['srcID']) {
      // retain contact with lower id
      $doSwap = TRUE;
    }
    if ($doSwap) {
      $tempID = $data['srcID'];
      $data['srcID'] = $data['dstID'];
      $data['dstID'] = $tempID;
    }
  }

  if ($type == 'batch') {
    $conflicts = &$data['fields_in_conflict'];
    $fieldsToAbort = array('move_rel_table_users');
    if (!empty(array_intersect($fieldsToAbort, array_keys($conflicts)))) {
      // Do not proceed with merge. Return with conflicts present.
      return;
    }

    // Fields we assume we dont need to merge in case of conflict. 
    // We have already made sure that the contact we retaining ($mainId) is the one with ID & externalID that we want to keep
    $fieldsToSkip  = array('move_external_identifier');

    $migrationInfo = &$data['old_migration_info'];
    foreach ($conflicts as $key => &$val) {
      if (in_array($key, $fieldsToSkip) || $otherId < $mainId) {
        // IF main contact is newest OR we don't want to proceed with merge for this column, 
        // THEN we keep the value of main contact, by not doing any merge for this column
        unset($conflicts[$key]);
      } else if ($otherId > $mainId) { // If duplicate contact is newest
        // we consider value from newest contact, which we can figure out from $migrationInfo
        $val = $migrationInfo[$key];
      }
    }
  }
}
