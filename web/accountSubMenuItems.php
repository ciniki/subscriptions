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
function ciniki_subscriptions_web_accountSubMenuItems($ciniki, $settings, $business_id) {

    $submenu = array();

    //
    // FIXME: Check if any public subscriptions available for company
    //

    $submenu[] = array('name'=>'Mailing Lists', 'priority'=>400, 
        'package'=>'ciniki', 'module'=>'subscriptions', 
        'selected'=>($ciniki['request']['page'] == 'account' && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'subscriptions')?'yes':'no',
        'url'=>$ciniki['request']['base_url'] . '/account/subscriptions');

	return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
