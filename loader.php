<?php
/*
Plugin Name: Woocommerce Nono Print Pricing
Plugin URI: http://geek.1bigidea.com/
Description: Adds new pricing mechanism based on min order qty and per-piece pricing beyond min order qty
Version: 1.0
Author: Tom Ransom
Author URI: http://1bigidea.com
Network Only: false

Copyright 2013 Tom Ransom (email: transom@1bigidea.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class NonoPrintPricing {
	private static $_this;
	var $plugin_slug = "NonoPrintPricing";
	var $plugin_name = "Nono Print Pricing";
	var $plugin_version = "1.0";

	var $min_order_key = '_nono_min_order_qty';
	var $price_per_piece_key = '_nono_price_per_piece';
	public static $pricing_table_key = '_nono_pricing_table';

	function __construct() {

		register_activation_hook(   __FILE__, array( __CLASS__, 'activate'   ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		add_action('init', array($this, 'init'));
		add_action('admin_init', array($this, 'admin_init'));

	}
	function __destruct(){
	}
	function activate() {
		// Add options, initiate cron jobs here
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}
	function deactivate() {
		// Remove cron jobs here
	}
	function uninstall() {
		// Delete options here
	}
	static function this(){
		// enables external management of filters/actions
		// http://hardcorewp.com/2012/enabling-action-and-filter-hook-removal-from-class-based-wordpress-plugins/
		// enables external management of filters/actions
		// http://hardcorewp.com/2012/enabling-action-and-filter-hook-removal-from-class-based-wordpress-plugins/
		// http://7php.com/how-to-code-a-singleton-design-pattern-in-php-5/
		if( !is_object(self::$_this) ) self::$_this = new NonoPrintPricing();

		return self::$_this;
	}
	/*
	 *	Functions below actually do the work
	 */
	function init(){
		// load localization strings
		load_plugin_textdomain('nono-per-unit', FALSE, plugin_basename(__FILE__).'/localization');

		include_once('inc/class.shortcodes.php');
		include_once('inc/class.printing_product.php');

		add_filter('woocommerce_product_class', array($this, 'initiate_product'), 10, 4);

		if( ! is_admin() ){
			add_action('wp_enqueue_scripts', array($this, 'front_enqueue') );
		}
	}

	function front_enqueue(){
		wp_enqueue_script('non-per-unit-js', plugins_url('/js/nono-per-unit.js', __FILE__), array('jquery') );

		// http://josscrowcroft.github.com/accounting.js/
		wp_enqueue_script('accounting', plugins_url('/js/accounting.min.js', __FILE__) );
	}

	function initiate_product($classname, $product_type, $post_type, $product_id){
kickout('initiate_product', $classname, $product_type, $post_type, $product_id, FILE_APPEND);

// 		switch($classname){
// 			case 'WC_Product_Variable':
// 				return 'NONO_Product_Variable';
// 			case 'WC_Product_Variation':
// 				return 'NONO_Product_Variation';
// 		}

		return $classname;
	}

	/**
	 *	Administrative Details
	 */
	function admin_init(){
		include_once('inc/class.admin_product.php');

		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue') );
	}
	function admin_enqueue($hook){
		global $post;

		if( ! in_array($hook, array('post-new.php', 'post.php')) || 'product' != $post->post_type ) return;

		wp_enqueue_script('nono-per-unit-js', plugins_url('/js/product-editor.js', __FILE__), array('jquery'));
		wp_enqueue_style('nono-per-unit', plugins_url('woo-per-unit-pricing.css', __FILE__) );
	}
	/**
	 *	Use product to lookup and return the price at a given qty
	 */

	function determine_price($the_product, $qty){

		if( $the_product->post_parent != 0 ){
			$the_product = get_product($the_product->post_parent);
		}

		$prices = get_post_meta($the_product->id, NonoPrintPricing::$pricing_table_key, true);
		ksort($prices, SORT_NUMERIC);

		$pricing = 0;
		foreach($prices as $min => $details ){
			if( $qty >= $min ) {
				$pricing = ($min * $details['price']) + ( ($qty - $min) * $details['addl'] );
			}
		}
		return $pricing;
	}
}
new NonoPrintPricing();