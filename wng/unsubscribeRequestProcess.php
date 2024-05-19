<?php
//
// Description
// -----------
// This function will process the request from a unsubscribe click in an email
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_subscriptions_wng_unsubscribeRequestProcess(&$ciniki, $tnid, &$request) {

    $request['breadcrumbs'][] = array(
        'page-class' => 'page-mail',
        'title' => 'Unsubscribe',
        'url' => "{$request['ssl_domain_base_url']}/mail/subscriptions",
        );

    $blocks = array();

    $limit_width = 'limit-width limit-width-60';

    if( isset($_GET['e']) && $_GET['e'] != '' 
        && isset($_GET['s']) && $_GET['s'] != ''
        && isset($_GET['k']) && $_GET['k'] != ''
        ) {
        //
        // Get the information about the customer, from the link provided in the email.  The
        // email must be less than 30 days since it was sent for the link to still be active
        //
        $strsql = "SELECT subcust.id AS id, "
            . "subcust.customer_id, "
            . "subcust.status, "
            . "customers.display_name, "
            . "subscriptions.name, "
            . "subscriptions.flags, "
            . "subscriptions.description, "
            . "subscriptions.notify_emails "
            . "FROM ciniki_mail AS mail "
            . "INNER JOIN ciniki_subscription_customers AS subcust ON ("
                . "mail.customer_id = subcust.customer_id "
                . "AND subcust.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_subscriptions AS subscriptions ON ("
                . "subcust.subscription_id = subscriptions.id "
                . "AND subscriptions.uuid = '" . ciniki_core_dbQuote($ciniki, $_GET['s']) . "' "
                . "AND subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_customer_emails AS emails ON ("
                . "subcust.customer_id = emails.customer_id "
                . "AND emails.email = '" . ciniki_core_dbQuote($ciniki, $_GET['e']) . "' "
                . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "emails.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE mail.unsubscribe_key = '" . ciniki_core_dbQuote($ciniki, $_GET['k']) . "' "
            . "AND mail.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.subscriptions', 'subscription');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.subscriptions.15', 'msg'=>'Unable to load subscription', 'err'=>$rc['err']));
        }
        if( !isset($rc['subscription']) ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => $limit_width,
                'level' => 'error', 
                'content' => 'Invalid subscription',
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }
        $subscription = $rc['subscription'];

        //
        // Check if current unsubscribed
        //
        if( $subscription['status'] == 60 ) {
            $blocks[] = array(
                'type' => 'msg',
                'class' => $limit_width,
                'level' => 'success',
                'content' => 'You are already removed from ' . $subscription['name'],
                );
            $blocks[] = array(
                'type' => 'buttons',
                'class' => 'aligncenter ' . $limit_width,
                'items' => array(
                    array('text' => 'Continue', 'url' => "{$request['ssl_domain_base_url']}/"),
                    ),
                );
            return array('stat'=>'ok', 'blocks'=>$blocks);
        }

        //
        // Deal with public subscriptions
        //
        elseif( ($subscription['flags']&0x01) == 0x01 ) {

            if( isset($_POST['f-action']) && $_POST['f-action'] == 'unsubscribe' ) {
                if( !isset($_POST['f-confirm']) || $_POST['f-confirm'] != 'on' ) {
                    $blocks[] = array(
                        'type' => 'msg',
                        'class' => $limit_width,
                        'level' => 'error',
                        'content' => 'You must click on the checkbox to confirm',
                        );
                }
                else {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.subscriptions.customer', $subscription['id'], array(
                        'status' => 60,
                        ), 0x07);
                    if( $rc['stat'] != 'ok' ) {
                        $blocks[] = array(
                            'type' => 'msg',
                            'class' => $limit_width,
                            'level' => 'error',
                            'content' => 'Internal Error: Please contact us to get removed from this subscription',
                            );
                        return array('stat'=>'ok', 'blocks'=>$blocks);
                    }
                    $blocks[] = array(
                        'type' => 'msg',
                        'class' => $limit_width,
                        'level' => 'success',
                        'content' => 'You have been removed from ' . $subscription['name'] . '.',
                        );
                    $blocks[] = array(
                        'type' => 'buttons',
                        'class' => 'aligncenter ' . $limit_width,
                        'items' => array(
                            array('text' => 'Continue', 'url' => "{$request['ssl_domain_base_url']}/"),
                            ),
                        );
                    return array('stat'=>'ok', 'blocks'=>$blocks);
                }
                 
            }

            $blocks[] = array(
                'type' => 'text',
                'class' => $limit_width,
                'title' => 'Unsubscribe from ' . $subscription['name'],
                'content' => 'If you would like to be removed from ' . $subscription['name'] . ' please confirm and click "Unsubscribe"',
                );
            $blocks[] = array(
                'type' => 'form',
                'class' => $limit_width,
                'submit-label' => 'Unsubscribe',
                'fields' => array(
                    'action' => array(
                        'id' => 'action',
                        'ftype' => 'hidden',
                        'value' => 'unsubscribe',
                        ),
                    'confirm' => array(
                        'id' => 'confirm',
                        'ftype' => 'checkbox',
                        'label' => 'Yes I want to unsubscribe from ' . $subscription['name'],
                        ),
                    ),
                );
        

        } 
        //
        // Private subscriptions
        //
        else {
            
            //
            // Check if form submited and process unsubscribe
            //
            if( isset($_POST['f-action']) && $_POST['f-action'] == 'unsubscribe' ) {
                if( !isset($_POST['f-confirm']) || $_POST['f-confirm'] != 'on' ) {
                    $blocks[] = array(
                        'type' => 'msg',
                        'class' => $limit_width,
                        'level' => 'error',
                        'content' => 'You must click on the checkbox to confirm',
                        );
                }
                else {
                    //
                    // Email the owners about the unsubscribe request
                    //
                    $strsql = "SELECT user_id "
                        . "FROM ciniki_tenant_users "
                        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . "AND package = 'ciniki' "
                        . "AND (permission_group = 'owners') "
                        . "AND status = 10 "
                        . "";
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
                    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.bugs', 'user_ids', 'user_id');
                    if( $rc['stat'] != 'ok' || !isset($rc['user_ids']) || !is_array($rc['user_ids']) ) {
                        $blocks[] = array(
                            'type' => 'msg',
                            'class' => $limit_width,
                            'level' => 'error',
                            'content' => 'Internal Error: Please contact us to get removed from this subscription',
                            );
                        return array('stat'=>'ok', 'blocks'=>$blocks);
                    }
                    
                    $email_sent = 'no';
                    $email_subject = $subscription['display_name'] . ' requested unsubscribe from ' . $subscription['name'];
                    $email_text = $subscription['display_name'] . ' has requested to be removed from the '
                        . 'private subscription list ' . $subscription['name'] . '.  Please remove them '
                        . 'as they are unable to do this themselves. '
                        . "\n\nReason: " 
                        . (isset($_POST['f-reason']) && $_POST['f-reason'] != '' ? $_POST['f-reason'] : 'None given')
                        . '';
                    if( isset($subscription['notify_emails']) && $subscription['notify_emails'] != '' ) {
                        $emails = explode(',', $subscription['notify_emails']);
                        foreach($emails as $email) {
                            $email = trim($email);
                            if( $email != '' ) {
                                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                                $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                                    'customer_id' => 0,
                                    'customer_email' => $email,
                                    'customer_name' => '',
                                    'subject' => $email_subject,
                                    'html_content' => $email_text,
                                    'text_content' => $email_text,
                                    ));
                                if( $rc['stat'] != 'ok' ) {
                                    error_log('ERR: Unable to email submission' . print_r($rc['err'], true));
                                } else {
                                    $email_sent = 'yes';
                                    $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
                                }
                            }
                        }
                    } 
                    if( $email_sent == 'no' ) {
                        foreach($rc['user_ids'] as $user_id) {
                            // 
                            // Don't email the submitter, they will get a separate email
                            //
                            $ciniki['emailqueue'][] = array(
                                'user_id' => $user_id,
                                'subject' => $email_subject,
                                'textmsg' => $email_text,
                                );
                        }
                    }

                    $blocks[] = array(
                        'type' => 'msg',
                        'class' => $limit_width,
                        'level' => 'success',
                        'content' => 'We have notified the appropriate people to have you removed from ' . $subscription['name'] . '.',
                        );
                    $blocks[] = array(
                        'type' => 'buttons',
                        'class' => 'aligncenter ' . $limit_width,
                        'items' => array(
                            array('text' => 'Continue', 'url' => "{$request['ssl_domain_base_url']}/"),
                            ),
                        );
                    return array('stat'=>'ok', 'blocks'=>$blocks);
                }
            }
            if( $subscription['description'] == '' ) {
                $subscription['description'] = 'This is an administration list for account notifications and cannot be automatically unsubscribed.';
            }
            $subscription['description'] .= '<br/><br/>If you would like to be removed from future updates, please fill in the form below to request removal.';

            $blocks[] = array(
                'type' => 'text',
                'class' => $limit_width,
                'title' => 'Unsubscribe from ' . $subscription['name'],
                'level' => 1,
                'content' => $subscription['description'],
                );
            $blocks[] = array(
                'type' => 'form',
                'class' => $limit_width,
                'submit-label' => 'Request Unsubscribe',
                'fields' => array(
                    'action' => array(
                        'id' => 'action',
                        'ftype' => 'hidden',
                        'value' => 'unsubscribe',
                        ),
                    'confirm' => array(
                        'id' => 'confirm',
                        'ftype' => 'checkbox',
                        'label' => 'Yes I want to unsubscribe from ' . $subscription['name'],
                        ),
                    'reason' => array(
                        'id' => 'reason',
                        'ftype' => 'textarea',
                        'label' => 'Reason (optional)',
                        'value' => isset($_POST['f-reason']) ? $_POST['f-reason'] : '',
                        ),
                    ),
                );
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
