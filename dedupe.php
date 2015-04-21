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
 * buildForm hook implementation
 * On "civicrm/contact/dedupefind?reset=1&rgid=18&action=preview" url always
 * keep "All Contacts" option selected by default
 *
 */
function dedupe_civicrm_buildForm( $formName, &$form ) {
  if ( $formName == 'CRM_Contact_Form_DedupeFind' ) {
    $defaults['group_id'] = ''; 
    $form->setDefaults( $defaults );
  }
}

/**
 * Implements hook_civicrm_merge
 */
function dedupe_civicrm_merge($type, &$data, $mainId, $otherId, $tables) {
  if ($type == 'flip') {
    $doSwap = FALSE;

    $mainUserId  = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFMatch', $data['dstID'], 'uf_id', 'contact_id');
    $otherUserId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFMatch', $data['srcID'], 'uf_id', 'contact_id');
    if (!$mainUserId && $otherUserId) {
      // if only the other contact has drupal user, do swap
      $doSwap = TRUE;
    }
    else if (!$mainUserId && $otherUserId) {
      // if main contact has drupal user, don't swap
      $doSwap = FALSE;
    } 
    else { 
      // let max contribution rule decide the baising
      $query  = "SELECT count(*) FROM civicrm_contribution WHERE contact_id = %1";
      $SrcCount = CRM_Core_DAO::singleValueQuery($query, array(1 => array($data['srcID'], 'Integer')));
      $dstCount = CRM_Core_DAO::singleValueQuery($query, array(1 => array($data['dstID'], 'Integer')));
      if ($SrcCount > $dstCount) {
        // keep contact with max contributions as destination (contact to be retained)
        $doSwap = TRUE;
      }
    }

    if ($doSwap) {
      $tempID = $data['srcID'];
      $data['srcID'] = $data['dstID'];
      $data['dstID'] = $tempID;
    }
    $data['auto_flip'] = FALSE;
  }
  
  if ($type == 'batch') {
    static $mailingBlockID = NULL, $mailingBlockSegID = NULL;
    if (!$mailingBlockID) {
      $mailingBlockID    = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', 'Blocks', 'id', 'name');
      $mailingBlockSegID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', 'New_Blocks', 'id', 'name');
    }

    $conflicts = &$data['fields_in_conflict'];
    $migrationInfo = &$data['old_migration_info'];
    $fieldsToAbort = 
      array(
        'move_first_name',
        'move_last_name',
        'move_do_not_phone', 
        'move_do_not_email', 
        'move_do_not_mail', 
        'move_do_not_sms', 
        'move_do_not_trade', 
        'move_is_opt_out',
        "move_is_deceased",
      );
    $fieldsToAbort = array_intersect($fieldsToAbort, array_keys($conflicts));
    if (!empty($fieldsToAbort)) {
      // Do not proceed with merge. Return with conflicts present.
      return;
    }

    // if both the contacts are deceased, it might not have raised a conflict.
    // But we do want it to raise a conflict anyway. Just checking one contact
    // should be fine.
    $query  = "SELECT is_deceased FROM civicrm_contact WHERE id = %1";
    $isMainDeceased = CRM_Core_DAO::singleValueQuery($query, array(1 => array($mainId, 'Integer')));
    if ($isMainDeceased) {
      // forcibly raise a conflict
      $conflicts['move_is_deceased'] = NULL;
      return;
    }

    $fieldsToAbortIfNotSame = 
      array(
        "move_custom_{$mailingBlockID}",
        "move_custom_{$mailingBlockSegID}",
      );
    foreach ($fieldsToAbortIfNotSame as $key => $val) {
      if (array_key_exists($key, $conflicts) && 
        $migrationInfo[$key]['main'] == $migrationInfo[$key]['other']) {
          // for mailing block customs, if values are same no point in raising a
          // conflict or merging
          unset($conflicts[$key], $migrationInfo[$key]);
        }
    }
    // see if we still have conflicts present
    $fieldsToAbortIfNotSame = array_intersect($fieldsToAbortIfNotSame, array_keys($conflicts));
    if (!empty($fieldsToAbortIfNotSame)) {
      // Do not proceed with merge. Return with conflicts present.
      return;
    }

    $mainFieldsToPreserve = array('move_external_identifier');
    foreach ($conflicts as $key => $val) {
      // in case of conflict if we preserve the field for contact with latest
      // contact ID. These are fields like ext-id, date-of-birth,
      // year-of-inauguration and so on..
      // Note the conflict here means that the field has value for both the
      // contacts.
      if (($mainId >= $otherId) || in_array($key, $mainFieldsToPreserve)) {
        // if main contact id is latest or the field is ext-ID,
        // preserve that of main contact
        unset($conflicts[$key], $migrationInfo[$key]);
      } else {
        // if other contact is latest,
        // unset $conflicts so process doesn't halt, and let other contact
        // override the main contact.
        unset($conflicts[$key]);
      }
    }

    // if both contacts have cms user, raise a conflict 
    // note: this field is never in $conflicts, and therefore we handle
    // separately
    $mainUserId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFMatch', $mainId, 'uf_id', 'contact_id');
    if ($mainUserId && $migrationInfo['move_rel_table_users'] == 1) {
      $otherUserId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFMatch', $otherId, 'uf_id', 'contact_id');
      if ($otherUserId) {
        // let's forcibly raise a conflict
        $conflicts['move_rel_table_users'] = NULL;
      }
      // don't move user, but proceed with merge if no conflicts
      unset($migrationInfo['move_rel_table_users']);
    }

    // if any other data has empty value, make sure we don't merge it
    foreach ($migrationInfo['rows'] as $movKey => $movVal) {
      if ($movVal['other'] === '' || $movVal['other'] === NULL) {
        unset($migrationInfo[$movKey]);
      }
    }
  }
}

