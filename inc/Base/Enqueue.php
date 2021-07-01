<?php 
/**
 * @package  ZenDevPlugin
 */
namespace ZENDEVPLUGIN\Base;

use \ZENDEVPLUGIN\Base\BaseController;

/**
* 
*/
class Enqueue extends BaseController
{
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}
	
	function enqueue() {
		// enqueue all our scripts
		wp_enqueue_style( 'zendevplugincss', $this->plugin_url . 'assets/zendevplugincss.css' );
		wp_enqueue_script( 'zendevpluginjs', $this->plugin_url . 'assets/zendevpluginjs.js' );
	}
}