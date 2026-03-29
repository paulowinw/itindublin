<?php
/**
 * Forminator Addon Mailjet Form Hooks.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Mailjet_Form_Hooks
 *
 * Hooks that used by Mailjet Integration defined here
 */
class Forminator_Mailjet_Form_Hooks extends Forminator_Integration_Form_Hooks {

	/**
	 * Return special integration args
	 *
	 * @param array $submitted_data Submitted data.
	 * @param array $addon_setting_values Integration settings.
	 * @return array
	 */
	protected function get_special_addon_args( $submitted_data, $addon_setting_values ) {
		return array(
			'name' => $submitted_data[ $addon_setting_values['fields_map']['name'] ] ?? '',
		);
	}
}
