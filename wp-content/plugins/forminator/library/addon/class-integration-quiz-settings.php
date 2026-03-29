<?php
/**
 * The Forminator_Integration_Quiz_Settings class.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Integration_Quiz_Settings
 * Any change(s) to this file is subject to:
 * - Properly Written DocBlock! (what is this, why is that, how to be like those, etc, as long as you want!)
 * - Properly Written Changelog!
 *
 * @since 1.6.2
 */
abstract class Forminator_Integration_Quiz_Settings extends Forminator_Integration_Settings {

	/**
	 * Current Quiz Settings
	 *
	 * @since 1.6.2
	 * @var array
	 */
	protected $quiz_settings = array();

	/**
	 * An addon can be force disconnected from quiz, if its not met requirement, or data changed externally
	 * example :
	 *  - Mail List deleted on mailchimp server app
	 *  - Fields removed
	 *
	 * @since 1.6.2
	 * @var bool
	 */
	protected $is_force_quiz_disconnected = false;

	/**
	 * Reason of Force disconnected
	 *
	 * @since 1.6.2
	 * @var string
	 */
	protected $force_quiz_disconnected_reason = '';


	/**
	 * Quiz Model
	 *
	 * @since 1.6.2
	 * @var Forminator_Quiz_Model|null
	 */
	protected $quiz = null;

	/**
	 * Current lead fields
	 *
	 * @since 1.14
	 * @var Forminator_Form_Model|null
	 */
	protected $form_fields;

	/**
	 * Current lead settings
	 *
	 * @since 1.14
	 * @var Forminator_Form_Model|null
	 */
	protected $form_settings;

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'quiz';

	/**
	 * Forminator_Integration_Quiz_Settings constructor.
	 *
	 * @since 1.6.2
	 *
	 * @param Forminator_Integration $addon Class Forminator_Integration.
	 * @param int                    $quiz_id Quiz Id.
	 *
	 * @throws Forminator_Integration_Exception When there is an Integration error.
	 */
	public function __construct( Forminator_Integration $addon, $quiz_id ) {
		$this->addon     = $addon;
		$this->module_id = $quiz_id;
		$this->quiz      = Forminator_Base_Form_Model::get_model( $this->module_id );
		if ( ! $this->quiz ) {
			/* translators: Quiz ID */
			throw new Forminator_Integration_Exception( sprintf( esc_html__( 'Quiz with id %d could not be found', 'forminator' ), esc_html( $this->module_id ) ) );
		}
		$this->quiz_settings = forminator_addon_format_quiz_settings( $this->quiz );
		if ( isset( $this->quiz_settings['hasLeads'] ) && $this->quiz_settings['hasLeads'] ) {
			$lead_model          = Forminator_Base_Form_Model::get_model( $this->quiz_settings['leadsId'] );
			$this->form_fields   = ! empty( $lead_model ) ? forminator_addon_format_form_fields( $lead_model ) : array();
			$this->form_settings = ! empty( $lead_model ) ? forminator_addon_format_form_settings( $lead_model ) : array();
		}
	}

	/**
	 * Override this function if addon need to do something with addon quiz setting values
	 *
	 * @example transform, load from other storage ?
	 * called when rendering tab on quiz settings
	 *
	 * @since   1.6.2
	 *
	 * @param mixed $values Settings.
	 *
	 * @return mixed
	 */
	public function before_get_quiz_settings_values( $values ) {
		return $values;
	}

	/**
	 * Override this function if addon need to do something with addon quiz setting values
	 *
	 * @example transform, load from other storage ?
	 * called when rendering tab on quiz settings
	 * @since   1.6.2
	 *
	 * @param mixed $values Settings.
	 *
	 * @return mixed
	 */
	public function before_save_quiz_settings_values( $values ) {
		return $values;
	}

	/**
	 * Get status of force disconnected from WP post_meta
	 *
	 * @since 1.6.2
	 * @return bool
	 */
	final public function is_force_quiz_disconnected() {
		$disconnected = get_post_meta( $this->module_id, 'forminator_addon_' . $this->addon->get_slug() . '_quiz_disconnect', true );

		if ( ! empty( $disconnected ) && isset( $disconnected['disconnect'] ) && $disconnected['disconnect'] ) {
			$this->is_force_quiz_disconnected     = true;
			$this->force_quiz_disconnected_reason = $disconnected['disconnect_reason'];
		}

		return $this->is_force_quiz_disconnected;
	}

