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
function ciniki_subscriptions_updateSubscriber($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('10','60'), 'name'=>'Status'), 
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Flags'), 
        'latest'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latest'), 
        'subscribers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subscribers'), 
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
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.updateSubscriber'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check for an existing record
    //
    $strsql = "SELECT ciniki_subscription_customers.id "
        . "FROM ciniki_subscription_customers "
        . "WHERE ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
        . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_subscription_id = 0;
    // If a record exists
    if( isset($rc['subscription']) ) {
        $customer_subscription_id = $rc['subscription']['id'];
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the customers subscription
    //
    if( $customer_subscription_id > 0 ) {
        $strsql = "UPDATE ciniki_subscription_customers "
            . "SET status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $customer_subscription_id) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
            return $rc;
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'], 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'status', $args['status']);
    } else {
        //
        // Get a new UUID
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.artcatalog');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $args['uuid'] = $rc['uuid'];

        $strsql = "INSERT INTO ciniki_subscription_customers (uuid, business_id, subscription_id, customer_id, status, date_added, last_updated "
            . ") VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
            . "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
            . ") "
            . "";
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.subscriptions');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
            return $rc;
        }
        $customer_subscription_id = $rc['insert_id'];
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'], 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'customer_id', $args['customer_id']);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'], 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'subscription_id', $args['subscription_id']);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $args['business_id'], 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'status', $args['status']);
    }

    //
    // Update the last_updated for the subscription
    //
//  $strsql = "UPDATE ciniki_subscriptions SET last_updated = UTC_TIMESTAMP() "
//      . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//      . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' ";
//  $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
//  if( $rc['stat'] != 'ok' ) { 
//      ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
//      return $rc;
//  }

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
        'args'=>array('id'=>$customer_subscription_id));

    //
    // Check if the latest subscribers should be sent
    //
    if( isset($args['latest']) && $args['latest'] == 'yes' ) {
        $strsql = "SELECT ciniki_subscription_customers.id, "
            . "ciniki_customers.id AS customer_id, "
            . "ciniki_customers.display_name, "
            . "IFNULL(ciniki_subscription_customers.status, 0) AS status "
            . "FROM ciniki_subscription_customers, ciniki_customers "
            . "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
            . "AND ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_subscription_customers.status = 10 "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_subscription_customers.last_updated DESC "
            . "LIMIT 11 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.subscriptions', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'name'=>'customer',
                'fields'=>array('customer_id', 'display_name', 'status')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) ) {
            return array('stat'=>'ok', 'latest'=>$rc['customers']);
        } else {
            return array('stat'=>'ok', 'latest'=>array());
        }
    }

    //
    // Check if all subscribers should be sent
    //
    if( isset($args['subscribers']) && $args['subscribers'] == 'yes' ) {
        $strsql = "SELECT ciniki_subscription_customers.id, "
            . "ciniki_customers.id AS customer_id, "
            . "ciniki_customers.display_name, "
            . "IFNULL(ciniki_subscription_customers.status, 0) AS status "
            . "FROM ciniki_subscription_customers, ciniki_customers "
            . "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
            . "AND ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_subscription_customers.status = 10 "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_customers.sort_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.subscriptions', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'name'=>'customer',
                'fields'=>array('customer_id', 'display_name', 'status')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) ) {
            return array('stat'=>'ok', 'subscribers'=>$rc['customers']);
        } else {
            return array('stat'=>'ok', 'subscribers'=>array());
        }
    }

    return array('stat'=>'ok');
}
?>
