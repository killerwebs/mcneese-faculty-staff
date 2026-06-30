<?php
/**
 * Registers + conditionally enqueues the front-end CSS/JS.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

class FS_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		wp_register_style( 'faculty-staff', FS_DIR_URL . 'assets/css/faculty-staff.css', array(), FS_DIR_VERSION );
		wp_register_script( 'faculty-staff', FS_DIR_URL . 'assets/js/faculty-staff.js', array(), FS_DIR_VERSION, true );

		// Single bio pages always get the styles.
		if ( is_singular( fs_post_type() ) || is_post_type_archive( fs_post_type() ) || is_tax( fs_taxonomy() ) ) {
			self::enqueue();
		}
	}

	/**
	 * Safe to call from inside a shortcode; only enqueues once.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'faculty-staff' );
		wp_enqueue_script( 'faculty-staff' );
	}
}
