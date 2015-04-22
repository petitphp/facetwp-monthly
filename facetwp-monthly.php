<?php
/*
Plugin Name: FacetWP - Monthly
Plugin URI: https://github.com/petitphp/facetwp-monthly
Description: Filter your posts by monthly archive
Version: 1.0
Author: PetitPHP
Author URI: https://github.com/petitphp
*/

// don't load directly
if ( !defined( 'ABSPATH' ) ) {
	die( '-1' );
}

define( 'FWP_MTLY_VER', '1.0.0' );
define( 'FWP_MTLY_URL', plugin_dir_url( __FILE__ ) );
define( 'FWP_MTLY_DIR', plugin_dir_path( __FILE__ ) );

class FWP_MTLY {

	function __construct() {
		add_action( 'init' , array( $this, 'init' ) );
	}


	/**
	 * Intialize.
	 */
	function init() {
		add_filter( 'facetwp_facet_types', array( $this, 'register_facet_type' ) );
	}

	/**
	 * Register the "monthly" facet type.
	 *
	 * @param array $facet_types list of registered facets
	 *
	 * @return array
	 */
	function register_facet_type( $facet_types ) {
		include( dirname( __FILE__ ) . '/classes/monthly.php' );
		$facet_types['monthly'] = new FacetWP_Facet_Monthly();
		return $facet_types;
	}
}

$fwp_p2p = new FWP_MTLY();
