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

		extract( shortcode_atts( array('id' => 0, 'price_levels' => "50,100,250,500,1000"), $atts) );

		if( 0 == $id ) {
			$the_product = $product;
		} else {
			$the_product = get_product($id);
		}

		if ( ! NonoPrintPricing::is_bulk_product($the_product->post) ) return '';

		$rows = explode(',', $price_levels);

		$output = '<table class="price-table">';
		$output .= sprintf('<thead><th>%s</th><th>%s</th></thead>', __('Qty', 'nono-per-unit'), __('Price', 'nono-per-unit'));
		foreach( $rows as $qty ){
			$reg_price = NonoPrintPricing::determine_price($the_product, $qty);
			if( ! $reg_price ) continue; // this qty could be less than the pricing minimum quantity

			$reg_price_formatted = woocommerce_price($reg_price, array());
			$sale_price = NonoPrintPricing::determine_price($the_product, $qty, 'sale');
			if( !empty($sale_price) ){
				$reg_price_formatted = '<del>'.$reg_price_formatted.'</del>';
				$sale_price = woocommerce_price($sale_price, array());
			}

			$row_text = '<tr><td class="price-table-qty">%d</td><td class="price-table-amount">%s %s</td></tr>';
			$output .= sprintf($row_text, (int) $qty, $reg_price_formatted, $sale_price );
		}
		$output .= '</table>';

		return $output;
	}
}
new NonoPrintPricingShortcodes;
