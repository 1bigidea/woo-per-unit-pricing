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
	}
	function initiate_product($classname, $product_type, $post_type, $product_id){
kickout('initiate_product', $classname, $product_type, $post_type, $product_id, FILE_APPEND);

		switch($classname){
			case 'WC_Product_Variable':
				return 'NONO_Product_Variable';
			case 'WC_Product_Variation':
				return 'NONO_Product_Variation';
		}

		return $classname;
	}

	function admin_init(){
		add_action('woocommerce_product_write_panel_tabs', array($this, 'add_panel') );
		add_action('woocommerce_product_write_panels', array($this, 'show_panel'));

//		add_action('woocommerce_product_after_variable_attributes', array($this, 'variation_admin'), 10, 3);
		add_action('woocommerce_process_product_meta_variable', array($this, 'save_pricing_info'), 10 ,1);

		add_action('admin_enqueue_scripts', array($this, 'enqueue') );
	}
	function enqueue($hook){
		global $post;

		if( ! in_array($hook, array('post-new.php', 'post.php')) || 'product' != $post->post_type ) return;

		wp_enqueue_script('nono-per-unit-js', plugins_url('/js/product-editor.js', __FILE__), array('jquery'));
		wp_enqueue_style('nono-per-unit', plugins_url('woo-per-unit-pricing.css', __FILE__) );
	}
	/**
	 *	Insert Custom Write Panel in Woo Product Editor
	 */
	function add_panel(){
		printf('<li class="nono-price-panel nono-pricing-option"><a href="#nono_price_panel">%s</a></li>', __( 'Price Tables', 'nono-per-unit' ) );
	}
	function show_panel(){
		global $post;

		$prices = get_post_meta($post->ID, self::$pricing_table_key, true);
		if( empty($prices) ){
			$prices = array(
				'empty1' => array('price' => '', 'label' => 'Digital'),
				'empty2' => array('price' => '', 'label' => 'Offset')
			);
		}

		echo '<div id="nono_price_panel" class="panel woocommerce_options_panel">';

			echo '<div class="options_group nono-price-table">';
				echo '<table id="nono-pricing-table-edit">';
					printf('<thead><td>%s</td><td>%s</td><td>%s</td><td>&nbsp;</td></thead>',
						__('Min Qty', 'nono-per-unit'),
						__('Price Each', 'nono-per-unit'),
						__('Label', 'nono-per-unit')
					);
					foreach( $prices as $min => $price_row){
						// handle the case where pricing hasn't been set yet (new products etc.)
						if(is_nan($min)){
							$min = 0;
						}

						echo '<tr>';
						printf('<td><input type="text" class="value-field" value="%d" size="8"  name="nono_price_table_qty[]"></td>', $min);
						printf('<td><input type="text" class="value-field" value="%s" size="10" name="nono_price_table_price[]"></td>', number_format ( $price_row['price'], 2, ',', '.') );
						printf('<td><input type="text" value="%s" size="20" name="nono_price_table_label[]"></td>', $price_row['label']);
						echo '<td><a href="#" class="add-row action-icon" /><a href="#" class="delete-row action-icon" /></td>';
						echo '</tr>';
					}
				echo '</table>';
			echo '</div>';
		echo '</div>';
	}


	function save_pricing_info($post_id){

		if( !isset($_POST) ||  !isset($_POST['nono_price_table_qty']) ) return; // Not a Variable product with pricing table
		$pricing = array();
		for( $i=0;$i<count($_POST['nono_price_table_qty']);$i++ ){
			$index = absint(str_replace(',', '.', str_replace('.', '', $_POST['nono_price_table_qty'][$i])));
			if( 0 == $index ) continue; // zero quantities are not allowed

			$price = floatval(str_replace(',', '.', str_replace('.', '', $_POST['nono_price_table_price'][$i])));
			$pricing[$index] = array(
				'price' => $price,
				'label'	=> stripslashes ( $_POST['nono_price_table_label'][$i] )
			);
		}
		ksort($pricing, SORT_NUMERIC);
		update_post_meta($post_id, self::$pricing_table_key, $pricing);
kickout('save_pricing_info', $_POST, $post_id, $sorted_pricing);

		return;

		if( false === $index ) return;

		update_post_meta($variation_id, $this->min_order_key, absint($_POST['variable_min_qty'][$index]) );
		update_post_meta($variation_id, $this->price_per_piece_key, (float) $_POST['variable_per_piece_price'][$index]);
	}
}

if ( ! class_exists( 'Autoload_WP' ) ) {
	/**
	 * Generic autoloader for classes named in WordPress coding style.
	 */
	class Autoload_WP {

		public $dir = __DIR__;

		function __construct( $dir = '' ) {

			if ( ! empty( $dir ) )
				$this->dir = $dir;

			spl_autoload_register( array( $this, 'spl_autoload_register' ) );
		}

		function spl_autoload_register( $class_name ) {

			$class_path = $this->dir . '/class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

			if ( file_exists( $class_path ) )
				include $class_path;
		}
	}
}
new NonoPrintPricing();