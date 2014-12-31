<?php
//
// Description
// -----------
// This function will return a list of active subscriptions.
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
function ciniki_subscriptions_hooks_subscriptionList($ciniki, $business_id, $args) {

	$strsql = "SELECT id, name, description "
		. "FROM ciniki_subscriptions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND status = 10 "
		. "ORDER BY name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.subscriptions', array(
		array('container'=>'subscriptions', 'fname'=>'id',
			'fields'=>array('id', 'name', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['subscriptions']) ) {
		return array('stat'=>'ok', 'subscriptions'=>array());
	}
	$subscriptions = $rc['subscriptions'];

	return array('stat'=>'ok', 'subscriptions'=>$subscriptions);
}
?>
