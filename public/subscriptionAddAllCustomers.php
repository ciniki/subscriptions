<?php
//
// Description
// -----------
// This function will add a new subscription to the subscriptions module.
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
function ciniki_subscriptions_subscriptionAddAllCustomers(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'checkAccess');
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.subscriptionAddAllCustomer'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the list of customers who currently have no status with this subscription
    //
    $strsql = "SELECT ciniki_customers.id AS customer_id, "
        . "IFNULL(ciniki_subscription_customers.status, 0) AS status "
        . "FROM ciniki_customers "
        . "LEFT JOIN ciniki_subscription_customers ON ("
            . "ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
            . "AND ciniki_customers.id = ciniki_subscription_customers.customer_id "
            . "AND ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_customers.status < 60 "
        . "HAVING status = 0 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {  
        $customers = $rc['rows'];
        foreach($customers as $customer) {
            $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.subscriptions.customer', array(
                'subscription_id'=>$args['subscription_id'],
                'customer_id'=>$customer['customer_id'],
                'status'=>10,
                ), 0);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'settingsUpdateSubscriptions');
    $rc = ciniki_web_settingsUpdateSubscriptions($ciniki, $modules, $args['business_id']);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'subscriptions');

    $ciniki['syncqueue'][] = array('push'=>'ciniki.subscriptions.customer', 
        'args'=>array('id'=>$args['subscription_id']));

    return array('stat'=>'ok');
}
?>
