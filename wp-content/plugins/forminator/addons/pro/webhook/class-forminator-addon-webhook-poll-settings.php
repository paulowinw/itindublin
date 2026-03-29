<?php
/**
 * Forminator Webhook Poll Settings
 *
 * @package Forminator
 */

/**
 * Class Forminator_Webhook_Poll_Settings
 * Handle how poll settings displayed and saved
 *
 * @since 1.6.1
 */
class Forminator_Webhook_Poll_Settings extends Forminator_Integration_Poll_Settings {
	use Forminator_Webhook_Settings_Trait;

	/**
	 * Build sample data form current fields
	 *
	 * @since 1.6.1
	 *
	 * @return array
	 */
	private function build_form_sample_data() {
		$sample_data = array();

		$sample_data['poll-name']  = forminator_get_name_from_model( $this->poll );
		$sample_data['vote']       = 'Vote';
		$sample_data['vote-extra'] = 'Vote Extra';
		$sample_data['results']    = array();

		$fields_array = $this->poll->get_fields_as_array();
		$map_entries  = Forminator_Form_Entry_Model::map_polls_entries( $this->module_id, $fields_array );
		$fields       = $this->poll->get_fields();

		foreach ( $fields as $field ) {
			$label = addslashes( $field->title );

			$slug    = isset( $field->slug ) ? $field->slug : sanitize_title( $label );
			$entries = 0;
			if ( in_array( $slug, array_keys( $map_entries ), true ) ) {
				$entries = $map_entries[ $slug ];
			}
			$sample_data['results'][ $slug ] = array(
				'label' => $label,
				'votes' => $entries,
			);
		}

		return $sample_data;
	}
}
