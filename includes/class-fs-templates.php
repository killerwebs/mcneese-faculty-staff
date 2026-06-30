<?php
/**
 * Loads the plugin's single + archive templates, allowing theme overrides.
 *
 * A theme can override by placing `faculty-staff/single-faculty_staff.php` or
 * `faculty-staff/archive-faculty_staff.php` in its directory.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

class FS_Templates {

	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
	}

	public static function template_include( $template ) {
		$post_type = fs_post_type();

		if ( is_singular( $post_type ) ) {
			return self::locate( 'single-faculty_staff.php', $template );
		}

		if ( is_post_type_archive( $post_type ) || is_tax( fs_taxonomy() ) ) {
			return self::locate( 'archive-faculty_staff.php', $template );
		}

		return $template;
	}

	/**
	 * Prefer a theme override, then the plugin template, then the original.
	 */
	protected static function locate( $file, $default ) {
		$theme = locate_template( array( 'faculty-staff/' . $file ) );
		if ( $theme ) {
			return $theme;
		}
		$plugin = FS_DIR_PATH . 'templates/' . $file;
		if ( file_exists( $plugin ) ) {
			return $plugin;
		}
		return $default;
	}
}
