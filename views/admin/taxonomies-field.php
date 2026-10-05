<?php
/**
 * Taxonomies field for Viget Primary Term
 *
 * @var array $rows Rows from Settings::get_rows().
 *
 * @package Viget\PrimaryTerm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Viget\PrimaryTerm\Settings;

// Shown in a tooltip on registered rows. wp_get_tooltip() is WordPress 7.1+, so older versions fall back to a title.
$vgpt_registered_note = __( 'Registered in code', 'viget-primary-term' );
?>
<table class="widefat striped" id="vgpt-taxonomies-table">
	<thead>
		<tr>
			<td class="check-column"><span class="screen-reader-text"><?php esc_html_e( 'Enabled', 'viget-primary-term' ); ?></span></td>
			<th><?php esc_html_e( 'Taxonomy', 'viget-primary-term' ); ?></th>
			<th><?php esc_html_e( 'Post Types', 'viget-primary-term' ); ?></th>
			<th class="vgpt-icon-cell"><span class="screen-reader-text"><?php esc_html_e( 'Status', 'viget-primary-term' ); ?></span></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $rows as $row ) : ?>
		<?php
		$vgpt_id = 'vgpt-taxonomy-' . $row['taxonomy'];
		$vgpt_on = $row['checked'] && ! $row['note'];
		?>
		<tr class="<?php echo $row['registered'] ? 'vgpt-row-registered' : ''; ?><?php echo $row['note'] ? ' vgpt-row-flagged' : ''; ?>">
			<th scope="row" class="check-column">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $vgpt_id ); ?>"
					<?php if ( ! $row['registered'] ) : ?>
						name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[taxonomies][]"
					<?php endif; ?>
					value="<?php echo esc_attr( $row['taxonomy'] ); ?>"
					<?php checked( $vgpt_on ); ?>
					<?php disabled( $row['registered'] ); ?>
				/>
			</th>
			<td>
				<label for="<?php echo esc_attr( $vgpt_id ); ?>"><?php echo esc_html( $row['label'] ); ?> (<?php echo esc_html( $row['taxonomy'] ); ?>)</label>
			</td>
			<td><?php echo esc_html( implode( ', ', $row['post_types'] ) ); ?></td>
			<td class="vgpt-icon-cell">
				<?php if ( $row['registered'] ) : ?>
					<?php
					$vgpt_tip  = $row['note'] ?? $vgpt_registered_note;
					$vgpt_icon = $row['note'] ? 'dashicons-no-alt' : 'dashicons-lock';
					?>
					<?php if ( function_exists( 'wp_get_tooltip' ) ) : ?>
						<?php
						echo wp_get_tooltip(
							$vgpt_tip,
							[
								'icon'  => $vgpt_icon,
								'class' => 'vgpt-registered-icon',
							]
						);
						?>
					<?php else : ?>
						<span class="dashicons <?php echo esc_attr( $vgpt_icon ); ?> vgpt-registered-icon" title="<?php echo esc_attr( $vgpt_tip ); ?>" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php echo esc_html( $vgpt_tip ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
