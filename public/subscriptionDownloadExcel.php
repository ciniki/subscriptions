<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the members belong to.
//
// Returns
// -------
// A word document
//
function ciniki_subscriptions_subscriptionDownloadExcel(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'subscription_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subscription'), 
        'columns'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Columns'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'checkAccess');
    $rc = ciniki_subscriptions_checkAccess($ciniki, $args['business_id'], 'ciniki.subscriptions.subscriptionDownloadExcel', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];


	require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
	$objPHPExcel = new PHPExcel();

	$strsql = "SELECT ciniki_customers.id, prefix, first, middle, last, suffix, "
		. "company, display_name, "
		. "ciniki_customers.type, "
		. "ciniki_customers.status, "
//		. "CONCAT_WS(': ', ciniki_customer_phones.phone_label, "
//			. "ciniki_customer_phones.phone_number) AS phones, "
		. "ciniki_customer_phones.id AS phone_id, "
		. "ciniki_customer_phones.phone_label, "
		. "ciniki_customer_phones.phone_number, "
		. "ciniki_customer_emails.id AS email_id, "
		. "ciniki_customer_emails.email, "
		. "ciniki_customer_addresses.id AS address_id, "
		. "ciniki_customer_addresses.address1, "
		. "ciniki_customer_addresses.address2, "
		. "ciniki_customer_addresses.city, "
		. "ciniki_customer_addresses.province, "
		. "ciniki_customer_addresses.postal "
//		. "CONCAT_WS(', ', ciniki_customer_addresses.address1, "
//			. "ciniki_customer_addresses.address2, "
//			. "ciniki_customer_addresses.city, "
//			. "ciniki_customer_addresses.province, "
//			. "ciniki_customer_addresses.postal) AS mailing_addresses "
		. "FROM ciniki_subscription_customers "
		. "LEFT JOIN ciniki_customers ON (ciniki_subscription_customers.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customer_phones ON (ciniki_subscription_customers.customer_id = ciniki_customer_phones.customer_id "
			. "AND ciniki_customer_phones.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customer_emails ON (ciniki_subscription_customers.customer_id = ciniki_customer_emails.customer_id "
			. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customer_emails.flags&0x10 = 0 " // Make sure emails are wanted
			. ") "
		. "LEFT JOIN ciniki_customer_addresses ON (ciniki_subscription_customers.customer_id = ciniki_customer_addresses.customer_id "
			. "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_customer_addresses.flags&0x04 > 0 " 	// Mailing addresses only
			. ") "
		. "WHERE ciniki_subscription_customers.subscription_id = '" . ciniki_core_dbQuote($ciniki, $args['subscription_id']) . "' "
		. "AND ciniki_subscription_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_subscription_customers.status = 10 "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
			'fields'=>array('id', 'prefix', 'first', 'middle', 'last', 'suffix',
				'company', 'display_name', 'type', 'status'),
		//		'phones', 'emails', 'mailing_addresses'),
			'maps'=>array(
				'type'=>array('1'=>'Individual', '2'=>'Business'),
				),
			'containers'=>array(
				'phones'=>array('fname'=>'phone_id', 'name'=>'phone',
					'fields'=>array('label'=>'phone_label', 'number'=>'phone_number')),
				'emails'=>array('fname'=>'email_id', 'name'=>'email',
					'fields'=>array('email')),
				'mailing_addresses'=>array('fname'=>'address_id', 'name'=>'address',
					'fields'=>array('address1', 'address2', 'city', 'province', 'postal')),
				)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
//	print_r($rc);
//	exit;
	$objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

	//
	// Add headers
	//
	$row = 1;
	$col = 0;
	foreach($args['columns'] as $column) {
		$value = '';
		switch($column) {
			case 'prefix': $value = 'Prefix'; break;
			case 'first': $value = 'First'; break;
			case 'middle': $value = 'Middle'; break;
			case 'last': $value = 'Last'; break;
			case 'suffix': $value = 'Suffix'; break;
			case 'company': $value = 'Company'; break;
			case 'display_name': $value = 'Name'; break;
			case 'type': $value = 'Type'; break;
			case 'phones': $value = 'Phones'; break;
			case 'emails': $value = 'Emails'; break;
			case 'mailing_addresses': $value = 'Mailing Addresses'; break;
			case 'notes': $value = 'Notes'; break;
			case 'short_bio': $value = 'Short Bio'; break;
		}
		$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $value, false);
		$col++;
	}
	$row++;

	foreach($rc['customers'] as $customer) {
		$col = 0;
		foreach($args['columns'] as $column) {
			if( $column == 'phones' ) {
				$val = '';
				foreach($customer['phones'] as $pid => $phone) {
					$val .= ($val!=''?', ':'') . ($phone['label']!=''?($phone['label'] . ': '):'') . $phone['number'];
				}
				$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $val, false);
//				$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true);
			}
			elseif( $column == 'emails' ) {
				$val = '';
				foreach($customer['emails'] as $eid => $email) {
					$val .= ($val!=''?', ':'') . $email['email'];
				}
				$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $val, false);
			}
			elseif( $column == 'mailing_addresses' ) {
				$val = '';	
				foreach($customer['mailing_addresses'] as $mid => $address) {
					$addr = '';
					if( $address['address1'] != '' ) { $addr .= ($addr!=''?', ':'') . $address['address1']; }
					if( $address['address2'] != '' ) { $addr .= ($addr!=''?', ':'') . $address['address2']; }
					if( $address['city'] != '' ) { $addr .= ($addr!=''?', ':'') . $address['city']; }
					if( $address['province'] != '' ) { $addr .= ($addr!=''?', ':'') . $address['province']; }
					if( $address['postal'] != '' ) { $addr .= ($addr!=''?', ':'') . $address['postal']; }
					if( $addr != '' ) {
						$val .= ($val!=''?', ':'') . $addr;
					}
				}
				$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $val, false);
			}
			elseif( isset($customer[$column]) ) {
				$objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $customer[$column], false);
			}
			$col++;
		}
		$row++;
	}

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
