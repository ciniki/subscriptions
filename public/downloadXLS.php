<?php
//
// Description
// -----------
// This function will generate an Excel file from the data in subscriptions_excel_data;
//
// Info
// ----
// Status:              alpha
//
// Arguments
// ---------
// api_key:
// auth_token:      
// excel_id:            The excel ID from the table subscriptions_excel;
//
// Returns
// -------
//
function ciniki_subscriptions_downloadXLS($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
        '_subscription_id'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Subscription'),
        '_subscription_name'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Subscription Name'),
        'customer_id'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Customer'),
        'display_name'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Name'),
        'first'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'First'),
        'last'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Last'),
        'shipping_address'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Shipping Address'),
        'billing_address'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Billing Address'),
        'mailing_address'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Mailing Address'),
        'primary_email'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Primary Email'),
        'alternate_email'=>array('required'=>'no', 'default'=>'No', 'blank'=>'yes', 'name'=>'Alternate Email'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid, and the subscription
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'checkAccess');
    $ac = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.downloadXLS');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Load the subscription information
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    if( $args['mailing_address'] == 'Yes' ) {
        $strsql = "SELECT ciniki_subscriptions.id, "
            . "ciniki_subscriptions.name, "
            . "ciniki_subscriptions.description, "
            . "ciniki_customers.id AS customer_id, "
            . "ciniki_customers.display_name, "
            . "ciniki_customers.first, ciniki_customers.last, "
            . "ciniki_customers.primary_email, "
            . "ciniki_customers.alternate_email, "
            . "ciniki_customer_addresses.address1, ciniki_customer_addresses.address2, "
            . "ciniki_customer_addresses.city, ciniki_customer_addresses.province, "
            . "ciniki_customer_addresses.postal, ciniki_customer_addresses.country "
            . "FROM ciniki_subscriptions, ciniki_subscription_customers, ciniki_customers "
            . "LEFT JOIN ciniki_customer_addresses ON (ciniki_customers.id = ciniki_customer_addresses.customer_id "
                . "AND ciniki_customer_addresses.flags & 0x04 = 0x04 ) "
            . "WHERE ciniki_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
            . "AND ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
            . "AND ciniki_subscription_customers.status = 10 "
            . "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";

    } else {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, "
            . "ciniki_customers.id, CONCAT_WS(' ', prefix, first, middle, last, suffix) AS customer_name, "
            . "ciniki_customers.first, ciniki_customers.last, ciniki_customers.primary_email, ciniki_customers.alternate_email "
            . "FROM ciniki_subscriptions, ciniki_subscription_customers, ciniki_customers "
            . "WHERE ciniki_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
            . "AND ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
            . "AND ciniki_subscription_customers.status = 10 "
            . "AND ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
    }
    //
    // Build array of columns
    //
    $cols = array();
    if( isset($args['_subscription_id']) && $args['_subscription_id'] == 'Yes' ) {
        array_push($cols, array('name'=>'Subscription ID', 'col'=>'id'));
    }
    if( isset($args['_subscription_name']) && $args['_subscription_name'] == 'Yes' ) {
        array_push($cols, array('name'=>'Subscription', 'col'=>'name'));
    }
    if( isset($args['customer_id']) && $args['customer_id'] == 'Yes' ) {
        array_push($cols, array('name'=>'Customer ID', 'col'=>'customer_id'));
    }
    if( isset($args['customer_name']) && $args['customer_name'] == 'Yes' ) {
        array_push($cols, array('name'=>'Customer', 'col'=>'customer_name'));
    }
    if( isset($args['prefix']) && $args['prefix'] == 'Yes' ) {
        array_push($cols, array('name'=>'Prefix', 'col'=>'prefix'));
    }
    if( isset($args['first']) && $args['first'] == 'Yes' ) {
        array_push($cols, array('name'=>'First Name', 'col'=>'first'));
    }
    if( isset($args['middle']) && $args['middle'] == 'Yes' ) {
        array_push($cols, array('name'=>'Middle Name', 'col'=>'middle'));
    }
    if( isset($args['last']) && $args['last'] == 'Yes' ) {
        array_push($cols, array('name'=>'Last Name', 'col'=>'last'));
    }
    if( isset($args['suffix']) && $args['suffix'] == 'Yes' ) {
        array_push($cols, array('name'=>'Suffix', 'col'=>'suffix'));
    }
    if( isset($args['company']) && $args['company'] == 'Yes' ) {
        array_push($cols, array('name'=>'Company', 'col'=>'company'));
    }
    if( isset($args['mailing_address']) && $args['mailing_address'] == 'Yes' ) {
        array_push($cols, array('name'=>'Address 1', 'col'=>'address1'));
        array_push($cols, array('name'=>'Address 2', 'col'=>'address2'));
        array_push($cols, array('name'=>'City', 'col'=>'city'));
        array_push($cols, array('name'=>'Province/State', 'col'=>'province'));
        array_push($cols, array('name'=>'Postal/Zip', 'col'=>'postal'));
        array_push($cols, array('name'=>'Country', 'col'=>'country'));
    }
    if( isset($args['primary_email']) && $args['primary_email'] == 'Yes' ) {
        array_push($cols, array('name'=>'Email', 'col'=>'primary_email'));
    }
    if( isset($args['alternate_email']) && $args['alternate_email'] == 'Yes' ) {
        array_push($cols, array('name'=>'Email', 'col'=>'alternate_email'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFetchHashRow');
    $rc = ciniki_core_dbQuery($ciniki, $strsql, 'ciniki.subscriptions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $result_handle = $rc['handle'];

    // Keep track of new row counter, to avoid deleted rows.
    $result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
    $cur_excel_row = 1;
    $prev_db_row = $result['row']['row'];

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="export.xls"');
    header('Cache-Control: max-age=0');

    //
    // Excel streaming code found at: http://px.sklar.com/code.html/id=488
    //

    echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);

    for($i=0;$i<count($cols);$i++) {
        $len = strlen($cols[$i]['name']);
        echo pack("ssssss", 0x204, 8 + $len, $cur_excel_row-1, $i, 0x0, $len);
        echo $cols[$i]['name'];
    }
    $cur_excel_row++;

    while( isset($result['row']) ) {
        for($i=0;$i<count($cols);$i++) {
            if( $cols[$i]['col'] == 'customer_name' ) {
                $customer_name = preg_replace('/(^\s+|\s+$)/', '', $result['row'][$cols[$i]['col']]);
                $customer_name = preg_replace('/\s\s+/', ' ', $customer_name);
                $len = strlen($customer_name);
                echo pack("ssssss", 0x204, 8 + $len, $cur_excel_row-1, $i, 0x0, $len);
                echo $customer_name;
            } else {
                $len = strlen($result['row'][$cols[$i]['col']]);
                echo pack("ssssss", 0x204, 8 + $len, $cur_excel_row-1, $i, 0x0, $len);
                echo $result['row'][$cols[$i]['col']];
            }
        }
        
        $result = ciniki_core_dbFetchHashRow($ciniki, $result_handle);
        $cur_excel_row++;
    }

    //
    // End the excel file
    //
    echo pack("ss", 0x0A, 0x00);

    exit;

    return array('stat'=>'ok');
}
?>