	/**
	 * Get disconnected reason
	 *
	 * @since 1.6.2
	 * @return string
	 */
	final public function force_quiz_disconnected_reason() {
		return $this->force_quiz_disconnected_reason;
	}

	/**
	 * Force quiz to be disconnected with addon
	 *
	 * @since 1.6.2
	 *
	 * @param string $reason Reason for disconnect.
	 */
	final public function force_quiz_disconnect( $reason ) {
		$this->is_force_quiz_disconnected     = true;
		$this->force_quiz_disconnected_reason = $reason;

		$this->addon_settings = array();

		$this->save_module_settings_values();
	}

	/**
	 * Save disconnect reason to WP post_meta
	 *
	 * @since 1.6.2
	 */
	final public function save_force_quiz_disconnect_reason() {
		if ( $this->is_force_quiz_disconnected ) {
			update_post_meta(
				$this->module_id,
				'forminator_addon_' . $this->addon->get_slug() . '_quiz_disconnect',
				array(
					'disconnect'        => true,
					'disconnect_reason' => $this->force_quiz_disconnected_reason,
				)
			);
		}
	}

	/**
	 * Remove disconnect reason quiz WP post_meta
	 *
	 * @since 1.6.2
	 */
	final public function remove_saved_force_quiz_disconnect_reason() {
		delete_post_meta( $this->module_id, 'forminator_addon_' . $this->addon->get_slug() . '_quiz_disconnect' );
	}

	/**
	 * Get current quiz settings
	 *
	 * @since 1.6.2
	 * @return array
	 */
	final public function get_quiz_settings() {
		return $this->quiz_settings;
	}

	/**
	 * Has lead
	 *
	 * @return bool
	 */
	public function has_lead() {
		$quiz_settings = $this->get_quiz_settings();

		return ! empty( $quiz_settings['hasLeads'] );
	}

	/**
	 * Get current form settings
	 *
	 * @since 1.1
	 * @return array
	 */
	final public function get_form_settings() {
		return $this->form_settings;
	}

	/**
	 * Get current lead form fields
	 *
	 * @since 1.1
	 * @return array
	 */
	final public function get_form_fields() {
		return $this->form_fields;
	}

	/**
	 * Get current lead form fields
	 *
	 * @since 1.1
	 * @return array
	 */
	final public function get_quiz_fields() {
		return $this->quiz->questions;
	}

	/**
	 * Override this function to set wizardable settings
	 * Default its and empty array which is indicating that Integration doesnt have settings
	 *
	 * Its multi array, with numerical key, start with `0`
	 * Every step on wizard, will consist at least
	 * - `callback` : when application requesting wizard, Forminator will do `call_user_func` on this value, with these arguments
	 *      - `$submitted_data` : array of submitted data POST-ed by user
	 *      - `$quiz_id` : current quiz_id when called on `Quiz Settings` or 0 when called on Global Settings
	 * - `is_completed` : when application requesting wizard, will check if `Previous Step` `is_completed` by doing `call_user_func` on its value
	 *      this function should return `true` or `false`
	 *
	 * @since 1.6.2
	 * @return array
	 */
	public function module_settings_wizards() {
		// What this function return should looks like.
		$steps = array(
			// First Step / step `0`.
			array(
				/**
				 * Value of `callback` will be passed as first argument of `call_user_func`
				 * it does not have to be passed `$this` as reference such as `array( $this, 'sample_setting_first_step' )`,
				 * But its encouraged to passed `$this` because you will be benefited with $this class instance, in case you need to call private function or variable inside it
				 * you can make the value to be `some_function_name` as long `some_function_name` as long it will globally callable which will be checked with `is_callable`
				 * and should be able to accept 2 arguments $submitted_data, $quiz_id
				 *
				 * This callback should return an array @see Forminator_Integration::sample_setting_first_step()
				 *
				 * @see Forminator_Integration::sample_setting_first_step()
				 */
				'callback'     => array( $this, 'sample_setting_first_step' ),
				/**
				 * Before Forminator call the `callback`, Forminator will attempt to run `is_completed` from the previous step
				 * In this case, `is_completed` will be called when Forminator trying to display Settings Wizard for Second Step / step `1`
				 * Like `callback` its value will be passed as first argument of `call_user_func`
				 * and no arguments passed to this function when its called
				 *
				 * @see Forminator_Integration::sample_setting_first_step_is_completed()
				 */
				'is_completed' => array( $this, 'sample_setting_first_step_is_completed' ),
			),
		);

		return array();
	}

