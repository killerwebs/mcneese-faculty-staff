<?php
/**
 * Shortcodes for displaying faculty & staff.
 *
 *   [faculty_directory]                 Full, filterable/searchable grid.
 *   [faculty_directory department="history" filter="false"]
 *   [faculty_directory layout="list" show_email="true" show_phone="true"]
 *   [faculty_directory groupby="department" index="true"]
 *   [faculty_department dept="history"] Convenience: one department, no filter bar.
 *   [faculty_member slug="michael-smith"]  A single person card.
 *
 * Presentation attributes (directory + department):
 *   layout        grid | list | compact | names      (default grid)
 *   columns       items per row on desktop            (default 3)
 *   columns_md    items per row <=900px               (default min(2, columns))
 *   columns_sm    items per row <=600px               (default 1)
 *   photo_shape   square | circle | portrait          (default square)
 *   accent        hex color, re-skins this instance   (e.g. #8a0050)
 *   groupby       '' | department                     (section headings)
 *   index         A-Z jump bar                        (default false)
 *   filter        department filter pills             (default true)
 *   search        live search box                     (default true)
 *   show_title    position under the name             (default true)
 *   show_dept     department label on each card       (default false)
 *   show_email    email link on each card             (default false)
 *   show_phone    phone link on each card             (default false)
 *   show_location office location on each card        (default false)
 *   show_website  website link on each card           (default false)
 *
 * @package FacultyStaff
 */

defined( 'ABSPATH' ) || exit;

class FS_Shortcodes {

	const LAYOUTS = array( 'grid', 'list', 'compact', 'names' );
	const SHAPES  = array( 'square', 'circle', 'portrait' );

	public static function init() {
		add_shortcode( 'faculty_directory', array( __CLASS__, 'directory' ) );
		add_shortcode( 'faculty_department', array( __CLASS__, 'department' ) );
		add_shortcode( 'faculty_member', array( __CLASS__, 'member' ) );
	}

