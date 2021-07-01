<?php

/**
 * ZenDevPlugin
 */

namespace ZENDEVPLUGIN\Base;

class ZenDevActions {
    //This function is used for getting instance of class
    public static function get_instance() {
        static $instance = null;

        if ( $instance == null ) {
            $instance = new self();
        }

        return $instance;
    }

    // Create user on checkout form if order is from Guest account
    public static function my_custom_checkout_field_process($fields,$data) { 
        if($data['createaccount'] == 1) 
        {
            $body = [
                "email" => $data['billing_email'],
                "password" => $data['account_password'],
                "realName" => $data['billing_first_name'],
                "addressLine" => $data['billing_address_1'],
                "postalCode" => $data['billing_postcode'],
                "city" => $data['billing_city'],
                "phoneNumber" => $data['billing_phone'],
            ];

            $body = wp_json_encode( $body );
            $token1 = self::check_if_exist();
            $ch = curl_init();

            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://app.videquus.se/v1/v2/customers',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                  'Authorization: '. $token1,
                  'Content-Type: application/json'
                ),
            )); 
 
            $result = curl_exec($ch);
            
            // Uncomment line below for debugging log.txt
            //file_put_contents(__DIR__.'/log.txt', print_r($result, true),FILE_APPEND);

            if (curl_errno($ch)) 
            {
                echo 'Error:' . curl_error($ch);
            }

            curl_close($ch);
        }
    }

    // function for debugging purpose, log.txt location - 'zendev-plugin/inc/Base' 
    public static function log_value($value){
        file_put_contents(__DIR__.'/log.txt', print_r($value, true),FILE_APPEND);
        return;
    } 

    public static function get_oauth() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://videquus-prod.eu.auth0.com/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"client_id\":\"BMTnlblBveHmG5a7SsTp8sCwpWtMzUtV\",\"client_secret\":\"PIRGpWXbCb4ZPjjYqq5xLXYnjGCiiRBRtb5E2Iu74pDhEPqxnlEvOC68vC_JJEkE\",\"audience\":\"https://api.videquus.se\",\"grant_type\":\"client_credentials\"}");
        
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($ch);
        if (curl_errno($ch))
        {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        
        return $result;
    }

    public static function check_if_exist() 
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "zendev_oauth";
        $result_auth = $wpdb->get_row("SELECT * FROM {$table_name} LIMIT 1");
        if($result_auth != "") 
        {
            $datetime1 = new \DateTime();
            $datetime2 = new \DateTime($result_auth->valid_until);
            $interval = $datetime1->diff($datetime2);
            $elapsed = $interval->format('%a');
            if($elapsed >= 2) 
            {
                $token = self::get_oauth();
                $tt = json_decode($token, true);
                $token1 = 'Bearer '. $tt["access_token"];
                $save_data = [
                    'oauth' => $token1,
                    'valid_until'    => date("Y-m-d H:i:s"),
                ];
                $table = $wpdb->prefix.'zendev_oauth';
                $wpdb->update($table,$save_data, ["id" => 1]);
                return $token1;
            }
            return $result_auth->oauth;
        }
        $token = self::get_oauth();
        $tt = json_decode($token, true);
        $token1 = 'Bearer '. $tt["access_token"];
        $save_data = [
            'oauth' => $token1,
            'valid_until'    => date("Y-m-d H:i:s"),
        ];
        $table = $wpdb->prefix.'zendev_oauth';
        $wpdb->insert($table,$save_data);

        return $token1;
    }

    public static function ct_checkout_subscription_created( $order_id ) 
    {
        global $wpdb;

        $order = new \WC_Order( $order_id );
        $items = $order->get_items();
        $email = $order->get_billing_email(); 

        //self::log_value($order_id);

        $get_camera_status = self::get_camera_data($order_id); 
        $camera_status = '';

        foreach( $items as $item ) 
        {
            $item_meta_data = $item->get_meta_data();
            $item_key = $item_meta_data[0]->key;

            //self::log_value($item);

            $sub_type = "";
            if($item->get_product_id() == 1329) 
            {
                $sub_type = "lite"; // lite  
            }
            elseif($item->get_product_id() == 1330) 
            {
                $sub_type = "standard"; // standard 
            }
            elseif($item->get_product_id() == 1331) 
            {
                $sub_type = "foaling"; // Foaling 
            } 

            $body = [
                "customerEmail" => $email,
                "subscriptionType" => $sub_type,
                "orderId" => $order_id,
                "preservedLimit" => false,
                "active" => false
            ];
            $body = wp_json_encode($body);
            $token1 = self::check_if_exist();

            if(empty($item_key)){
                $ch = curl_init(); 
                curl_setopt_array($ch, array(
                    CURLOPT_URL => 'https://app.videquus.se/v1/v2/subscriptions/create',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $body,
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: '. $token1,
                        'Content-Type: application/json'
                    ),
                ));
                
                $result = curl_exec($ch); 

                /// Uncomment line below for debugging log.txt
                // file_put_contents(__DIR__.'/log.txt', print_r($item_key, true),FILE_APPEND);

                if (curl_errno($ch)) 
                {
                    echo 'Error:' . curl_error($ch);
                }

                $save_data = [
                    'user_id'           => $order->get_customer_id(),
                    'customer_email'    => $email,
                    'subscription_type' => $sub_type,
                    'order_id'          => $order_id,
                    'status'            => "ordered",
                    'created_at'        => date("Y-m-d H:i:s"),
                ];

                $table = $wpdb->prefix.'zendev_customer_info';
                $wpdb->insert($table,$save_data); 
            }
        }
    }

    // split order with same quantity
    public static function split_order_with_same_products($order_id)
    {
        $completed_order = new \WC_Order($order_id);    
        $customer_id = $completed_order->get_customer_id();
        $order_items = $completed_order->get_items(); 

        //uncocomment to log value in the file
        //self::log_value($completed_order);
        $address = array(
            'first_name' => $completed_order->get_billing_first_name(),
            'last_name'  => $completed_order->get_billing_last_name(),
            'company'    => '',
            'email'      => $completed_order->get_billing_email(),
            'phone'      => $completed_order->get_billing_phone(),
            'address_1'  => $completed_order->get_billing_address_1(),
            'address_2'  => $completed_order->get_billing_address_2(),
            'city'       => $completed_order->get_billing_city(),
            'state'      => $completed_order->get_billing_state(),
            'postcode'   => $completed_order->get_billing_postcode(),
            'country'    => $completed_order->get_billing_country()
        );

        foreach($order_items as $item){ 
            // Item/Product ID
            $product_id = $item->get_product_id(); 

            // Item metadata
            $item_meta_data = $item->get_meta_data();
            $item_key = $item_meta_data[0]->key;
              
            //set arguments of new split order - status 'wc-pending'
            $new_order_args = array(
                'customer_id' => $customer_id,
                'status'      => 'wc-pending',
            );
            $new_order = wc_create_order($new_order_args);
            
            $new_order->set_address($address, 'billing');
            $new_order->set_address($address, 'shipping');

            $product_to_add = wc_get_product($product_id);
            //uncocomment to log value in the file
            //self::log_value($product_to_add);
            $new_order->add_product($product_to_add, 1, array());
            // crete new subscription 
            $subscription = wcs_create_subscription(array(
                'order_id' => $new_order->get_id(),
                'status' => 'pending', // Status should be initially set to pending to match how normal checkout process goes
                'billing_period' => \WC_Subscriptions_Product::get_period($product_id),
                'billing_interval' => \WC_Subscriptions_Product::get_interval($product_id)
            ));

            // Modeled after WC_Subscriptions_Cart::calculate_subscription_totals()
		    $start_date = gmdate( 'Y-m-d H:i:s' );

            //Add product to subscription
            $subscription->add_product( $product_to_add, 1, array(
                'order_version' => $completed_order->get_version(), 
                'totals' => array(
                    'subtotal' => $product_to_add->get_price(), 
                    'subtotal_tax' => 0, 
                    'total' => $product_to_add->get_price(), 
                    'tax' => 0, 
                    'tax_data' => array(
                        'subtotal' => array(), 
                        'total' => array()
                    )
                )
            ));

            $dates = array(
                'trial_end'    => \WC_Subscriptions_Product::get_trial_expiration_date( $product_to_add, $start_date ),
                'next_payment' => \WC_Subscriptions_Product::get_first_renewal_payment_date( $product_to_add, $start_date ),
                'end'          => \WC_Subscriptions_Product::get_expiration_date( $product_to_add, $start_date ),
            );

            $subscription->update_dates($dates);
            $subscription->calculate_totals();

            // Update order status with custom note
            $note = ! empty( $note ) ? $note : __( 'Programmatically added order and subscription with ZenDev - plugin.' );
		    $new_order->update_status( 'completed', $note, true );
            
            // Update subscription status to 'on-hold' from pending with note  
            $subscription->update_status('on-hold', $note, true);
            
            $new_order->set_address($address, 'billing');
            $new_order->set_address($address, 'shipping');
            
            $available_gateways   = WC()->payment_gateways->get_available_payment_gateways();
            $order_payment_method = \wcs_get_objects_property( $completed_order, 'payment_method' );
            $new_order->set_payment_method( $available_gateways[ $order_payment_method ] );
            //uncocomment to log value in the file
            //self::log_value($available_gateways);
            // $new_order->set_payment_method($payment_gateways['stripe']); 
            $new_order->update_status('on-hold');
		    $new_order->add_order_note('This order created automatically');
            $new_order->save();
            $completed_order->remove_item($item->get_id());
        }

        //\wp_delete_post($order_id, true);
    }

    // On creating new subscription, update it on-hold
    public static function update_subscription_onhold( $order_id ) 
    {  
        $order = wc_get_order($order_id);   // Order Object     
        $subscriptions = wcs_get_subscriptions_for_order( $order_id, array('order_type' => 'any') );

        $items = $order->get_items();
        $item_key = ''; // item metadata key, if it's empty it's switching subscription

        foreach( $items as $item ) 
        {
            $item_meta_data = $item->get_meta_data();
            $item_key = $item_meta_data[0]->key;
        }

        foreach( $subscriptions as $subscription_id => $subscription ){
            self::log_value($subscription->order_type);
            if(empty($item_key))
            { 
                $note = ! empty( $note ) ? $note : __( 'Subscription updated on-hold until activated.' ); 
                $subscription->update_status( 'on-hold', $note, true ); 
            } 
            else 
            { 
                $note = ! empty( $note ) ? $note : __( 'Subscription is switched with status cancelled.' ); 
                $subscription->update_status( 'cancelled', $note, true ); 
            }
        } 
    }

    // get camera status 
    public static function get_camera_data( $order_id )
    {
        global $wpdb;
        $current_user = wp_get_current_user();
        $email = $current_user->user_email;
        $sql = $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}zendev_customer_info WHERE customer_email = %s AND order_id = %d", $email, $order_id );

        return $wpdb->get_results( $sql );
    } 

    // get camera id
    public static function get_camera_id() 
    {
        global $wpdb;
        $current_user = wp_get_current_user();
        $email = $current_user->user_email;
        $sql = $wpdb->prepare( "SELECT camera_id FROM {$wpdb->prefix}zendev_customer_info WHERE customer_email = %s", $email );
        
        $results = $wpdb->get_results($sql);
        if(empty($results)) {
            $results = 'N/A';

            return $results;     
        } else {
            foreach($results as $result) {
                return $result;
            }
        }
    }

    // get camera name -- in proggress camera_name still not implementet in database from API
    public static function get_camera_name( $order_id )
    {
        global $wpdb;
        $current_user = wp_get_current_user();
        $email = $current_user->user_email;
        $sql = $wpdb->prepare( "SELECT camera_name FROM {$wpdb->prefix}zendev_customer_info WHERE customer_email = %s AND order_id = %d", $email, $order_id );
        
        $results = $wpdb->get_results($sql);
        if(empty($results)) {
            $results = 'N/A';
        }
        return $results;
    }

    // function that update subscription type in backend if customer trigger switching downgrade/upgrade order
    public static function switch_subscription($order_id)
    {
        self::log_value($order_id);
        $order = new \WC_Order( $order_id );
        $items = $order->get_items();
        $email = $order->get_billing_email(); 

        $get_camera_status = self::get_camera_data($order_id); 
        $camera_status = '';

        if(!empty($get_camera_status))
        {
            foreach($get_camera_status as $c)
            {
                $cstat = $c->status;
                if( $cstat == 'ordered')
                {
                    $camera_status = 'ordered';
                } 
                elseif($cstat == 'activated')
                {
                    $camera_status = 'activated';
                }
            }
        }

        foreach( $items as $item ) 
        {
            $sub_type = "";
            if($item->get_product_id() == 1329) 
            {
                $sub_type = "lite"; // lite  
            }
            elseif($item->get_product_id() == 1330) 
            {
                $sub_type = "standard"; // standard 
            }
            elseif($item->get_product_id() == 1331) 
            {
                $sub_type = "foaling"; // Foaling 
            }
        }

        $get_camera_id = self::get_camera_id();

        $type = gettype($get_camera_id);

        $body = [
            "customerEmail" => $email,
            "subscriptionType" => $sub_type,
            //"cameraId" => $get_camera_id->camera_id
            "orderId"   => $order_id
        ];

        $body = wp_json_encode($body);
        $token1 = self::check_if_exist();

        if($camera_status == 'activated')
        {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://app.videquus.se/v1/v2/subscriptions/update-subscriptiontype',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: '. $token1,
                    'Content-Type: application/json'
                ),
            ));
            
            $result = curl_exec($ch);
        }        
        
        if (curl_errno($ch)) 
        {
            echo 'Error:' . curl_error($ch);
        }  
    }
}