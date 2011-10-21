<?php
//
// Description
// -----------
// This function will get the history of a field from the core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// key:					The detail key to get the history for.
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_subscriptions_getHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'subscriptions_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No order specified'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No field specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/subscriptions/private/checkAccess.php');
	$rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.getHistory', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbGetChangeLog.php');
	return ciniki_core_dbGetChangeLog($ciniki, $args['business_id'], 'subscriptions', $args['subscriptions_id'], $args['field'], 'subscriptions');
}
?>
