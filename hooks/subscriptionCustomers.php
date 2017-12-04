<?php
//
// Description
// -----------
// This function will return a list of active subscriptions.
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
function ciniki_subscriptions_hooks_subscriptionCustomers($ciniki, $tnid, $args) {

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'maps');
    $rc = ciniki_subscriptions_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of customers for the subscriptions requested
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $strsql = "SELECT ciniki_subscriptions.id, "
        . "ciniki_subscriptions.name, "
        . "ciniki_subscription_customers.subscription_id, "
        . "ciniki_subscription_customers.customer_id, "
        . "ciniki_subscription_customers.status AS status_text "
        . "FROM ciniki_subscriptions "
        . "LEFT JOIN ciniki_subscription_customers ON ("
            . "ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_subscriptions.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['subscription_ids']) . ") "
        . "AND ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY ciniki_subscriptions.id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.subscriptions', array(
        array('container'=>'subscriptions', 'fname'=>'id',
            'fields'=>array('id', 'name')),
        array('container'=>'customers', 'fname'=>'customer_id',
            'fields'=>array('status_text'),
            'maps'=>array('status_text'=>$maps['subscription_customer']['status'])),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscriptions']) ) {
        return array('stat'=>'ok', 'subscriptions'=>array());
    }
    $subscriptions = $rc['subscriptions'];

    return array('stat'=>'ok', 'subscriptions'=>$subscriptions);
}
?>
