<?php
/*
Plugin Name: Shopp Rejoiner
Description: Integrates the Shopp e-commerce platform with Rejoiner.
Version: 1.1
Author: Jackson Whelan
Author URI: http://jacksonwhelan.com/
License: GPL version 3.0 @see http://www.gnu.org/licenses/gpl.html

	Shopp Rejoiner - integrates Shopp and Rejoiner
	Copyright (C) 2014 Jackson Whelan

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

class Shopp_Rejoiner {

	public static function init() {
		if ( defined('SHOPP_VERSION') )	new Shopp_Rejoiner;
	}

	public function __construct() {
		$this->add_menu_page();
		$this->add_filters();
	}
	
	protected function add_menu_page() {
		if ( ! is_admin() ) return;
		require dirname( __FILE__ ) . '/inc/options.php';
		add_action( 'admin_menu', array( $this, 'register_settings_page' ), 50 );
	}
	
	protected function add_filters() {
		if ( is_admin() ) return;
		require dirname( __FILE__ ) . '/inc/options.php';
		add_filter( 'shopp_checkout_page', array( $this, 'filter_checkout_page' ), 50 );
		add_filter( 'shopp_thanks', array( $this, 'filter_thanks_page' ), 50 );
		add_action( 'init', array( $this, 'resession_cart' ), 1 );
	}	
	
	public function resession_cart() {
		if( class_exists( 'Shopping' ) && isset( $_REQUEST['resess'] ) ) {
			$Shopping = new Shopping;
			$Shopping->resession( $_REQUEST['resess'] ); 
		}
	}
	
	public function filter_checkout_page( $content ) {
		$opts = $this->get_rejoiner_options();
		
		if ( shopp( 'cart.hasitems' ) ) :
			$subtotal = 0;
			$items = array();
			while ( shopp( 'cart.items' ) ) : 
				$items[] = array(
					'product_id' => shopp( 'cartitem.product', 'return=true' ),
					'name' => shopp( 'cartitem.name', 'return=true' ),
					'item_qty' => shopp( 'cartitem.quantity', 'return=true' ),
					'price' => $this->convert_to_cents( shopp( 'cartitem.unitprice', 'return=true' ) ),
					'qty_price' => $this->convert_to_cents( shopp( 'cartitem.total', 'return=true' ) ),
					'image_url' => shopp( 'cartitem.coverimage', 'property=src&return=true' ),
					'description' => shopp( 'cartitem.description', 'return=true' )
				);
				$subtotal = $subtotal+$this->convert_to_cents( shopp( 'cartitem.total', 'return=true' ) );
			endwhile;
			$cart = array(
				'value' => (string) $subtotal,
				'totalItems' => shopp( 'cart.total-quantity', 'return=true'  ),
			);
		endif;
		
		$js = $this->build_rejoiner_push( $items, $cart );
		
		return $js . $content;
		
	}
	
	public function filter_thanks_page( $content ) {
	
		$js = $this->build_rejoiner_convert();
		
		return $js . $content;
		
	}
	
	public function rejoiner_encode( $array ) {
		
		$json = '{';
		
		foreach( $array as $key => $val ) {
			
			$items[]= "'$key' : '$val'";
			
		}
		
		$json.= implode( ', ', $items ) . '}';
		
		return $json;		
		
	}
	
	public function build_rejoiner_push( $items, $cart ) {
	
		$options = $this->get_rejoiner_options();
		$returnUrl = shopp( 'checkout.url', 'return=true' ) . '?resess=' . session_id();
		
		$cart['returnUrl'] = apply_filters( 'rejoiner_returnurl', $returnUrl, session_id(), $cart );
		$cartdata = $this->rejoiner_encode( $cart );
		$cartjs = "_rejoiner.push(['setCartData', $cartdata]);";
		
		foreach( $items as $item ) {
			
			$data = $this->rejoiner_encode( $item );
			$itemjs.= "_rejoiner.push(['setCartItem', $data]);\r\n";
			
		}
		
		if( !empty( $options['account']['value'] ) && !empty( $options['domain']['value'] ) ) {
				
			$js = <<<EOF
<!-- Rejoiner Tracking - added by ShoppRejoiner -->

<script type='text/javascript'>
var _rejoiner = _rejoiner || [];
_rejoiner.push(['setAccount', '{$options['account']['value']}']);
_rejoiner.push(['setDomain', '{$options['domain']['value']}']);

(function() {
    var s = document.createElement('script'); s.type = 'text/javascript';
    s.async = true;
    s.src = 'https://s3.amazonaws.com/rejoiner/js/v3/t.js';
    var x = document.getElementsByTagName('script')[0];
    x.parentNode.insertBefore(s, x);
})();
</script>

<script type='text/javascript'>
    $cartjs
    $itemjs
</script>

<!-- End Rejoiner Tracking -->
EOF;
		} else {
			
			$js = "\r\n<!-- ShoppRejoiner ERROR: You must enter your details on the ShoppRejoiner settings tab. -->\r\n";	
			
		}
		
		return $js;           
		
	}
	
	public function build_rejoiner_convert() {
	
		$options = $this->get_rejoiner_options();
		$order_id = shopp('purchase','id','return=true');
		
		$js = <<<EOF
<!-- Rejoiner Conversion - added by ShoppRejoiner -->

<script type='text/javascript'>
var _rejoiner = _rejoiner || [];
_rejoiner.push(['setAccount', '{$options['account']['value']}']);
_rejoiner.push(['setDomain', '{$options['domain']['value']}']);
_rejoiner.push(['sendConversion']);

(function() {
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.async = true;
    s.src = 'https://s3.amazonaws.com/rejoiner/js/v3/t.js';
    var x = document.getElementsByTagName('script')[0];
    x.parentNode.insertBefore(s, x);
})();
</script>

<script type='text/javascript'>
_rejoiner.push(['setCartData', {'customer_order_number': '$order_id'}]);
</script>

<!-- End Rejoiner Conversion -->
                            		
EOF;
		
		return $js;
	}
	
	public function convert_to_cents( $price ) {
	
		$price = str_replace( array( '$', '.' ), '', $price );
		return $price;	
		
	}
	
	public function register_settings_page() {
		shopp_admin_add_submenu( __( 'Rejoiner', 'shopprejoiner' ), 'settings-rejoiner', 'shopp-setup',
			array('Shopp_Rejoiner_Options', 'controller'), 'shopp_settings' );
	}

	protected function get_rejoiner_options() {
		$options = (array) get_option( Shopp_Rejoiner_Options::PAGE_SETTINGS, array() );
		return $options;
	}

}

add_action( 'plugins_loaded', array( 'Shopp_Rejoiner', 'init'), 80 );