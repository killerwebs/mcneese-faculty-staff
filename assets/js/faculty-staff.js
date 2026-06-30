/**
 * Faculty & Staff Directory — client-side filter + live search.
 * No dependencies. Filters the already-rendered cards by department and text.
 */
( function () {
	'use strict';

	function initDirectory( root ) {
		var cards = Array.prototype.slice.call( root.querySelectorAll( '.fs-card' ) );
		var filters = Array.prototype.slice.call( root.querySelectorAll( '.fs-filter' ) );
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

		filters.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				activeDept = btn.getAttribute( 'data-dept' ) || '';
				filters.forEach( function ( b ) {
					var on = b === btn;
					b.classList.toggle( 'is-active', on );
					b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				} );
				apply();
			} );
		} );

		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				query = searchInput.value.trim().toLowerCase();
				apply();
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

	function init() {
		var dirs = document.querySelectorAll( '.fs-directory' );
		Array.prototype.forEach.call( dirs, initDirectory );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
