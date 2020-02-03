<?php
//
// Description
// -----------
//
// Info
// ----
// Status:          defined
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_subscriptions_subscriptionSubscriberList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
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
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.subscriptionSubscriberList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $strsql = "SELECT sc.id, "
        . "customers.id AS customer_id, "
        . "customers.display_name, "
        . "IFNULL(sc.status, 0) AS status, "
        . "IFNULL(customers.member_status, 0) AS member_status, "
        . "IFNULL(emails.email, '') AS emails "
        . "FROM ciniki_subscription_customers AS sc "
        . "INNER JOIN ciniki_customers AS customers ON ("
            . "sc.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE sc.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
        . "AND sc.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND sc.status = 10 "
        . "ORDER BY customers.sort_name ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.subscriptions', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'name'=>'customer',
            'fields'=>array('customer_id', 'display_name', 'status', 'member_status', 'emails'),
            'dlists'=>array('emails'=>','),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customers']) ) {
        return array('stat'=>'ok', 'customers'=>array());
    }

    return array('stat'=>'ok', 'customers'=>$rc['customers']);
}
?>
