<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Arguments
// ---------
// ciniki:
// business_id: 		The ID of the business the request is for.
// method:				The requested method.
// subscription_id:		The ID of the subscription the request is for.  Only checked if 
//						subscription_id is specified and greater than zero.
// 
// Returns
// -------
//
function ciniki_subscriptions_checkAccess($ciniki, $business_id, $method, $subscription_id) {
	//
	// Check if the business is active and the module is enabled
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkModuleAccess.php');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'subscriptions');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['ruleset']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'382', 'msg'=>'No permissions granted'));
	}
	$modules = $rc['modules'];

	//
	// Load the rulesets for this module
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/subscriptions/private/getRulesets.php');
	$rulesets = ciniki_subscriptions_getRuleSets($ciniki);

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// Check to see if the ruleset is valid
	//
	if( !isset($rulesets[$rc['ruleset']]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'383', 'msg'=>'Access denied.'));
	}
	$ruleset = $rc['ruleset'];

	// 
	// Get the rules for the specified method
	//
	$rules = array();
	if( isset($rulesets[$ruleset]['methods']) && isset($rulesets[$ruleset]['methods'][$method]) ) {
		$rules = $rulesets[$ruleset]['methods'][$method];
	} elseif( isset($rulesets[$ruleset]['default']) ) {
		$rules = $rulesets[$ruleset]['default'];
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'384', 'msg'=>'Access denied.'));
	}


	//
	// Check the subscription_id is attached to the business
	//
	if( $subscription_id > 0 ) {
		$strsql = "SELECT id, business_id FROM ciniki_subscriptions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $subscription_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// If nothing was returned, deny
		// if business_id is not the same, deny (extra check)
		// if subscription id is not the same, deny (extra check)
		//
		if( !isset($rc['subscription']) 
			|| $rc['subscription']['business_id'] != $business_id 
			|| $rc['subscription']['id'] != $subscription_id ) {
			// Access denied!
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'388', 'msg'=>'Access denied'));
		}
	}

	//
	// Apply the rules.  Any matching rule will allow access.
	//

	//
	// If business_group specified, check the session user in the business_users table.
	//
	if( isset($rules['permission_groups']) && $rules['permission_groups'] > 0 ) {
		//
		// If the user is attached to the business AND in the one of the accepted permissions group, they will be granted access
		//
		$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND CONCAT_WS('.', package, permission_group) IN ('" . implode("','", $rules['permission_groups']) . "') "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'385', 'msg'=>'Access denied.', 'err'=>$rc['err']));
		}
		
		//
		// If the user has permission, return ok
		//
		if( isset($rc['rows']) && isset($rc['rows'][0]) 
			&& $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
			return array('stat'=>'ok');
		}
	}

	//
	// Default, return fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'510', 'msg'=>'Access denied'));
}
?>
