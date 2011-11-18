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
// 
// user_id: 		The user making the request
// 
// Returns
// -------
// <subscriptions>
//	<subscription id="" name="" description="" count='13' />
// </subscriptions>
//
function ciniki_subscriptions_stats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/subscriptions/private/checkAccess.php');
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.stats', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of orders in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT ciniki_subscriptions.id, name, description, COUNT(ciniki_subscription_customers.customer_id) AS count "
		. "FROM ciniki_subscriptions "
		. "LEFT JOIN ciniki_subscription_customers ON (subscriptions.id = subscription_customers.subscription_id AND ciniki_subscription_customers.status = 1) "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "GROUP BY ciniki_subscriptions.id "
		. "ORDER BY name "
		. "";
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'subscriptions', 'subscriptions', 'subscription', array('stat'=>'ok', 'subscriptions'=>array()));
    if( $rc['stat'] != 'ok' ) { 
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'386', 'msg'=>'Unable to retrieve subscriptions', 'err'=>$rc['err']));
    }
	if( !isset($rc['subscriptions']) ) {
		return array('stat'=>'ok', 'subscriptions'=>array());
	}

	return array('stat'=>'ok', 'subscriptions'=>$rc['subscriptions']);
}
?>
