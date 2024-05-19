<?php
//
// Description
// -----------
//
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_subscriptions_wng_unsubscribe(&$ciniki, $tnid, $request, $subscription_id, $customer_id) {

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if already in table
    //
    $strsql = "SELECT ciniki_subscription_customers.id, "
        . "ciniki_subscription_customers.status, "
        . "ciniki_subscriptions.id, "
        . "ciniki_subscriptions.name, "
        . "ciniki_subscriptions.flags, "
        . "CONCAT_WS(' ', ciniki_customers.first, ciniki_customers.last) AS customer_name "
        . "FROM ciniki_subscription_customers "
        . "LEFT JOIN ciniki_subscriptions ON ("
            . "ciniki_subscription_customers.subscription_id = ciniki_subscriptions.id "
            . "AND ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $subscription_id) . "' "
        . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $db_updated = 0;
    if( isset($rc['subscription']) ) {
        $customer_subscription_id = $rc['subscription']['id'];
        $subscription = $rc['subscription'];
        if( $rc['subscription']['status'] < 60 ) {
            //
            // Check if public subscription
            //
            if( (($rc['subscription']['flags'])&0x01) == 1 ) {
                $strsql = "UPDATE ciniki_subscription_customers SET status = 60 "
                    . "WHERE subscription_id = '" . ciniki_core_dbQuote($ciniki, $subscription_id) . "' "
                    . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
                if( $rc['stat'] != 'ok' ) { 
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $tnid, 
                    2, 'ciniki_subscription_customers', $customer_subscription_id, 'status', '60');
                $db_updated = 1;
            } else {
                //
                //  Email the owners a bug was added to the system.
                //
                $strsql = "SELECT user_id FROM ciniki_tenant_users "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND package = 'ciniki' "
                    . "AND (permission_group = 'owners') "
                    . "AND status = 10 "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
                $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.bugs', 'user_ids', 'user_id');
                if( $rc['stat'] != 'ok' || !isset($rc['user_ids']) || !is_array($rc['user_ids']) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.11', 'msg'=>'Unable to find tenant owners', 'err'=>$rc['err']));
                }
                error_log("SUBSCRIPTIONS: Unsubscribe customer {$subscription['customer_name']} from {$subscription['name']}\n");
                
                foreach($rc['user_ids'] as $user_id) {
                    // 
                    // Don't email the submitter, they will get a separate email
                    //
                    $ciniki['emailqueue'][] = array('user_id'=>$user_id,
                        'subject'=>$subscription['customer_name'] . ' requested unsubscribe from ' . $subscription['name'],
                        'textmsg'=>$subscription['customer_name'] . ' has requested to be removed from the '
                            . 'private subscription list ' . $subscription['name'] . '.  Please remove them '
                            . 'as they are unable to do this themselves. '
                            . '',
                        );
                }
            }

            //
            // Email the customer to let them know there were unsubscribed
            //
        }
    } else {
        //
        // Get a uuid
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $uuid = $rc['uuid'];

        //
        // Update the customers subscription
        //
        $strsql = "INSERT INTO ciniki_subscription_customers (uuid, tnid, subscription_id, customer_id, status, date_added, last_updated "
            . ") VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $subscription_id) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, '60') . "', "
            . "UTC_TIMESTAMP(), UTC_TIMESTAMP() "
            . ") "
            . "";
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.subscriptions');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
            return $rc;
        }
        $customer_subscription_id = $rc['insert_id'];

        //
        // Log the change in the database
        //
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $tnid, 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'uuid', $uuid);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $tnid, 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'subscription_id', $subscription_id);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $tnid, 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'customer_id', $customer_id);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.subscriptions', 'ciniki_subscription_history', $tnid, 
            1, 'ciniki_subscription_customers', $customer_subscription_id, 'status', '60');
        $db_updated = 1;
    }


    //
    // Update the last_updated for the subscription
    //
    if( $db_updated > 0 ) {
        $strsql = "UPDATE ciniki_subscriptions SET last_updated = UTC_TIMESTAMP() "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $subscription_id) . "' ";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.subscriptions');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.subscriptions');
            return $rc;
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    if( $db_updated > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'subscriptions');

        $ciniki['syncqueue'][] = array('push'=>'ciniki.subscriptions.customer', 
            'args'=>array('id'=>$customer_subscription_id));
    }

    return array('stat'=>'ok');
}
?>
