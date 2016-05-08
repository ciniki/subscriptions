<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_subscriptions_web_processRequest(&$ciniki, $settings, $business_id, $args) {

    $page = array(
        'title'=>'Mailing List',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );
    $page['breadcrumbs'][] = array('name'=>'Mailing Lists', 'url'=>$ciniki['request']['domain_base_url'] . '/subscriptions');

    //
    // Load the public mailing lists
    //
    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, "
            . "IF(ciniki_subscription_customers.status=10, 'yes', 'no') AS subscribed "
            . "FROM ciniki_subscriptions "
            . "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
                . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "') "
            . "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
    } else {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, 'no' AS subscribed "
            . "FROM ciniki_subscriptions "
            . "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.subscriptions', array(
        array('container'=>'subscriptions', 'fname'=>'id',
            'fields'=>array('id', 'name', 'description', 'subscribed')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscriptions']) || count($rc['subscriptions']) == 0 ) {
        return array('stat'=>'ok', 'blocks'=>array());
    }
    $subscriptions = $rc['subscriptions'];

    if( count($subscriptions) > 1 ) {
        $page['title'] = 'Mailing Lists';
    }

    $display = 'list';
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'confirm' && isset($_GET['k']) && $_GET['k'] != '' ) {
        $errors = 'no';
        $strsql = "SELECT signup_data "
            . "FROM ciniki_subscription_signups "
            . "WHERE signup_key = '" . ciniki_core_dbQuote($ciniki, $_GET['k']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'signup');
        if( $rc['stat'] != 'ok' ) {
            $errors = 'yes';
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Oops, we are having problems with your signup.  Please try again or contact us for help.');
        }
        if( !isset($rc['signup']) ) {
            $errors = 'yes';
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Confirmation messages are only valid for 24 hours, you will need to signup again.');
        }
        if( $errors == 'no' ) {
            $signup_data = unserialize($rc['signup']['signup_data']);

            ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'subscribe');
            //
            // Check if email already exists
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerLookup');
            $rc = ciniki_customers_hooks_customerLookup($ciniki, $business_id, array('email'=>$signup_data['email']));
            if( $rc['stat'] == 'ok' && isset($rc['customers']) ) {
                //
                // Make sure their subscription is enabled
                //
                foreach($rc['customers'] as $customer_id => $customer) {
                    foreach($signup_data['subscriptions'] as $sid => $v) {
                        $rc = ciniki_subscriptions_web_subscribe($ciniki, $settings, $business_id, $sid, $customer_id);
                        if( $rc['stat'] != 'ok' ) {
                            $errors = 'multi';
                            error_log("WEB-SUBSCRIPTIONS: Unable to subscribe customer $customer_id to subscription $sid");
                        }
                    }
                }
            } else {
                //
                // Create customer
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'web', 'customerAdd');
                $rc = ciniki_customers_web_customerAdd($ciniki, $business_id, array(
                    'name'=>$signup_data['name'],
                    'email_address'=>$signup_data['email'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    $errors = 'yes';
                    error_log("WEB-SUBSCRIPTIONS: Unable to add customer " . $signup_data['name'] . ' (' . $signup_data['email'] . ')');
                }
                $customer_id = $rc['id'];

                //
                // Add them to the subscription
                //
                if( $errors == 'no' ) {
                    foreach($signup_data['subscriptions'] as $sid => $v) {
                        $rc = ciniki_subscriptions_web_subscribe($ciniki, $settings, $business_id, $sid, $customer_id);
                        if( $rc['stat'] != 'ok' ) {
                            $errors = 'multi';
                            error_log("WEB-SUBSCRIPTIONS: Unable to subscribe customer $customer_id to subscription $sid");
                        }
                    }
                }
            }

            if( $errors == 'no' ) {
                $display = 'no';
                $page['blocks'][] = array('type'=>'formmessage', 'level'=>'success', 'message'=>'You have been subscribed.');
                $strsql = "DELETE FROM ciniki_subscription_signups "
                    . "WHERE signup_key = '" . ciniki_core_dbQuote($ciniki, $_GET['k']) . "' "
                    . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
                $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.subscriptions');
            }
        }
    }
  
    if( $display == 'list' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'subscriptionManager');
        $rc = ciniki_subscriptions_web_subscriptionManager($ciniki, $settings, $business_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['blocks']) ) {
            foreach($rc['blocks'] as $bid => $block) {
                if( isset($rc['blocks'][$bid]['title']) ) {
                    unset($rc['blocks'][$bid]['title']);
                }
            }
            $page['blocks'] = array_merge($page['blocks'], $rc['blocks']);
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
