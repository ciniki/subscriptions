<?php
//
// Description
// -----------
// This function will add a new subscription to the subscriptions module.
//
// Info
// ----
// Status:          defined
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_subscriptions_subscriptionAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Flags'),
        'description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Description'), 
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
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.subscriptionAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Add the subscription
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.subscriptions.subscription', $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $subscription_id = $rc['id'];

    // 
    // Update the web settings
    //
    if( isset($modules['ciniki.web']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'settingsUpdateSubscriptions');
        $rc = ciniki_web_settingsUpdateSubscriptions($ciniki, $modules, $args['business_id']);
        if( $rc['stat'] != 'ok' ) {
            // Don't return error code to user, they successfully updated the record
            error_log("ERR: " . $rc['code'] . ' - ' . $rc['msg']);
        }
    }

    return array('stat'=>'ok', 'id'=>$subscription_id);
}
?>
