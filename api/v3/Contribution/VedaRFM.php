<?php

/**
 * Contribution.VedaRFM API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_contribution_vedarfm_spec(&$spec) {
//  $spec['magicword']['api.required'] = 1;
}

/**
 * Contribution.VedaRFM API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contribution_vedarfm($params) {
    $sql = "DROP TABLE IF EXISTS ps_temp_rfm ";
    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }
    $piGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'payment_instrument', 'id', 'name');

    $sql  = ' CREATE TABLE ps_temp_rfm ';
    $sql .= ' AS ';
    $sql .= ' SELECT avg(a.total_amount) avg_donation ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 1 MONTH) , 1, 0)) countributions_last_1_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 3 MONTH) , 1, 0)) countributions_last_3_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 6 MONTH) , 1, 0)) countributions_last_6_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 12 MONTH) , 1, 0)) countributions_last_12_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 24 MONTH) , 1, 0)) countributions_last_24_month ';
    $sql .= ' , a.contact_id ';
    $sql .= ' , b.label AS payment_instrument ';
    $sql .= ' , MAX(a.receive_date) AS last_contribution_date ';
    $sql .= ' FROM civicrm_contribution AS a ';
    $sql .= " LEFT JOIN (SELECT * FROM civicrm_option_value WHERE option_group_id = $piGroupId) AS b ON a.payment_instrument_id = b.value ";
    $sql .= ' WHERE a.total_amount > 0 ';
    $sql .= ' GROUP BY a.contact_id, b.label ';
    
    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    $rfmInfo = _vedarfm_getCustomInfo('Recency_Frequency_Monetary');
    $sql  = "TRUNCATE TABLE {$rfmInfo['table_name']}";
    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    $sql  = " INSERT INTO {$rfmInfo['table_name']} ";
    $sql .= " (entity_id ";
    $sql .= " ,{$rfmInfo['payment_instrument']['column_name']} ";
    $sql .= " ,{$rfmInfo['avg_donation']['column_name']} ";
    $sql .= " ,{$rfmInfo['first_contribution_date']['column_name']} ";
    $sql .= " ,{$rfmInfo['last_contribution_date']['column_name']} ";
    $sql .= " ,{$rfmInfo['number_of_contributions_in_last_1_month']['column_name']} ";
    $sql .= " ,{$rfmInfo['number_of_contributions_in_last_3_months']['column_name']} ";
    $sql .= " ,{$rfmInfo['number_of_contributions_in_last_6_months']['column_name']} ";
    $sql .= " ,{$rfmInfo['number_of_contributions_in_last_12_months']['column_name']} ";
    $sql .= " ,{$rfmInfo['number_of_contributions_in_last_24_months']['column_name']}) ";
    $sql .= " SELECT a.contact_id ";
    $sql .= " , b.label AS payment_instrument ";
    $sql .= " , avg(a.total_amount) avg_donation ";
    $sql .= " , MIN(a.receive_date) AS first_contribution_date ";
    $sql .= " , MAX(a.receive_date) AS last_contribution_date ";
    $sql .= " , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 1 MONTH) , 1, 0)) countributions_last_1_month ";
    $sql .= " , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 3 MONTH) , 1, 0)) countributions_last_3_month ";
    $sql .= " , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 6 MONTH) , 1, 0)) countributions_last_6_month ";
    $sql .= " , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 12 MONTH) , 1, 0)) countributions_last_12_month ";
    $sql .= " , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 24 MONTH) , 1, 0)) countributions_last_24_month ";
    $sql .= " FROM civicrm_contribution AS a ";
    $sql .= " LEFT JOIN (SELECT * FROM civicrm_option_value WHERE option_group_id = $piGroupId) AS b ON a.payment_instrument_id = b.value ";
    $sql .= " WHERE a.total_amount > 0 ";
    $sql .= " GROUP BY a.contact_id, b.label ";

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }
    
//    return civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL); 

    // STT Stuff
    // Tag Anyone that has a future pay id or has paid by Standing Order or Paid 3 times or more in past 6 months
    // with regular giver tag
/*
SELECT entity_id AS contact_id
, 'FP' AS giving_method
FROM civicrm_value_future_pay_18
UNION
SELECT DISTINCT contact_id
, 'SO' AS giving_method
FROM civicrm_contribution
WHERE payment_instrument_id = 14;
*/

    // Update a TAG that indicates if STT should thank the person for that contribution or not
    // Rule is it must be a donation
    // First insert any custom records for contributions that don't have them
    $donorInfo = _vedarfm_getCustomInfo('Donor_Information');
    $sql  = " INSERT INTO {$donorInfo['table_name']} ";
    $sql .= " (entity_id) ";
    $sql .= " SELECT a.id ";
    $sql .= " FROM civicrm_contribution AS a ";
    $sql .= " WHERE NOT EXISTS (SELECT 1 FROM {$donorInfo['table_name']} b WHERE a.id = b.entity_id) ";

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    // Now set the Thanking Required Flag
    // Set Everything to No thats null first
    $sql  = " UPDATE {$donorInfo['table_name']} ";
    $sql .= " SET {$donorInfo['thank_you_required']['column_name']} = 0 ";
    $sql .= " WHERE {$donorInfo['thank_you_required']['column_name']} IS NULL ";

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    // So we want to thank all donations that who are not paid by SO/JG/VMG
    $pi = CRM_Contribute_PseudoConstant::paymentInstrument();
    $piList = array();
    foreach ($pi as $val => $label) {
      if (in_array($label, 
          array('Gift Aid Reclaim', 
            'Just Giving - Gift Aid', 
            'Just Giving SMS', 
            'Standing Order', 
            'Virgin Money Giving', 
            'Virgin Money Giving - Gift Aid', 
            'Voucher - CAF GAYE'))) {
        $piList[] = $val;
      }
    }
    $piList = implode(', ', $piList); 

    $ft = CRM_Contribute_PseudoConstant::financialType();
    $ftList = array();
    foreach ($ft as $val => $label) {
      if (in_array($label, 
          array('Grant UK', 
            'Grant Non UK', 
            'Merchandise', 
            'JG - Income', 
            'JG - Gift Aid', 
            'Donation - Gift Aid', 
            'VMG - Income', 
            'VMG - Gift Aid'))) {
        $ftList[] = $val;
      }
    }
    $ftList = implode(', ', $ftList); 

    $sql  = " UPDATE {$donorInfo['table_name']} a ";
    $sql .= " JOIN civicrm_contribution b ON a.entity_id = b.id ";
    $sql .= " SET a.{$donorInfo['thank_you_required']['column_name']} = 1 ";
    $sql .= " WHERE b.payment_instrument_id NOT IN ({$piList}) ";
    if (!empty($ftList)) {
      $sql .= " AND b.contribution_type_id NOT IN ({$ftList}) ";
    }
    $sql .= " AND a.{$donorInfo['thank_you_required']['column_name']} = 0 ";

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    // Now Set any Future Pay Contributions to not be thanked
    $futurePayInfo = _vedarfm_getCustomInfo('Future_Pay');
    $sql  = " UPDATE {$donorInfo['table_name']} a ";
    $sql .= " JOIN civicrm_contribution b ON a.entity_id = b.id ";
    $sql .= " SET a.{$donorInfo['thank_you_required']['column_name']} = 0 ";
    $sql .= " WHERE EXISTS (SELECT 1 FROM {$futurePayInfo['table_name']} d WHERE d.entity_id = b.contact_id) ";
    $sql .= " AND a.{$donorInfo['thank_you_required']['column_name']} = 1 ";

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }


    return civicrm_api3_create_success(1, $params, NULL, NULL); 

}

function _vedarfm_getCustomInfo($title) {
  $customInfo = array();

  $sql = "
SELECT     g.table_name, f.name, f.column_name, f.label as title
FROM       civicrm_custom_field f
INNER JOIN civicrm_custom_group g ON f.custom_group_id = g.id
WHERE      ( g.name = %1 )
";
  $params = array(1 => array($title, 'String'));
  $dao    = CRM_Core_DAO::executeQuery($sql, $params);
  
  while ($dao->fetch()) {
    $customInfo['table_name'] = $dao->table_name;
    $customInfo[strtolower($dao->name)]   = 
      array('column_name' => $dao->column_name, 
        'title' => $dao->title, 
        'name'  => $dao->name,);
  }
  return $customInfo;
}

