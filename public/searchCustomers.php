<?php
//
// Description
// -----------
// Search active orders and last updated at the top.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_subscriptions_searchCustomers($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'checkAccess');
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.searchCustomers', $args['subscription_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of orders in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT ciniki_subscription_customers.subscription_id, ciniki_subscription_customers.customer_id, "
		. "CONCAT_WS(' ', first, last) AS name "
		. ", ciniki_customers.id "
		. "FROM ciniki_subscription_customers "
		. "LEFT JOIN ciniki_customers ON (ciniki_subscription_customers.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
		. "AND ciniki_subscription_customers.status = 10 "
		. "AND ( ciniki_customers.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%') "
		. "";

	$strsql .= "ORDER BY ciniki_customers.last, ciniki_customers.first ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.subscriptions', 'customers', 'customer', array('stat'=>'ok', 'customers'=>array()));
}
?>
