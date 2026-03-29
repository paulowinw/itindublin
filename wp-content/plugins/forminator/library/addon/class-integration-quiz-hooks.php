<?php
/**
 * The Forminator_Integration_Quiz_Hooks class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Quiz_Hooks
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * If you override any of these method, please add necessary hooks in it,
 * Which you can see below, as a reference and keep the arguments signature.
 * If needed you can call these method, as parent::method_name(),
 * and add your specific hooks.
 *
 * @since 1.6.2
 */
abstract class Forminator_Integration_Quiz_Hooks extends Forminator_Integration_Hooks {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static string $slug = 'quiz';

	/**
	 * Lead settings instance
	 *
	 * @since 1.6.2
	 * @var Forminator_Integration_Quiz_Settings|null
	 */
	protected $lead_settings_instance;

	/**
	 * Lead Model
	 *
	 * @since 1.6.2
	 * @var Forminator_Form_Model
	 */
	protected $lead_model;

	/**
	 * Forminator_Integration_Quiz_Hooks constructor.
	 *
	 * @param Forminator_Integration $addon Integration.
	 * @param int                    $module_id Module ID.
	 *
	 * @since 1.6.2
	 * @throws Forminator_Integration_Exception When module ID is invalid.
	 */
	public function __construct( Forminator_Integration $addon, int $module_id ) {
		parent::__construct( $addon, $module_id );

		if ( isset( $this->module->settings['hasLeads'] ) && $this->module->settings['hasLeads'] ) {
			$this->lead_model             = Forminator_Base_Form_Model::get_model( $this->module->settings['leadsId'] );
			$this->lead_settings_instance = $this->addon->get_addon_settings( $this->module->settings['leadsId'], 'form' );
		}
	}

	/**
	 * Override this function to execute action after entry saved
	 *
	 * Its void function, so return value will be ignored, and forminator process will always continue
	 * unless it generates unrecoverable error, so please be careful on extending this function
	 *
	 * @since 1.6.2
	 *
	 * @param Forminator_Form_Entry_Model $entry_model Form entry Model.
	 */
	public function after_entry_saved( Forminator_Form_Entry_Model $entry_model ) {
		$addon_slug             = $this->addon->get_slug();
		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;

		/**
		 * Fires when entry already saved on db
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter probably might be not available.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.6.2
		 *
		 * @param int                                          $quiz_id                current Quiz ID.
		 * @param Forminator_Form_Entry_Model                  $entry_model            Forminator Entry Model.
		 * @param Forminator_Integration_Quiz_Settings|null $quiz_settings_instance of Integration Quiz Settings.
		 */
		do_action(
			'forminator_addon_quiz_' . $addon_slug . '_after_entry_saved',
			$quiz_id,
			$entry_model,
			$quiz_settings_instance
		);
	}

	/**
	 * Get Submit quiz error message
	 *
	 * @since 1.6.2
	 * @return string
	 */
	public function get_submit_error_message() {
		$addon_slug             = $this->addon->get_slug();
		$quiz_id                = $this->module_id;
		$quiz_settings_instance = $this->settings_instance;

		$error_message = $this->submit_error_message;
		/**
		 * Filter error message on submit
		 *
		 * Although it can be used for all addon.
		 * Please keep in mind that if the addon override this method,
		 * then this filter probably not be available.
		 * To be sure please check individual addon documentations.
		 *
		 * @since 1.6.2
		 *
		 * @param array                                        $export_columns         column to be exported.
		 * @param int                                          $quiz_id                current quiz ID.
		 * @param Forminator_Integration_Quiz_Settings|null $quiz_settings_instance of Integration quiz Settings.
		 */
		$error_message = apply_filters(
			'forminator_addon_' . $addon_slug . '_submit_quiz_error_message',
			$error_message,
			$quiz_id,
			$quiz_settings_instance
		);

		return $error_message;
	}

	/**
	 * Check if element_id is stripe type
	 *
	 * @since 1.7
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return bool
	 */
	public static function element_is_stripe( $element_id ) {
		$is_stripe = stripos( $element_id, self::STRIPE_ELEMENT_PREFIX ) !== false;

		/**
		 * Filter stripe flag of element
		 *
		 * @since 1.7
		 *
		 * @param bool   $is_stripe
		 * @param string $element_id
		 *
		 * @return bool
		 */
		$is_stripe = apply_filters( 'forminator_addon_element_is_stripe', $is_stripe, $element_id );

		return $is_stripe;
	}

	/**
	 * Check if element_id is calculation type
	 *
	 * @since 1.7
	 *
	 * @param string $element_id Element Id.
	 *
	 * @return bool
	 */
	public static function element_is_calculation( $element_id ) {
		$is_calculation = stripos( $element_id, self::CALCULATION_ELEMENT_PREFIX ) !== false;

		/**
		 * Filter calculation flag of element
		 *
		 * @since 1.7
		 *
		 * @param bool   $is_calculation
		 * @param string $element_id
		 *
		 * @return bool
		 */
		$is_calculation = apply_filters( 'forminator_addon_element_is_calculation', $is_calculation, $element_id );

		return $is_calculation;
	}

	/**
	 * Return submitted data.
	 *
	 * @param array $submitted_data Submitted data.
	 * @param int   $module_id Module ID.
	 * @param array $current_entry_fields Current entry fields.
	 * @return array
	 */
	protected function prepare_submitted_data( array $submitted_data, int $module_id, array $current_entry_fields ): array {
		$quiz_submitted_data = get_quiz_submitted_data( $this->module, $submitted_data, $current_entry_fields );
		$quiz_settings       = $this->settings_instance->get_quiz_settings();
		$addons_fields       = $this->settings_instance->get_form_fields();
		$submitted_data      = get_addons_lead_form_entry_data( $quiz_settings, $submitted_data, $addons_fields );

		$submitted_data = $this->reformat_submitted_data( $submitted_data, $module_id, $current_entry_fields );
		$submitted_data = array_merge( $submitted_data, $quiz_submitted_data );

		return $submitted_data;
	}
}
