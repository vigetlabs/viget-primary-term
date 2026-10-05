<?php
/**
 * Uninstall routine for Viget Primary Term
 *
 * Removes plugin settings. Primary term meta is content, so it stays.
 *
 * @package Viget\PrimaryTerm
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'vgpt_settings' );
