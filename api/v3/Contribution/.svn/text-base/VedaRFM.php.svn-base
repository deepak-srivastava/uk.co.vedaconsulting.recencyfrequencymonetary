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
    $sql .= ' LEFT JOIN (SELECT * FROM civicrm_option_value WHERE option_group_id = 10) AS b ON a.payment_instrument_id = b.value ';
    $sql .= ' WHERE a.total_amount > 0 ';
    $sql .= ' GROUP BY a.contact_id, b.label ';
    
    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    $sql  = ' TRUNCATE TABLE civicrm_value_recency_frequency_monetary_17 ';

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    $sql  = ' INSERT INTO civicrm_value_recency_frequency_monetary_17 ';
    $sql .= ' (entity_id ';
    $sql .= ' ,payment_instrument_88 ';
    $sql .= ' ,avg_donation_89 ';
    $sql .= ' ,first_contribution_date_110 ';
    $sql .= ' ,last_contribution_date_90 ';
    $sql .= ' ,number_of_contributions_in_last__91 ';
    $sql .= ' ,number_of_contributions_in_last__92 ';
    $sql .= ' ,number_of_contributions_in_last__93 ';
    $sql .= ' ,number_of_contributions_in_last__94 ';
    $sql .= ' ,number_of_contributions_in_last__95) ';
    $sql .= ' SELECT a.contact_id ';
    $sql .= ' , b.label AS payment_instrument ';
    $sql .= ' , avg(a.total_amount) avg_donation ';
    $sql .= ' , MIN(a.receive_date) AS first_contribution_date ';
    $sql .= ' , MAX(a.receive_date) AS last_contribution_date ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 1 MONTH) , 1, 0)) countributions_last_1_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 3 MONTH) , 1, 0)) countributions_last_3_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 6 MONTH) , 1, 0)) countributions_last_6_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 12 MONTH) , 1, 0)) countributions_last_12_month ';
    $sql .= ' , SUM(IF(a.receive_date > DATE_SUB(NOW(), INTERVAL 24 MONTH) , 1, 0)) countributions_last_24_month ';
    $sql .= ' FROM civicrm_contribution AS a ';
    $sql .= ' LEFT JOIN (SELECT * FROM civicrm_option_value WHERE option_group_id = 10) AS b ON a.payment_instrument_id = b.value ';
    $sql .= ' WHERE a.total_amount > 0 ';
    $sql .= ' GROUP BY a.contact_id, b.label ';

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
    $sql  = ' INSERT INTO civicrm_value_donor_information_3 ';
    $sql .= ' (entity_id) ';
    $sql .= ' SELECT a.id ';
    $sql .= ' FROM civicrm_contribution AS a ';
    $sql .= ' WHERE NOT EXISTS (SELECT 1 FROM civicrm_value_donor_information_3 b WHERE a.id = b.entity_id) ';

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    // Now set the Thanking Required Flag
    // Set Everything to No thats null first
    $sql  = ' UPDATE civicrm_value_donor_information_3 ';
    $sql .= ' SET thank_you_required_111 = 0 ';
    $sql .= ' WHERE thank_you_required_111 IS NULL ';

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    // So we want to thank all donations that who are not paid by SO/JG/VMG
    $sql  = ' UPDATE civicrm_value_donor_information_3 a ';
    $sql .= ' JOIN civicrm_contribution b ON a.entity_id = b.id ';
    $sql .= ' SET a.thank_you_required_111 = 1 ';
    $sql .= ' WHERE b.payment_instrument_id NOT IN (13, 11, 10, 14, 6, 12, 16) ';
    $sql .= ' AND b.contribution_type_id NOT IN (2, 4, 5, 6, 7, 8, 12, 13) ';
    $sql .= ' AND a.thank_you_required_111 = 0 ';

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    // Now Set any Future Pay Contributions to not be thanked
    $sql  = ' UPDATE civicrm_value_donor_information_3 a ';
    $sql .= ' JOIN civicrm_contribution b ON a.entity_id = b.id ';
    $sql .= ' SET a.thank_you_required_111 = 0 ';
    $sql .= ' WHERE EXISTS (SELECT 1 FROM civicrm_value_future_pay_18 d WHERE d.entity_id = b.contact_id) ';
    $sql .= ' AND a.thank_you_required_111 = 1 ';

    try {
        CRM_Core_DAO::executeQuery($sql);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }


    return civicrm_api3_create_success(1, $params, NULL, NULL); 

}

