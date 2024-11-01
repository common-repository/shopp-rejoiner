<div class="shopp wrap">

	<div class="icon32"></div>
	<?php
	shopp_admin_screen_tabs();
	do_action('shopp_admin_notices');
	?>

	<form action="<?php esc_attr_e($action) ?>" method="post">

	<p><?php _e('Insert your Rejoiner details below. Get your free Rejoiner account at <a href="http://rejoiner.com" target="_blank">Rejoiner.com</a> to get started. Once you have an account, login to your <a href="https://app.rejoiner.com/app/implementation" target="_blank">Rejoiner dashboard</a> and visit the implementation tab.', 'shopprejoiner') ?></p>

	<table class="widefat">
		<thead>
			<tr>
				<th><?php _e('Setting', 'shopprejoiner') ?></th>
				<th><?php _e('Value', 'shopprejoiner') ?></th>
				<th><?php _e('Description', 'shopprejoiner') ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($rejoiner_settings as $setting => $field ): ?>
			<tr>
				<td><?php _e( $field['name'], 'shopprejoiner' ) ?></td>
				<td><input type="text" name="value-<?php esc_attr_e( $setting ) ?>" value="<?php esc_attr_e( $field['value'] ) ?>" /></td>
				<td><?php _e( $field['description'] ) ?></td>
			</tr>
		<?php endforeach; ?>
		<?php do_action( 'shopprejoiner-options-table' ) ?>
		</tbody>
	</table>

	<p> <input type="submit" class="button-primary" value="<?php _e('Save changes', 'shopprejoiner') ?>" /> </p>

	</form>
</div>