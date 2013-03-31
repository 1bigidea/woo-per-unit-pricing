<?php

class NonoPrintPricingShortcodes {
	private static $_this;
	var $plugin_slug = "NonoPrintPricingShortcodes";
	var $plugin_name = "";
	var $plugin_version = "1.0";

	function __construct() {

		add_shortcode('pricetable', array($this, 'pricetable'));

	}
	static function this(){
		// enables external management of filters/actions
		// http://hardcorewp.com/2012/enabling-action-and-filter-hook-removal-from-class-based-wordpress-plugins/
		// enables external management of filters/actions
		// http://hardcorewp.com/2012/enabling-action-and-filter-hook-removal-from-class-based-wordpress-plugins/
		// http://7php.com/how-to-code-a-singleton-design-pattern-in-php-5/
		if( !is_object(self::$_this) ) self::$_this = new NonoPrintPricingShortcodes();

		return self::$_this;
	}
	/*
	 *	Functions below actually do the work
	 */

	function pricetable($atts){
		global $woocommerce, $product;

		extract( shortcode_atts(array('id' => 0, 'qty' => "50,100,250,500,1000"), $atts) );

		if( 0 == $id ) {
			$this_product = $product;
		} else {
			// get the product
		}

		$variations = $product->get_available_variations();

		foreach ($variations as $variation){
			$v_product = new WC_Product_Variation($variation['variation_id']);

			$method = $variation['attributes']['attribute_pa_printing-method'];
			$prices[$method] = array(
				'min'		=> get_post_meta($variation['variation_id'], '_nono_min_order_qty', true),
				'price'		=> $v_product->price,
				'sale_price'=> $v_product->sale_price,
				'addl_pieces'	=> get_post_meta($variation['variation_id'], '_nono_price_per_piece', true),
			);
		}

		$rows = explode(',', $qty);

		$output = '<table class="price-table">';
		$output .= sprintf('<thead><th>%s</th><th>%s</th></thead>', __('Qty', 'nono-per-unit'), __('Price', 'nono-per-unit'));
		foreach( $rows as $row ){
			$row_text = '<tr><td class="price-table-qty">%d</td><td class="price-table-amount">%s</td></tr>';
			$output .= sprintf($row_text, (int) $row, number_format(self::determine_price($row, $prices), 2, ',', '') );
		}
		$output .= '</table>';

		return $output;
	}

	function determine_price($qty, $prices){
		$pricing = 0;
		foreach($prices as $method => $details ){
			if( $qty >= $details['min'] ) {
				$calc = ($details['min'] * $details['price']) + ( ($qty - $details['min']) * $details['addl_pieces'] );
				if( $qty >= $details['min'] )
					$pricing = $calc;
			}
		}
		return $pricing;
	}
}
new NonoPrintPricingShortcodes;