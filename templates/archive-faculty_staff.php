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

$shortcode = '[faculty_directory]';
$heading   = post_type_archive_title( '', false );

if ( is_tax( fs_taxonomy() ) ) {
	$term      = get_queried_object();
	$heading   = $term->name;
	$shortcode = '[faculty_directory department="' . esc_attr( $term->slug ) . '" filter="false"]';
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
