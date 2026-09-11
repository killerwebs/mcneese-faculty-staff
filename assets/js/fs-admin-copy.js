/**
 * Copy-to-clipboard for shortcode snippets in the admin.
 * Any <button class="fs-copy" data-copy="..."> copies its data-copy value.
 */
( function () {
	'use strict';
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '.fs-copy' ) : null;
		if ( ! btn ) {
			return;
		}
		e.preventDefault();
		var text = btn.getAttribute( 'data-copy' ) || '';
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text );
		}
		var original = btn.textContent;
		btn.textContent = btn.getAttribute( 'data-copied-label' ) || 'Copied';
		setTimeout( function () { btn.textContent = original; }, 1200 );
	} );
} )();
