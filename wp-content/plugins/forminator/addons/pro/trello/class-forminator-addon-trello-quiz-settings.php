<?php
/**
 * Forminator Trello poll settings
 *
 * @package Forminator
 */

/**
 * Class Forminator_Trello_Quiz_Settings
 * Handle how quiz settings displayed and saved
 *
 * @since 1.6.2
 */
class Forminator_Trello_Quiz_Settings extends Forminator_Integration_Quiz_Settings {
	use Forminator_Trello_Settings_Trait;

	/**
	 * Has lead
	 *
	 * @return bool
	 */
	public function has_lead() {
		return true;
	}
}
