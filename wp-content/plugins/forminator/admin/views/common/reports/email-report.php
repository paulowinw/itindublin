<?php
/**
 * Template admin/views/common/reports/email-report.php
 *
 * @package Forminator
 */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml" lang="en">

<head>
	<title><?php esc_html_e( 'Email template', 'forminator' ); ?></title>
	<!--[if !mso]><!-->
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!--<![endif]-->
	<meta property="og:title" content="Email template">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style type="text/css">
		* {
			font-family: 'Roboto', sans-serif;
		}

		body {
			margin: 0;
			padding: 0;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}

		table,
		td {
			border-collapse: collapse;
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
		}

		img {
			border: 0;
			height: auto;
			line-height: 100%;
			outline: none;
			text-decoration: none;
			-ms-interpolation-mode: bicubic;
		}

		p {
			margin: 0;
		}

		@media only screen and (max-width:480px) {
			body > table {
				width: 290px !important;
			}

			table thead th,
			table thead + tbody tr td {
				padding-left: 8px !important;
				padding-right: 2px !important;
			}

			table thead th:first-child {
				width: 50px !important;
			}

			table {
				padding: 0 !important;
			}
		}
	</style>
	<!--[if mso]>
	<xml>
		<o:OfficeDocumentSettings>
			<o:AllowPNG/>
			<o:PixelsPerInch>96</o:PixelsPerInch>
		</o:OfficeDocumentSettings>
	</xml>
	<![endif]-->
	<!--[if !mso]><!-->
	<?php /* phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet */ ?>
	<link href="https://fonts.bunny.net/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" type="text/css">

	<!--<![endif]-->
</head>

