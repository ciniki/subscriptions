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
    $strsql = "SELECT COUNT(*) AS num "
        . "FROM ciniki_subscriptions "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND status = 10 "
        . "AND (flags&0x01) = 0x01 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'total');
    if( $rc['stat'] == 'ok' && isset($rc['total']['num']) && $rc['total']['num'] > 0 ) {
        $submenu[] = array('name'=>'Mailing Lists', 'priority'=>400, 
            'package'=>'ciniki', 'module'=>'subscriptions', 
            'selected'=>($ciniki['request']['page'] == 'account' && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'subscriptions')?'yes':'no',
            'url'=>$ciniki['request']['base_url'] . '/account/subscriptions');
    }


    return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