	/**
	 * [faculty_directory] — grid/list of people with an optional department
	 * filter bar, live search, A-Z index, and per-department grouping.
	 */
	public static function directory( $atts ) {
		$atts = shortcode_atts(
			array(
				'department'    => '',     // slug(s) or name(s), comma-separated, to scope the list.
				'layout'        => 'grid', // grid | list | compact | names
				'columns'       => 3,
				'columns_md'    => '',     // tablet override (<=900px)
				'columns_sm'    => '',     // mobile override (<=600px)
				'photo_shape'   => 'square', // square | circle | portrait
				'accent'        => '',     // hex color override for this instance
				'orderby'       => 'last_name', // last_name | title | menu_order | date | rand
				'order'         => 'ASC',
				'filter'        => 'true', // show the department filter pills
				'search'        => 'true', // show the live search box
				'groupby'       => '',     // '' | department
				'index'         => 'false', // A-Z jump bar
				'show_title'    => 'true', // show the position under the name
				'show_dept'     => 'false', // show department label on each card
				'dept_badge'    => 'false', // show department as a pill overlay on the photo
				'show_email'    => 'false',
				'show_phone'    => 'false',
				'show_location' => 'false',
				'show_website'  => 'false',
				'show_contact'  => 'false', // shorthand: show email + phone + office + website
				'show_excerpt'  => 'false', // short bio excerpt on the card
				'button'        => '',      // call-to-action label, e.g. "View Profile" (empty = none)
				'view_toggle'   => 'false', // Grid / List view switch in the toolbar
				'sort'          => 'false', // A-Z / Z-A sort control in the toolbar
				'per_page'      => 0,       // client-side pagination; 0 = show all
				'number'        => -1,     // max people (-1 = all)
				'empty'         => __( 'No faculty or staff found.', 'faculty-staff' ),
			),
			$atts,
			'faculty_directory'
		);

		FS_Assets::enqueue();

		// last_name is sorted in PHP, so a `number` limit must be applied AFTER
		// sorting or the subset would be picked by title order instead.
		$limit = 0;
		if ( 'last_name' === $atts['orderby'] && (int) $atts['number'] > 0 ) {
			$limit          = (int) $atts['number'];
			$atts['number'] = -1;
		}

		$query = self::query( $atts );
		if ( ! $query->have_posts() ) {
			return '<p class="fs-empty">' . esc_html( $atts['empty'] ) . '</p>';
		}
		update_post_thumbnail_cache( $query ); // one query for all featured images instead of two per card.

		// Default order is by last name, which has no DB column — sort here.
		if ( 'last_name' === $atts['orderby'] && ! empty( $query->posts ) ) {
			usort(
				$query->posts,
				function ( $a, $b ) {
					return strcmp( self::sort_key( get_the_title( $a->ID ) ), self::sort_key( get_the_title( $b->ID ) ) );
				}
			);
			if ( 'DESC' === strtoupper( $atts['order'] ) ) {
				$query->posts = array_reverse( $query->posts );
			}
			if ( $limit > 0 ) {
				$query->posts = array_slice( $query->posts, 0, $limit );
			}
		}

		$layout  = in_array( $atts['layout'], self::LAYOUTS, true ) ? $atts['layout'] : 'grid';
		$shape   = in_array( $atts['photo_shape'], self::SHAPES, true ) ? $atts['photo_shape'] : 'square';
		$groupby = ( 'department' === strtolower( (string) $atts['groupby'] ) ) ? 'department' : '';

		$atts['layout'] = $layout; // normalized value drives per-card photo logic.

		// show_contact is a convenience that turns on every contact row.
		if ( self::truthy( $atts['show_contact'] ) ) {
			$atts['show_email']    = 'true';
			$atts['show_phone']    = 'true';
			$atts['show_location'] = 'true';
			$atts['show_website']  = 'true';
		}

		// Filter pills are redundant when scoped to a department or grouped by one.
		$show_filter = self::truthy( $atts['filter'] ) && empty( $atts['department'] ) && ! $groupby;
		$show_search = self::truthy( $atts['search'] );
		$show_index  = self::truthy( $atts['index'] );
		$view_toggle = self::truthy( $atts['view_toggle'] );
		$sort        = self::truthy( $atts['sort'] );

		// Column counts, with sensible responsive fallbacks.
		$columns    = max( 1, (int) $atts['columns'] );
		$columns_md = ( '' !== $atts['columns_md'] ) ? max( 1, (int) $atts['columns_md'] ) : min( 2, $columns );
		$columns_sm = ( '' !== $atts['columns_sm'] ) ? max( 1, (int) $atts['columns_sm'] ) : 1;

		// Optional accent re-skin for this instance only.
		$style  = '';
		$accent = $atts['accent'] ? sanitize_hex_color( $atts['accent'] ) : '';
		if ( $accent ) {
			$style = '--fs-accent:' . $accent . ';';
		}

		$per_page = ( 'department' === $groupby ) ? 0 : max( 0, (int) $atts['per_page'] );

		ob_start();
		printf(
			'<div class="fs-directory" data-layout="%s" data-photo-shape="%s" data-per-page="%d"%s>',
			esc_attr( $layout ),
			esc_attr( $shape ),
			$per_page,
			$style ? ' style="' . esc_attr( $style ) . '"' : ''
		);

		if ( $show_filter || $show_search || $view_toggle || $sort ) {
			self::render_toolbar(
				$query,
				array(
					'filter'      => $show_filter,
					'search'      => $show_search,
					'view_toggle' => $view_toggle,
					'sort'        => $sort,
					'layout'      => $layout,
					'order'       => ( 'DESC' === strtoupper( $atts['order'] ) ) ? 'desc' : 'asc',
					'alpha'       => in_array( $atts['orderby'], array( 'last_name', 'title' ), true ),
				)
			);
		}

		if ( $show_index ) {
			self::render_index( $query );
		}

		if ( 'department' === $groupby ) {
			self::render_grouped( $query, $atts, $columns, $columns_md, $columns_sm );
		} else {
			self::render_grid( $query->posts, $atts, $columns, $columns_md, $columns_sm );
		}

		echo '<p class="fs-no-results" hidden>' . esc_html__( 'No matches.', 'faculty-staff' ) . '</p>';
		if ( $per_page > 0 ) {
			echo '<nav class="fs-pager" hidden aria-label="' . esc_attr__( 'Directory pagination', 'faculty-staff' ) . '"></nav>';
		}
		echo '</div>'; // .fs-directory

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * [faculty_department dept="history"] — a single department, no filter bar.
	 * All presentation attributes from [faculty_directory] pass straight through.
	 */
	public static function department( $atts ) {
		$atts = (array) $atts;

		if ( ! empty( $atts['dept'] ) ) {
			$atts['department'] = $atts['dept'];
		}
		unset( $atts['dept'] );

		$atts['filter'] = 'false';
		if ( ! isset( $atts['search'] ) ) {
			$atts['search'] = 'false';
		}

		return self::directory( $atts );
	}

	/**
	 * [faculty_member slug="michael-smith"] or id="123" — one person card.
	 */
	public static function member( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'            => 0,
				'slug'          => '',
				'layout'        => 'grid',
				'photo_shape'   => 'square',
				'accent'        => '',
				'show_title'    => 'true',
				'show_dept'     => 'true',
				'dept_badge'    => 'false',
				'show_email'    => 'true',
				'show_phone'    => 'true',
				'show_location' => 'true',
				'show_website'  => 'true',
				'show_excerpt'  => 'false',
				'button'        => '',
			),
			$atts,
			'faculty_member'
		);

