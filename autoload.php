<?php

spl_autoload_register( function ( $className ) {
	/**
	 * Initial path and vars.
	 */
	$ds  = DIRECTORY_SEPARATOR;
	$dir = dirname( __FILE__ ) . $ds . 'src';

	/**
	 * Generate the direct path to the class
	 */
	$className = str_replace( 'IgniteKit\\WP\\OptionBuilder\\', '', $className );
	$className = str_replace( '\\', $ds, $className );

	/**
	 * Load the class if all fine.
	 */
	$file = "{$dir}{$ds}{$className}.php";
	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );