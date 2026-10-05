<?php
/**
 * Helpers for Viget Primary Term
 *
 * @package Viget\PrimaryTerm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vgpt' ) ) {
	/**
	 * Return the Viget Primary Term core instance.
	 *
	 * @return Viget\PrimaryTerm\Core
	 */
	function vgpt(): Viget\PrimaryTerm\Core {
		if ( ! class_exists( Viget\PrimaryTerm\Core::class ) ) {
			require_once VGPT_PLUGIN_PATH . 'includes/class-core.php';
		}
		return Viget\PrimaryTerm\Core::get_instance();
	}
}
