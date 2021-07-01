<?php
/**
 * @package  ZenDevPlugin
 */
namespace ZENDEVPLUGIN\Base;

class Deactivate
{
	public static function deactivate() {
		flush_rewrite_rules();
	}
}