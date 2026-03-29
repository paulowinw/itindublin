<?php
/**
 * Template admin/views/settings/tab-emails.php
 *
 * @package Forminator
 */

$sender_email_address = get_global_sender_email_address();
$sender_name          = get_global_sender_name();
?>

<div id="forminator-section-settings-from-headers" class="sui-box-settings-row">

	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'From Headers', 'forminator' ); ?></span>
		<span class="sui-description"><?php esc_html_e( 'Choose the default sender name and sender email address for all of your outgoing emails from Forminator.', 'forminator' ); ?></span>
	</div>

	<div class="sui-box-settings-col-2">

		<div class="sui-form-field">

			<label for="forminator-settings--sender-email"
				class="sui-label"><?php esc_html_e( 'Sender email address', 'forminator' ); ?></label>
			<input type="email"
				name="sender_email"
				placeholder="<?php esc_html_e( 'Enter email', 'forminator' ); ?>"
				value="<?php echo esc_html( $sender_email_address ); ?>"
				id="forminator-settings--sender-email"
				class="sui-form-control forminator-required"/>
			<span class="sui-error-message"
				style="display: none;"><?php esc_html_e( 'Please, enter a valid email address.', 'forminator' ); ?></span>

		</div>

		<div class="sui-form-field">

			<label for="forminator-settings--sender-name"
				class="sui-label"><?php esc_html_e( 'Sender name', 'forminator' ); ?></label>
			<input type="text"
				name="sender_name"
				placeholder="<?php esc_html_e( 'Enter name', 'forminator' ); ?>"
				value="<?php echo esc_html( $sender_name ); ?>"
				id="forminator-settings--sender-name"
				class="sui-form-control forminator-required"/>
			<span class="sui-error-message"
				style="display: none;"><?php esc_html_e( 'The sender email cannot be empty.', 'forminator' ); ?></span>

		</div>

	</div>

</div>
