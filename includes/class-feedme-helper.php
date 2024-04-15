<?php
/**
 * @since      1.1.0
 * @package    feedme
 * @subpackage feedme/includes
 * @author     Rohit Sharma
 */

class feedme_helper {
	protected $loader,$plugin_name,$version;

	public function __construct() {
		$this->plugin_name 	= 'feedme';
		$this->version 		= '1.1.0';
	}

	public function clean($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}
}