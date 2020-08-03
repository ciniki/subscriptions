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
// 
// user_id:         The user making the request
// 
// Returns
// -------
// <subscriptions>
//  <subscription id="" name="" description="" />
// </subscriptions>
//
function ciniki_subscriptions_subscriptionList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
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
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.subscriptionList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    
    $status_sql = '';
    if( isset($args['status']) && $args['status'] != '' ) {
        $status_sql .= "AND ciniki_subscriptions.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }

    //
    // Get the number of orders in each status for the tenant, 
    // if no rows found, then return empty array
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, "
            . "ciniki_subscriptions.description, ciniki_subscription_customers.status "
            . "FROM ciniki_subscriptions "
            . "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
                . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "') "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . $status_sql
            . "ORDER BY ciniki_subscriptions.name "
            . "";
    } else {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, "
            . "ciniki_subscriptions.flags "
            . "FROM ciniki_subscriptions "
            . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . $status_sql
            . "ORDER BY ciniki_subscriptions.name "
            . "";
    }

    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscriptions', 'subscription', array('stat'=>'ok', 'subscriptions'=>array()));
    if( $rc['stat'] != 'ok' ) { 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.10', 'msg'=>'Unable to retrieve subscriptions', 'err'=>$rc['err']));
    }
    if( !isset($rc['subscriptions']) ) {
        return array('stat'=>'ok', 'subscriptions'=>array());
    }

    return array('stat'=>'ok', 'subscriptions'=>$rc['subscriptions']);
}
?>
