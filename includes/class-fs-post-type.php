<?php
/**
 * Registers the Faculty & Staff custom post type and the Departments taxonomy.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

class FS_Post_Type {

	/**
	 * Register the post type + taxonomy. Hooked on `init`.
	 */
	public static function register() {
		self::register_post_type();
		self::register_taxonomy();
	}

	/**
	 * Use the classic editor for people so the detail fields appear as one
	 * organized form right under the bio, instead of being tucked into a
	 * block-editor sidebar panel. Hooked on `use_block_editor_for_post_type`.
	 */
	public static function use_classic_editor( $use_block, $post_type ) {
		if ( fs_post_type() === $post_type ) {
			return false;
		}
		return $use_block;
	}

	protected static function register_post_type() {
		$labels = array(
			'name'               => __( 'Faculty & Staff', 'faculty-staff' ),
			'singular_name'      => __( 'Faculty / Staff', 'faculty-staff' ),
			'menu_name'          => __( 'Faculty & Staff', 'faculty-staff' ),
			'add_new'            => __( 'Add New', 'faculty-staff' ),
			'add_new_item'       => __( 'Add New Person', 'faculty-staff' ),
			'edit_item'          => __( 'Edit Person', 'faculty-staff' ),
			'new_item'           => __( 'New Person', 'faculty-staff' ),
			'view_item'          => __( 'View Profile', 'faculty-staff' ),
			'search_items'       => __( 'Search Faculty & Staff', 'faculty-staff' ),
			'not_found'          => __( 'No people found', 'faculty-staff' ),
			'not_found_in_trash' => __( 'No people found in Trash', 'faculty-staff' ),
			'all_items'          => __( 'All People', 'faculty-staff' ),
		);

		$args = array(
			'labels'        => $labels,
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-groups',
			'menu_position' => 26,
			'rewrite'       => array(
				'slug'       => apply_filters( 'fs_rewrite_slug', 'faculty' ),
				'with_front' => false,
			),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'author', 'custom-fields' ),
			'taxonomies'    => array( fs_taxonomy() ),
		);

		register_post_type( fs_post_type(), apply_filters( 'fs_post_type_args', $args ) );
	}

	protected static function register_taxonomy() {
		$labels = array(
			'name'              => __( 'Departments', 'faculty-staff' ),
			'singular_name'     => __( 'Department', 'faculty-staff' ),
			'search_items'      => __( 'Search Departments', 'faculty-staff' ),
			'all_items'         => __( 'All Departments', 'faculty-staff' ),
			'edit_item'         => __( 'Edit Department', 'faculty-staff' ),
			'update_item'       => __( 'Update Department', 'faculty-staff' ),
			'add_new_item'      => __( 'Add New Department', 'faculty-staff' ),
			'new_item_name'     => __( 'New Department Name', 'faculty-staff' ),
			'menu_name'         => __( 'Departments', 'faculty-staff' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => apply_filters( 'fs_taxonomy_rewrite_slug', 'department' ),
				'with_front' => false,
			),
		);

		register_taxonomy( fs_taxonomy(), array( fs_post_type() ), apply_filters( 'fs_taxonomy_args', $args ) );
	}
}
