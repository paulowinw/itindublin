<?php
/**
 * Template admin/views/common/entries/content.php
 *
 * @package Forminator
 */

$count = Forminator_Form_Entry_Model::count_all_entries();

if ( $count > 0 ) {
	$markup = $this->render_entries();

	$form_type = Forminator_Core::sanitize_text_field( 'form_type' );
	if ( $form_type ) {
		update_option( 'forminator_submissions_form_type', $form_type );
	}
	$form_id = filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
	if ( $form_id ) {
		update_option( 'forminator_submissions_form_id', $form_id );
	}

	$form_type = get_option( 'forminator_submissions_form_type', 'forminator_forms' );
	$form_id   = get_option( 'forminator_submissions_form_id' );

	?>

	<form method="get"
		name="bulk-action-form"
		class="sui-box">

		<div class="fui-entries-bar">

			<div class="fui-bar-selectors">

				<input type="hidden" name="page" value="forminator-entries" />

				<select
					name="form_type"
					onchange="submit()"
					class="sui-select sui-select-sm fui-bar-selectors__module"
					data-placeholder="<?php esc_html_e( 'Type', 'forminator' ); ?>"
					data-search="false"
				>
					<option></option>
					<?php foreach ( $this->get_form_types() as $_post_type => $name ) { ?>
						<option value="<?php echo esc_attr( $_post_type ); ?>" <?php echo selected( $_post_type, $form_type ); ?>><?php echo esc_html( $name ); ?></option>
					<?php } ?>
				</select>


				<?php Forminator_Entries_Page::render_form_switcher( $form_type, $form_id ); ?>

			</div>

			<button class="sui-button sui-button-blue show-submissions" onclick="submit()"><?php esc_html_e( 'Show Submissions', 'forminator' ); ?></button>

			<?php if ( $markup ) : ?>
				<a href="/" class="sui-button sui-button-ghost wpmudev-open-modal" data-modal="exports-schedule"><i class="sui-icon-paperclip" aria-hidden="true"></i> <?php esc_html_e( 'Export', 'forminator' ); ?></a>
			<?php endif; ?>

		</div>

	</form>

	<?php if ( $markup ) : ?>

		<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php else : ?>

		<div class="sui-box sui-message">

			<?php if ( forminator_is_show_branding() ) : ?>
				<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?>"
					srcset="<?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result.png' ); ?> 1x, <?php echo esc_url( forminator_plugin_url() . 'assets/images/forminator-no-result@2x.png' ); ?> 2x"
					alt="<?php esc_html_e( 'Forminator', 'forminator' ); ?>"
					class="sui-image"/>
			<?php endif; ?>

			<div class="sui-message-content">

				<h2><?php esc_html_e( 'Almost there!', 'forminator' ); ?></h2>

				<p><?php esc_html_e( 'Select the form, poll or quiz module to view the corresponding submissions.', 'forminator' ); ?></p>

			</div>

		</div>

	<?php endif; ?>

	<?php
} else {
	include forminator_plugin_dir() . 'admin/views/common/entries/content-none.php';
}
