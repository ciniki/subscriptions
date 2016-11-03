<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// 
// Returns
// -------
//
function ciniki_subscriptions_cron_jobs($ciniki) {

    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for ciniki.subscriptions jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');

    //
    // Get the list of signups that can be deleted after 24 hours
    //
    $strsql = "DELETE FROM ciniki_subscription_signups WHERE date_added < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR) ";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) {
        ciniki_cron_logMsg($ciniki, 0, array('code'=>'ciniki.subscriptions.12', 'msg'=>'Unable to remove 24 hour old subscription signups.',
            'severity'=>50, 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
