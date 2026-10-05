/**
 * External dependencies
 */
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,

	entry: {
		editor: path.resolve( process.cwd(), 'src/js/editor.js' ),
		'admin-style': path.resolve( process.cwd(), 'src/css/admin.css' ),
	},

	plugins: [ ...defaultConfig.plugins, new RemoveEmptyScriptsPlugin() ],
};
