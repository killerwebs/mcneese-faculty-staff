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

		var activeDept = '';
		var query = '';

		function apply() {
			var visible = 0;
			cards.forEach( function ( card ) {
				var depts = ( card.getAttribute( 'data-departments' ) || '' ).split( /\s+/ );
				var haystack = card.getAttribute( 'data-search' ) || '';
				var matchDept = ! activeDept || depts.indexOf( activeDept ) !== -1;
				var matchText = ! query || haystack.indexOf( query ) !== -1;
				var show = matchDept && matchText;
				card.hidden = ! show;
				if ( show ) {
					visible++;
				}
			} );
			// Hide any department group whose cards are all filtered out.
			groups.forEach( function ( group ) {
				group.hidden = ! group.querySelector( '.fs-card:not([hidden])' );
			} );
			if ( noResults ) {
				noResults.hidden = visible !== 0;
			}
		}

		if ( filterSelect ) {
			filterSelect.addEventListener( 'change', function () {
				activeDept = filterSelect.value || '';
				apply();
			} );
		}

		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				query = searchInput.value.trim().toLowerCase();
				apply();
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
			Array.prototype.forEach.call( grids, function ( grid ) {
				var items = Array.prototype.slice.call( grid.querySelectorAll( '.fs-card' ) );
				items.sort( function ( a, b ) {
					var an = a.getAttribute( 'data-name' ) || '';
					var bn = b.getAttribute( 'data-name' ) || '';
					return dir === 'desc' ? bn.localeCompare( an ) : an.localeCompare( bn );
				} );
				items.forEach( function ( item ) { grid.appendChild( item ); } );
			} );
		}
		if ( sortSelect ) {
			sortSelect.addEventListener( 'change', function () {
				applySort( sortSelect.value || 'asc' );
			} );
		}

		// A-Z index: scroll to the first visible card starting with that letter.
		indexLinks.forEach( function ( link ) {
			if ( link.classList.contains( 'is-disabled' ) ) {
				return;
			}
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var letter = link.getAttribute( 'data-letter' );
				var target = cards.filter( function ( card ) {
					return ! card.hidden && card.getAttribute( 'data-letter' ) === letter;
				} )[ 0 ];
				if ( target ) {
					target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			} );
		} );
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
