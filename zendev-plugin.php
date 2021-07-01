<?php
/**
 * @package  ZenDevPlugin
 */
/*
Plugin Name: ZendevPlugin
Description: This plugins is all functions needed for videqques made by ZenDev Team
Version: 1.0.0
Author: Emel Rizvanovic
Author URI: https://zendev.se
License: GPLv2 or later
Text Domain: ZenDev Plugin
*/


// If this file is called firectly, abort!!!
defined( 'ABSPATH' ) or die( 'Error' );

// Require once the Composer Autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
}

/**
 * The code that runs during plugin activation
 */
function activate_zendev_plugin() {
	ZENDEVPLUGIN\Base\Activate::activate();
	ZENDEVPLUGIN\Base\Database::createDatabase();
}
register_activation_hook( __FILE__, 'activate_zendev_plugin' );

/**
 * The code that runs during plugin deactivation
 */
function deactivate_zendev_plugin() {
	ZENDEVPLUGIN\Base\Deactivate::deactivate();
	//ZENDEVPLUGIN\Base\Database::deleteTable();
}
register_deactivation_hook( __FILE__, 'deactivate_zendev_plugin' );

/**
 * Initialize all the core classes of the plugin
 */
if ( class_exists( 'ZENDEVPLUGIN\\Init' ) ) {
	ZENDEVPLUGIN\Init::register_services();
}

add_action( 'init', array( 'ZENDEVPLUGIN\Base\Hooks', 'registerHooks' ) );


