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

		if( ! $this->check_for_woocommerce() ) return false;

		// load localization strings
		load_plugin_textdomain('nono-per-unit', FALSE, plugin_basename(__FILE__).'/languages');

		include_once('inc/class.shortcodes.php');
		include_once('inc/class.printing_product.php');

		add_filter('woocommerce_product_class', array($this, 'initiate_product'), 10, 4);

		if( ! is_admin() ){
			add_action('wp_enqueue_scripts', array($this, 'front_enqueue') );
		}

	}

	function check_for_woocommerce(){
		$taxes = get_taxonomy('product_type');
		if( false === $taxes ) return $taxes; // Nope - Looks like WooCommerce isn't installed

		// Let's be sure that our product type is setup as a term
		if( ! get_term_by( 'slug', sanitize_title( 'bulk' ), 'product_type' ) ){
			wp_insert_term('bulk', 'product_type');
		}

		return true;
	}

	function front_enqueue(){

		wp_enqueue_script('nono-per-unit-js', plugins_url('/js/nono-per-unit.js', __FILE__), array('jquery') );

		// http://josscrowcroft.github.com/accounting.js/
		wp_enqueue_script('accounting', plugins_url('/js/accounting.min.js', __FILE__) );

		// this is a product page
		if ( is_singular('product') ){
			$product = get_queried_object();
			$product_type = wp_get_object_terms($product->ID, array('product_type') );
			$types = wp_list_pluck('slug', $product_type);

			if( $this->is_bulk_product($product) ){
				$pricing = $this->get_current_pricing($product);

				wp_localize_script('nono-per-unit-js', 'nono_bulk_prices', $pricing);
			}
kickout('product', $product_type, $product);
		}
	}

	function initiate_product($classname, $product_type, $post_type, $product_id){
//kickout('initiate_product', $classname, $product_type, $post_type, $product_id, FILE_APPEND);

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

	function determine_price($the_product, $qty, $price_level = 'regular'){

		if( 'sale' == $price_level ){
			$date_from = get_post_meta($the_product->id, '_sale_price_dates_from', true);
			$date_to   = get_post_meta($the_product->id, '_sale_price_dates_to', true);

			if( !($date_from <= time() && time() <= $date_to) ) // Product not on sale (per schedule)
				return '';
		}

		$bulk_pricing = get_post_meta($the_product->id, NonoPrintPricing::$pricing_table_key, true);
		$prices = $bulk_pricing[$price_level];
		ksort($prices, SORT_NUMERIC);

		// Check that the qty requested is within the range of prices
		$quantities = array_keys($prices);
		if( $qty < $quantities[0] ) return false;

		$pricing = 0;
		foreach($prices as $min => $details ){
			if( $qty >= $min ) {
				$pricing = ($min * $details['price']) + ( ($qty - $min) * $details['addl'] );
			}
		}

		if( 0 == $pricing && 'sale' == $price_level ) {
			return '';
		}

		return $pricing;
	}

	/**
	 *  Utility Functions
	 */
	function get_current_pricing($id){

		if ( is_object($id) ) $id = $id->ID;

		$bulk_pricing = get_post_meta($id, NonoPrintPricing::$pricing_table_key, true);
		/** keys
		 * bool     enabled_sale
		 * array    regular
		 * array    sale
		 */

		// Special Price date range
		$sale_price_dates_from 	= get_post_meta( $post->ID, '_sale_price_dates_from', true );
		$sale_price_dates_to 	= get_post_meta( $post->ID, '_sale_price_dates_to', true );

		$prices = $bulk_pricing['regular'];
		ksort($prices, SORT_NUMERIC);

		return $prices;
	}


	function is_bulk_product($id=0){

		if ( is_object($id) ){
			if( isset($id->ID) ) {
				$id = $id->ID;
			} elseif ( isset($id->id) ){
				$id = $id->id;
			}
		}

		if ( is_array($id) ) {
			if ( isset($id['id']) ){
				$id = $id['id'];
			}elseif ( isset($id['ID']) ){
				$id = $id['ID'];
			}
		}

		$product_type = wp_get_object_terms($id, 'product_type', array('fields' => 'names'));
		if( ! in_array('bulk', $product_type) ) return false; // Not a bulk product so no output

		return true;
	}

	function is_on_sale($id=0){

		if ( is_object($id) ) $id = $id->ID;

		$date_from = get_post_meta($id, '_sale_price_dates_from', true);
		$date_to   = get_post_meta($id, '_sale_price_dates_to', true);

		if( !($date_from <= time() && time() <= $date_to) ) // Product not on sale (per schedule)
		return '';
	}

}
new NonoPrintPricing();

	function temp_debuga($validates, $product_id, $quantity){
		kickout('add-to-cart_validates', $validates, $product_id, $quantity, $_REQUEST, FILE_APPEND);

		return $validates;
	}
	add_filter('woocommerce_add_to_cart_validation', 'temp_debuga', 10, 3);

function temp_debug($cart_item_data, $product_id, $variation_id){
kickout('add-to-cart', $cart_item_data, $product_id, $variation_id, $_REQUEST, FILE_APPEND);

	return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'temp_debug', 10, 3);
