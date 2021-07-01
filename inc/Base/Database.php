<?php
/**
 *  ZenDev Plugin for Videqqus
 */

 namespace ZENDEVPLUGIN\Base;

 class Database {
    //Function for creating database
    public static function createDatabase() {
        global $wpdb;
        $table_name = $wpdb->prefix . "zendev_customer_info";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
        id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL ,
        customer_email text NOT NULL,
        subscription_type text NOT NULL,
        camera_id INT NULL ,
        status text NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        if ( ! function_exists('dbDelta') ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }

        dbDelta( $sql ,true);
        $table_name1 = $wpdb->prefix . "zendev_oauth";

        $sql1 = "CREATE TABLE {$table_name1} (
        id INT NOT NULL AUTO_INCREMENT,
        oauth text NOT NULL ,
        valid_until datetime NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta( $sql1 ,true);
    }

    public static function deleteTable() {

		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}zendev_customer_info;" );
	}
 }