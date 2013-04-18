<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_subscriptions_sync_objects($ciniki, &$sync, $business_id, $args) {
	
	//
	// NOTES: When pushing a change, grab the history for the current session
	// When increment/partial/full, sync history on it's own
	//

	//
	// Working on version 2 of sync, completely object based
	//
	$objects = array();
	$objects['subscription'] = array(
		'name'=>'Subscription',
		'table'=>'ciniki_subscriptions',
		'fields'=>array(
			'flags'=>array(),
			'name'=>array(),
			'description'=>array(),
			),
		'history_table'=>'ciniki_subscription_history',
		);
	$objects['customer'] = array(
		'name'=>'Subscription Customer',
		'table'=>'ciniki_subscription_customers',
		'fields'=>array(
			'subscription_id'=>array('ref'=>'ciniki.subscriptions.subscription'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'status'=>array(),
			),
		'history_table'=>'ciniki_subscription_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
