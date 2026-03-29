<?php
/**
 * The Forminator_Polls_General_Data_Protection class.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Polls_General_Data_Protection
 *
 * General Data Protection Applied for Polls
 *
 * @since 1.0.6
 */
class Forminator_Polls_General_Data_Protection extends Forminator_General_Data_Protection {

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected static $module_slug = 'poll';

	/**
	 * Forminator_Polls_General_Data_Protection constructor
	 */
	public function __construct() {
		parent::__construct( esc_html__( 'Forminator Polls', 'forminator' ) );
	}

	/**
	 * Add Privacy Message
	 *
	 * @since 1.0.6
	 *
	 * @return string
	 */
	public function get_privacy_message() {
		ob_start();
		include __DIR__ . '/policy-text.php';
		$content = ob_get_clean();
		$content = apply_filters( 'forminator_polls_privacy_policy_content', $content );

		return $content;
	}

	/**
	 * Anon IP
	 *
	 * @since 1.0.6
	 * @return bool
	 */
	public function personal_data_cleanup() {
		$this->anonymize_ip_votes();
		$this->remove_poll_submissions();

		return true;
	}

	/**
	 * Anonymize IP votes
	 *
	 * @since 1.6
	 */
	public function anonymize_ip_votes() {

		// todo : deprecate this, instead get all polls and read its settings.
		$overridden_polls_privacy = get_option( 'forminator_poll_privacy_settings', array() );
		// process overridden.
		foreach ( $overridden_polls_privacy as $form_id => $retentions ) {
			$retain_number = (int) $retentions['ip_address_retention_number'];
			$retain_unit   = $retentions['ip_address_retention_unit'];

			$retain_time = $this->get_retain_time( $retain_number, $retain_unit );
			if ( ! $retain_time ) {
				continue;
			}

			$entry_ids = Forminator_Form_Entry_Model::get_older_entry_ids( $retain_time, '', $form_id );

			foreach ( $entry_ids as $entry_id ) {
				$entry_model = new Forminator_Form_Entry_Model( $entry_id );
				$this->anonymize_entry_model( $entry_model );
			}
		}

		$retain_number = get_option( 'forminator_retain_votes_interval_number', 0 );
		$retain_unit   = get_option( 'forminator_retain_votes_interval_unit', 'days' );

		$retain_time = $this->get_retain_time( $retain_number, $retain_unit );
		if ( ! $retain_time ) {
			return false;
		}

		// todo : select only un-anonymized.
		$entry_ids = Forminator_Form_Entry_Model::get_older_entry_ids( $retain_time, 'poll' );

		foreach ( $entry_ids as $entry_id ) {
			$entry_model = new Forminator_Form_Entry_Model( $entry_id );
			if ( in_array( $entry_model->form_id, array_keys( $overridden_polls_privacy ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				// use overridden.
				continue;
			}
			$this->anonymize_entry_model( $entry_model );
		}

		return true;
	}

	/**
	 * Remove poll's submissions
	 *
	 * @since 1.6
	 */
	public function remove_poll_submissions() {

		/**
		 * PROCESS OVERIDDEN POLL FIRST
		 */
		// get all ALL-Status poll.
		$polls = Forminator_Poll_Model::model()->get_all_models( 'any' );

		// find overidden.
		$overidden_poll_ids = array(); // will be used to filter out global settings retention later.
		if ( isset( $polls['models'] ) && is_array( $polls['models'] ) ) {
			foreach ( $polls['models'] as $poll ) {
				/**
				 * Forminator_Poll_Model
				 *
				 * @var Forminator_Poll_Model $poll */
				$settings = $poll->settings;
				if ( isset( $settings['enable-submissions-retention'] ) ) {
					$is_overidden = filter_var( $settings['enable-submissions-retention'], FILTER_VALIDATE_BOOLEAN );
					if ( $is_overidden ) {
						$overidden_poll_ids[] = (int) $poll->id;

						$retain_number = 0;
						if ( isset( $settings['submissions-retention-number'] ) ) {
							$retain_number = intval( $settings['submissions-retention-number'] );
						}

						$retain_unit = 'days';
						if ( isset( $settings['submissions-retention-unit'] ) ) {
							$retain_unit = $settings['submissions-retention-unit'];
						}

						$retain_time = $this->get_retain_time( $retain_number, $retain_unit );
						if ( ! $retain_time ) {
							continue;
						}
						$this->delete_older_entries( $poll->id, $retain_time );
					}
				}
			}
		}

		/**
		 * PROCESS FROM GLOBAL SETTINGS
		 */
		$retain_number = get_option( 'forminator_retain_poll_submissions_interval_number', 0 );
		$retain_unit   = get_option( 'forminator_retain_poll_submissions_interval_unit', 'days' );

		$retain_time = $this->get_retain_time( $retain_number, $retain_unit );
		if ( ! $retain_time ) {
			return false;
		}

		$entry_ids = Forminator_Form_Entry_Model::get_older_entry_ids( $retain_time, 'poll' );

		foreach ( $entry_ids as $entry_id ) {
			$entry_model = new Forminator_Form_Entry_Model( $entry_id );
			$poll_id     = (int) $entry_model->form_id;
			if ( in_array( $poll_id, $overidden_poll_ids, true ) ) {
				// handled by overidden.
				continue;
			}
			$entry_model->delete();
		}
	}
}
