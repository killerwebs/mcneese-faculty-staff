<?php
/**
 * Directory archive + department term archive.
 *
 * Reuses the [faculty_directory] shortcode so the archive and shortcode pages
 * look identical. On a department term archive it pre-scopes to that term.
 *
 * Override by copying to `your-theme/faculty-staff/archive-faculty_staff.php`.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

get_header();

/**
 * Default attributes for the archive directory. Filterable so a site can
 * tweak the overview without editing this template:
 *
 *     add_filter( 'fs_archive_atts', function () {
 *         return 'sort="true" view_toggle="true" dept_badge="true" button="View Profile"';
 *     } );
 */
$atts    = apply_filters( 'fs_archive_atts', 'sort="true" view_toggle="true" dept_badge="true" show_contact="true" button="View Profile" per_page="12"' );
$heading = post_type_archive_title( '', false );

if ( is_tax( fs_taxonomy() ) ) {
	$term      = get_queried_object();
	$heading   = $term->name;
	$shortcode = '[faculty_directory department="' . esc_attr( $term->slug ) . '" filter="false" ' . $atts . ']';
} else {
	$shortcode = '[faculty_directory ' . $atts . ']';
}
?>
<div class="fs-archive">
	<header class="fs-archive-header">
		<h1 class="fs-archive-title"><?php echo esc_html( $heading ? $heading : __( 'Directory', 'faculty-staff' ) ); ?></h1>
		<?php if ( is_tax( fs_taxonomy() ) && term_description() ) : ?>
			<div class="fs-archive-desc"><?php echo wp_kses_post( term_description() ); ?></div>
		<?php endif; ?>
	</header>

	<?php echo do_shortcode( $shortcode ); ?>
</div>
<?php

get_footer();
