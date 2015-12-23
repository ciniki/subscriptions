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
function ciniki_subscriptions_objects($ciniki) {
	$objects = array();
	$objects['subscription'] = array(
		'name'=>'Subscription',
		'table'=>'ciniki_subscriptions',
		'fields'=>array(
			'status'=>array(),
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
	$objects['setting'] = array(
		'type'=>'settings',
		'name'=>'Subscription Settings',
		'table'=>'ciniki_subscriptions_settings',
		'history_table'=>'ciniki_subscription_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
