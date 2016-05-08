<?php
//
// Description
// -----------
// This function will get the public subscriptions and which ones the user is
// subscribed to.
//
//
// Returns
// -------
//
function ciniki_subscriptions_web_subscriptionManager($ciniki, $settings, $business_id) {

    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, "
            . "IF(ciniki_subscription_customers.status=10, 'yes', 'no') AS subscribed "
            . "FROM ciniki_subscriptions "
            . "LEFT JOIN ciniki_subscription_customers ON (ciniki_subscriptions.id = ciniki_subscription_customers.subscription_id "
                . "AND ciniki_subscription_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "') "
            . "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
    } else {
        $strsql = "SELECT ciniki_subscriptions.id, ciniki_subscriptions.name, ciniki_subscriptions.description, 'no' AS subscribed "
            . "FROM ciniki_subscriptions "
            . "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_subscriptions.flags&0x01) = 0x01 "
            . "ORDER BY ciniki_subscriptions.name "
            . "";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.subscriptions', array(
        array('container'=>'subscriptions', 'fname'=>'id',
            'fields'=>array('id', 'name', 'description', 'subscribed')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscriptions']) || count($rc['subscriptions']) == 0 ) {
        return array('stat'=>'ok', 'blocks'=>array());
    }
    $subscriptions = $rc['subscriptions'];
    
    $title = 'Mailing List';
    if( count($subscriptions) > 1 ) {
        $title = 'Mailing Lists';
    }

    $blocks = array();  

    //
    // subscribe message
    //
    $html_message = "In order to complete your subscription, please click on the link below or copy and paste it into your web browser.\n\n"
        . "{_confirm_url_}";
    $text_message = $html_message;

    //
    // Process any form submissions
    //
    $name = (isset($_POST['subscription_name']) ? $_POST['subscription_name'] : '');
    $email = (isset($_POST['subscription_email']) ? $_POST['subscription_email'] : '');
    $errors = 'no';
    $display_form = 'yes';
    if( isset($_POST['subscriptions-action']) && $_POST['subscriptions-action'] == 'subscribe' ) {
        if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
            //
            // If the customer is signed in, then just subscribe them
            //

        } else {
            //
            // create the signup key
            //
            $signup_key = '';
            $chars = 'abcefghijklmnopqrstuvwxyzABCEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            for($i=0;$i<20;$i++) {
                $signup_key .= substr($chars, rand(0, strlen($chars)-1), 1);
            }

            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
            $rc = ciniki_core_dbUUID($ciniki, 'ciniki.subscriptions');
            if( $rc['stat'] != 'ok' ) {
                $errors = 'yes';
                error_log('WEB-SUBSCRIPTIONS: Unable to get UUID');
                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>"Oops, we seem to have a problem. Please try again or contact us for help.");
            } else {
                $signup_key .= '-' . $rc['uuid'];
            }

            $signup_key = sha1($signup_key);

            //
            // Build the signup_data
            //
            if( !isset($_POST['subscription_name']) || $_POST['subscription_name'] == ''
                || !isset($_POST['subscription_email']) || $_POST['subscription_email'] == ''
                ) {
                $errors = 'yes';
                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'You must enter your name and a valid email address.');
            }
            if( $errors == 'no' 
                && !preg_match("/.\@./", $_POST['subscription_email']) 
                ) {
                $errors = 'yes';
                $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'You must enter a valid email address.');
            }
            if( $errors == 'no' ) {
                $signup_data = array(
                    'name' => (isset($_POST['subscription_name'])?$_POST['subscription_name']:''),
                    'email' => (isset($_POST['subscription_email'])?$_POST['subscription_email']:''),
                    'subscriptions'=>array(),
                    );
                foreach($subscriptions as $sub) {
                    $field_id = 'subscription-' . $sub['id'];
                    if( isset($_POST[$field_id]) && $_POST[$field_id] == 'on' ) {
                        $signup_data['subscriptions'][$sub['id']] = 'yes';
                    }
                }
                if( count($signup_data['subscriptions']) == 0 ) {
                    $errors = 'yes';
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'You must select a mailing list to join.');
                }
            }
            if( $errors == 'no' ) {
                //
                // Insert signup data into database
                //
                $strsql = "INSERT INTO ciniki_subscription_signups (business_id, signup_key, signup_data, date_added) VALUES ("
                    . "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
                    . "'" . ciniki_core_dbQuote($ciniki, $signup_key) . "', "
                    . "'" . ciniki_core_dbQuote($ciniki, serialize($signup_data)) . "', "
                    . "UTC_TIMESTAMP() "
                    . ")";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.subscriptions');
                if( $rc['stat'] != 'ok' ) {
                    $errors = 'yes';
                    error_log('WEB-SUBSCRIPTIONS: Unable to insert subscription signup');
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Oops, we seem to have a problem. Please try again or contact us for help.');
                }
            } 
            if( $errors == 'no' ) {                 
                //
                // Create the confirm url
                //
                $confirm_url = $ciniki['request']['domain_base_url'] . '/subscriptions/confirm?k=' . $signup_key;
                $html_message = str_ireplace('{_confirm_url_}', $confirm_url, $html_message);
                $text_message = str_ireplace('{_confirm_url_}', $confirm_url, $text_message);

                //
                // Send email
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
                $rc = ciniki_mail_hooks_addMessage($ciniki, $business_id, array(
                    'object'=>'ciniki.subscriptions.signup',
                    'object_id'=>$signup_key,
                    'customer_id'=>0,
                    'customer_name'=>$_POST['subscription_name'],
                    'customer_email'=>$_POST['subscription_email'],
                    'subject'=>'Complete your subscription',
                    'html_content'=>$html_message,
                    'text_content'=>$text_message,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    $errors = 'yes';
                    error_log('WEB-SUBSCRIPTIONS: Unable to send message');
                    $blocks[] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Oops, we seem to have a problem. Please try again or contact us for help.');
                } else {
                    $display_form = 'no';
                    $blocks[] = array('type'=>'formmessage', 'level'=>'success', 'message'=>'We have sent you a confirmation message. '
                        . 'Please check your email and click on the link provided to complete your subscription.');
                    $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'business_id'=>$business_id);
                }
            }
        }
    }

    $html = '';
    $html .= "<form action='" . $ciniki['request']['domain_base_url'] . "/subscriptions' method='post'>";
