<?php
/**
 * Custom fields for a faculty/staff member.
 *
 * Meta keys intentionally match the export format (`fs_title`, `fs_email`,
 * `fs_phone`, `fs_location`) so CSV re-imports map cleanly, plus a couple of
 * optional extras.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

class FS_Meta {

	/**
	 * Field definitions: key => label. `fs_image_url` holds a remote photo URL
	 * used as a fallback when no featured image has been attached.
	 */
	public static function fields() {
		return array(
			'fs_title'    => __( 'Title / Position', 'faculty-staff' ),
			'fs_email'    => __( 'Email', 'faculty-staff' ),
			'fs_phone'    => __( 'Phone', 'faculty-staff' ),
			'fs_location' => __( 'Office Location', 'faculty-staff' ),
			'fs_website'  => __( 'Website / CV URL', 'faculty-staff' ),
			'fs_image_url'=> __( 'Photo URL (fallback)', 'faculty-staff' ),
		);
	}

	/**
	 * The detail fields organized into labeled sections for the edit form,
	 * with input types, placeholders, and help text. Keys must match fields().
	 */
	public static function field_groups() {
		return array(
			array(
				'title'  => __( 'Position', 'faculty-staff' ),
				'fields' => array(
					'fs_title' => array(
						'type'        => 'text',
						'placeholder' => __( 'e.g. Associate Professor of History', 'faculty-staff' ),
						'desc'        => __( 'Shown under the name on directory cards and the bio page.', 'faculty-staff' ),
					),
				),
			),
			array(
				'title'  => __( 'Contact', 'faculty-staff' ),
				'fields' => array(
					'fs_email'    => array(
						'type'        => 'email',
						'placeholder' => 'name@mcneese.edu',
					),
					'fs_phone'    => array(
						'type'        => 'text',
						'placeholder' => '337-475-0000',
					),
					'fs_location' => array(
						'type'        => 'text',
						'placeholder' => __( 'e.g. Kaufman Hall 103', 'faculty-staff' ),
					),
				),
			),
			array(
				'title'  => __( 'Links & Photo', 'faculty-staff' ),
				'fields' => array(
					'fs_website'   => array(
						'type'        => 'url',
						'placeholder' => 'https://',
					),
					'fs_image_url' => array(
						'type'        => 'url',
						'placeholder' => 'https://',
						'desc'        => __( 'Used only when no Featured Image is set. The CSV importer fills this in automatically.', 'faculty-staff' ),
					),
				),
			),
		);
	}

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_' . fs_post_type(), array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_panel' ) );

		// Scannable list-table columns.
		add_filter( 'manage_' . fs_post_type() . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . fs_post_type() . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	/**
	 * Is the block editor in use for our post type on the current screen?
	 */
	protected static function is_block_editor() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		return $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor();
	}

	/**
	 * Load the document-sidebar panel in the block editor.
	 */
	public static function enqueue_editor_panel() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || fs_post_type() !== $screen->post_type ) {
			return;
		}
		wp_enqueue_script(
			'fs-editor-panel',
			FS_DIR_URL . 'assets/js/fs-editor-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n' ),
			FS_DIR_VERSION,
			true
		);
		wp_localize_script( 'fs-editor-panel', 'fsEditorPanel', array( 'postType' => fs_post_type() ) );
	}

	/**
	 * Register meta so values are sanitized and exposed in the REST API.
	 */
	public static function register_meta() {
		$post_type = fs_post_type();
		foreach ( array_keys( self::fields() ) as $key ) {
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => array( __CLASS__, 'sanitize_field' ),
					'auth_callback'     => function() {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	public static function sanitize_field( $value, $key = '' ) {
		if ( 'fs_email' === $key ) {
			return sanitize_email( $value );
		}
		if ( in_array( $key, array( 'fs_website', 'fs_image_url' ), true ) ) {
			return esc_url_raw( $value );
		}
		return sanitize_text_field( $value );
	}

	public static function add_meta_box() {
		// In the block editor the fields live in the document sidebar panel
		// (see enqueue_editor_panel); a duplicate meta box would fight it on
		// save, so only register the classic meta box when the block editor
		// is not in use.
		if ( self::is_block_editor() ) {
			return;
		}
		add_meta_box(
			'fs_details',
			__( 'Faculty / Staff Details', 'faculty-staff' ),
			array( __CLASS__, 'render' ),
			fs_post_type(),
			'normal',
			'high'
		);
	}

	public static function render( $post ) {
		wp_nonce_field( 'fs_save_details', 'fs_details_nonce' );
		$labels = self::fields();
		?>
		<style>
			.fs-form { margin: 4px 0 0; }
			.fs-form-section { padding: 4px 0 18px; }
			.fs-form-section + .fs-form-section { border-top: 1px solid #e2e6ea; padding-top: 16px; }
			.fs-form-section-title { margin: 0 0 14px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #50575e; }
			.fs-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px 24px; }
			.fs-form-row label { display: block; font-weight: 600; margin-bottom: 4px; }
			.fs-form-row .fs-input { width: 100%; }
			.fs-form-desc { margin: 5px 0 0; color: #646970; font-size: 12px; }
			.fs-form-note { margin: 16px 0 0; padding-top: 14px; border-top: 1px solid #e2e6ea; color: #646970; }
		</style>
		<div class="fs-form">
			<?php foreach ( self::field_groups() as $group ) : ?>
				<div class="fs-form-section">
					<h2 class="fs-form-section-title"><?php echo esc_html( $group['title'] ); ?></h2>
					<div class="fs-form-grid">
						<?php foreach ( $group['fields'] as $key => $field ) :
							$value = get_post_meta( $post->ID, $key, true );
							$label = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
							?>
							<div class="fs-form-row">
								<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
								<input
									type="<?php echo esc_attr( $field['type'] ); ?>"
									id="<?php echo esc_attr( $key ); ?>"
									name="<?php echo esc_attr( $key ); ?>"
									value="<?php echo esc_attr( $value ); ?>"
									class="fs-input regular-text"
									placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"
								/>
								<?php if ( ! empty( $field['desc'] ) ) : ?>
									<p class="fs-form-desc"><?php echo esc_html( $field['desc'] ); ?></p>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<p class="fs-form-note">
				<?php esc_html_e( 'Name is the title above. Biography goes in the editor. Photo is set with the Featured Image box, and Departments in the Departments box.', 'faculty-staff' ); ?>
			</p>
		</div>
		<?php
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['fs_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fs_details_nonce'] ) ), 'fs_save_details' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( array_keys( self::fields() ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw   = wp_unslash( $_POST[ $key ] );
			$clean = self::sanitize_field( $raw, $key );
			if ( '' === $clean ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $clean );
			}
		}
	}

	/* ----------------------------------------------------------------- *
	 * Admin list-table columns
	 * ----------------------------------------------------------------- */

	/**
	 * Insert Position / Email / Phone columns after the title.
	 */
	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['fs_title'] = __( 'Position', 'faculty-staff' );
				$new['fs_email'] = __( 'Email', 'faculty-staff' );
				$new['fs_phone'] = __( 'Phone', 'faculty-staff' );
			}
		}
		return $new;
	}

	/**
	 * Render the custom column values.
	 */
	public static function column_content( $column, $post_id ) {
		if ( ! in_array( $column, array( 'fs_title', 'fs_email', 'fs_phone' ), true ) ) {
			return;
		}
		$value = get_post_meta( $post_id, $column, true );
		if ( '' === $value ) {
			echo '<span aria-hidden="true">—</span>';
			return;
		}
		if ( 'fs_email' === $column && is_email( $value ) ) {
			$safe = antispambot( $value );
			echo '<a href="mailto:' . esc_attr( $safe ) . '">' . esc_html( $safe ) . '</a>';
			return;
		}
		echo esc_html( $value );
	}

	/**
	 * Resolve the best available photo URL for a person: featured image first,
	 * then the stored remote fallback URL.
	 */
	public static function photo_url( $post_id, $size = 'medium' ) {
		if ( has_post_thumbnail( $post_id ) ) {
			$src = get_the_post_thumbnail_url( $post_id, $size );
			if ( $src ) {
				return $src;
			}
		}
		$fallback = get_post_meta( $post_id, 'fs_image_url', true );
		return $fallback ? $fallback : '';
	}
}