		$post = null;
		if ( $atts['id'] ) {
			$post = get_post( (int) $atts['id'] );
		} elseif ( $atts['slug'] ) {
			$post = get_page_by_path( sanitize_title( $atts['slug'] ), OBJECT, fs_post_type() );
		}

		if ( ! $post || fs_post_type() !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		$layout = in_array( $atts['layout'], self::LAYOUTS, true ) ? $atts['layout'] : 'grid';
		$shape  = in_array( $atts['photo_shape'], self::SHAPES, true ) ? $atts['photo_shape'] : 'square';
		$style  = '';
		$accent = $atts['accent'] ? sanitize_hex_color( $atts['accent'] ) : '';
		if ( $accent ) {
			$style = '--fs-accent:' . $accent . ';';
		}

		$atts['layout'] = $layout; // normalized value drives per-card photo logic.

		FS_Assets::enqueue();
		ob_start();
		printf(
			'<div class="fs-directory fs-single-card" data-layout="%s" data-photo-shape="%s"%s>',
			esc_attr( $layout ),
			esc_attr( $shape ),
			$style ? ' style="' . esc_attr( $style ) . '"' : ''
		);
		echo '<div class="fs-grid" style="--fs-columns:1;--fs-columns-md:1;--fs-columns-sm:1">';
		self::render_card( $post->ID, $atts );
		echo '</div></div>';
		return ob_get_clean();
	}

	/* ----------------------------------------------------------------- *
	 * Internals
	 * ----------------------------------------------------------------- */

	protected static function query( $atts ) {
		$orderby = in_array( $atts['orderby'], array( 'title', 'menu_order', 'date', 'rand', 'last_name' ), true ) ? $atts['orderby'] : 'last_name';
		if ( 'menu_order' === $orderby ) {
			$orderby = 'menu_order title';
		} elseif ( 'last_name' === $orderby ) {
			// No DB column for last name; fetch by title, then re-sort in PHP.
			$orderby = 'title';
		}

		$args = array(
			'post_type'      => fs_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['number'],
			'orderby'        => $orderby,
			'order'          => ( 'DESC' === strtoupper( $atts['order'] ) ) ? 'DESC' : 'ASC',
			'no_found_rows'  => true,
		);

		if ( ! empty( $atts['department'] ) ) {
			$args['tax_query'] = array( self::department_clause( $atts['department'] ) );
		}

		return new WP_Query( apply_filters( 'fs_directory_query_args', $args, $atts ) );
	}

