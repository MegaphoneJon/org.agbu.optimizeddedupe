<?php
/*
 * Match on email, or all 4 of the following: First 3 letters of first name,
 * first 5 chars each of last name, street address, postal code.
 */

class CRM_Dedupe_BAO_QueryBuilder_OnlineDonations extends CRM_Dedupe_BAO_QueryBuilder {

  /**
   * @param $rg
   *
   * @return array
   */
  public static function record($rg) {
    $civicrm_email = CRM_Utils_Array::value('civicrm_email', $rg->params, []);
    $civicrm_contact = CRM_Utils_Array::value('civicrm_contact', $rg->params);
    $civicrm_address = CRM_Utils_Array::value('civicrm_address', $rg->params, []);


    $params = [
      1 => [substr(CRM_Utils_Array::value('first_name', $civicrm_contact, ''), 0, 3) . '%', 'String'],
      2 => [substr(CRM_Utils_Array::value('last_name', $civicrm_contact, ''), 0, 5) . '%', 'String'],
      3 => [CRM_Utils_Array::value('email', $civicrm_email, ''), 'String'],
      4 => [substr(CRM_Utils_Array::value('street_address', $civicrm_address, ''), 0, 5) . '%', 'String'],
      5 => [substr(CRM_Utils_Array::value('postal_code', $civicrm_address, ''), 0, 5) . '%', 'String'],
    ];

    return ["civicrm_contact.{$rg->name}.{$rg->threshold}" => CRM_Core_DAO::composeQuery("
                SELECT contact.id as id1, {$rg->threshold} as weight
                FROM civicrm_contact as contact
                JOIN civicrm_email as email ON email.contact_id=contact.id
                WHERE contact_type = 'Individual'
                AND email = %3
                UNION DISTINCT
                SELECT contact.id as id1, {$rg->threshold} as weight
                FROM civicrm_contact as contact
                JOIN civicrm_address as address ON contact.id = address.contact_id
                WHERE contact_type = 'Individual'
                AND first_name LIKE %1
                AND last_name LIKE %2
                AND street_address LIKE %4
                AND postal_code LIKE %5", $params, TRUE),
    ];
  }

  public static function internal($rg) {
    //We're gonna break this up a bit for better indexing.
    $sql = "CREATE TEMPORARY TABLE onlinedonationdedupe (
                               first_name varchar(3),
                               last_name varchar(5),
                               street_address varchar(5),
                               postal_code varchar(5),
                               contact_id int,
                               INDEX(contact_id),
                               INDEX(first_name),
                               INDEX(last_name),
                               INDEX(street_address),
                               INDEX(postal_code)
                              ) ENGINE=InnoDB";
    CRM_Core_DAO::executeQuery($sql);

    // Insert duplicate emails
//    $sql = "
//    INSERT INTO onlinedonationdedupe (email, contact_id1, contact_id2)
//    SELECT email1.email as email, email1.contact_id as contact_id1, email2.contact_id as contact_id2
//    FROM civicrm_email as email1
//    JOIN civicrm_email as email2 USING (email)
//    WHERE email1.contact_id < email2.contact_id
//    AND  " . self::internalFilters($rg, "email1.contact_id", "email2.contact_id");

    CRM_Core_DAO::executeQuery($sql);

    // This query does NOT work like the one above, which is not great on my part.
    $sql = "INSERT INTO onlinedonationdedupe (first_name, last_name, street_address, postal_code, contact_id1, contact_id2)
        SELECT LEFT(first_name, 3), LEFT(last_name, 5), LEFT(street_address, 5), LEFT(postal_code, 5), t1.id id1, t2.id id2
        FROM civicrm_contact t1
        JOIN civicrm_address adr1 on t1.id = adr1.contact_id
        JOIN (SELECT cc.id, cc.contact_type, cc.first_name, cc.last_name, adr2.postal_code, adr2.street_address
        FROM civicrm_contact cc
        JOIN civicrm_address adr2 ON cc.id = adr2.contact_id) t2 ON LEFT(t1.first_name, 3) = LEFT(t2.first_name, 3)
        AND LEFT(t1.last_name, 5) = LEFT(t2.last_name, 5)
        AND LEFT(adr1.postal_code, 5) = LEFT(t2.postal_code, 5)
        AND LEFT(adr1.street_address, 5) = LEFT(t2.street_address, 5)
         WHERE t1.contact_type = 'Individual' AND
         t2.contact_type = 'Individual' AND
         t1.id < t2.id AND
         t1.first_name IS NOT NULL AND
         t1.last_name IS NOT NULL AND
         adr1.postal_code IS NOT NULL AND
         adr1.street_address IS NOT NULL";
  }

}
