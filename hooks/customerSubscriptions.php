<?php
//
// Description
// -----------
// This function will return a list of active subscriptions for a customer.
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
// method:				The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_subscriptions_hooks_customerSubscriptions($ciniki, $business_id, $args) {

	$subscriptions = array();
	if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
		$strsql = "SELECT ciniki_subscriptions.id, "
			. "ciniki_subscriptions.name, "
			. "ciniki_subscriptions.description "
			. "FROM ciniki_subscription_customers, ciniki_subscriptions "
			. "WHERE ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_subscription_customers.subscription_id = ciniki_subscriptions.id "
			. "AND ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_subscription_customers.status = 10 "	// Customer is active subscription
			. "AND ciniki_subscriptions.status = 10 "			// Subscription is active, no archived/deleted
			. "ORDER BY name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.subscriptions', array(
			array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
				'fields'=>array('id', 'name', 'description')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['subscriptions']) ) {
			$subscriptions = $rc['subscriptions'];
		}
	}

	return array('stat'=>'ok', 'subscriptions'=>$subscriptions);
}
?>
