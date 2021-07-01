<?php

/**
 * ZenDevPlugin
 */

namespace ZENDEVPLUGIN\Base;

class ZenDevRest {

    //This function is used for getting instance of class
    public static function get_instance() 
    {
        static $instance = null;

        if ( $instance == null ) {
            $instance = new self();
        }

        return $instance;
    }

    //create new rest endpoints

    //save camera to table
    public static function camera_save_to_table() 
    {    
        \register_rest_route('vq/v1', 'camera', [
            "methods" => "POST",
            "callback" => array(self::get_instance(),'vq_camera_id_save'),
            'permission_callback' => '__return_true',
        ]);
    }

    public static function vq_camera_id_save($request) 
    {
        global $wpdb;
        $data1 = $request->get_body();
        $data = \json_decode($data1, true);

        $order_id = $data['orderId'];
        $customer = \get_user_by('email', $data['customerEmail']);  

        // \write_log($data->body);
        $table = $wpdb->prefix.'zendev_customer_info'; 
        
        if(!empty($order_id))
        {
            $rows_affected = $wpdb->query(
            $wpdb->prepare("
                            UPDATE {$table}
                            SET  status = %s
                            WHERE customer_email = %s AND status = %s AND subscription_type = %s AND order_id = %d LIMIT 1;",
                            "activated", $data['customerEmail'], "ordered", $data["subscriptionType"], $data["orderId"]
                            )
            );
        } else {
            $rows_affected = $wpdb->query(
            $wpdb->prepare("
                            UPDATE {$table}
                            SET  camera_id = %s, status = %s
                            WHERE customer_email = %s AND status = %s AND subscription_type = %s AND camera_id IS NULL LIMIT 1;",
                            $data['camera_id'], "activated", $data['customerEmail'], "ordered", $data["subscriptionType"]
                            )
            );
        }
        if($rows_affected > 0) 
        {
            $body = [
                "customerEmail"     => $data['customerEmail'],
                "orderId"           => $data['orderId'],
                "active"            => true
            ];
            $body = wp_json_encode($body);
            $token1 = self::check_if_exist();
            
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://app.videquus.se/v1/v2/subscriptions/update-active',
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

            // Uncomment line below for debugging log.txt
            //file_put_contents(__DIR__.'/log.txt', print_r($result, true),FILE_APPEND);

            if (curl_errno($ch)) 
            {
                echo 'Error:' . curl_error($ch);
            } 

            // From down below is code that will put subscription status to active 
            // but only for specific subscription type that we match with $request_product_id

            $order = wc_get_order($order_id); // parent order ID
            $items = $order->get_items();

            $child_order_id = $order_id;
            $children_orders_id = array(); // children order IDs

            // from order_id we will get split order_ids buy summing + 2 for each item in parent order
            foreach($items as $item){
                $child_order_id += 2;
                array_push($children_orders_id, $child_order_id);
            }

            // product_id from data request by backend application
            $request_product_id = '';

            if( $data["subscriptionType"] == 'standard' )
            {
                $request_product_id = 1330;
            } 
            elseif ( $data["subscriptionType"] == 'foaling' ) 
            {
                $request_product_id = 1331;
            } 
            elseif ( $data["subscriptionType"] == 'lite') 
            {
                $request_product_id = 1329;
            }
            
            // Put parent subscription with all items to active
            $subscriptions = wcs_get_subscriptions_for_order( $order_id, array('order_type' => 'any') ); 
            foreach( $subscriptions as $subscription_id => $subscription )
            {  
                $note = ! empty( $note ) ? $note : __( 'Parent subscription is activated by backend application.' ); 
                $subscription->update_status( 'active', $note, true ); 
            }   

            // Put child subscriptions (or individual one that are splitted from parnet order) to active status
            foreach( $children_orders_id as $child ){ 
                $split_order = wc_get_order($child);
                $items_split_order = $split_order->get_items();
                $product_id_match = false;

                foreach($items_split_order as $split_item)
                { 
                    if($split_item['product_id'] == $request_product_id)
                    {
                        $product_id_match = true;
                    } 
                    else 
                    {
                        $product_id_match = false;
                    }
                }
                
                if( $product_id_match == true ) {
                    $subscriptions = wcs_get_subscriptions_for_order( $child, array('order_type' => 'any') );
                    foreach( $subscriptions as $subscription_id => $subscription )
                    {  
                        $note = ! empty( $note ) ? $note : __( 'Child subscription is activated by backend application.' ); 
                        $subscription->update_status( 'active', $note, true ); 
                        //file_put_contents(__DIR__.'/log.txt', print_r('direnkica', true),FILE_APPEND); 
                    }
                }   
            }
        }
            
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
    
    public static function check_if_exist() {
        global $wpdb;
        $table_name = $wpdb->prefix . "zendev_oauth";
        $result_auth = $wpdb->get_row("SELECT * FROM {$table_name} LIMIT 1");
        if($result_auth != "") {
            $datetime1 = new \DateTime();
            $datetime2 = new \DateTime($result_auth->valid_until);
            $interval = $datetime1->diff($datetime2);
            $elapsed = $interval->format('%a');
            if($elapsed >= 1) {
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
            $token = self::get_oauth();

            return $token;
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
}