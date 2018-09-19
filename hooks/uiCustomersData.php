<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_subscriptions_hooks_uiCustomersData($ciniki, $tnid, $args) {

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'maps');
    $rc = ciniki_subscriptions_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Setup for how to query customers
    //
    $customer_id_sql = '';
    if( isset($args['customer_id']) ) {
        $customer_id_sql .= "AND subcustomers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $customer_id_sql .= "AND subcustomers.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    
    //
    // Get the list of subscriptions
    //
    $strsql = "SELECT subs.id, "
        . "subs.name, "
        . "subcustomers.id AS customer_subscription_id, "
        . "subs.description, "
        . "subcustomers.status, "
        . "IFNULL(customers.display_name, '') AS display_name "
        . "FROM ciniki_subscriptions AS subs "
        . "LEFT JOIN ciniki_subscription_customers AS subcustomers ON ("
            . "subs.id = subcustomers.subscription_id "
            . $customer_id_sql
            . "AND subcustomers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "subcustomers.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE subs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY subs.name, customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription', 'fields'=>array('id', 'name', 'description', 'status', 'display_name'), 'dlists'=>array('display_name'=>'<br/>')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['subscriptions']) && count($rc['subscriptions']) > 0 ) {
        //
        // Process the list
        //
        if( isset($args['customer_id']) || (isset($args['customer_ids']) && count($args['customer_ids']) == 1) ) {
            foreach($rc['subscriptions'] as $sid => $sub) {
                if( $sub['status'] == 10 ) {
                    $rc['subscriptions'][$sid]['display_name'] = 'Subscribed';
                } else if( $sub['status'] == 60 ) {
                    $rc['subscriptions'][$sid]['display_name'] = 'Removed';
                } else {
                    $rc['subscriptions'][$sid]['display_name'] = 'Not Subscribed';
                }
            }
        }


        $sections = array(
            'subscriptions' => array(
                'label' => 'Subscriptions',
                'type' => 'simplegrid', 
                'num_cols' => 2,
                'headerValues' => array('Subscription', 'Name'),
                'cellClasses' => array('aligntop', ''),
                'noData' => 'No subscriptions',
//                'editApp' => array('app'=>'ciniki.fatt.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
                'cellValues' => array(
                    '0' => "d.name",
                    '1' => "d.display_name",
                    ),
                'data' => $rc['subscriptions'],
                ),
            );
        $rsp['tabs'][] = array(
            'id' => 'ciniki.subscriptions.lists',
            'label' => 'Subscriptions',
            'sections' => $sections,
            );
    } else {
        return array('stat'=>'ok');
    }

    return $rsp;
}
?>
