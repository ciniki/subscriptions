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
function ciniki_subscriptions_updateCustomerSubscriptions($ciniki, $business_id, $customer_id, $subs, $unsubs) {
	//
	// Get the existing subscriptions for a customer
	//
	$strsql = "SELECT id, subscription_id, status "
		. "FROM ciniki_subscription_customers "
		. "WHERE ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.subscriptions', array(
		array('container'=>'subscriptions', 'fname'=>'subscription_id',
			'fields'=>array('id', 'status')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$subscriptions = array();
	if( isset($rc['subscriptions']) ) {
		$subscriptions = $rc['subscriptions'];
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	foreach($subs as $sid) {
		//
		// If the subscription doesn't exist, add it
		//
		if( !isset($subscriptions[$sid]) ) {
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.subscriptions.customer', array(
				'subscription_id'=>$sid, 'customer_id'=>$customer_id, 'status'=>'10'), 0x06);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
		elseif( isset($subscriptions[$sid]) && $subscriptions[$sid]['status'] != 10 ) {
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.subscriptions.customer', $subscriptions[$sid]['id'],
				array('status'=>'10'), 0x06);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	foreach($unsubs as $sid) {
		//
		// If the subscription doesn't exist, add it
		//
		if( !isset($subscriptions[$sid]) ) {
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.subscriptions.customer', array(
				'subscription_id'=>$sid, 'customer_id'=>$customer_id, 'status'=>'60'), 0x06);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
		elseif( isset($subscriptions[$sid]) && $subscriptions[$sid]['status'] != 60 ) {
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.subscriptions.customer', $subscriptions[$sid]['id'],
				array('status'=>'60'), 0x06);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
