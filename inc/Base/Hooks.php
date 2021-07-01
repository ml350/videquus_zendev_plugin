<?php

/**
* ZenDev Plugin for Videqqus
*/

namespace ZENDEVPLUGIN\Base;

class Hooks {
    //This function is used for getting instance of class
    public static function get_instance() {
        static $instance = null;

        if ( $instance == null ) {
            $instance = new self();
        }

        return $instance;
    }

    //This function is for registering all Hooks
    public static function registerHooks() {
        // hooks plugin need
        add_action( 'woocommerce_checkout_create_order' ,array('\ZENDEVPLUGIN\Base\ZenDevActions', 'my_custom_checkout_field_process'), 10,2 );

        // add_action( 'woocommerce_subscription_after_actions' ,array('\ZENDEVPLUGIN\Base\ZenDevActions', 'add_switch_button'), 10,0 );
        add_action( 'woocommerce_payment_complete' ,array('\ZENDEVPLUGIN\Base\ZenDevActions', 'ct_checkout_subscription_created'), 10,1 );

        // split order to as individual with more then same product
        add_action('woocommerce_payment_complete', array('\ZENDEVPLUGIN\Base\ZenDevActions', 'split_order_with_same_products'), 10, 1);

        // after subscription is created, switch it's status to pending
        add_action( 'woocommerce_payment_complete', array('\ZENDEVPLUGIN\Base\ZenDevActions', 'update_subscription_onhold'), 10,1 );

        // get camera id to display it on my account page
        //add_action('woocommerce_account_content', array('\ZENDEVPLUGIN\Base\ZenDevActions', 'get_camera_id'), 10,0 );
        
        add_action( 'woocommerce_my_subscriptions_after_subscription_id', array ( '\ZENDEVPLUGIN\Base\ZenDevActions', 'get_camera_id' ), 10, 1 );
        
        // upgrade/downgrage subscription
        add_action( 'woocommerce_subscriptions_switch_completed', array('\ZENDEVPLUGIN\Base\ZenDevActions', 'switch_subscription'), 10,1 );

        //rest api Hooks
        add_action( 'rest_api_init' ,array('\ZENDEVPLUGIN\Base\ZenDevRest', 'camera_save_to_table'), 10,0 );
    }
}