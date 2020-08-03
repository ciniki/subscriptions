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
//  <subscription id="" name="" description="" count='13' />
// </subscriptions>
//
function ciniki_subscriptions_stats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.stats'); 
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
    $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, "
        . "ciniki_subscriptions.description, "
        . "IF((ciniki_subscriptions.flags&0x01)=0x01, 'Yes', 'No') AS public, "
        . "IF((ciniki_subscriptions.flags&0x02)=0x02, 'Yes', 'No') AS auto_subscribe, "
        . "COUNT(ciniki_subscription_customers.customer_id) AS count "
        . "FROM ciniki_subscriptions "
        . "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
            . "AND ciniki_subscription_customers.customer_id > 0 "
            . "AND ciniki_subscription_customers.status = 10 ) "
        . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . $status_sql
        . "GROUP BY ciniki_subscriptions.id "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.subscriptions', array(
        array('container'=>'subscriptions', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'description', 'public', 'auto_subscribe', 'count'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.13', 'msg'=>'Unable to load subscriptions', 'err'=>$rc['err']));
    }
    $subscriptions = isset($rc['subscriptions']) ? $rc['subscriptions'] : array();

    return array('stat'=>'ok', 'subscriptions'=>$subscriptions);
}
?>