function dedupe_civicrm_dupeQuery($form, $type, &$data) {
  if ($type == 'supportedFields' && !empty($data)) {
    $data['Individual']['civicrm_contact']['custom_first_last']                = "Custom: First Name AND Last Name";
    $data['Individual']['civicrm_contact']['custom_first_last_email']          = "Custom: First Name AND Last Name AND Email";
    $data['Individual']['civicrm_contact']['custom_first_last_phone']          = "Custom: First Name AND Last Name AND Phone";
    $data['Individual']['civicrm_contact']['custom_first_last_postcode']       = "Custom: First Name AND Last Name AND Postal Code";
    $data['Individual']['civicrm_contact']['custom_initial_last_email']        = "Custom: Initial AND Last Name AND Email";
    $data['Individual']['civicrm_contact']['custom_initial_last_email_dob']    = "Custom: Initial AND Last Name AND Email AND DOB";
    $data['Individual']['civicrm_contact']['custom_initial_last_postcode']     = "Custom: Initial AND Last Name AND Postal Code";
    $data['Individual']['civicrm_contact']['custom_initial_last_postcode_dob'] = "Custom: Initial AND Last Name AND Postal Code AND DOB";
    $data['Individual']['civicrm_contact']['custom_initial_last_phone']        = "Custom: Initial AND Last Name AND Phone";
    $data['Individual']['civicrm_contact']['custom_prefix_last_email']         = "Custom: Prefix AND Last Name AND Email";
    $data['Individual']['civicrm_contact']['custom_prefix_last_postcode']      = "Custom: Prefix AND Last Name AND Postal Code";
    $data['Individual']['civicrm_contact']['custom_prefix_initial_last_email'] = "Custom: Prefix AND Initial AND Last Name AND Email";
    $data['Individual']['civicrm_contact']['custom_prefix_initial_last_postcode'] = "Custom: Prefix AND Initial AND Last Name AND Postal Code";
    $data['Individual']['civicrm_contact']['custom_prefix_initial_last_phone']    = "Custom: Prefix AND Initial AND Last Name AND Phone";
  }
  if ($type == 'dedupeIndexes' && !empty($data)) {
    foreach ($data['civicrm_contact'] as $key => $val) {
      if (in_array($val, 
        array(
          'custom_first_last',
          'custom_first_last_phone', 
          'custom_first_last_email', 
          'custom_first_last_postcode',
          'custom_prefix_last_email',
          'custom_prefix_last_postcode',
          'custom_initial_last_email',
          'custom_initial_last_postcode',
          'custom_initial_last_email_dob',
          'custom_initial_last_postcode_dob',
          'custom_initial_last_phone',
          'custom_prefix_initial_last_email',
          'custom_prefix_initial_last_postcode',
          'custom_prefix_initial_last_phone',
        ))
      ) {
        unset($data['civicrm_contact'][$key]);
      }
    }
  }
  if ($type == 'table' && !empty($data)) {
    foreach ($data as $key => &$query) {
      list($table, $col, $wt) = explode('.', $key);
      if ($table == 'civicrm_contact' && $col == 'custom_first_last') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
      JOIN civicrm_contact t2 ON t1.first_name = t2.first_name AND t1.last_name = t2.last_name
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.first_name IS NOT NULL AND 
           t1.last_name  IS NOT NULL";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_first_last_email') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_email em1 ON t1.id = em1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, em2.email 
               FROM civicrm_contact cc
         INNER JOIN civicrm_email em2 ON cc.id = em2.contact_id ) t2 ON t1.first_name = t2.first_name AND 
                                                                        t1.last_name = t2.last_name AND 
                                                                        em1.email = t2.email
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.first_name IS NOT NULL AND 
           t1.last_name  IS NOT NULL AND 
           em1.email IS NOT NULL AND em1.email <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_first_last_phone') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_phone ph1 ON t1.id = ph1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, ph2.phone 
               FROM civicrm_contact cc
         INNER JOIN civicrm_phone ph2 ON cc.id = ph2.contact_id ) t2 ON t1.first_name = t2.first_name AND 
                                                                        t1.last_name = t2.last_name AND 
                                                                        REPLACE(ph1.phone, ' ', '') = REPLACE(t2.phone, ' ', '')
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.first_name IS NOT NULL AND 
           t1.last_name  IS NOT NULL AND 
           ph1.phone IS NOT NULL AND ph1.phone <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_first_last_postcode') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_address adr1 ON t1.id = adr1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, adr2.postal_code
               FROM civicrm_contact cc
         INNER JOIN civicrm_address adr2 ON cc.id = adr2.contact_id ) t2 ON t1.first_name    = t2.first_name  AND 
                                                                            t1.last_name     = t2.last_name   AND 
                                                                            adr1.postal_code = t2.postal_code
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.first_name IS NOT NULL AND 
           t1.last_name  IS NOT NULL AND 
           adr1.postal_code IS NOT NULL AND adr1.postal_code <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_prefix_last_email') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_email em1 ON t1.id = em1.contact_id 
      JOIN ( SELECT cc.id, cc.prefix_id, cc.last_name, cc.contact_type, em2.email 
               FROM civicrm_contact cc
         INNER JOIN civicrm_email em2 ON cc.id = em2.contact_id ) t2 ON t1.prefix_id = t2.prefix_id AND 
                                                                        t1.last_name = t2.last_name AND 
                                                                        em1.email = t2.email
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.prefix_id IS NOT NULL AND 
           t1.last_name IS NOT NULL AND 
           em1.email IS NOT NULL AND em1.email <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_prefix_last_postcode') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_address adr1 ON t1.id = adr1.contact_id 
      JOIN ( SELECT cc.id, cc.prefix_id, cc.last_name, cc.contact_type, adr2.postal_code
               FROM civicrm_contact cc
         INNER JOIN civicrm_address adr2 ON cc.id = adr2.contact_id ) t2 ON t1.prefix_id     = t2.prefix_id   AND 
                                                                            t1.last_name     = t2.last_name   AND 
                                                                            adr1.postal_code = t2.postal_code
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.prefix_id IS NOT NULL AND 
           t1.last_name IS NOT NULL AND 
           adr1.postal_code IS NOT NULL AND adr1.postal_code <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_initial_last_email') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_email em1 ON t1.id = em1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, em2.email 
               FROM civicrm_contact cc
         INNER JOIN civicrm_email em2 ON cc.id = em2.contact_id ) t2 ON t1.first_name = t2.first_name AND
                                                                        t1.last_name = t2.last_name AND 
                                                                        em1.email = t2.email
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name  IS NOT NULL AND 
           em1.email IS NOT NULL AND em1.email <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_initial_last_email_dob') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_email em1 ON t1.id = em1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, cc.birth_date, em2.email 
               FROM civicrm_contact cc
         INNER JOIN civicrm_email em2 ON cc.id = em2.contact_id ) t2 ON t1.first_name = t2.first_name AND
                                                                        t1.last_name = t2.last_name   AND 
                                                                        t1.birth_date = t2.birth_date AND
                                                                        em1.email = t2.email
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name  IS NOT NULL AND 
           t1.birth_date IS NOT NULL AND 
           em1.email IS NOT NULL AND em1.email <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_initial_last_postcode') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_address adr1 ON t1.id = adr1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, adr2.postal_code
               FROM civicrm_contact cc
         INNER JOIN civicrm_address adr2 ON cc.id = adr2.contact_id ) t2 ON t1.first_name    = t2.first_name AND
                                                                            t1.last_name     = t2.last_name  AND 
                                                                            adr1.postal_code = t2.postal_code
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name  IS NOT NULL AND 
           adr1.postal_code IS NOT NULL AND adr1.postal_code <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_initial_last_postcode_dob') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_address adr1 ON t1.id = adr1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, cc.birth_date, adr2.postal_code
               FROM civicrm_contact cc
         INNER JOIN civicrm_address adr2 ON cc.id = adr2.contact_id ) t2 ON t1.first_name    = t2.first_name AND
                                                                            t1.last_name     = t2.last_name  AND 
                                                                            t1.birth_date    = t2.birth_date AND
                                                                            adr1.postal_code = t2.postal_code
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name  IS NOT NULL AND 
           t1.birth_date IS NOT NULL AND 
           adr1.postal_code IS NOT NULL AND adr1.postal_code <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_initial_last_phone') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_phone ph1 ON t1.id = ph1.contact_id 
      JOIN ( SELECT cc.id, cc.first_name, cc.last_name, cc.contact_type, ph2.phone 
               FROM civicrm_contact cc
         INNER JOIN civicrm_phone ph2 ON cc.id = ph2.contact_id ) t2 ON t1.first_name = t2.first_name AND
                                                                        t1.last_name  = t2.last_name AND 
                                                                        REPLACE(ph1.phone, ' ', '') = REPLACE(t2.phone, ' ', '')
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name  IS NOT NULL AND 
           ph1.phone IS NOT NULL AND ph1.phone <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_prefix_initial_last_email') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_email em1 ON t1.id = em1.contact_id 
      JOIN ( SELECT cc.id, cc.prefix_id, cc.first_name, cc.last_name, cc.contact_type, em2.email 
               FROM civicrm_contact cc
         INNER JOIN civicrm_email em2 ON cc.id = em2.contact_id ) t2 ON t1.prefix_id = t2.prefix_id AND 
                                                                        t1.first_name = t2.first_name AND
                                                                        t1.last_name = t2.last_name AND 
                                                                        em1.email = t2.email
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.prefix_id IS NOT NULL AND
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name IS NOT NULL AND 
           em1.email IS NOT NULL AND em1.email <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_prefix_initial_last_postcode') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_address adr1 ON t1.id = adr1.contact_id 
      JOIN ( SELECT cc.id, cc.prefix_id, cc.first_name, cc.last_name, cc.contact_type, adr2.postal_code
               FROM civicrm_contact cc
         INNER JOIN civicrm_address adr2 ON cc.id = adr2.contact_id ) t2 ON t1.prefix_id = t2.prefix_id AND 
                                                                            t1.first_name = t2.first_name AND
                                                                            t1.last_name     = t2.last_name   AND 
                                                                            adr1.postal_code = t2.postal_code
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.prefix_id IS NOT NULL AND
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name IS NOT NULL AND 
           adr1.postal_code IS NOT NULL AND adr1.postal_code <> ''";
      }
      if ($table == 'civicrm_contact' && $col == 'custom_prefix_initial_last_phone') {
        $data[$key] = "
    SELECT t1.id id1, t2.id id2, $wt weight 
      FROM civicrm_contact t1
INNER JOIN civicrm_phone ph1 ON t1.id = ph1.contact_id 
      JOIN ( SELECT cc.id, cc.prefix_id, cc.first_name, cc.last_name, cc.contact_type, REPLACE(ph2.phone, ' ', '') as rphone 
               FROM civicrm_contact cc
         INNER JOIN civicrm_phone ph2 ON cc.id = ph2.contact_id ) t2 ON t1.prefix_id  = t2.prefix_id AND 
                                                                        t1.first_name = t2.first_name AND
                                                                        t1.last_name  = t2.last_name AND 
                                                                        REPLACE(ph1.phone, ' ', '') = t2.rphone
     WHERE t1.contact_type = 'Individual' AND 
           t2.contact_type = 'Individual' AND 
           t1.id < t2.id AND 
           t1.prefix_id IS NOT NULL AND 
           CHAR_LENGTH(t1.first_name) = 1 AND
           t1.last_name  IS NOT NULL AND 
           ph1.phone IS NOT NULL AND ph1.phone <> ''";
      }
    }
  }
  // make sure custom queries are executed first
  if ($type == 'tableCount' && !empty($data)) {
    $customQuery = array();
    foreach ($data as $key => &$query) {
      list($table, $col, $wt) = explode('.', $key);
      if (in_array($col, 
        array(
          'custom_first_last',
          'custom_first_last_phone', 
          'custom_first_last_email', 
          'custom_first_last_postcode',
          'custom_prefix_last_email',
          'custom_prefix_last_postcode',
          'custom_initial_last_email',
          'custom_initial_last_postcode',
          'custom_initial_last_email_dob',
          'custom_initial_last_postcode_dob',
          'custom_initial_last_phone',
          'custom_prefix_initial_last_email',
          'custom_prefix_initial_last_postcode',
          'custom_prefix_initial_last_phone',
        ))
      ) {
        $customQuery = array($key => $query);
        unset($data[$key]);
      }
    }
    if (!empty($customQuery)) {
      $data = array_merge($customQuery, $data);
    }
  }
}
