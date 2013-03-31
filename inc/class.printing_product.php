<?php
/**
 *	Extends the Product Variation Class of WC_Product_Variation of WooCommerce
 */

class NONO_Product_Variable extends WC_Product_Variable {

}

class NONO_Product_Variation extends WC_Product_Variation {

	function __construct(){
		parent::__construct();

		$this->price = $this->special_price = $this->regular_price = (float) 1.00;

	}

}