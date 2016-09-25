<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_subscriptions_hooks_uiSettings($ciniki, $business_id, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());  

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['business']['modules']['ciniki.subscriptions'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3200,
            'label'=>'Subscriptions', 
            'edit'=>array('app'=>'ciniki.subscriptions.main'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    if( isset($ciniki['business']['modules']['ciniki.subscriptions'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>3200, 'label'=>'Subscriptions', 'edit'=>array('app'=>'ciniki.subscriptions.settings'));
    }

    return $rsp;
}
?>