	/**
	 * Build a tax_query clause that accepts term slugs OR names, comma-separated.
	 */
	protected static function department_clause( $value ) {
		$terms = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		$slugs = array();
		$names = array();
		foreach ( $terms as $t ) {
			// Looks like a slug if it has no spaces/uppercase.
			if ( $t === sanitize_title( $t ) ) {
				$slugs[] = $t;
			} else {
				$names[] = $t;
			}
		}
		// Resolve names to slugs so we can use a single field=slug clause.
		foreach ( $names as $name ) {
			$term = get_term_by( 'name', $name, fs_taxonomy() );
			if ( $term ) {
				$slugs[] = $term->slug;
			} else {
				$slugs[] = sanitize_title( $name );
			}
		}

		return array(
			'taxonomy' => fs_taxonomy(),
			'field'    => 'slug',
			'terms'    => array_values( array_unique( $slugs ) ),
		);
	}

	protected static function render_toolbar( $query, $opts ) {
		$show_filter = ! empty( $opts['filter'] );
		$show_search = ! empty( $opts['search'] );
		$view_toggle = ! empty( $opts['view_toggle'] );
		$sort        = ! empty( $opts['sort'] );
		$layout      = isset( $opts['layout'] ) ? $opts['layout'] : 'grid';
		$sort_dir    = ( isset( $opts['order'] ) && 'desc' === $opts['order'] ) ? 'desc' : 'asc';
		$alpha       = ! empty( $opts['alpha'] ); // is the rendered order already A-Z/Z-A?

		ob_start();

		if ( $show_search ) {
			echo '<div class="fs-tb-search">';
			echo self::icon_search(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
			printf(
				'<input type="search" class="fs-search-input" placeholder="%s" aria-label="%s" />',
				esc_attr__( 'Search by name or title…', 'faculty-staff' ),
				esc_attr__( 'Search faculty and staff', 'faculty-staff' )
			);
			echo '</div>';
		}

		if ( $show_filter ) {
			$terms = self::terms_in_query( $query );
			if ( $terms ) {
				echo '<select class="fs-tb-select fs-filter-select" aria-label="' . esc_attr__( 'Filter by department', 'faculty-staff' ) . '">';
				printf( '<option value="">%s</option>', esc_html__( 'All Departments', 'faculty-staff' ) );
				foreach ( $terms as $term ) {
					printf( '<option value="%s">%s</option>', esc_attr( $term->slug ), esc_html( $term->name ) );
				}
				echo '</select>';
			}
		}

		if ( $sort ) {
			echo '<select class="fs-tb-select fs-sort-select" aria-label="' . esc_attr__( 'Sort order', 'faculty-staff' ) . '">';
			if ( ! $alpha ) {
				// Rendered in menu_order/date/rand: offer (and keep) that order as the default.
				printf( '<option value="" selected>%s</option>', esc_html__( 'Default order', 'faculty-staff' ) );
			}
			printf( '<option value="asc"%s>%s</option>', $alpha ? selected( $sort_dir, 'asc', false ) : '', esc_html__( 'A → Z', 'faculty-staff' ) );
			printf( '<option value="desc"%s>%s</option>', $alpha ? selected( $sort_dir, 'desc', false ) : '', esc_html__( 'Z → A', 'faculty-staff' ) );
			echo '</select>';
		}

		if ( $view_toggle ) {
			$list = ( 'list' === $layout );
			echo '<div class="fs-tb-views" role="group" aria-label="' . esc_attr__( 'View', 'faculty-staff' ) . '">';
			printf(
				'<button type="button" class="fs-view-btn%1$s" data-view="grid" aria-label="%2$s" aria-pressed="%3$s">%4$s</button>',
				$list ? '' : ' is-active',
				esc_attr__( 'Grid view', 'faculty-staff' ),
				$list ? 'false' : 'true',
				self::icon_grid() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
			);
			printf(
				'<button type="button" class="fs-view-btn%1$s" data-view="list" aria-label="%2$s" aria-pressed="%3$s">%4$s</button>',
				$list ? ' is-active' : '',
				esc_attr__( 'List view', 'faculty-staff' ),
				$list ? 'true' : 'false',
				self::icon_list() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG.
			);
			echo '</div>';
		}

		$inner = trim( ob_get_clean() );
		if ( '' !== $inner ) {
			echo '<div class="fs-toolbar">' . $inner . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped pieces above.
		}
	}

	/* Inline SVG icons (currentColor) for the toolbar. */
	protected static function icon_search() {
		return '<svg class="fs-tb-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';
	}
	protected static function icon_grid() {
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>';
	}
	protected static function icon_list() {
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="4" width="5" height="5" rx="1"></rect><rect x="10" y="5" width="11" height="2" rx="1"></rect><rect x="3" y="14" width="5" height="5" rx="1"></rect><rect x="10" y="15" width="11" height="2" rx="1"></rect></svg>';
	}

	/**
	 * A-Z jump bar. Letters with no people are rendered disabled.
	 */
	protected static function render_index( $query ) {
		$present = array();
		foreach ( $query->posts as $post ) {
			$present[ self::sort_letter( get_the_title( $post->ID ) ) ] = true;
		}

		echo '<nav class="fs-index" aria-label="' . esc_attr__( 'Jump to letter', 'faculty-staff' ) . '">';
		foreach ( range( 'A', 'Z' ) as $letter ) {
			if ( isset( $present[ $letter ] ) ) {
				printf(
					'<a class="fs-index-link" href="#" data-letter="%1$s">%1$s</a>',
					esc_html( $letter )
				);
			} else {
				printf(
					'<span class="fs-index-link is-disabled" aria-hidden="true">%s</span>',
					esc_html( $letter )
				);
			}
		}
		echo '</nav>';
	}

	/**
	 * Render a flat grid of the given posts.
	 *
	 * @param WP_Post[] $posts
	 */
	protected static function render_grid( $posts, $atts, $columns, $columns_md, $columns_sm ) {
		printf(
			'<div class="fs-grid" style="--fs-columns:%d;--fs-columns-md:%d;--fs-columns-sm:%d">',
			(int) $columns,
			(int) $columns_md,
			(int) $columns_sm
		);
		foreach ( $posts as $post ) {
			self::render_card( $post->ID, $atts );
		}
		echo '</div>'; // .fs-grid
	}

	/**
	 * Render one grid section per department, headed by the department name.
	 * People in multiple departments appear under each. People with no
	 * department fall into a final "Other" section.
	 */
	protected static function render_grouped( $query, $atts, $columns, $columns_md, $columns_sm ) {
		$terms     = self::terms_in_query( $query ); // slug => term, alpha by name
		$by_term   = array();
		$ungrouped = array();

		foreach ( $query->posts as $post ) {
			$pterms = get_the_terms( $post->ID, fs_taxonomy() );
			if ( is_array( $pterms ) && $pterms ) {
				foreach ( $pterms as $t ) {
					$by_term[ $t->slug ][] = $post;
				}
			} else {
				$ungrouped[] = $post;
			}
		}

		foreach ( $terms as $slug => $term ) {
			if ( empty( $by_term[ $slug ] ) ) {
				continue;
			}
			printf( '<section class="fs-group" data-dept="%s">', esc_attr( $slug ) );
			printf( '<h2 class="fs-group-title">%s</h2>', esc_html( $term->name ) );
			self::render_grid( $by_term[ $slug ], $atts, $columns, $columns_md, $columns_sm );
			echo '</section>';
		}

		if ( $ungrouped ) {
			echo '<section class="fs-group" data-dept="">';
			printf( '<h2 class="fs-group-title">%s</h2>', esc_html__( 'Other', 'faculty-staff' ) );
			self::render_grid( $ungrouped, $atts, $columns, $columns_md, $columns_sm );
			echo '</section>';
		}
	}

	protected static function render_card( $post_id, $atts ) {
		$permalink = get_permalink( $post_id );
		$name      = get_the_title( $post_id );
		$position  = get_post_meta( $post_id, 'fs_title', true );
		$photo     = FS_Meta::photo_url( $post_id, 'medium' );
		$show_photo = ! ( isset( $atts['layout'] ) && 'names' === $atts['layout'] );

		$terms      = get_the_terms( $post_id, fs_taxonomy() );
		$dept_slugs = array();
		$dept_names = array();
		if ( is_array( $terms ) ) {
			foreach ( $terms as $t ) {
				$dept_slugs[] = $t->slug;
				$dept_names[] = $t->name;
			}
		}

		$haystack = mb_strtolower( wp_strip_all_tags( $name . ' ' . $position . ' ' . implode( ' ', $dept_names ) ) );

		printf(
			'<article class="fs-card" data-departments="%s" data-search="%s" data-letter="%s" data-name="%s">',
			esc_attr( implode( ' ', $dept_slugs ) ),
			esc_attr( $haystack ),
			esc_attr( self::sort_letter( $name ) ),
			esc_attr( self::sort_key( $name ) )
		);

		if ( $show_photo ) {
			echo '<a class="fs-card-photo-link" href="' . esc_url( $permalink ) . '" tabindex="-1" aria-hidden="true">';
			echo '<div class="fs-card-photo">';
			if ( $photo ) {
				printf(
					'<img src="%s" alt="%s" loading="lazy" />',
					esc_url( $photo ),
					esc_attr( $name )
				);
			} else {
				echo '<span class="fs-card-initials" aria-hidden="true">' . esc_html( self::initials( $name ) ) . '</span>';
			}
			if ( self::truthy_att( $atts, 'dept_badge' ) && $dept_names ) {
				echo '<span class="fs-card-badge">' . esc_html( implode( ', ', $dept_names ) ) . '</span>';
			}
			echo '</div>';
			echo '</a>';
		}

		echo '<div class="fs-card-body">';
		echo '<h3 class="fs-card-name"><a href="' . esc_url( $permalink ) . '">' . esc_html( $name ) . '</a></h3>';

		if ( self::truthy_att( $atts, 'show_title' ) && $position ) {
			echo '<p class="fs-card-title">' . esc_html( $position ) . '</p>';
		}
		if ( self::truthy_att( $atts, 'show_dept' ) && $dept_names ) {
			$label = _n( 'Department:', 'Departments:', count( $dept_names ), 'faculty-staff' );
			echo '<p class="fs-card-dept"><span class="fs-card-dept-label">' . esc_html( $label ) . '</span> ' . esc_html( implode( ', ', $dept_names ) ) . '</p>';
		}

		self::render_contact( $post_id, $atts );

		if ( self::truthy_att( $atts, 'show_excerpt' ) ) {
			$excerpt = self::excerpt( $post_id );
			if ( $excerpt ) {
				echo '<p class="fs-card-excerpt">' . esc_html( $excerpt ) . '</p>';
			}
		}

		$button = isset( $atts['button'] ) ? trim( (string) $atts['button'] ) : '';
		if ( '' !== $button ) {
			echo '<a class="fs-card-button" href="' . esc_url( $permalink ) . '">' . esc_html( $button ) . '</a>';
		}

		echo '</div>'; // .fs-card-body
		echo '</article>';
	}

	/**
	 * A short plain-text bio excerpt for cards: the manual excerpt if set,
	 * otherwise trimmed post content.
	 */
	protected static function excerpt( $post_id, $words = 24 ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		$raw = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
		$raw = wp_strip_all_tags( strip_shortcodes( (string) $raw ) );
		return '' !== trim( $raw ) ? wp_trim_words( $raw, $words, '…' ) : '';
	}

	/**
	 * Optional contact rows on a card: email, phone, office, website.
	 * Emails are obfuscated; phones become tel: links.
	 */
	protected static function render_contact( $post_id, $atts ) {
		$rows = array();

		if ( self::truthy_att( $atts, 'show_email' ) ) {
			$email = get_post_meta( $post_id, 'fs_email', true );
			if ( $email && is_email( $email ) ) {
				$safe   = antispambot( $email );
				$rows[] = '<li class="fs-c-email"><a href="mailto:' . esc_attr( $safe ) . '">' . esc_html( $safe ) . '</a></li>';
			}
		}
		if ( self::truthy_att( $atts, 'show_phone' ) ) {
			$phone = get_post_meta( $post_id, 'fs_phone', true );
			if ( $phone ) {
				$rows[] = '<li class="fs-c-phone"><a href="tel:' . esc_attr( self::tel( $phone ) ) . '">' . esc_html( $phone ) . '</a></li>';
			}
		}
		if ( self::truthy_att( $atts, 'show_location' ) ) {
			$location = get_post_meta( $post_id, 'fs_location', true );
			if ( $location ) {
				$rows[] = '<li class="fs-c-loc">' . esc_html( $location ) . '</li>';
			}
		}
		if ( self::truthy_att( $atts, 'show_website' ) ) {
			$website = get_post_meta( $post_id, 'fs_website', true );
			if ( $website ) {
				$rows[] = '<li class="fs-c-web"><a href="' . esc_url( $website ) . '" rel="nofollow">' . esc_html__( 'Website', 'faculty-staff' ) . '</a></li>';
			}
		}

		if ( $rows ) {
			echo '<ul class="fs-card-contact">' . implode( '', $rows ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each row escaped above.
		}
	}

	/**
	 * Normalize a phone string into a dialable tel: value.
	 */
	protected static function tel( $phone ) {
		return preg_replace( '/[^0-9+]/', '', $phone );
	}

	/**
	 * Department terms actually used by the people in this query, sorted by name.
	 */
	protected static function terms_in_query( $query ) {
		$found = array();
		foreach ( $query->posts as $post ) {
			$terms = get_the_terms( $post->ID, fs_taxonomy() );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $t ) {
					$found[ $t->slug ] = $t;
				}
			}
		}
		uasort( $found, function( $a, $b ) {
			return strcasecmp( $a->name, $b->name );
		} );
		return $found;
	}

