<?php
/**
 * The Forminator_Integration_Poll_Hooks class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Poll_Hooks
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * If you override any of these method, please add necessary hooks in it,
 * Which you can see below, as a reference and keep the arguments signature.
 * If needed you can call these method, as parent::method_name(),
 * and add your specific hooks.
 *
 * @since 1.6.1
 */
abstract class Forminator_Integration_Poll_Hooks extends Forminator_Integration_Hooks {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static string $slug = 'poll';

	/**
	 * Override this function to execute action after entry saved
	 *
	 * Its void function, so return value will be ignored, and forminator process will always continue
	 * unless it generates unrecoverable error, so please be careful on extending this function
	 *
	 * @since 1.6.1
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry model.
	 */
	public function after_entry_saved( Forminator_Form_Entry_Model $entry_model ) {
		$addon_slug             = $this->addon->get_slug();
		$poll_id                = $this->module_id;
		$poll_settings_instance = $this->settings_instance;

		/**
		 * Fires when entry already saved on db
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter probably won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.6.1
		 *
		 * @param int                                          $poll_id                current Poll ID.
		 * @param Forminator_Form_Entry_Model                  $entry_model            Forminator Entry Model.
		 * @param Forminator_Integration_Poll_Settings|null $poll_settings_instance of Integration Poll Settings.
		 */
		do_action(
			'forminator_addon_poll_' . $addon_slug . '_after_entry_saved',
			$poll_id,
			$entry_model,
			$poll_settings_instance
		);
	}

	/**
	 * Get Submit poll error message
	 *
	 * @since 1.6.1
	 * @return string
	 */
	public function get_submit_error_message() {
		$addon_slug             = $this->addon->get_slug();
		$poll_id                = $this->module_id;
		$poll_settings_instance = $this->settings_instance;

		$error_message = $this->submit_error_message;
		/**
		 * Filter addon columns to be displayed on export submissions
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter probably won't be applied.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.6.1
		 *
		 * @param array                                        $export_columns         column to be exported.
		 * @param int                                          $poll_id                current poll ID.
		 * @param Forminator_Integration_Poll_Settings|null $poll_settings_instance of Integration Poll Settings.
		 */
		$error_message = apply_filters(
			'forminator_addon_' . $addon_slug . '_submit_poll_error_message',
			$error_message,
			$poll_id,
			$poll_settings_instance
		);

		return $error_message;
	}
}
