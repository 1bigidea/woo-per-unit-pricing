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
		add_action('woocommerce_product_after_variable_attributes', array($this, 'variation_admin'), 10, 3);
		add_action('woocommerce_save_product_variation', array($this, 'save_pricing_info') );
	}
	/**
	 *	Display fields for Min Qty and Price per Piece
	 */
	function variation_admin($loop, $variation_data, $variation){

		$_min_qty = get_post_meta($variation->ID, $this->min_order_key, true);
		$_per_piece_price = get_post_meta($variation->ID, $this->price_per_piece_key, true);
?>
		<tr>
			<td>
				<div>
					<label><?php _e( 'Min Qty:', 'nono-print-pricing' ); ?> <a class="tips" data-tip="<?php _e( 'Minimum number of pieces at above price', 'nono-print-pricing' ); ?>" href="#">[?]</a></label>
					<input type="number" size="5" name="variable_min_qty[<?php echo $loop; ?>]" value="<?php if ( isset( $_min_qty ) ) echo esc_attr( $_min_qty ); ?>" />
				</div>
			</td>
			<td>
				<div>
					<label><?php echo __( 'Price per piece:', 'nono-print-pricing' ) . ' ('.get_woocommerce_currency_symbol().')'; ?></label>
					<input type="number" size="5" name="variable_per_piece_price[<?php echo $loop; ?>]" value="<?php if ( isset( $_per_piece_price ) ) echo number_format( $_per_piece_price, 2, '.', '' ); ?>" step="any" min="0" placeholder="<?php _e( 'Per piece price (required)', 'nono-print-pricing' ); ?>" />
				</div>
			</td>
		</tr>
<?php
	}
	function save_pricing_info($variation_id){

		$variation_ids = $_POST['variable_post_id'];

		$index = array_search($variation_id, $_POST['variable_post_id']);

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