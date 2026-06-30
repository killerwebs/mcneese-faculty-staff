<?php
/**
 * Single faculty/staff bio page.
 *
 * Override by copying to `your-theme/faculty-staff/single-faculty_staff.php`.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id   = get_the_ID();
	$name      = get_the_title();
	$position  = get_post_meta( $post_id, 'fs_title', true );
	$email     = get_post_meta( $post_id, 'fs_email', true );
	$phone     = get_post_meta( $post_id, 'fs_phone', true );
	$location  = get_post_meta( $post_id, 'fs_location', true );
	$website   = get_post_meta( $post_id, 'fs_website', true );
	$photo     = FS_Meta::photo_url( $post_id, 'large' );
	$terms     = get_the_terms( $post_id, fs_taxonomy() );
	?>
	<div class="fs-single">
		<div class="fs-single-inner">

			<aside class="fs-single-sidebar">
				<div class="fs-single-photo">
					<?php if ( $photo ) : ?>
						<img src="<?php echo esc_url( $photo ); ?>" alt="<?php echo esc_attr( $name ); ?>" />
					<?php endif; ?>
				</div>

				<div class="fs-single-contact">
					<?php if ( $email ) : ?>
						<p class="fs-contact-row"><span class="fs-contact-label"><?php esc_html_e( 'Email', 'faculty-staff' ); ?></span>
							<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
					<?php endif; ?>
					<?php if ( $phone ) : ?>
						<p class="fs-contact-row"><span class="fs-contact-label"><?php esc_html_e( 'Phone', 'faculty-staff' ); ?></span>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
					<?php endif; ?>
					<?php if ( $location ) : ?>
						<p class="fs-contact-row"><span class="fs-contact-label"><?php esc_html_e( 'Office', 'faculty-staff' ); ?></span>
							<?php echo esc_html( $location ); ?></p>
					<?php endif; ?>
					<?php if ( $website ) : ?>
						<p class="fs-contact-row"><span class="fs-contact-label"><?php esc_html_e( 'Website', 'faculty-staff' ); ?></span>
							<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'faculty-staff' ); ?></a></p>
					<?php endif; ?>
				</div>
			</aside>

			<div class="fs-single-main">
				<header class="fs-single-header">
					<h1 class="fs-single-name"><?php echo esc_html( $name ); ?></h1>
					<?php if ( $position ) : ?>
						<p class="fs-single-position"><?php echo esc_html( $position ); ?></p>
					<?php endif; ?>
					<?php if ( is_array( $terms ) && $terms ) : ?>
						<p class="fs-single-depts">
							<?php
							$links = array();
							foreach ( $terms as $t ) {
								$links[] = '<a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a>';
							}
							echo wp_kses_post( implode( ', ', $links ) );
							?>
						</p>
					<?php endif; ?>
				</header>

				<div class="fs-single-bio">
					<?php the_content(); ?>
				</div>

				<?php
				// Default to the directory archive; faculty-staff.js rewrites this
				// to the referring page (when same-origin) so visitors return to
				// the department list they came from instead of the full directory.
				$archive  = get_post_type_archive_link( fs_post_type() );
				$fallback = $archive ? $archive : home_url( '/' );
				?>
				<p class="fs-single-back">
					<a href="<?php echo esc_url( $fallback ); ?>" class="fs-back-link" data-back-label="<?php esc_attr_e( 'Back', 'faculty-staff' ); ?>">&larr; <?php esc_html_e( 'Back to Directory', 'faculty-staff' ); ?></a>
				</p>
			</div>

		</div>
	</div>
	<?php

endwhile;

get_footer();
