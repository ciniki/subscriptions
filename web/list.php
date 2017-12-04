<?php
//
// Description
// -----------
// This function will get the public subscriptions and which ones the user is
// subscribed to.
//
//
// Returns
// -------
//
function ciniki_subscriptions_web_list($ciniki, $settings, $tnid) {
    
    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, "
            . "IF(ciniki_subscription_customers.status=10, 'yes', 'no') AS subscribed "
            . "FROM ciniki_subscriptions "
            . "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
                . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "') "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
            . "";
    } else {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, '0' AS subscribed "
            . "FROM ciniki_subscriptions "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
            . "";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.subscriptions', array(
        array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
            'fields'=>array('id', 'name', 'description', 'subscribed')),
        ));

    return $rc;
}
?>
