<?php
/**
 * Plugin Name:       McNeese Faculty Staff
 * Plugin URI:        https://killerwebsites.com
 * Description:        Manage faculty & staff and display them anywhere with shortcodes. Includes a filterable directory, per-department lists, individual bio pages, and a CSV importer.
 * Version:           1.2.4
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Killerwebsites.com
 * Author URI:        https://killerwebsites.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       faculty-staff
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

define( 'FS_DIR_VERSION', '1.2.4' );
define( 'FS_DIR_FILE', __FILE__ );
define( 'FS_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'FS_DIR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Post type and taxonomy slugs are filterable so the plugin can match an
 * existing install (e.g. an export that used `faculty_staff` / `departments`).
 */
function fs_post_type() {
	return apply_filters( 'fs_post_type', 'faculty_staff' );
}

function fs_taxonomy() {
	return apply_filters( 'fs_taxonomy', 'departments' );
}

require_once FS_DIR_PATH . 'includes/class-fs-post-type.php';
require_once FS_DIR_PATH . 'includes/class-fs-meta.php';
require_once FS_DIR_PATH . 'includes/class-fs-templates.php';
require_once FS_DIR_PATH . 'includes/class-fs-shortcodes.php';
require_once FS_DIR_PATH . 'includes/class-fs-assets.php';

if ( is_admin() ) {
	require_once FS_DIR_PATH . 'includes/class-fs-importer.php';
	require_once FS_DIR_PATH . 'includes/class-fs-shortcode-help.php';
}

add_action( 'init', array( 'FS_Post_Type', 'register' ) );
add_action( 'init', array( 'FS_Meta', 'register_meta' ) );
add_filter( 'use_block_editor_for_post_type', array( 'FS_Post_Type', 'use_classic_editor' ), 10, 2 );

FS_Meta::init();
FS_Templates::init();
FS_Shortcodes::init();
FS_Assets::init();

if ( is_admin() ) {
	FS_Importer::init();
	FS_Shortcode_Help::init();
}

/**
 * Activation: register the post type, then flush rewrite rules so the
 * directory archive and bio permalinks work immediately.
 */
function fs_activate() {
	FS_Post_Type::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'fs_activate' );

function fs_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'fs_deactivate' );