//    if( isset($error_msg) && $error_msg != '' ) {
 //       $html .= "<div class='form-message-content'>"
  //          . "<div class='form-result-message form-" . ($errors == 'no' ? 'success' : 'error') . "-message'><div class='form-message-wrapper'>"
   //         . "<p>$error_msg</p></div></div></div>";
    //}
    if( $display_form == 'yes' ) {
        $html .= "<input type='hidden' name='subscriptions-action' value='subscribe'/>";
        if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
            $html .= "";
        } else {
            if( isset($settings['page-contact-subscriptions-intro-message']) && $settings['page-contact-subscriptions-intro-message'] != '' ) {
                $html .= "<p>" . $settings['page-contact-subscriptions-intro-message'] . "</p>";
            } elseif( count($subscriptions) > 1 ) {
                $html .= "<p>Enter your name and email address, then pick which Mailing Lists you would like to be subscribed.</p>";
            } else {
                $html .= "<p>Enter your name and email address and we'll add you to our mailing list.</p>";
            }
            $html .= ""
                . "<div class='input'>"
                . "<label for='subscription_name'>Name</label>"
                . "<input type='text' class='text' id='subscription_name' name='subscription_name' value='$name'>"
                . "</div>"
                . "<div class='input'>"
                . "<label for='subscription_email'>Email Address</label>"
                . "<input type='email' class='text' id='subscription_email' name='subscription_email' value='$email'>"
                . "</div>"
                . "";
        }
        if( count($subscriptions) > 1 ) {
            $html .= "<br/>";
            foreach($subscriptions as $sid => $sub) {
                $html .= "<div class='checkbox'>"
                    . "<input type='checkbox' class='checkbox' id='subscription-" . $sub['id'] . "' name='subscription-" . $sub['id'] . "'>"
                    . "<label class='checkbox' for='subscription-" . $sub['id'] . "'>" . $sub['name'] . "</label>";
                if( $sub['description'] != '' ) {
                    $html .= "<p class='subscription-description'>" . $sub['description'] . "</p>";
                }
                $html .= "</div>";
            }
        } else {
            $html .= "<input type='hidden' name='subscription-" . $subscriptions[0]['id'] . "' value='on'>";
        }
        $html .= "<div class='submit'>";
        $html .= "<input type='submit' class='submit' name='submit' value=' Subscribe '>";
        $html .= "</div>";
    }

    $html .= "</form>";
// REMOVE WHEN WORKING FOR signed in customer
if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
    $html = '';
}
    $blocks[] = array('type'=>'content', 'title'=>$title, 'html'=>$html);

	return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
