/**
 * Faculty & Staff — editor sidebar panel.
 *
 * Surfaces the contact/detail fields in the block editor's document sidebar
 * (the "Faculty / Staff" tab) instead of the easily-missed meta box at the
 * bottom of the editor. Reads/writes post meta registered with show_in_rest.
 *
 * Written with wp.element.createElement so it needs no build step.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.element || ! wp.plugins || ! wp.components || ! wp.data ) {
		return;
	}

	var el       = wp.element.createElement;
	var register = wp.plugins.registerPlugin;
	var Panel    = ( wp.editor && wp.editor.PluginDocumentSettingPanel )
		|| ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	var TextControl  = wp.components.TextControl;
	var useSelect    = wp.data.useSelect;
	var useDispatch  = wp.data.useDispatch;
	var __ = ( wp.i18n && wp.i18n.__ ) ? wp.i18n.__ : function ( s ) { return s; };

	if ( ! register || ! Panel || ! TextControl || ! useSelect || ! useDispatch ) {
		return;
	}

	var config    = window.fsEditorPanel || { postType: 'faculty_staff' };
	var FIELDS = [
		{ key: 'fs_title',    label: __( 'Title / Position', 'faculty-staff' ), type: 'text' },
		{ key: 'fs_email',    label: __( 'Email', 'faculty-staff' ),            type: 'email' },
		{ key: 'fs_phone',    label: __( 'Phone', 'faculty-staff' ),            type: 'text' },
		{ key: 'fs_location', label: __( 'Office Location', 'faculty-staff' ),  type: 'text' },
		{ key: 'fs_website',  label: __( 'Website / CV URL', 'faculty-staff' ), type: 'url' }
	];

	function FacultyPanel() {
		var data = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			return {
				postType: editor.getCurrentPostType(),
				meta: editor.getEditedPostAttribute( 'meta' ) || {}
			};
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;

		if ( data.postType !== config.postType ) {
			return null;
		}

		function setMeta( key, value ) {
			var patch = {};
			patch[ key ] = value;
			editPost( { meta: patch } );
		}

		var controls = FIELDS.map( function ( field ) {
			return el( TextControl, {
				key: field.key,
				type: field.type,
				label: field.label,
				value: data.meta[ field.key ] || '',
				onChange: function ( value ) { setMeta( field.key, value ); },
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: false
			} );
		} );

		return el(
			Panel,
			{
				name: 'fs-details',
				title: __( 'Faculty / Staff Details', 'faculty-staff' ),
				className: 'fs-details-panel'
			},
			controls
		);
	}

	register( 'fs-details-panel', { render: FacultyPanel } );
} )( window.wp );