<body bgcolor="#f2f2f2" style="background: #f2f2f2; margin: 0;">
	<table role="presentation" cellpadding="0" cellspacing="0" style="max-width:600px;border-spacing:0;border-collapse:collapse;margin:0 auto;padding:0;width:100%;min-width:270px;height:100%" width="100%" height="100%">
		<tbody>
			<tr>
				<td valign="top" style="font-family:Roboto,sans-serif;border-collapse:collapse;word-break:break-word;">
					<div style="background:#ffffff;max-width:600px;margin-top:40px;margin-left:auto;margin-right:auto;border-radius:20px;overflow:hidden;">
						<!-- Header image -->
						<div style="padding-top:35px;padding-bottom:35px;background:#1F2852;text-align:center;">
							<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/mail-wpmudev-logo.png' ); ?>" alt="" style="max-width:195px;">
						</div>
						<!-- END Header image -->
						<div style="padding:30px 25px 40px;">
							<div style="margin-bottom: 30px;">
								<p style="font-size:25px;line-height:30px;font-weight:700;margin:0;">
									<?php
									printf(
									/* Translators: 1. Form label 2. Opening <a> tag with link to the site url. */
										esc_html__( 'Your Forminator %1$s summary for %2$s', 'forminator' ),
										esc_html( $args['label'] ),
										'<a href="' . esc_url( $args['site_url'] ) . '" style="color:#0059FF;text-decoration: none;">' . esc_html( $args['site_name'] ) . '</a>'
									);
									?>
								</p>
							</div>
							<div style="font-size:16px;line-height:24px;color:#1a1a1a;">
								<p style="margin-bottom: 45px;"
								>
								<?php
								printf(
								/* translators: %s: Recipient name */
									esc_html__( 'Hi %s,', 'forminator' ),
									esc_html( $args['recipient']['name'] )
								);
								?>
										</p>
								<p>
								<?php
								printf(
								/* Translators: 1. Schedule 2. Site url, 3. Report dashboard URL. */
									esc_html__( 'Here is the %1$s summary of how your form(s) are performing on %2$s. View the full reports %3$shere%4$s.', 'forminator' ),
									esc_html( $args['schedule'] ),
									'<a href="' . esc_url( $args['site_url'] ) . '" style="color:#0059FF;text-decoration: none;">' . esc_html( $args['site_name'] ) . '</a>',
									'<a href="' . esc_url( admin_url( 'admin.php?page=forminator-reports&section=dashboard' ) ) . '" target="_blank" style="color:#0059FF;text-decoration: none;">',
									'</a>'
								);
								?>
								</p>
							</div>
						</div>
						<table style="padding: 0 25px;border-spacing:0px;border-collapse:separate;margin-bottom:50px;width:100%;text-align:left;">
							<thead style="background:#F2F2F2;font-size: 12px;line-height: 14px;">
							<tr>
								<th style="padding: 8px 15px 8px 20px;"><?php echo esc_html( ucfirst( $args['module'] ) ); ?></th>
								<th style="padding: 8px 5px; width: 15%;"><?php esc_html_e( 'Views', 'forminator' ); ?></th>
								<th style="padding: 8px 5px;"><?php esc_html_e( 'Submissions / Conversion rate', 'forminator' ); ?></th>
								<?php if ( 'forms' === $args['module'] ) { ?>
									<?php if ( ! forminator_form_abandonment_disabled() ) { ?>
										<th style="padding: 8px 5px;"><?php esc_html_e( 'Abandonments / Drop off rate', 'forminator' ); ?></th>
									<?php } ?>
									<?php if ( ! forminator_payments_disabled() ) { ?>
										<th style="padding: 8px 20px 8px 5px; width: 15%;"><?php esc_html_e( 'Payments', 'forminator' ); ?></th>
									<?php } ?>
								<?php } ?>
							</tr>
							</thead>
							<tbody style="font-size:12px;line-height:22px;font-weight:500;color:#666666;">
							<?php
							if ( ! empty( $args['reports'] ) ) {
								foreach ( $args['reports'] as $report ) {
									?>
									<tr style="border-bottom: 1px solid #f2f2f2;">
										<td style="padding: 20px 15px 20px 20px;color: #1a1a1a;"><strong><?php echo esc_html( $report['title'] ); ?></strong></td>
										<td style="padding: 20px 5px;"><?php echo esc_html( $report['views'] ); ?></td>
										<td style="padding: 20px 5px;"><?php echo esc_html( $report['submission'] . ' / ' . $report['conversion'] ); ?></td>
										<?php if ( 'forms' === $args['module'] ) { ?>
											<?php if ( ! forminator_form_abandonment_disabled() ) { ?>
												<td style="padding: 20px 5px;"><?php echo esc_html( $report['abandoned'] . ' / ' . $report['dropoff'] ); ?></td>
											<?php } ?>
											<?php if ( ! forminator_payments_disabled() ) { ?>
												<td style="padding: 20px 20px 20px 5px;"><?php echo esc_html( $report['payments'] ); ?></td>
											<?php } ?>
										<?php } ?>
									</tr>
									<?php
								}
							}
							?>
							</tbody>
						</table>
						<div style="padding: 0 25px;">
							<p style="font-size:16px;line-height:24px;color:#1a1a1a;">
								<?php
								printf(
								/* Translators: 1. Opening <a> tag with link to the report dashboard url, 2. closing <a> tag. */
									esc_html__( 'You can change the frequency of receiving this email in Forminator\'s  %1$sReport - Notification%2$s section.', 'forminator' ),
									'<a href="' . esc_url( admin_url( 'admin.php?page=forminator-reports&section=notification' ) ) . '" target="_blank" style="color:#0059FF;text-decoration: none;">',
									'</a>'
								);
								?>
							</p>
							<p style="font-size:16px;line-height:28px;margin: 40px 0 30px;"><strong><?php esc_html_e( 'Forminator', 'forminator' ); ?></strong></p>
							<p style="font-size:16px;line-height:28px;"><?php esc_html_e( 'WPMU DEV Team', 'forminator' ); ?></p>
						</div>
						<!-- Footer Image -->
						<div style="background: #E7F1FB;padding-top: 25px;padding-bottom: 25px;margin-top: 40px;text-align: center;">
							<img src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/mail-wpmudev-logo-text.png' ); ?>" alt="" style="max-width:195px;">
						</div>
						<!-- END Footer image -->
					</div>
					<div style="margin: 0 auto 20px; text-align: center;">
						<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
								style="width:100%;">
							<tbody>
							<tr>
								<td style="direction:ltr;font-size:0px;padding:25px 20px 15px;text-align:center;">
									<!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:560px;" ><![endif]-->
									<div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
										<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
											<tbody>
											<tr>
												<td align="center" style="font-size:0px;padding:0;word-break:break-word;">
													<!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" ><tr><td><![endif]-->
													<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="float:none;display:inline-table;">
														<tr class="hidden-img">
															<td style="padding:1px;vertical-align:middle;">
																<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:transparent;border-radius:3px;width:0;">
																	<tr>
																		<td style="font-size:0;height:0;vertical-align:middle;width:0;">
																			<img height="0" style="border-radius:3px;display:block;" width="0" />
																		</td>
																	</tr>
																</table>
															</td>
															<td style="vertical-align:middle;">
																<span style="color:#333333;font-size:13px;font-weight:700;font-family:Roboto, Arial, sans-serif;line-height:25px;text-decoration:none;"><?php esc_html_e( 'Follow us', 'forminator' ); ?></span>
															</td>
														</tr>
													</table>
													<!--[if mso | IE]></td><td><![endif]-->
													<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="float:none;display:inline-table;">
														<tr>
															<td style="padding:1px;vertical-align:middle;">
																<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:transparent;border-radius:3px;width:25px;">
																	<tr>
																		<td style="font-size:0;height:25px;vertical-align:middle;width:25px;">
																			<a href="https://www.facebook.com/wpmudev" target="_blank">
																				<img height="25" src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/mail-button-logo-facebook.png' ); ?>" style="border-radius:3px;display:block;" width="25" />
																			</a>
																		</td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>
													<!--[if mso | IE]></td><td><![endif]-->
													<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="float:none;display:inline-table;">
														<tr>
															<td style="padding:1px;vertical-align:middle;">
																<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:transparent;border-radius:3px;width:25px;">
																	<tr>
																		<td style="font-size:0;height:25px;vertical-align:middle;width:25px;">
																			<a href="https://www.instagram.com/wpmu_dev/" target="_blank">
																				<img height="25" src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/mail-button-logo-instagram.png' ); ?>" style="border-radius:3px;display:block;" width="25" />
																			</a>
																		</td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>
													<!--[if mso | IE]></td><td><![endif]-->
													<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="float:none;display:inline-table;">
														<tr>
															<td style="padding:1px;vertical-align:middle;">
																<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:transparent;border-radius:3px;width:25px;">
																	<tr>
																		<td style="font-size:0;height:25px;vertical-align:middle;width:25px;">
																			<a href="https://twitter.com/wpmudev" target="_blank">
																				<img height="25" src="<?php echo esc_url( forminator_plugin_url() . 'assets/images/mail-button-logo-x.png' ); ?>" style="border-radius:3px;display:block;" width="25" />
																			</a>
																		</td>
																	</tr>
																</table>
															</td>
														</tr>
													</table>
													<!--[if mso | IE]></td></tr></table><![endif]-->
												</td>
											</tr>
											</tbody>
										</table>
									</div>
									<!--[if mso | IE]></td></tr></table><![endif]-->
								</td>
							</tr>
							</tbody>
						</table>
						<p style="font-size:10px;line-height:15px;margin: 0 0 15px;color:#505050;">
							<?php esc_html_e( 'INCSUB PO BOX 163, ALBERT PARK, VICTORIA.3206 AUSTRALIA', 'forminator' ); ?>
						</p>
					</div>
				</td>
			</tr>
		</tbody>
		</table>
	</body>
</html>
