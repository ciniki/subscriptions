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
function ciniki_subscriptions_web_accountProcessRequest($ciniki, $settings, $business_id, $args) {

    $page = array(
        'title'=>'Mailing Lists',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );
    $page['breadcrumbs'][] = array('name'=>'Mailing Lists', 'url'=>$ciniki['request']['domain_base_url'] . '/account/subscriptions');

    $base_url = $args['base_url'];

    //
    // Load the settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_subscriptions_settings', 'business_id', $business_id, 'ciniki.subscriptions', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']) ) {
        $settings = $rc['settings'];
    } else {
        $settings = array();
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'subscribe');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'unsubscribe');

    //
    // Get the list of subscription
    //
	if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
		$strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, "
			. "IF(ciniki_subscription_customers.status=10, 'yes', 'no') AS subscribed "
			. "FROM ciniki_subscriptions "
			. "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
				. "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "') "
			. "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
			. "";
	} else {
		$strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, '0' AS subscribed "
			. "FROM ciniki_subscriptions "
			. "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
			. "";
	}

	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.subscriptions', array(
		array('container'=>'subscriptions', 'fname'=>'id',
			'fields'=>array('id', 'name', 'description', 'subscribed')),
		));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['subscriptions']) ) {
        $subscriptions = $rc['subscriptions'];
    } else {
        $subscriptions = array();
    }

    //
    // Check for a form submit with subscribe/unsubscribe
    //
    if( isset($_POST['action']) && $_POST['action'] == 'update' ) {
        foreach($subscriptions as $snum => $subscription) {
            $sid = $subscription['id'];
            // Check if the subscribed to the subscription
            if( isset($_POST["subscription-$sid"]) && $_POST["subscription-$sid"] == $sid ) {
                if( $subscription['subscribed'] == 'no' ) {
                    ciniki_subscriptions_web_subscribe($ciniki, $settings, $business_id, $sid, $ciniki['session']['customer']['id']);
                    $subscription_err_msg = 'Your subscriptions have been updated.';
                    $subscriptions[$snum]['subscribed'] = 'yes';
                }
            } else {
                if( $subscription['subscribed'] == 'yes' ) {
                    ciniki_subscriptions_web_unsubscribe($ciniki, $settings, $business_id, $sid, $ciniki['session']['customer']['id']);
                    $subscription_err_msg = 'Your subscriptions have been updated.';
                    $subscriptions[$snum]['subscribed'] = 'no';
                }
            }
        }
    }

    //
    // Check for an introduction message
    //
    if( isset($settings['page-account-intro-message']) && $settings['page-account-intro-message'] != '' ) {
        $page['blocks'][] = array('type'=>'message', 'content'=>$settings['page-account-intro-message']);
    }

    if( isset($subscription_err_msg) && $subscription_err_msg != '' ) {
        $page['blocks'][] = array('type'=>'formmessage', 'level'=>'success', 'message'=>$subscription_err_msg);
    }

    //
    // Show the list of subscriptions available for the subscribe/unsubscribe
    //
    $content = "<form action='' method='POST'>";
    $content .= "<input type='hidden' name='action' value='update'/>";
    foreach($subscriptions as $snum => $subscription) {
        $sid = $subscription['id'];
        $content .= "<input id='subscription-$sid' type='checkbox' class='checkbox' name='subscription-$sid' value='$sid' ";
        if( $subscription['subscribed'] == 'yes' ) {
            $content .= " checked";
        }
        $content .= "/>";
        $content .= " <label class='checkbox' for='subscription-$sid'>" . $subscription['name'] . "</label><br/>";
    }
    $content .= "<div class='submit'><input type='submit' class='submit' value='Save Changes'></div>\n";
    $content .= "</form>\n";

    $page['blocks'][] = array('type'=>'content', 'content'=>$content);


	return array('stat'=>'ok', 'page'=>$page);
}
?>
