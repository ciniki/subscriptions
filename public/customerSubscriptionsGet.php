<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_subscriptions_customerSubscriptionsGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
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
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.customerSubscriptionsGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the list of subscriptions
    //
    $strsql = "SELECT subs.id, "
        . "subs.name, "
        . "subcustomers.id AS customer_subscription_id, "
        . "subcustomers.customer_id, "
        . "subs.description, "
        . "subcustomers.status, "
        . "IFNULL(customers.display_name, '') AS display_name "
        . "FROM ciniki_subscriptions AS subs "
        . "LEFT JOIN ciniki_subscription_customers AS subcustomers ON ("
            . "subs.id = subcustomers.subscription_id "
            . "AND subcustomers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND subcustomers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "subcustomers.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE subs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND subs.status = 10 "
        . "ORDER BY subs.name, customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'subscriptions', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'description', 'status', 'display_name'), 
            'dlists'=>array('display_name'=>'<br/>'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp = array('stat'=>'ok', 'subscriptions'=>(isset($rc['subscriptions']) ? $rc['subscriptions'] : array()));

    //
    // Load the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], array(
        'customer_id' => $args['customer_id'],
        'phones' => 'yes',
        'emails' => 'yes', 
        'addresses' => 'yes',
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.83', 'msg'=>'Unable to load customer details', 'err'=>$rc['err']));
    }
    if( isset($rc['details']) ) {
        $rsp['customer_details'] = $rc['details'];
    }

    return $rsp;
}
?>
