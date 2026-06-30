<?php
/**
 * In-admin shortcode reference page (Faculty & Staff → Shortcodes).
 *
 * Documents the three shortcodes and every attribute, with copy-paste
 * examples and a live list of the site's department slugs.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

class FS_Shortcode_Help {

	const CAPABILITY = 'edit_posts';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );

		// "Shortcode" column on the Departments term list.
		add_filter( 'manage_edit-' . fs_taxonomy() . '_columns', array( __CLASS__, 'term_columns' ) );
		add_filter( 'manage_' . fs_taxonomy() . '_custom_column', array( __CLASS__, 'term_column_content' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_copy' ) );
	}

	/**
	 * Load the copy-to-clipboard helper on the Departments term screen.
	 */
	public static function enqueue_copy( $hook ) {
		if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || fs_taxonomy() !== $screen->taxonomy ) {
			return;
		}
		wp_enqueue_script( 'fs-admin-copy', FS_DIR_URL . 'assets/js/fs-admin-copy.js', array(), FS_DIR_VERSION, true );
	}

	/**
	 * Add a Shortcode column to the Departments list, just before Count.
	 */
	public static function term_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'posts' === $key ) {
				$new['fs_shortcode'] = __( 'Shortcode', 'faculty-staff' );
			}
			$new[ $key ] = $label;
		}
		if ( ! isset( $new['fs_shortcode'] ) ) {
			$new['fs_shortcode'] = __( 'Shortcode', 'faculty-staff' );
		}
		return $new;
	}

	/**
	 * Render the per-department shortcode + copy button.
	 */
	public static function term_column_content( $content, $column, $term_id ) {
		if ( 'fs_shortcode' !== $column ) {
			return $content;
		}
		$term = get_term( $term_id, fs_taxonomy() );
		if ( ! $term || is_wp_error( $term ) ) {
			return $content;
		}
		$code = '[faculty_department dept="' . $term->slug . '"]';
		return sprintf(
			'<code style="font-size:12px">%1$s</code> <button type="button" class="button button-small fs-copy" data-copy="%2$s" data-copied-label="%3$s">%4$s</button>',
			esc_html( $code ),
			esc_attr( $code ),
			esc_attr__( 'Copied', 'faculty-staff' ),
			esc_html__( 'Copy', 'faculty-staff' )
		);
	}

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . fs_post_type(),
			__( 'Shortcodes', 'faculty-staff' ),
			__( 'Shortcodes', 'faculty-staff' ),
			self::CAPABILITY,
			'fs-shortcodes',
			array( __CLASS__, 'render' )
		);
	}

	/* ----------------------------------------------------------------- *
	 * Attribute definitions
	 * ----------------------------------------------------------------- */

	protected static function directory_attributes() {
		return array(
			array( 'layout', 'grid', __( 'grid, list, compact, or names', 'faculty-staff' ) ),
			array( 'columns', '3', __( 'Cards per row on desktop.', 'faculty-staff' ) ),
			array( 'columns_md', 'auto', __( 'Cards per row on tablet (≤900px). Defaults to 2.', 'faculty-staff' ) ),
			array( 'columns_sm', '1', __( 'Cards per row on phones (≤600px).', 'faculty-staff' ) ),
			array( 'photo_shape', 'square', __( 'square, circle, or portrait.', 'faculty-staff' ) ),
			array( 'accent', '—', __( 'Hex color to re-skin just this instance, e.g. #8a0050.', 'faculty-staff' ) ),
			array( 'department', '—', __( 'Limit to one or more departments (slug or name, comma-separated).', 'faculty-staff' ) ),
			array( 'orderby', 'title', __( 'title, menu_order, date, or rand.', 'faculty-staff' ) ),
			array( 'order', 'ASC', __( 'ASC or DESC.', 'faculty-staff' ) ),
			array( 'filter', 'true', __( 'Show the department filter pills.', 'faculty-staff' ) ),
			array( 'search', 'true', __( 'Show the live search box.', 'faculty-staff' ) ),
			array( 'groupby', '—', __( 'Set to "department" to add a heading per department.', 'faculty-staff' ) ),
			array( 'index', 'false', __( 'Show an A–Z jump bar.', 'faculty-staff' ) ),
			array( 'view_toggle', 'false', __( 'Show a Grid / List view switch in the toolbar.', 'faculty-staff' ) ),
			array( 'sort', 'false', __( 'Show an A–Z / Z–A sort control in the toolbar.', 'faculty-staff' ) ),
			array( 'show_title', 'true', __( 'Show the position under the name.', 'faculty-staff' ) ),
			array( 'show_dept', 'false', __( 'Show the department label on each card.', 'faculty-staff' ) ),
			array( 'dept_badge', 'false', __( 'Show the department as a pill overlay on the photo (like the program cards).', 'faculty-staff' ) ),
			array( 'show_email', 'false', __( 'Show a clickable email on each card.', 'faculty-staff' ) ),
			array( 'show_phone', 'false', __( 'Show a clickable phone number on each card.', 'faculty-staff' ) ),
			array( 'show_location', 'false', __( 'Show the office location on each card.', 'faculty-staff' ) ),
			array( 'show_website', 'false', __( 'Show a website link on each card.', 'faculty-staff' ) ),
			array( 'show_contact', 'false', __( 'Shorthand: show email, phone, office, and website on each card.', 'faculty-staff' ) ),
			array( 'show_excerpt', 'false', __( 'Show a short bio excerpt on each card.', 'faculty-staff' ) ),
			array( 'button', '—', __( 'CTA label linking to the bio, e.g. "View Profile". Empty = no button.', 'faculty-staff' ) ),
			array( 'number', '-1', __( 'Maximum people to show (-1 = all).', 'faculty-staff' ) ),
			array( 'empty', '—', __( 'Message shown when no people match.', 'faculty-staff' ) ),
		);
	}

	protected static function member_attributes() {
		return array(
			array( 'slug', '—', __( 'The person\'s slug, e.g. jane-doe.', 'faculty-staff' ) ),
			array( 'id', '0', __( 'Use the numeric post ID instead of a slug.', 'faculty-staff' ) ),
			array( 'layout', 'grid', __( 'grid, list, compact, or names.', 'faculty-staff' ) ),
			array( 'photo_shape', 'square', __( 'square, circle, or portrait.', 'faculty-staff' ) ),
			array( 'accent', '—', __( 'Hex color override.', 'faculty-staff' ) ),
			array( 'show_title', 'true', __( 'Show the position.', 'faculty-staff' ) ),
			array( 'show_dept', 'true', __( 'Show the department(s).', 'faculty-staff' ) ),
			array( 'show_email', 'true', __( 'Show the email.', 'faculty-staff' ) ),
			array( 'show_phone', 'true', __( 'Show the phone.', 'faculty-staff' ) ),
			array( 'show_location', 'true', __( 'Show the office location.', 'faculty-staff' ) ),
			array( 'show_website', 'true', __( 'Show the website link.', 'faculty-staff' ) ),
			array( 'show_excerpt', 'false', __( 'Show a short bio excerpt.', 'faculty-staff' ) ),
		);
	}

	/* ----------------------------------------------------------------- *
	 * Rendering
	 * ----------------------------------------------------------------- */

	public static function render() {
		?>
		<div class="wrap fs-help">
			<h1><?php esc_html_e( 'Faculty & Staff Shortcodes', 'faculty-staff' ); ?></h1>
			<p class="fs-help-lead"><?php esc_html_e( 'Paste a shortcode into any page, post, or builder text/HTML widget. Click “Copy” on an example to grab it.', 'faculty-staff' ); ?></p>

			<style>
				.fs-help .fs-help-lead { font-size: 14px; max-width: 760px; }
				.fs-help h2 { margin-top: 34px; }
				.fs-help-card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 18px 20px; margin: 12px 0 4px; max-width: 920px; }
				.fs-help-example { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 6px 0; }
				.fs-help-example code { background: #f6f7f7; border: 1px solid #e0e0e0; border-radius: 4px; padding: 7px 10px; font-size: 13px; display: inline-block; }
				.fs-help table.widefat { max-width: 920px; margin-top: 8px; }
				.fs-help table.widefat td:first-child code { font-weight: 600; }
				.fs-help .fs-help-default { color: #646970; white-space: nowrap; }
				.fs-help-depts { max-width: 920px; }
				.fs-help-depts code { background: #f6f7f7; border: 1px solid #e0e0e0; border-radius: 4px; padding: 2px 6px; }
			</style>

			<?php
			// --- [faculty_directory] ---
			self::section(
				'[faculty_directory]',
				__( 'The full, filterable and searchable grid of everyone. Use it for a main directory page.', 'faculty-staff' ),
				array(
					'[faculty_directory]',
					'[faculty_directory view_toggle="true" sort="true" show_contact="true"]',
					'[faculty_directory groupby="department" index="true"]',
				),
				self::directory_attributes()
			);

			// --- [faculty_department] ---
			self::section(
				'[faculty_department]',
				__( 'One department, with the filter bar hidden. Drop it on a program or department page. Accepts every [faculty_directory] attribute below.', 'faculty-staff' ),
				array(
					'[faculty_department dept="accounting"]',
					'[faculty_department dept="accounting" show_excerpt="true" button="View Profile"]',
					'[faculty_department dept="History" layout="compact"]',
				),
				null,
				__( 'Required: <code>dept</code> (a department slug or name). All other attributes are the same as [faculty_directory].', 'faculty-staff' )
			);

			// --- [faculty_member] ---
			self::section(
				'[faculty_member]',
				__( 'A single person card. Handy for embedding one leader or contact on a page.', 'faculty-staff' ),
				array(
					'[faculty_member slug="jane-doe"]',
					'[faculty_member slug="jane-doe" layout="list" photo_shape="circle"]',
				),
				self::member_attributes()
			);

			self::departments_reference();
			self::bio_note();
			?>
		</div>

		<script>
		( function () {
			document.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest( '.fs-copy' );
				if ( ! btn ) { return; }
				var text = btn.getAttribute( 'data-copy' ) || '';
				if ( navigator.clipboard ) { navigator.clipboard.writeText( text ); }
				var label = btn.textContent;
				btn.textContent = <?php echo wp_json_encode( __( 'Copied', 'faculty-staff' ) ); ?>;
				setTimeout( function () { btn.textContent = label; }, 1200 );
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Render one shortcode block: heading, description, examples, optional table.
	 *
	 * @param string     $name        Shortcode (display).
	 * @param string     $desc        Description.
	 * @param string[]   $examples    Copy-paste examples.
	 * @param array|null $attributes  Rows of [name, default, description] or null.
	 * @param string     $note        Optional HTML note (used when no table).
	 */
	protected static function section( $name, $desc, $examples, $attributes = null, $note = '' ) {
		echo '<h2><code>' . esc_html( $name ) . '</code></h2>';
		echo '<div class="fs-help-card">';
		echo '<p>' . esc_html( $desc ) . '</p>';
		foreach ( $examples as $code ) {
			self::example( $code );
		}
		echo '</div>';

		if ( $note ) {
			echo '<p class="description" style="max-width:920px">' . wp_kses_post( $note ) . '</p>';
		}

		if ( is_array( $attributes ) ) {
			echo '<table class="widefat striped">';
			echo '<thead><tr><th>' . esc_html__( 'Attribute', 'faculty-staff' ) . '</th><th>' . esc_html__( 'Default', 'faculty-staff' ) . '</th><th>' . esc_html__( 'Description', 'faculty-staff' ) . '</th></tr></thead><tbody>';
			foreach ( $attributes as $row ) {
				printf(
					'<tr><td><code>%s</code></td><td class="fs-help-default">%s</td><td>%s</td></tr>',
					esc_html( $row[0] ),
					esc_html( $row[1] ),
					wp_kses_post( $row[2] )
				);
			}
			echo '</tbody></table>';
		}
	}

	protected static function example( $code ) {
		printf(
			'<div class="fs-help-example"><code>%1$s</code><button type="button" class="button button-small fs-copy" data-copy="%2$s">%3$s</button></div>',
			esc_html( $code ),
			esc_attr( $code ),
			esc_html__( 'Copy', 'faculty-staff' )
		);
	}

	/**
	 * Live list of department names + slugs for use in dept="..." / department="...".
	 */
	protected static function departments_reference() {
		$terms = get_terms(
			array(
				'taxonomy'   => fs_taxonomy(),
				'hide_empty' => false,
			)
		);

		echo '<h2>' . esc_html__( 'Your departments', 'faculty-staff' ) . '</h2>';
		echo '<div class="fs-help-card fs-help-depts">';

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			echo '<p>' . esc_html__( 'No departments yet. Add them under Faculty & Staff → Departments, or import a CSV with a Departments column.', 'faculty-staff' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<p>' . esc_html__( 'Copy a ready-made shortcode for any department, or use the slug in the department / dept attribute yourself:', 'faculty-staff' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Department', 'faculty-staff' ) . '</th><th>' . esc_html__( 'Slug', 'faculty-staff' ) . '</th><th>' . esc_html__( 'Count', 'faculty-staff' ) . '</th><th>' . esc_html__( 'Shortcode', 'faculty-staff' ) . '</th></tr></thead><tbody>';
		foreach ( $terms as $term ) {
			$code = '[faculty_department dept="' . $term->slug . '"]';
			printf(
				'<tr><td>%1$s</td><td><code>%2$s</code></td><td>%3$d</td><td><div class="fs-help-example"><code>%4$s</code><button type="button" class="button button-small fs-copy" data-copy="%5$s">%6$s</button></div></td></tr>',
				esc_html( $term->name ),
				esc_html( $term->slug ),
				(int) $term->count,
				esc_html( $code ),
				esc_attr( $code ),
				esc_html__( 'Copy', 'faculty-staff' )
			);
		}
		echo '</tbody></table>';
		echo '</div>';
	}

	protected static function bio_note() {
		echo '<h2>' . esc_html__( 'Bio pages', 'faculty-staff' ) . '</h2>';
		echo '<div class="fs-help-card">';
		echo '<p>' . esc_html__( 'Every person automatically gets their own bio page; cards, names, and the “View Profile” button link to it. Colors and fonts inherit from your theme, so the directory and bio pages match the rest of the site.', 'faculty-staff' ) . '</p>';
		echo '<p class="description">' . wp_kses_post( __( 'Prefer to design these in your page builder (e.g. a Breakdance template for the Faculty &amp; Staff post type and Departments taxonomy)? Add <code>add_filter( \'fs_use_plugin_templates\', \'__return_false\' );</code> and the plugin leaves the single + archive layout to your theme. The <code>[faculty_member]</code> shortcode and the post meta fields are available for dynamic data.', 'faculty-staff' ) ) . '</p>';
		$archive = get_post_type_archive_link( fs_post_type() );
		if ( $archive ) {
			printf(
				'<p>%s <a href="%s" target="_blank" rel="noopener">%s</a></p>',
				esc_html__( 'Directory archive:', 'faculty-staff' ),
				esc_url( $archive ),
				esc_html( $archive )
			);
		}
		echo '</div>';
	}
}
