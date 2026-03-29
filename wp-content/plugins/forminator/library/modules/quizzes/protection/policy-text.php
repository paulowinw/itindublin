<?php
/**
 * Policy text.
 *
 * @package Forminator
 */

?>
<div class="wp-suggested-text">
	<h2><?php esc_html_e( 'Which quizzes are collecting personal data?', 'forminator' ); ?></h2>
	<p class="privacy-policy-tutorial">
		<?php
		esc_html_e(
			'If you use Forminator PRO to create and embed any quizzes on your website, you may need to mention it here to properly distinguish it from other quizzes.',
			'forminator'
		);
		?>
	</p>

	<h2><?php esc_html_e( 'What personal data do we collect and why?', 'forminator' ); ?></h2>
	<p class="privacy-policy-tutorial">
		<?php esc_html_e( 'By default Forminator captures <strong>NO Personally Identifiable Information</strong> for each Quiz submission.', 'forminator' ); ?>
	</p>
	<p class="privacy-policy-tutorial">
		<?php
		esc_html_e(
			'In this section you should note what personal data you collected including which quizzes are available. You should also explain why this data is needed. Include the legal basis for your data collection and note the active consent the user has given.',
			'forminator'
		);
		?>
	</p>
	<p>
		<strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text: ', 'forminator' ); ?></strong>
		<?php esc_html_e( 'When visitors or users submit a quiz\'s answer, we capture <strong>NO Personally Identifiable Information</strong>.', 'forminator' ); ?>
	</p>

	<h2><?php esc_html_e( 'How long we retain your data', 'forminator' ); ?></h2>
	<p class="privacy-policy-tutorial">
		<?php
		esc_html_e(
			'By default Forminator retains all quizzes answers and <strong>forever</strong>. You can change this setting in <strong>Forminator</strong> &raquo; <strong>Settings</strong> &raquo;
		<strong>Data</strong>',
			'forminator'
		);
		?>
	</p>
	<p>
		<strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text: ', 'forminator' ); ?></strong>
		<?php esc_html_e( 'When visitors or users answer a quiz we retain the <strong>answers</strong> data for 30 days and then remove it from our system.', 'forminator' ); ?>
	</p>
	<h2><?php esc_html_e( 'Where we send your data', 'forminator' ); ?></h2>
	<p>
		<strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text: ', 'forminator' ); ?></strong>
		<?php esc_html_e( 'All collected data might be shown publicly and we send it to our workers or contractors to perform necessary actions based on answers.', 'forminator' ); ?>
	</p>
	<h2><?php esc_html_e( 'Third Parties', 'forminator' ); ?></h2>
	<p class="privacy-policy-tutorial">
		<?php
		esc_html_e(
			'If your quizzes utilize either built-in or external third party services, in this section you should mention any third parties and its privacy policy.',
			'forminator'
		);
		?>
	</p>
	<p class="privacy-policy-tutorial">
		<?php esc_html_e( 'By default Forminator Quizzes can be configured to connect with these third parties:', 'forminator' ); ?>
	</p>
	<ul class="privacy-policy-tutorial">
		<li><?php esc_html_e( 'Google Drive. Enabled when you activated and set up Google Drive on Integrations settings.', 'forminator' ); ?></li>
		<li><?php esc_html_e( 'Trello. Enabled when you activated and set up Trello on Integrations settings.', 'forminator' ); ?></li>
		<li><?php esc_html_e( 'Slack. Enabled when you activated and set up Slack on Integrations settings.', 'forminator' ); ?></li>
	</ul>
	<p>
		<strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text: ', 'forminator' ); ?></strong>
	<p>
		<?php
		esc_html_e(
			'We use Google Drive and Google Sheets to manage our integration data. Their privacy policy can be found here : https://policies.google.com/privacy?hl=en.',
			'forminator'
		);
		?>
	</p>
	<p><?php esc_html_e( 'We use Trello to manage our integration data. Their privacy policy can be found here : https://trello.com/privacy.', 'forminator' ); ?></p>
	<p><?php esc_html_e( 'We use Slack to manage our integration data. Their privacy policy can be found here : https://slack.com/privacy-policy.', 'forminator' ); ?></p>
	</p>
</div>
