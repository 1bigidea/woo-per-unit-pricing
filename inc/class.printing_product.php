<?php
/**
 *	Extends the Product Variation Class of WC_Product_Variation of WooCommerce
 */

class NONO_Product_Variable extends WC_Product_Variable {
	var $min_order_qty;

	public function __construct( $product ) {
		parent::__construct( $product );

		$prices = get_post_meta($product->ID, NonoPrintPricing::$pricing_table_key, true);
		if( !is_array($prices) ) return;

		ksort($prices, SORT_NUMERIC);
		foreach($prices as $min => $details){
			$this->min_order_qty = $min;
			$this->price = $this->special_price = $this->regular_price = $details['price'];
			break;
		}

		add_filter('woocommerce_quantity_input_args', array($this, 'set_min_quantities'), 10, 2);
	}

	function set_min_quantities($args, $product){
kickout('set_min_quantities_variable', $args, $product);
		$args['input_value'] = $args['min_value']	= $this->min_order_qty;

		return $args;
	}
}

class NONO_Product_Variation extends WC_Product_Variation {
	var $min_order_qty;

	function __construct($variation, $args = array() ){
		parent::__construct($variation, $args);

		$this->prices = get_post_meta($args['parent_id'], NonoPrintPricing::$pricing_table_key, true);
		ksort($this->prices, SORT_NUMERIC);
		foreach($this->prices as $min => $details){
			$this->min_order_qty = $min;
			$this->price = $this->special_price = $this->regular_price = $details['price'];
			break;
		}
		add_filter('woocommerce_quantity_input_args', array($this, 'set_min_quantities'), 10, 2);

		add_filter('woocommerce_available_variation', array($this, 'set_min_qty_attributes'), 10, 3);
	}

	function set_min_quantities($args, $product){
kickout('set_min_quantities_variation', $args, $product);
		$args['input_value'] = $args['min_value'] = $this->min_order_qty;

		return $args;
	}

	function set_min_qty_attributes($attributes, $object, $variation){

		$attributes['min_qty'] = $this->min_order_qty;
		$attributes['price_html'] = $this->get_price_html();
		$attributes['price_breaks'] = $this->prices;

		return $attributes;
	}
}