	/**
	 * Name words with the honorific ("Dr."), trailing credentials (", PhD")
	 * and suffixes ("Jr.") removed. Shared by sort_key() and initials().
	 */
	protected static function name_parts( $name ) {
		$name  = wp_strip_all_tags( (string) $name );
		$name  = preg_replace( '/,.*$/', '', $name );                                          // drop ", PhD" / ", MS"
		$name  = preg_replace( '/^(dr|prof|professor|mr|mrs|ms|mx|rev|fr)\.?\s+/i', '', $name ); // drop honorific
		$parts = array_values( array_filter( preg_split( '/\s+/', trim( $name ) ) ) );
		$suffixes = array( 'jr', 'sr', 'ii', 'iii', 'iv', 'phd', 'md', 'ms', 'mfa', 'dma', 'edd' );
		while ( count( $parts ) > 1 && in_array( mb_strtolower( rtrim( end( $parts ), '.' ) ), $suffixes, true ) ) {
			array_pop( $parts );
		}
		return $parts;
	}

	/**
	 * A last-name-first sort key: "Dr. Davaron Edwards" -> "edwards davaron edwards",
	 * so people sort and index by surname rather than the "Dr." prefix.
	 */
	protected static function sort_key( $name ) {
		$parts = self::name_parts( $name );
		if ( empty( $parts ) ) {
			return mb_strtolower( wp_strip_all_tags( (string) $name ) );
		}
		$last = end( $parts );
		return mb_strtolower( $last . ' ' . implode( ' ', $parts ) );
	}

	/**
	 * First A-Z letter (by surname) for the index, or '#' for anything else.
	 */
	protected static function sort_letter( $name ) {
		$first = mb_strtoupper( mb_substr( self::sort_key( $name ), 0, 1 ) );
		return preg_match( '/[A-Z]/', $first ) ? $first : '#';
	}

	protected static function initials( $name ) {
		$parts = self::name_parts( $name ); // "Dr. Jane Doe, PhD" -> JD, not DP
		if ( empty( $parts ) ) {
			return '';
		}
		$first = mb_substr( $parts[0], 0, 1 );
		$last  = ( count( $parts ) > 1 ) ? mb_substr( end( $parts ), 0, 1 ) : '';
		return mb_strtoupper( $first . $last );
	}

	protected static function truthy_att( $atts, $key ) {
		return isset( $atts[ $key ] ) && self::truthy( $atts[ $key ] );
	}

	protected static function truthy( $value ) {
		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}
}
