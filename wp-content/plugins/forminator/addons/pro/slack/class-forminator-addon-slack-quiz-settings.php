<?php
/**
 * Forminator Addon Slack Quiz Settings.
 *
 * @package Forminator
 */

/**
 * Class Forminator_Slack_Quiz_Settings
 * Handle how quiz settings displayed and saved
 *
 * @since 1.6.2
 */
class Forminator_Slack_Quiz_Settings extends Forminator_Integration_Quiz_Settings {
	use Forminator_Slack_Settings_Trait;

	/**
	 * Has lead
	 *
	 * @return bool
	 */
	public function has_lead() {
		return true;
	}
}
