<?php
/**
 * @package  ZenDevPlugin
 */
namespace ZENDEVPLUGIN\Base;

class Activate
{
	public static function activate() {
		flush_rewrite_rules();
	}
}