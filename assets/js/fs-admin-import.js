/**
 * Faculty & Staff — batched CSV import with a progress bar.
 * Reads its config from the #fs-import-progress element's data-config and
 * walks the rows in small AJAX batches so image downloads stay responsive.
 */
( function () {
	'use strict';

	var root = document.getElementById( 'fs-import-progress' );
	if ( ! root ) {
		return;
	}

	var cfg;
	try {
		cfg = JSON.parse( root.getAttribute( 'data-config' ) );
	} catch ( e ) {
		return;
	}

	var bar       = root.querySelector( '.fs-progress-bar' );
	var statusEl  = root.querySelector( '.fs-progress-status' );
	var countsEl  = root.querySelector( '.fs-progress-counts' );
	var logEl     = root.querySelector( '.fs-progress-log' );
	var doneEl    = root.querySelector( '.fs-progress-done' );

	var loggedErrors = 0;

	function setBar( processed, total ) {
		var pct = total ? Math.round( ( processed / total ) * 100 ) : 100;
		bar.style.width = pct + '%';
		statusEl.textContent = processed + ' of ' + total + ' (' + pct + '%)';
	}

	function setCounts( r ) {
		if ( ! r ) {
			return;
		}
		countsEl.innerHTML =
			'<strong>' + r.created + '</strong> created &nbsp; ' +
			'<strong>' + r.updated + '</strong> updated &nbsp; ' +
			'<strong>' + r.skipped + '</strong> skipped &nbsp; ' +
			'<strong>' + r.images + '</strong> photos';
	}

	function appendErrors( errors ) {
		if ( ! errors || errors.length <= loggedErrors ) {
			return;
		}
		logEl.hidden = false;
		for ( var i = loggedErrors; i < errors.length; i++ ) {
			var li = document.createElement( 'li' );
			li.textContent = errors[ i ];
			logEl.appendChild( li );
		}
		loggedErrors = errors.length;
	}

	function fail( message ) {
		statusEl.textContent = message || 'Import failed.';
		bar.style.background = '#d63638';
		doneEl.hidden = false;
	}

	function runBatch( offset ) {
		var body = new URLSearchParams();
		body.append( 'action', 'fs_import_batch' );
		body.append( 'nonce', cfg.nonce );
		body.append( 'job', cfg.job );
		body.append( 'offset', offset );

		fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) {
				if ( ! json || ! json.success ) {
					fail( ( json && json.data && json.data.message ) || 'Import error.' );
					return;
				}
				var d = json.data;
				setBar( d.processed, d.total );
				setCounts( d.results );
				appendErrors( d.errors );

				if ( d.done ) {
					statusEl.textContent = 'Done — ' + d.processed + ' of ' + d.total + ' processed.';
					doneEl.hidden = false;
				} else {
					runBatch( d.processed );
				}
			} )
			.catch( function () {
				fail( 'Network error. The import stopped; you can re-run it (already-imported people are matched and updated).' );
			} );
	}

	setBar( 0, cfg.total );
	runBatch( 0 );
} )();
