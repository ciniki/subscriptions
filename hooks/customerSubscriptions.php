<?php
//
// Description
// -----------
// This function will return a list of active subscriptions for a customer.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_subscriptions_hooks_customerSubscriptions($ciniki, $tnid, $args) {

    $subscriptions = array();
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql = "SELECT ciniki_subscriptions.id, "
            . "ciniki_subscriptions.name, "
            . "ciniki_subscriptions.description "
            . "FROM ciniki_subscription_customers, ciniki_subscriptions "
            . "WHERE ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_subscription_customers.subscription_id = ciniki_subscriptions.id "
            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_subscription_customers.status = 10 "  // Customer is active subscription
            . "AND ciniki_subscriptions.status = 10 "           // Subscription is active, no archived/deleted
            . "ORDER BY name "
            . "";
        if( isset($args['idlist']) && $args['idlist'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
            $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.subscriptions', 'subscriptions', 'id');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $subscriptions = isset($rc['subscriptions']) ? implode(',', $rc['subscriptions']) : '';
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.subscriptions', array(
                array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
                    'fields'=>array('id', 'name', 'description')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['subscriptions']) ) {
                $subscriptions = $rc['subscriptions'];
            }
        }
    }

    return array('stat'=>'ok', 'subscriptions'=>$subscriptions);
}
?>
