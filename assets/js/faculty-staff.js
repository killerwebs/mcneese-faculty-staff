/**
 * Faculty & Staff Directory — client-side filter + live search.
 * No dependencies. Filters the already-rendered cards by department and text.
 */
( function () {
	'use strict';

	function initDirectory( root ) {
		var cards = Array.prototype.slice.call( root.querySelectorAll( '.fs-card' ) );
		var filterSelect = root.querySelector( '.fs-filter-select' );
		var sortSelect = root.querySelector( '.fs-sort-select' );
		var groups = Array.prototype.slice.call( root.querySelectorAll( '.fs-group' ) );
		var indexLinks = Array.prototype.slice.call( root.querySelectorAll( '.fs-index-link' ) );
		var searchInput = root.querySelector( '.fs-search-input' );
		var noResults = root.querySelector( '.fs-no-results' );
		var pager = root.querySelector( '.fs-pager' );
		var grid = root.querySelector( '.fs-grid' );

		var activeDept = '';
		var query = '';
		var page = 1;
		// Pagination applies only to a flat (non-grouped) directory.
		var perPage = groups.length ? 0 : ( parseInt( root.getAttribute( 'data-per-page' ), 10 ) || 0 );

		// Cards matching the current department + text filter, in DOM order
		// (which the sort control may have rearranged).
		function matched() {
			var list = grid ? Array.prototype.slice.call( grid.querySelectorAll( '.fs-card' ) ) : cards;
			return list.filter( function ( card ) {
				var depts = ( card.getAttribute( 'data-departments' ) || '' ).split( /\s+/ );
				var haystack = card.getAttribute( 'data-search' ) || '';
				var matchDept = ! activeDept || depts.indexOf( activeDept ) !== -1;
				var matchText = ! query || haystack.indexOf( query ) !== -1;
				return matchDept && matchText;
			} );
		}

		function render() {
			var hits = matched();
			cards.forEach( function ( c ) { c.hidden = true; } );

			var shown = hits;
			if ( perPage > 0 ) {
				var pages = Math.max( 1, Math.ceil( hits.length / perPage ) );
				page = Math.min( Math.max( page, 1 ), pages );
				shown = hits.slice( ( page - 1 ) * perPage, ( page - 1 ) * perPage + perPage );
			}
			shown.forEach( function ( c ) { c.hidden = false; } );

			groups.forEach( function ( group ) {
				group.hidden = ! group.querySelector( '.fs-card:not([hidden])' );
			} );
			if ( noResults ) {
				noResults.hidden = hits.length !== 0;
			}
			renderPager( hits.length );
		}

		function pageWindow( pages, cur ) {
			var keep = {};
			keep[ 1 ] = 1; keep[ pages ] = 1;
			for ( var p = cur - 1; p <= cur + 1; p++ ) {
				if ( p >= 1 && p <= pages ) { keep[ p ] = 1; }
			}
			var nums = Object.keys( keep ).map( Number ).sort( function ( a, b ) { return a - b; } );
			var out = [], prev = 0;
			nums.forEach( function ( n ) {
				if ( prev && n - prev > 1 ) { out.push( 0 ); } // 0 = ellipsis
				out.push( n );
				prev = n;
			} );
			return out;
		}

		function renderPager( total ) {
			if ( ! pager ) { return; }
			if ( perPage <= 0 || total <= perPage ) {
				pager.innerHTML = '';
				pager.hidden = true;
				return;
			}
			pager.hidden = false;
			var pages = Math.ceil( total / perPage );
			var html = '<button type="button" class="fs-page fs-page-prev" data-page="' + ( page - 1 ) + '"' + ( page <= 1 ? ' disabled' : '' ) + ' aria-label="Previous">‹</button>';
			pageWindow( pages, page ).forEach( function ( n ) {
				if ( n === 0 ) {
					html += '<span class="fs-page fs-page-ellipsis">…</span>';
					return;
				}
				html += '<button type="button" class="fs-page' + ( n === page ? ' is-active' : '' ) + '"' + ( n === page ? ' aria-current="page"' : '' ) + ' data-page="' + n + '">' + n + '</button>';
			} );
			html += '<button type="button" class="fs-page fs-page-next" data-page="' + ( page + 1 ) + '"' + ( page >= pages ? ' disabled' : '' ) + ' aria-label="Next">›</button>';
			pager.innerHTML = html;
		}

		function reset() {
			page = 1;
			render();
		}

		if ( filterSelect ) {
			filterSelect.addEventListener( 'change', function () {
				activeDept = filterSelect.value || '';
				reset();
			} );
		}

		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				query = searchInput.value.trim().toLowerCase();
				reset();
			} );
		}

		// View toggle (grid / list): swap the directory layout in place.
		var viewBtns = Array.prototype.slice.call( root.querySelectorAll( '.fs-view-btn' ) );
		viewBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				root.setAttribute( 'data-layout', btn.getAttribute( 'data-view' ) || 'grid' );
				viewBtns.forEach( function ( b ) {
					var on = b === btn;
					b.classList.toggle( 'is-active', on );
					b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				} );
			} );
		} );

		// Sort (A → Z / Z → A): reorder cards within each grid by name.
		function applySort( dir ) {
			var grids = root.querySelectorAll( '.fs-grid' );
			Array.prototype.forEach.call( grids, function ( g ) {
				var items = Array.prototype.slice.call( g.querySelectorAll( '.fs-card' ) );
				items.sort( function ( a, b ) {
					var an = a.getAttribute( 'data-name' ) || '';
					var bn = b.getAttribute( 'data-name' ) || '';
					return dir === 'desc' ? bn.localeCompare( an ) : an.localeCompare( bn );
				} );
				items.forEach( function ( item ) { g.appendChild( item ); } );
			} );
		}
		if ( sortSelect ) {
			sortSelect.addEventListener( 'change', function () {
				applySort( sortSelect.value || 'asc' );
				reset();
			} );
		}

		if ( pager ) {
			pager.addEventListener( 'click', function ( e ) {
				var btn = e.target.closest ? e.target.closest( '[data-page]' ) : null;
				if ( ! btn || btn.disabled ) { return; }
				var p = parseInt( btn.getAttribute( 'data-page' ), 10 );
				if ( ! p ) { return; }
				page = p;
				render();
				if ( root.scrollIntoView ) {
					root.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			} );
		}

		// A-Z index: jump to the first matching card for a letter, switching
		// to its page first when pagination is on.
		indexLinks.forEach( function ( link ) {
			if ( link.classList.contains( 'is-disabled' ) ) {
				return;
			}
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var letter = link.getAttribute( 'data-letter' );
				var hits = matched();
				var idx = -1;
				for ( var i = 0; i < hits.length; i++ ) {
					if ( hits[ i ].getAttribute( 'data-letter' ) === letter ) { idx = i; break; }
				}
				if ( idx < 0 ) { return; }
				if ( perPage > 0 ) {
					page = Math.floor( idx / perPage ) + 1;
					render();
				}
				hits[ idx ].scrollIntoView( { behavior: 'smooth', block: 'start' } );
			} );
		} );

		render();
	}

	// On a bio page, point the "Back" link at the page the visitor came from
	// (when it's on this site) so they return to their department list rather
	// than the full directory. Computed client-side so it's never cached.
	function initBackLink() {
		var link = document.querySelector( '.fs-back-link' );
		if ( ! link || ! document.referrer ) {
			return;
		}
		try {
			var ref = new URL( document.referrer );
			var here = new URL( window.location.href );
			if ( ref.origin === here.origin && ref.href !== here.href ) {
				link.setAttribute( 'href', ref.href );
				link.textContent = '← ' + ( link.getAttribute( 'data-back-label' ) || 'Back' );
			}
		} catch ( e ) {}
	}

	function init() {
		var dirs = document.querySelectorAll( '.fs-directory' );
		Array.prototype.forEach.call( dirs, initDirectory );
		initBackLink();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
