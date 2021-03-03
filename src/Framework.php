<?php

namespace IgniteKit\WP\OptionBuilder;

/**
 * Class OPB
 *
 * The main class responsible for interacting with the library
 */
class Framework {

	/**
	 * OPB_Manager constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		Bootstrap::run();
	}

	/**
	 * Register admin settings
	 *
	 * @param $args
	 */
	public function register_settings( $args ) {
		if ( ! $args ) {
			return;
		}
		new Settings( $args );
	}

	/**
	 * Registers a metabox
	 *
	 * @param $args
	 */
	public function register_metabox( $args ) {
		if ( ! $args ) {
			return;
		}
		new Metabox( $args );
	}

}