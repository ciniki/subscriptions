<?php
//
// Description
// -----------
// This function returns a list of email addresses to be used for sending emails to customers.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_subscriptions_emailList($ciniki, $business_id, $subscription_ids) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

	//
	// Pull the list of email addresses which we are allowed to send emails to.
	//
	$strsql = "SELECT DISTINCT ciniki_customers.id AS customer_id, "
		. "CONCAT_WS(' ', ciniki_customers.first, ciniki_customers.last) AS customer_name, "
		. "ciniki_customer_emails.email, ciniki_subscriptions.uuid AS subscription_uuid "
		. "FROM ciniki_subscription_customers, ciniki_subscriptions, ciniki_customers, ciniki_customer_emails "
		. "WHERE ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_subscription_customers.subscription_id IN ('" . ciniki_core_dbQuoteIDs($ciniki, $subscription_ids) . "') "
		. "AND ciniki_subscription_customers.status = 10 "
		. "AND ciniki_subscription_customers.subscription_id = ciniki_subscriptions.id "
		. "AND ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
		. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_customers.id = ciniki_customer_emails.customer_id "
		. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_customer_emails.flags&0x30) = 0 "	// Only emals that are ok to send to
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'email');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['rows']) ) {
		return array('stat'=>'ok', 'emails'=>array());
	}
	return array('stat'=>'ok', 'emails'=>$rc['rows']);	
}
?>