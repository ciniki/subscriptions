<?php
//
// Description
// -----------
// This method will delete a subscription from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the subscription is attached to.
// subscription_id:         The ID of the subscription to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_subscriptions_subscriptionDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'subscription_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Subscription'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'checkAccess');
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.subscriptionDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the uuid of the subscription to be deleted
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscription']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.6', 'msg'=>'The subscription does not exist'));
    }
    $subscription_uuid = $rc['subscription']['uuid'];

    //
    // Check if any modules are currently using this subscription
    //
    foreach($ciniki['tenant']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'checkObjectUsed');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array(
                'object'=>'ciniki.subscriptions.subscription', 
                'object_id'=>$args['subscription_id'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.7', 'msg'=>'Unable to check if subscription is still be used', 'err'=>$rc['err']));
            }
            if( $rc['used'] != 'no' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.8', 'msg'=>"Subscription is still in use. " . $rc['msg']));
            }
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the connections between mailings and subscriptions
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_mailing_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.mail.mailing_subscription', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
                return $rc; 
            }
        }
    }

    //
    // Remove the customers for the subscription
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_subscription_customers "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.subscriptions.customer', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
                return $rc; 
            }
        }
    }

    //
    // Remove the subscription
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.subscriptions.subscription', 
        $args['subscription_id'], $subscription_uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
