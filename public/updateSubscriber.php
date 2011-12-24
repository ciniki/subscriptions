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
   
	$status = 0;
	if( $args['status'] == 'on' ) {
		$status = 1;
	} elseif( $args['status'] == 'off' ) {
		$status = 99;
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'392', 'msg'=>'Invalid status'));
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
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
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
		. "'" . ciniki_core_dbQuote($ciniki, $status) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
		. ") "
		. "ON DUPLICATE KEY UPDATE "
		. "status = '" . ciniki_core_dbQuote($ciniki, $status) . "', "
		. "last_updated = UTC_TIMESTAMP()"
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'subscriptions');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'subscriptions');
		return $rc;
	}

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

	ciniki_core_dbAddChangeLog($ciniki, 'subscriptions', $args['business_id'], 
		'ciniki_subscription_customers', $args['subscription_id'] . '-' . $args['customer_id'], 'status', $args['status']);


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
