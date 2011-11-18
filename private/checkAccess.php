<?php
//
// Description
// -----------
// This function will validate the user making the request has the 
// proper permissions to access or change the data.  This function
// must be called by all public API functions to ensure security.
//
// Info
// ----
// Status: 			beta
//
// Arguments
// ---------
// business_id: 		The ID of the business the request is for.
// 
// Returns
// -------
//
function ciniki_subscriptions_checkAccess($ciniki, $business_id, $method, $subscription_id) {

	//
	// Load the rulesets for this module
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/subscriptions/private/getRulesets.php');
	$rulesets = ciniki_subscriptions_getRuleSets($ciniki);

	//
	// Check if the module is turned on for the business
	// Check the business is active
	// Get the ruleset for this module
	//
	$strsql = "SELECT ruleset FROM businesses, business_modules "
		. "WHERE businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND businesses.status = 1 "														// Business is active
		. "AND businesses.id = business_modules.business_id "
		. "AND business_modules.package = 'ciniki' "
		. "AND business_modules.module = 'subscriptions' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'module');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['module']) || !isset($rc['module']['ruleset']) || $rc['module']['ruleset'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'382', 'msg'=>'Access denied.'));
	}

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// Check to see if the ruleset is valid
	//
	if( !isset($rulesets[$rc['module']['ruleset']]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'383', 'msg'=>'Access denied.'));
	}
	$ruleset = $rc['module']['ruleset'];

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
		$strsql = "SELECT id, business_id FROM subscriptions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $subscription_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'subscriptions', 'subscription');
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
		//
		// The subscription is attached to the specified business
		//
	}


	//
	// Apply the rules.  Any matching rule will allow access.
	//


	//
	// If business_group specified, check the session user in the business_users table.
	//
	if( isset($rules['business_group']) && $rules['business_group'] > 0 ) {
		//
		// Compare the session users bitmask, with the bitmask specified in the rules
		// If when OR'd together, any bits are set, they have access.
		//
		$strsql = sprintf("SELECT business_id, user_id FROM business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND (groups & 0x%x) > 0 ", ciniki_core_dbQuote($ciniki, $rules['business_group']));
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// Double check business_id and user_id match, for single row returned.
		//
		if( !isset($rc['user']) 
			|| !isset($rc['user']['business_id']) 
			|| $rc['user']['business_id'] != $business_id 
			|| $rc['user']['user_id'] != $ciniki['session']['user']['id'] ) {
			// Access Granted!
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'385', 'msg'=>'Access denied.'));
		}
	}

	//
	// If all the test passed, then return OK
	//
	return array('stat'=>'ok');
}
?>
