<?php
//
// Description
// -----------
// This function will return a subscription record
//
// Info
// ----
// Status:          started
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_subscriptions_subscriptionGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'),
        'latest'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latest'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'checkAccess');
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.subscriptionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    $strsql = "SELECT id, name, status, flags, description, notify_emails "
        . "FROM ciniki_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscription']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.9', 'msg'=>'Invalid subscription'));
    }
    $subscription = $rc['subscription'];

    //
    // Check if the latest subscribers should be sent
    //
    if( isset($args['latest']) && $args['latest'] == 'yes' ) {
        $strsql = "SELECT ciniki_subscription_customers.id, "
            . "ciniki_customers.id AS customer_id, "
            . "ciniki_customers.display_name, "
            . "IFNULL(ciniki_customers.member_status, 0) AS member_status "
            . "FROM ciniki_subscription_customers, ciniki_customers "
            . "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_subscription_customers.status = 10 "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_subscription_customers.last_updated DESC "
            . "LIMIT 11 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.subscriptions', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'name'=>'customer',
                'fields'=>array('customer_id', 'display_name', 'member_status')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) ) {
            $subscription['latest'] = $rc['customers'];
        } else {
            $subscription['latest'] = array();
        }
    }

    return array('stat'=>'ok', 'subscription'=>$subscription);
}
?>
