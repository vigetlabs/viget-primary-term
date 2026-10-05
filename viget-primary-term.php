<?php
/**
 * Plugin Name:       Viget Primary Term
 * Plugin URI:        https://github.com/vigetlabs/viget-primary-term
 * Description:       Lets editors pick a primary term for a post from the block editor's taxonomy panels.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            Viget
 * Author URI:        https://www.viget.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       viget-primary-term
 * Domain Path:       /languages
 *
 * @package Viget\PrimaryTerm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VGPT_PLUGIN_VERSION', '1.0.0' );
define( 'VGPT_PLUGIN_FILE', __FILE__ );
define( 'VGPT_PLUGIN_PATH', plugin_dir_path( VGPT_PLUGIN_FILE ) );
define( 'VGPT_PLUGIN_URL', plugin_dir_url( VGPT_PLUGIN_FILE ) );

require_once VGPT_PLUGIN_PATH . 'includes/helpers.php';

// Initialize the plugin.
vgpt();
