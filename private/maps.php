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
function ciniki_subscriptions_maps($ciniki) {

    $maps = array();
    $maps['subscription_customer'] = array(
        'status'=>array(
            '0'=>'Unknown',
            '2'=>'Pending',
            '10'=>'Subscribed',
            '60'=>'Removed',
            ),
        );
    
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
