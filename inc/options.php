<?php
class Shopp_Rejoiner_Options {

	public static $rejoiner_settings = array(
        'account' => array(
            'name' => 'Rejoiner Account',
            'description' => 'You can find your unique Site ID on the Implementation page inside of your Rejoiner dashboard.' ),
		'domain' => array(
			'name' => 'Domain',
			'description' => 'Enter your domain for the tracking code. Example: .domain.com or .www.domain.com' )
	);
	
	const PAGE_SETTINGS = 'shopprejoiner';

	public static function controller() {
		new self();
	}

	public function __construct() {
		$this->save();
		$this->form();
	}

	protected function save() {
		if ( ! isset( $_GET['_wpnonce'] ) or empty($_POST) ) return;
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'shopprejoinerchanges' ) ) return;

		$options = (array) get_option( self::PAGE_SETTINGS, array() );

		foreach ( self::$rejoiner_settings as $setting => $field ) {
			$valueField = "value-$setting";
			if ( isset( $_POST[$valueField] ) ) $options[$setting]['value'] = esc_attr( $_POST[$valueField] );
		}

		update_option( self::PAGE_SETTINGS, $options );
	}

	protected function form() {
		$options = (array) get_option( self::PAGE_SETTINGS, array() );

		foreach ( self::$rejoiner_settings as $setting => $field ) {
			if ( isset($options[$setting] ) ) extract( $options[$setting] );
			self::$rejoiner_settings[$setting]['value'] = isset( $value ) ? stripslashes( $value ) : '';
		}

		$rejoiner_settings = self::$rejoiner_settings;
		$action = wp_nonce_url( get_admin_url( null, 'admin.php?page=settings-rejoiner' ), 'shopprejoinerchanges' );
		include dirname( __FILE__ ) . '/adminpage.php';
	}
	
}