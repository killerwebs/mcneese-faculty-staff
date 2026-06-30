/**
 * Faculty & Staff — admin shortcode builder.
 * Reads the builder form controls and live-generates a clean shortcode,
 * emitting only attributes that differ from each shortcode's defaults.
 */
( function () {
	'use strict';

	var form = document.getElementById( 'fs-builder' );
	if ( ! form ) {
		return;
	}
	var typeSel = document.getElementById( 'fsb-type' );
	var output  = document.getElementById( 'fsb-output' );
	var copyBtn = document.getElementById( 'fsb-copy' );

	// Effective defaults per attribute; an object value means it varies by type.
	var DEFAULTS = {
		layout: 'grid',
		columns: '3',
		photo_shape: 'square',
		orderby: 'last_name',
		search: { faculty_directory: 'true', faculty_department: 'false' },
		filter: { faculty_directory: 'true' },
		sort: 'false',
		view_toggle: 'false',
		index: 'false',
		groupby: '',
		show_dept: { faculty_directory: 'false', faculty_department: 'false', faculty_member: 'true' },
		dept_badge: 'false',
		show_contact: 'false',
		show_excerpt: 'false',
		button: ''
	};

	function def( attr, type ) {
		var d = DEFAULTS[ attr ];
		if ( d && typeof d === 'object' ) {
			return d[ type ] || '';
		}
		return d === undefined ? '' : d;
	}

	function fields() {
		return Array.prototype.slice.call( form.querySelectorAll( '.fsb-field' ) );
	}
	function applies( field, type ) {
		var f = field.getAttribute( 'data-for' );
		return ! f || f.split( ',' ).indexOf( type ) !== -1;
	}
	function control( field ) {
		return field.querySelector( '[data-attr]' );
	}
	function checkboxVal( el ) {
		var on = el.getAttribute( 'data-on' ) || 'true';
		var off = el.getAttribute( 'data-off' );
		off = ( off === null ) ? 'false' : off;
		return el.checked ? on : off;
	}

	// When the type changes, reset checkboxes to that type's defaults.
	function resetChecks( type ) {
		fields().forEach( function ( field ) {
			var el = control( field );
			if ( el && el.type === 'checkbox' ) {
				el.checked = def( el.getAttribute( 'data-attr' ), type ) === 'true';
			}
		} );
	}

	function build() {
		var type = typeSel.value;
		fields().forEach( function ( field ) {
			field.style.display = applies( field, type ) ? '' : 'none';
		} );

		var parts = [];
		fields().forEach( function ( field ) {
			if ( ! applies( field, type ) ) {
				return;
			}
			var el = control( field );
			if ( ! el ) {
				return;
			}
			var attr = el.getAttribute( 'data-attr' );
			var val  = el.type === 'checkbox' ? checkboxVal( el ) : ( el.value || '' ).trim();

			if ( el.getAttribute( 'data-required' ) === '1' ) {
				if ( val !== '' ) {
					parts.push( attr + '="' + val + '"' );
				}
				return;
			}
			if ( val === '' || val === def( attr, type ) ) {
				return;
			}
			parts.push( attr + '="' + val + '"' );
		} );

		output.value = '[' + type + ( parts.length ? ' ' + parts.join( ' ' ) : '' ) + ']';
	}

	typeSel.addEventListener( 'change', function () {
		resetChecks( typeSel.value );
		build();
	} );
	form.addEventListener( 'input', build );
	form.addEventListener( 'change', build );

	if ( copyBtn ) {
		copyBtn.addEventListener( 'click', function () {
			output.select();
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( output.value );
			}
			var label = copyBtn.textContent;
			copyBtn.textContent = copyBtn.getAttribute( 'data-copied' ) || 'Copied';
			setTimeout( function () { copyBtn.textContent = label; }, 1200 );
		} );
	}

	resetChecks( typeSel.value );
	build();
} )();
