<?php
/**
 * CSV importer for faculty & staff.
 *
 * Flow: the CSV is uploaded + parsed in one quick POST (no downloads), stashed
 * in a transient, then processed in small AJAX batches so image downloads show
 * a live progress bar instead of a frozen page.
 *
 * Designed around the WP "Export" CSV format (columns: Title, Content, Slug,
 * Status, Departments, Image URL, fs_title, fs_email, fs_phone, fs_location,
 * Order, ...). Unknown columns are ignored; missing ones are skipped.
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

class FS_Importer {

	const CAPABILITY  = 'manage_options';
	const TRANSIENT   = 'fs_import_job_';
	const BATCH_SIZE  = 4; // rows per AJAX request (each may download an image)

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'wp_ajax_fs_import_batch', array( __CLASS__, 'ajax_batch' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . fs_post_type(),
			__( 'Import CSV', 'faculty-staff' ),
			__( 'Import CSV', 'faculty-staff' ),
			self::CAPABILITY,
			'fs-import',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue( $hook ) {
		if ( 'faculty_staff_page_fs-import' !== $hook && false === strpos( (string) $hook, 'fs-import' ) ) {
			return;
		}
		wp_enqueue_script( 'fs-admin-import', FS_DIR_URL . 'assets/js/fs-admin-import.js', array(), FS_DIR_VERSION, true );
	}

	/* ----------------------------------------------------------------- *
	 * Admin page
	 * ----------------------------------------------------------------- */

	public static function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to import.', 'faculty-staff' ) );
		}

		$job   = null;
		$error = '';
		if ( isset( $_POST['fs_import_submit'] ) ) {
			check_admin_referer( 'fs_import', 'fs_import_nonce' );
			$job = self::ingest_upload();
			if ( is_wp_error( $job ) ) {
				$error = $job->get_error_message();
				$job   = null;
			}
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Import Faculty & Staff', 'faculty-staff' ) . '</h1>';

		if ( $error ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		}

		if ( $job ) {
			self::render_progress_ui( $job );
		} else {
			self::render_form();
		}

		echo '</div>';
	}

	protected static function render_form() {
		?>
		<p><?php esc_html_e( 'Upload a CSV exported from WordPress (or any CSV with matching headers). People are matched by Slug (falling back to Title) so re-importing updates existing records instead of duplicating them.', 'faculty-staff' ); ?></p>

		<form method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'fs_import', 'fs_import_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="fs_csv"><?php esc_html_e( 'CSV file', 'faculty-staff' ); ?></label></th>
					<td><input type="file" id="fs_csv" name="fs_csv" accept=".csv,text/csv" required /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Options', 'faculty-staff' ); ?></th>
					<td>
						<label><input type="checkbox" name="fs_update_existing" value="1" checked /> <?php esc_html_e( 'Update existing people (match by slug/title)', 'faculty-staff' ); ?></label><br />
						<label><input type="checkbox" name="fs_download_images" value="1" checked /> <?php esc_html_e( 'Download photos into the Media Library and set as featured image', 'faculty-staff' ); ?></label>
						<p class="description"><?php esc_html_e( 'If unchecked, the photo URL is stored and used directly (no download). Downloading is slower but keeps images on your server.', 'faculty-staff' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Import CSV', 'faculty-staff' ), 'primary', 'fs_import_submit' ); ?>
		</form>

		<hr />
		<h2><?php esc_html_e( 'Recognized columns', 'faculty-staff' ); ?></h2>
		<p class="description"><code>Title</code>, <code>Content</code>, <code>Excerpt</code>, <code>Slug</code>, <code>Status</code>, <code>Order</code>, <code>Departments</code> (pipe-separated for multiple), <code>Image URL</code>, <code>fs_title</code>, <code>fs_email</code>, <code>fs_phone</code>, <code>fs_location</code>, <code>fs_website</code>.</p>
		<?php
	}

	protected static function render_progress_ui( $job ) {
		$data = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'fs_import_batch' ),
			'job'     => $job['id'],
			'total'   => $job['total'],
		);
		?>
		<style>
			.fs-progress-wrap{max-width:640px;margin:20px 0}
			.fs-progress-track{height:24px;background:#e2e6ea;border-radius:12px;overflow:hidden}
			.fs-progress-bar{height:100%;width:0;background:#2271b1;transition:width .2s ease}
			.fs-progress-meta{margin:10px 0;font-size:14px;color:#50575e}
			.fs-progress-log{max-width:640px;max-height:200px;overflow:auto;background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:10px 14px;margin-top:14px}
			.fs-progress-log li{margin:2px 0;color:#8a6d00}
		</style>
		<div class="fs-progress-wrap" id="fs-import-progress" data-config="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
			<p class="fs-progress-meta"><strong><?php esc_html_e( 'Importing…', 'faculty-staff' ); ?></strong> <span class="fs-progress-status"><?php printf( esc_html__( '0 of %d', 'faculty-staff' ), (int) $job['total'] ); ?></span></p>
			<div class="fs-progress-track"><div class="fs-progress-bar"></div></div>
			<p class="fs-progress-counts" style="margin-top:12px"></p>
			<ul class="fs-progress-log" hidden></ul>
			<p class="fs-progress-done" hidden>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . fs_post_type() ) ); ?>" class="button button-primary"><?php esc_html_e( 'View All People', 'faculty-staff' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . fs_post_type() . '&page=fs-import' ) ); ?>" class="button"><?php esc_html_e( 'Import Another', 'faculty-staff' ); ?></a>
			</p>
		</div>
		<noscript><p class="notice notice-warning"><?php esc_html_e( 'This importer needs JavaScript enabled.', 'faculty-staff' ); ?></p></noscript>
		<?php
	}

	/* ----------------------------------------------------------------- *
	 * Step 1 — parse the upload, stash rows in a transient (no downloads)
	 * ----------------------------------------------------------------- */

	protected static function ingest_upload() {
		if ( empty( $_FILES['fs_csv']['name'] ) ) {
			return new WP_Error( 'fs_no_file', __( 'Please choose a CSV file.', 'faculty-staff' ) );
		}
		if ( ! empty( $_FILES['fs_csv']['error'] ) ) {
			return new WP_Error( 'fs_upload_error', __( 'The file failed to upload. Try again.', 'faculty-staff' ) );
		}

		$tmp  = isset( $_FILES['fs_csv']['tmp_name'] ) ? $_FILES['fs_csv']['tmp_name'] : '';
		$name = isset( $_FILES['fs_csv']['name'] ) ? sanitize_file_name( $_FILES['fs_csv']['name'] ) : '';

		if ( ! $tmp || ! is_uploaded_file( $tmp ) ) {
			return new WP_Error( 'fs_invalid', __( 'Invalid upload.', 'faculty-staff' ) );
		}
		if ( strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) !== 'csv' ) {
			return new WP_Error( 'fs_not_csv', __( 'That does not look like a .csv file.', 'faculty-staff' ) );
		}

		$parsed = self::parse_csv( $tmp );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$id  = 'fs_' . wp_generate_password( 12, false );
		$job = array(
			'id'              => $id,
			'header'          => $parsed['header'],
			'rows'            => $parsed['rows'],
			'total'           => count( $parsed['rows'] ),
			'update_existing' => ! empty( $_POST['fs_update_existing'] ),
			'download_images' => ! empty( $_POST['fs_download_images'] ),
			'results'         => array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'images' => 0 ),
			'errors'          => array(),
		);

		if ( 0 === $job['total'] ) {
			return new WP_Error( 'fs_empty', __( 'No data rows found in that CSV.', 'faculty-staff' ) );
		}

		set_transient( self::TRANSIENT . $id, $job, HOUR_IN_SECONDS );
		return $job;
	}

	/**
	 * Parse a CSV file into header + rows. Uses escape="" so backslashes in
	 * content can't desync the parser (a known fgetcsv pitfall).
	 *
	 * @return array|WP_Error { header[], rows[] }
	 */
	protected static function parse_csv( $path ) {
		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'fs_open', __( 'Could not read the uploaded file.', 'faculty-staff' ) );
		}

		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		$header = self::read_row( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return new WP_Error( 'fs_empty', __( 'The CSV appears to be empty.', 'faculty-staff' ) );
		}
		$header = array_map( 'trim', $header );

		$rows = array();
		while ( ( $row = self::read_row( $handle ) ) !== false ) {
			if ( null === $row ) {
				continue;
			}
			if ( count( array_filter( $row, 'strlen' ) ) === 0 ) {
				continue; // blank line
			}
			$rows[] = $row;
		}
		fclose( $handle );

		return array( 'header' => $header, 'rows' => $rows );
	}

	/**
	 * fgetcsv wrapper. PHP < 7.4 ignores the empty escape arg; 7.4+ honors it.
	 */
	protected static function read_row( $handle ) {
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			return fgetcsv( $handle, 0, ',', '"', '' );
		}
		return fgetcsv( $handle, 0, ',', '"' );
	}

	/* ----------------------------------------------------------------- *
	 * Step 2 — AJAX: process one batch of rows
	 * ----------------------------------------------------------------- */

	public static function ajax_batch() {
		check_ajax_referer( 'fs_import_batch', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'faculty-staff' ) ), 403 );
		}

		$id     = isset( $_POST['job'] ) ? sanitize_text_field( wp_unslash( $_POST['job'] ) ) : '';
		$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;

		$job = get_transient( self::TRANSIENT . $id );
		if ( ! $job || empty( $job['rows'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Import session expired. Please upload the file again.', 'faculty-staff' ) ), 410 );
		}

		$index = array_flip( $job['header'] );
		$slice = array_slice( $job['rows'], $offset, self::BATCH_SIZE, true );

		foreach ( $slice as $row ) {
			$delta = self::process_row( $row, $index, $job['update_existing'], $job['download_images'] );
			foreach ( array( 'created', 'updated', 'skipped', 'images' ) as $k ) {
				$job['results'][ $k ] += $delta[ $k ];
			}
			if ( ! empty( $delta['error'] ) && count( $job['errors'] ) < 100 ) {
				$job['errors'][] = $delta['error'];
			}
		}

		$processed = min( $offset + self::BATCH_SIZE, $job['total'] );
		$done      = $processed >= $job['total'];

		if ( $done ) {
			delete_transient( self::TRANSIENT . $id );
		} else {
			set_transient( self::TRANSIENT . $id, $job, HOUR_IN_SECONDS );
		}

		wp_send_json_success(
			array(
				'processed' => $processed,
				'total'     => (int) $job['total'],
				'done'      => $done,
				'results'   => $job['results'],
				'errors'    => $job['errors'],
			)
		);
	}

	/**
	 * Create/update a single person from one CSV row.
	 *
	 * @return array { created, updated, skipped, images, error }
	 */
	protected static function process_row( $row, $index, $update_existing, $download_images ) {
		$delta = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'images' => 0, 'error' => '' );

		$get = function( $col ) use ( $row, $index ) {
			return isset( $index[ $col ], $row[ $index[ $col ] ] ) ? (string) $row[ $index[ $col ] ] : '';
		};

		$title = trim( $get( 'Title' ) );
		if ( '' === $title ) {
			$delta['skipped'] = 1;
			return $delta;
		}

		$slug     = sanitize_title( $get( 'Slug' ) ? $get( 'Slug' ) : $title );
		$existing = self::find_existing( $slug, $title );
		if ( $existing && ! $update_existing ) {
			$delta['skipped'] = 1;
			return $delta;
		}

		$status = strtolower( trim( $get( 'Status' ) ) );
		$status = in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ? $status : 'publish';

		$postarr = array(
			'post_type'    => fs_post_type(),
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $get( 'Content' ),
			'post_excerpt' => $get( 'Excerpt' ),
			'post_status'  => $status,
		);
		$order = $get( 'Order' );
		if ( '' !== $order && is_numeric( $order ) ) {
			$postarr['menu_order'] = (int) $order;
		}

		if ( $existing ) {
			$postarr['ID'] = $existing;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			$delta['skipped'] = 1;
			$delta['error']   = sprintf( '%1$s: %2$s', $title, $post_id->get_error_message() );
			return $delta;
		}

		// Meta fields.
		$meta_cols = array( 'fs_title', 'fs_email', 'fs_phone', 'fs_location', 'fs_website' );
		foreach ( $meta_cols as $key ) {
			$value = trim( $get( $key ) );
			if ( '' !== $value ) {
				update_post_meta( $post_id, $key, FS_Meta::sanitize_field( $value, $key ) );
			}
		}

		// Departments (pipe-separated; commas tolerated too).
		$dept_raw = $get( 'Departments' );
		if ( '' !== trim( $dept_raw ) ) {
			$names = array_filter( array_map( 'trim', preg_split( '/[|,]/', $dept_raw ) ) );
			if ( $names ) {
				wp_set_object_terms( $post_id, $names, fs_taxonomy(), false );
			}
		}

		// Image — the export may pipe-separate several URLs; use the first.
		$image_url = self::first_url( $get( 'Image URL' ) );
		if ( '' === $image_url ) {
			$image_url = self::first_url( $get( 'Attachment URL' ) );
		}
		if ( '' !== $image_url ) {
			update_post_meta( $post_id, 'fs_image_url', esc_url_raw( $image_url ) );
			if ( $download_images && ! has_post_thumbnail( $post_id ) ) {
				$att_id = self::sideload_image( $image_url, $post_id, $title );
				if ( ! is_wp_error( $att_id ) && $att_id ) {
					set_post_thumbnail( $post_id, $att_id );
					$delta['images'] = 1;
				} elseif ( is_wp_error( $att_id ) ) {
					$delta['error'] = sprintf( __( '%1$s: photo not downloaded (%2$s)', 'faculty-staff' ), $title, $att_id->get_error_message() );
				}
			}
		}

		update_post_meta( $post_id, '_fs_imported', current_time( 'mysql' ) );

		if ( $existing ) {
			$delta['updated'] = 1;
		} else {
			$delta['created'] = 1;
		}
		return $delta;
	}

	/* ----------------------------------------------------------------- *
	 * Helpers
	 * ----------------------------------------------------------------- */

	/**
	 * A cell may contain several URLs joined by "|" (or commas/whitespace).
	 * Return the first usable one.
	 */
	protected static function first_url( $value ) {
		$parts = preg_split( '/[|,\s]+/', (string) $value );
		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( '' !== $part && preg_match( '#^https?://#i', $part ) ) {
				return $part;
			}
		}
		return '';
	}

	/**
	 * Find an existing person by slug, then by exact title.
	 */
	protected static function find_existing( $slug, $title ) {
		if ( $slug ) {
			$post = get_page_by_path( $slug, OBJECT, fs_post_type() );
			if ( $post ) {
				return $post->ID;
			}
		}
		$found = get_posts(
			array(
				'post_type'      => fs_post_type(),
				'title'          => $title,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return $found ? $found[0] : 0;
	}

	/**
	 * Download an image once and reuse it on re-import (deduped by source URL).
	 *
	 * @return int|WP_Error attachment ID
	 */
	protected static function sideload_image( $url, $post_id, $title ) {
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_fs_source_url',
				'meta_value'     => esc_url_raw( $url ),
			)
		);
		if ( $existing ) {
			return $existing[0];
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$att_id = media_sideload_image( $url, $post_id, $title, 'id' );
		if ( is_wp_error( $att_id ) ) {
			return $att_id;
		}
		update_post_meta( $att_id, '_fs_source_url', esc_url_raw( $url ) );
		return $att_id;
	}
}
