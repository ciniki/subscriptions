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
function ciniki_subscriptions_downloadMailMerge($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid, and the subscription
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'checkAccess');
    $ac = ciniki_subscriptions_checkAccess($ciniki, $args['tnid'], 'ciniki.subscriptions.downloadMailMerge');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Load the subscription information
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT ciniki_subscriptions.id, "
        . "ciniki_subscriptions.name, "
        . "ciniki_subscriptions.description, "
        . "ciniki_customers.id AS customer_id, "
        . "ciniki_customers.display_name, "
        . "ciniki_customer_addresses.address1, ciniki_customer_addresses.address2, "
        . "ciniki_customer_addresses.city, ciniki_customer_addresses.province, "
        . "ciniki_customer_addresses.postal, ciniki_customer_addresses.country "
        . "FROM ciniki_subscriptions "
        . "INNER JOIN ciniki_subscription_customers ON ("
            . "ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_subscription_customers.status = 10 "
            . ") "
        . "INNER JOIN ciniki_customers ON ("
            . "ciniki_subscription_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_customer_addresses ON ("
            . "ciniki_customers.id = ciniki_customer_addresses.customer_id "
            . "AND (ciniki_customer_addresses.flags&0x04) = 0x04 "
            . ") "
        . "WHERE ciniki_subscriptions.id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
        . "AND ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.4', 'msg'=>'No customers found'));
    }
    $addresses = $rc['rows'];

    //
    // Create the excel file
    //
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $row = 0;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow(0, $row, 'Name', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow(1, $row, 'Line1', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow(2, $row, 'Line2', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow(3, $row, 'Line3', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow(4, $row, 'Line4', false);
    $objPHPExcelWorksheet->getStyle('A1:E1')->getFont()->setBold(true);
    $objPHPExcelWorksheet->freezePane('A2');

    $row++;

    foreach($addresses as $customer) {
        $objPHPExcelWorksheet->setCellValueByColumnAndRow(0, $row, $customer['display_name'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow(1, $row, $customer['address1'], false);
        $col = 2;
        if( $row['address2'] != '' ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['address2'], false);
        }
        if( $customer['city'] != '' || $customer['province'] != '' || $customer['postal'] != '' ) {
            $value = '';
            if( $customer['city'] != '' ) {
                $value .= $customer['city'];
            }
            if( $customer['province'] != '' ) {
                $value .= ($value != ''?', ':'') . $customer['province'];
            }
            if( $customer['postal'] != '' ) {
                $value .= ($value != ''?'  ':'') . $customer['postal'];
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $value, false);
        }
        if( $row['country'] != '' ) {
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['country'], false);
        }
        $row++;
    }

    $col = 0;
    PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
    $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
    $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);

    //
    // Redirect output to a client’s web browser (Excel)
    //
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="export.xls"');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    return array('stat'=>'exit');
}
?>
