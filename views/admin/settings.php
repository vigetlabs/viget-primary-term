<?php
/**
 * Settings page for Viget Primary Term
 *
 * @package Viget\PrimaryTerm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Viget\PrimaryTerm\Settings;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Primary Terms', 'viget-primary-term' ); ?></h1>
	<p><?php esc_html_e( 'Checked taxonomies get a primary term select under their panel in the block editor.', 'viget-primary-term' ); ?></p>
	<?php settings_errors( Settings::OPTION_NAME ); ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
		<?php
		settings_fields( Settings::OPTION_NAME );
		do_settings_sections( Settings::PAGE_SLUG );
		submit_button();
		?>
	</form>
</div>
