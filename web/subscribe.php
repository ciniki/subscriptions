<?php
//
// Description
// -----------
//
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_subscriptions_web_subscribe($ciniki, $settings, $business_id, $subscription_id) {

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
		. "'" . ciniki_core_dbQuote($ciniki, $subscription_id) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, '1') . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
		. ") "
		. "ON DUPLICATE KEY UPDATE "
		. "status = '" . ciniki_core_dbQuote($ciniki, '1') . "', "
		. "last_updated = UTC_TIMESTAMP()"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'subscriptions');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'subscriptions');
		return $rc;
	}
	$new_customer_subscription_id = $rc['insert_id'];

	//
	// Log the change in the database
	//
	ciniki_core_dbAddModuleHistory($ciniki, 'subscriptions', 'ciniki_subscription_history', $business_id, 
		2, 'ciniki_subscription_customers', $new_customer_subscription_id, 'status', '1');

	//
	// Update the last_updated for the subscription
	//
	$strsql = "UPDATE ciniki_subscriptions SET last_updated = UTC_TIMESTAMP() "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $subscription_id) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'subscriptions');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'subscriptions');
		return $rc;
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
