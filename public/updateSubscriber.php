<?php
//
// Description
// -----------
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_subscriptions_updateSubscriber($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No subscription specified'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No customer specified'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No status specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	if( $args['status'] != 1 && $args['status'] != 60 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'120', 'msg'=>'Invalid status'));
	}
   
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/subscriptions/private/checkAccess.php');
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.updateSubscriber', $args['subscription_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the id for this customer-subscription combination
	//
	$strsql = "SELECT ciniki_subscription_customers.id "
		. "FROM ciniki_subscription_customers, ciniki_subscriptions "
		. "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
		. "AND ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
		. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'subscriptions', 'subscription');
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'subscriptions');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Update the customers subscription
	//
	$strsql = "INSERT INTO ciniki_subscription_customers (subscription_id, customer_id, status, date_added, last_updated "
		. ") VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
		. ") "
		. "ON DUPLICATE KEY UPDATE "
		. "status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "last_updated = UTC_TIMESTAMP()"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'subscriptions');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'subscriptions');
		return $rc;
	}
	$new_customer_subscription_id = $rc['insert_id'];

	//
	// Update the last_updated for the subscription
	//
	$strsql = "UPDATE ciniki_subscriptions SET last_updated = UTC_TIMESTAMP() "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'subscriptions');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'subscriptions');
		return $rc;
	}

	//
	// If the record already exists, then only update the status
	//
	if( $customer_subscription_id > 0 ) {
		ciniki_core_dbAddModuleHistory($ciniki, 'subscriptions', 'ciniki_subscription_history', $args['business_id'], 
			1, 'ciniki_subscription_customers', $customer_subscription_id, 'status', $args['status']);
	} else {
		ciniki_core_dbAddModuleHistory($ciniki, 'subscriptions', 'ciniki_subscription_history', $args['business_id'], 
			1, 'ciniki_subscription_customers', $new_customer_subscription_id, 'customer_id', $args['customer_id']);
		ciniki_core_dbAddModuleHistory($ciniki, 'subscriptions', 'ciniki_subscription_history', $args['business_id'], 
			1, 'ciniki_subscription_customers', $new_customer_subscription_id, 'subscription_id', $args['subscription_id']);
		ciniki_core_dbAddModuleHistory($ciniki, 'subscriptions', 'ciniki_subscription_history', $args['business_id'], 
			1, 'ciniki_subscription_customers', $new_customer_subscription_id, 'status', $args['status']);
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'subscriptions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