	/**
	 * Get quiz settings data to export
	 *
	 * Default is from post_meta, override when needed
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function to_exportable_data() {
		$addon_slug    = $this->addon->get_slug();
		$quiz_settings = $this->get_settings_values();
		if ( empty( $quiz_settings ) ) {
			$exportable_data = array();
		} else {
			$exportable_data = array(
				'quiz_settings' => $quiz_settings,
				'version'       => $this->addon->get_version(),
			);
		}

		$quiz_id = $this->module_id;

		/**
		 * Filter quiz settings that will be exported when requested
		 *
		 * @since 1.6.2
		 *
		 * @param array $exportable_data
		 * @param int   $quiz_id
		 */
		$exportable_data = apply_filters( "forminator_addon_{$addon_slug}_quiz_settings_to_exportable_data", $exportable_data, $quiz_id );

		return $exportable_data;
	}

	/**
	 * Executed when quiz settings imported
	 *
	 * Default is save imported data to post_meta, override when needed
	 *
	 * @since 1.6.2
	 *
	 * @param mixed $import_data Import data.
	 * @throws Forminator_Integration_Exception When there is an Integration error.
	 */
	public function import_data( $import_data ) {
		$addon_slug = $this->addon->get_slug();
		$quiz_id    = $this->module_id;

		$import_data = apply_filters( "forminator_addon_{$addon_slug}_quiz_settings_import_data", $import_data, $quiz_id );

		/**
		 * Executed when importing quiz settings of this addon
		 *
		 * @since 1.6.2
		 *
		 * @param array $exportable_data
		 * @param int   $quiz_id
		 */
		do_action( "forminator_addon_{$addon_slug}_on_import_quiz_settings_data", $quiz_id, $import_data );

		try {
			// pre-basic-validation.
			if ( empty( $import_data ) ) {
				throw new Forminator_Integration_Exception( 'import_data_empty' );
			}

			if ( ! isset( $import_data['quiz_settings'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_no_quiz_settings' );
			}

			if ( empty( $import_data['quiz_settings'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_quiz_settings_empty' );
			}

			if ( ! isset( $import_data['version'] ) ) {
				throw new Forminator_Integration_Exception( 'import_data_no_version' );
			}
			$this->save_module_settings_values( $import_data['quiz_settings'] );

		} catch ( Forminator_Integration_Exception $e ) {
			forminator_addon_maybe_log( $e->getMessage() );
			// do nothing.
		}
	}

	/**
	 * Mailchimp Address type fields array
	 *
	 * @since 1.0 Mailchimp Integration
	 * @return array
	 */
	public function mail_address_fields() {

		$address = array(
			'addr1'   => 'Address 1',
			'addr2'   => 'Address 2',
			'city'    => 'City',
			'state'   => 'State',
			'zip'     => 'Zip',
			'country' => 'Country',
		);

		return $address;
	}

	/**
	 * Get fields for spesific addon field type
	 *
	 * @param string $type Field type.
	 * @return array
	 */
	protected function get_fields_for_type( $type = '' ) {
		$fields = parent::get_fields_for_type( $type );

		if ( 'email' === $type ) {
			return $fields;
		}

		$quiz_fields = array_map(
			function ( $quiz_question ) {
				return array(
					'element_id'  => $quiz_question['slug'],
					'field_label' => $quiz_question['title'],
				);
			},
			$this->get_quiz_fields()
		);
		array_unshift(
			$quiz_fields,
			array(
				'element_id'  => 'quiz-name',
				'field_label' => __( 'Quiz Name', 'forminator' ),
			)
		);
		if ( 'knowledge' === $this->quiz->quiz_type ) {
			$quiz_fields[] = array(
				'element_id'  => 'correct-answers',
				'field_label' => __( 'Correct Answers', 'forminator' ),
			);
			$quiz_fields[] = array(
				'element_id'  => 'total-answers',
				'field_label' => __( 'Total Answers', 'forminator' ),
			);
		} elseif ( 'nowrong' === $this->quiz->quiz_type ) {
			$quiz_fields[] = array(
				'element_id'  => 'result-answers',
				'field_label' => __( 'Result Answer', 'forminator' ),
			);
		}

		return array_merge( $fields, $quiz_fields );
	}
}
