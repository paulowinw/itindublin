import React, { useState } from 'react';
import Tooltip from '../../components/tooltip/tooltip';
import { __, sprintf } from '@wordpress/i18n';
import { PreviousStepLink, DefaultStep } from '../../components/index';
import ICONS from '../../../icons';
import { useStateValue } from '../../store/store';
import { checkRequiredPlugins } from '../../steps/import-site/import-utils';
import SurveyForm from './survey';
import AdvancedSettings from './advanced-settings';
import './style.scss';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

const { phpVersion, analytics, firstImportStatus } = starterTemplates;

const Survey = () => {
	const storedState = useStateValue();
	const [
		{
			currentIndex,
			builder,
			requiredPlugins,
			notInstalledList,
			notActivatedList,
			analyticsFlag,
			shownRequirementOnce,
			pluginInstallationAttempts,
			fileSystemPermissions,
			formDetails,
			allowResetSite,
		},
		dispatch,
	] = storedState;

	const notInstalled = requiredPlugins?.required_plugins?.notinstalled;
	const notActivated = requiredPlugins?.required_plugins?.inactive;
	const allPuginList = [];
	if ( notInstalled?.length > 0 ) {
		notInstalled.map( ( plugin ) => {
			return allPuginList.push( {
				plugin,
				action: __( 'Install & Activate', 'astra-sites' ),
			} );
		} );
	}

	if ( notActivated?.length > 0 ) {
		notActivated.map( ( plugin ) => {
			return allPuginList.push( {
				plugin,
				action: __( 'Activate', 'astra-sites' ),
			} );
		} );
	}
	const terms = (
		<a
			className="st-link"
			href="https://store.brainstormforce.com/terms-and-conditions/"
			target="_blank"
			rel="noreferrer"
		>
			Terms
		</a>
	);

	const privacyPolicy = (
		<a
			className="st-link"
			href="https://store.brainstormforce.com/privacy-policy/"
			target="_blank"
			rel="noreferrer"
		>
			Privacy Policy
		</a>
	);
	const manualPluginInstallation = () => {
		return (
			<form className="install-plugins-form" onSubmit={ recheckPlugins }>
				<h1>{ __( 'Required plugins missing', 'astra-sites' ) }</h1>
				<p>
					{ __(
						'Some required plugins could not be able to be installed/activated due to some limitations coming from this website’s hosting service provider.',
						'astra-sites'
					) }
				</p>
				<p>
					{ __(
						'We request you to please install/update the following plugins to proceed.',
						'astra-sites'
					) }
				</p>
				<h5>{ __( 'Required plugins -', 'astra-sites' ) }</h5>
				<ul className="manual-required-plugins-list">
					{ allPuginList.map( ( value, index ) => {
						return (
							<li key={ index }>
								{ value.plugin.name }
								&nbsp;-&nbsp;
								<a
									href={ value.plugin?.action }
									target="_blank"
									rel="noreferrer"
								>
									{ value.action }
								</a>
							</li>
						);
					} ) }
				</ul>
				<button
					type="submit"
					className="submit-survey-btn button-text d-flex-center-align"
				>
					{ __( 'Start Importing', 'astra-sites' ) }
					{ ICONS.arrowRight }
				</button>
			</form>
		);
	};

	const thirtPartyPlugins =
		requiredPlugins !== null
			? requiredPlugins.third_party_required_plugins
			: [];
	const isThirtPartyPlugins = thirtPartyPlugins.length > 0;

	const [ skipPlugins, setSkipPlugins ] = useState( isThirtPartyPlugins );

	const compatibilities = astraSitesVars?.compatibilities;
	const requirementsErrors = compatibilities.errors;
	let requirementWarning = compatibilities.warnings;

	if (
		requiredPlugins &&
		requiredPlugins.update_avilable_plugins.length > 0
	) {
		const updatePluginsList = [];
		requiredPlugins.update_avilable_plugins.map( ( plugin ) => {
			return updatePluginsList.push( plugin.name );
		} );

		const output = [ '<ul>' ];
		updatePluginsList.forEach( function ( item ) {
			output.push( '<li>' + item + '</li>' );
		} );
		output.push( '</ul>' );

		const tooltipString =
			astraSitesVars?.compatibilities_data[ 'update-available' ];
		tooltipString.tooltip = tooltipString.tooltip.replace(
			'##LIST##',
			output.join( '' )
		);

		requirementWarning = {
			...requirementWarning,
			'update-available': tooltipString,
		};
	}

	let requirementsFlag;
	if ( shownRequirementOnce === true ) {
		requirementsFlag = false;
	} else {
		requirementsFlag =
			Object.keys( requirementsErrors ).length > 0 ||
			Object.keys( requirementWarning ).length > 0;
	}

	// Apply multisite-specific requirement flag logic
	if ( starterTemplates?.isMultisite ) {
		// For multisite: use the multisite-aware checking
		const shouldSkipRequirements = starterTemplates?.canActivatePlugins
			? notInstalled?.length <= 0 &&
			  starterTemplates?.themeStatus !== 'not-installed' // No Plugins/Themes are available to install
			: allPuginList?.length <= 0 &&
			  starterTemplates?.themeStatus === 'installed-and-active'; // No Plugins/Themes are available to install or activate

		if ( shouldSkipRequirements ) {
			requirementsFlag = false;
		}
	}

	const [ showRequirementCheck, setShowRequirementCheck ] =
		useState( requirementsFlag );

	const updateFormDetails = ( field, value ) => {
		dispatch( {
			type: 'set',
			formDetails: {
				...formDetails,
				[ field ]: value,
			},
		} );
	};

	const setStartFlag = () => {
		const content = new FormData();
		content.append( 'action', 'astra-sites-set_start_flag' );
		content.append( '_ajax_nonce', astraSitesVars?._ajax_nonce );
		content.append( 'template_type', 'classic' );

		fetch( ajaxurl, {
			method: 'post',
			body: content,
		} );
	};

	const hasAgreedFirstTime = allowResetSite || firstImportStatus;

	const handleSurveyFormSubmit = ( e, skipSubscription = false ) => {
		e.preventDefault();

		if ( hasAgreedFirstTime ) {
			setStartFlag();

			setTimeout( () => {
				dispatch( {
					type: 'set',
					currentIndex: currentIndex + 1,
				} );
			}, 500 );

			if ( analytics !== 'yes' ) {
				// Send data to analytics.
				const answer = analyticsFlag ? 'yes' : 'no';
				const optinAnswer = new FormData();
				optinAnswer.append( 'action', 'astra-sites-update-analytics' );
				optinAnswer.append(
					'_ajax_nonce',
					astraSitesVars?._ajax_nonce
				);
				optinAnswer.append( 'data', answer );

				fetch( ajaxurl, {
					method: 'post',
					body: optinAnswer,
				} )
					.then( ( response ) => response.json() )
					.then( ( response ) => {
						if ( response.success ) {
							starterTemplates.analytics = answer;
						}
					} );
			}

			// Skip subscription if chose to skip and start building.
			if ( skipSubscription ) {
				return;
			}

			if ( astraSitesVars?.subscribed === 'yes' ) {
				dispatch( {
					type: 'set',
					user_subscribed: true,
				} );
				return;
			}

			if ( ! formDetails.opt_in && ! formDetails.email ) {
				return;
			}

			const templatePlugins = requiredPlugins.required_plugins;

			const pluginLists = [
				templatePlugins?.active,
				templatePlugins?.inactive,
				templatePlugins?.notinstalled,
				notInstalledList,
				notActivatedList,
			];

			const uniqueFeatures = Array.from(
				new Set(
					pluginLists
						.flatMap( ( list ) =>
							Array.isArray( list ) ? list : []
						)
						.map( ( plugin ) => plugin?.slug )
						.filter( Boolean )
				)
			);

			const subscriptionFields = {
				EMAIL: formDetails.email,
				FIRSTNAME: formDetails.first_name,
				PAGE_BUILDER: builder,
				OPT_IN: formDetails.opt_in,
				FEATURES: uniqueFeatures,
			};

			const content = new FormData();
			content.append( 'action', 'astra-sites-update-subscription' );
			content.append( '_ajax_nonce', astraSitesVars?._ajax_nonce );
			content.append( 'data', JSON.stringify( subscriptionFields ) );

			fetch( ajaxurl, {
				method: 'post',
				body: content,
			} )
				.then( ( response ) => response.json() )
				.then( () => {
					dispatch( {
						type: 'set',
						user_subscribed: true,
					} );
				} );
		}
	};

	const handlePluginFormSubmit = ( e ) => {
		e.preventDefault();
		dispatch( {
			type: 'set',
			skippedPlugins: thirtPartyPlugins,
		} );
		setSkipPlugins( false );
	};

	const recheckPlugins = ( e ) => {
		e.preventDefault();
		checkRequiredPlugins( storedState );
	};

	const agrText = sprintf(
		// translators: %1$s is a terms link, %2$s is a privacy policy link
		__( 'By continuing, you agree to our %1$s and %2$s.', 'astra-sites' ),
		'_terms_',
		'_privacy_'
	);
	const [ beforeTerms, afterTerms ] = agrText.split( '_terms_' );
	const [ beforePrivacy, afterPrivacy ] = afterTerms.split( '_privacy_' );

	const surveyForm = () => {
		return (
			<form className="survey-form" onSubmit={ handleSurveyFormSubmit }>
				{ astraSitesVars?.subscribed !== 'yes' && (
					<SurveyForm
						formDetails={ formDetails }
						updateFormDetails={ updateFormDetails }
					/>
				) }
				<AdvancedSettings />
				<button
					type="submit"
					className="submit-survey-btn button-text d-flex-center-align"
					style={
						! hasAgreedFirstTime
							? {
									backgroundColor: '#E5E7EB',
									cursor: 'not-allowed',
									color: '#9CA3AF',
							  }
							: null
					}
				>
					{ __( 'Submit & Build My Website', 'astra-sites' ) }
					{ ! hasAgreedFirstTime
						? ICONS.arrowRightDisabled
						: ICONS.arrowRight }
				</button>

				<button
					className="p-2 bg-transparent border-none text-classic-button flex justify-self-center text-sm cursor-pointer"
					onClick={ ( e ) => handleSurveyFormSubmit( e, true ) }
				>
					{ __( 'Skip & Start Building', 'astra-sites' ) }
				</button>

				<p className="!text-zip-app-inactive-icon subscription-agreement-text text-center mt-4">
					{ beforeTerms }
					{ terms }
					{ beforePrivacy }
					{ privacyPolicy }
					{ afterPrivacy }
				</p>
			</form>
		);
	};

	const thirdPartyPluginList = () => {
		return (
			<form
				className="required-plugins-form"
				onSubmit={ handlePluginFormSubmit }
			>
				<h1>{ __( 'Required plugins missing', 'astra-sites' ) }</h1>
				<p>
					{ __(
						"This starter template requires premium plugins. As these are third party premium plugins, you'll need to purchase, install and activate them first.",
						'astra-sites'
					) }
				</p>
				<h5>{ __( 'Required plugins -', 'astra-sites' ) }</h5>
				<ul className="third-party-required-plugins-list">
					{ thirtPartyPlugins.map( ( plugin, index ) => {
						return (
							<li
								data-slug={ plugin.slug }
								data-init={ plugin.init }
								data-name={ plugin.name }
								key={ index }
							>
								<a
									href={ plugin.link }
									target="_blank"
									rel="noreferrer"
								>
									{ plugin.name }
								</a>
							</li>
						);
					} ) }
				</ul>
				<button
					type="submit"
					className="submit-survey-btn button-text d-flex-center-align"
				>
					{ __( 'Skip & Start Importing', 'astra-sites' ) }
					{ ICONS.arrowRight }
				</button>
			</form>
		);
	};

	const handleRequirementCheck = () => {
		setShowRequirementCheck( false );
		dispatch( {
			type: 'set',
			shownRequirementOnce: true,
		} );
	};

	const hardRequirement = () => {
		return (
			<div className="requirement-check-wrap">
				<h1 className="text-3xl font-bold text-zip-app-heading max-md:!text-3xl max-sm:!text-2xl">
					{ __( "We're Almost There!", 'astra-sites' ) }
				</h1>

				<p>
					{ __(
						'The Starter Template you are trying to import requires a few plugins to be installed and activated. Your current PHP version does not match the minimum requirement for these plugins.',
						'astra-sites'
					) }
				</p>

				<p className="current-php-version">
					<strong>{ `Current PHP version: ${ phpVersion }` }</strong>
				</p>

				<ul className="requirement-check-list">
					{ Object.values( requiredPlugins.incompatible_plugins ).map(
						( value, index ) => {
							return (
								<li key={ index }>
									<div className="requirement-list-item">
										{ `${ value.name } - PHP Version: ${ value.min_php_version } or higher` }
									</div>
								</li>
							);
						}
					) }
				</ul>
			</div>
		);
	};

	const optionalRequirement = () => {
		// Cache frequently accessed properties for better performance
		const isMultisite = starterTemplates?.isMultisite;
		const canActivatePlugins = starterTemplates?.canActivatePlugins;
		const canInstallPlugins = starterTemplates?.canInstallPlugins;
		const themeStatus = starterTemplates?.themeStatus;

		// Helper function to determine if theme is missing based on user permissions
		const checkThemeMissing = () => {
			return canActivatePlugins
				? themeStatus === 'not-installed'
				: themeStatus !== 'installed-and-active';
		};

		// Helper function to check if plugins are missing
		const checkPluginsMissing = () => {
			if ( canInstallPlugins ) {
				return false;
			}
			return canActivatePlugins
				? ( notInstalled?.length || 0 ) > 0
				: ( allPuginList?.length || 0 ) > 0;
		};

		// Determine multisite scenario with missing components
		let isMultisiteWithMissingPluginOrTheme = false;
		let isThemeMissing = false;

		if ( isMultisite ) {
			isThemeMissing = checkThemeMissing();
			const hasPluginsMissing = checkPluginsMissing();
			isMultisiteWithMissingPluginOrTheme =
				isThemeMissing || hasPluginsMissing;
		}

		// Determine the appropriate requirement list based on user permissions
		const requireList = canActivatePlugins ? notInstalled : allPuginList;

		// Render multisite-specific requirement screen
		if ( isMultisiteWithMissingPluginOrTheme ) {
			return (
				<div className="requirement-check-wrap">
					<h2 className="text-3xl font-bold text-zip-app-heading max-md:!text-3xl max-sm:!text-2xl pb-4">
						{ __(
							'Missing Required Plugins or Theme',
							'astra-sites'
						) }
					</h2>
					<p className="!mb-4">
						{ __(
							'In a multisite environment, you need to have all required plugins and themes already installed and activated by your network administrator to import this template.',
							'astra-sites'
						) }
					</p>
					{ isThemeMissing && (
						<>
							<h5>{ __( 'Missing Theme:', 'astra-sites' ) }</h5>
							<ul className="requirement-check-list !list-disc">
								<li>{ 'Astra' }</li>
							</ul>
						</>
					) }
					{ requireList?.length > 0 && (
						<>
							<h5>{ __( 'Missing Plugins:', 'astra-sites' ) }</h5>
							<ul className="requirement-check-list">
								{ requireList.map( ( value, index ) => (
									<li key={ index }>
										<div className="requirement-list-item">
											{ canActivatePlugins
												? value.name
												: value.plugin.name }
										</div>
									</li>
								) ) }
							</ul>
						</>
					) }
					<p>
						{ __(
							'Please contact your network administrator to install and activate these plugins.',
							'astra-sites'
						) }
					</p>
					{ starterTemplates?.canInstallPlugins && (
						<button
							className="submit-survey-btn button-text d-flex-center-align"
							onClick={ handleRequirementCheck }
						>
							<span className="leading-[15px]">
								{ __( 'Continue Anyway', 'astra-sites' ) }
							</span>
							{ ICONS.arrowRight }
						</button>
					) }
				</div>
			);
		}

		// Original requirement screen for non-multisite scenarios
		return (
			<div className="requirement-check-wrap">
				<h1 className="text-3xl font-bold text-zip-app-heading max-md:!text-3xl max-sm:!text-2xl !text-center">
					{ __( "We're Almost There!", 'astra-sites' ) }
				</h1>

				<p>
					{ __(
						"You're close to importing the template. To complete the process, please clear the following conditions.",
						'astra-sites'
					) }
				</p>

				<ul className="requirement-check-list">
					{ Object.keys( requirementsErrors ).length > 0 &&
						Object.values( requirementsErrors ).map(
							( value, index ) => {
								return (
									<li key={ index }>
										<div className="requirement-list-item">
											{ value.title }
											<Tooltip
												interactive={ true }
												content={
													<span
														dangerouslySetInnerHTML={ {
															__html: value.tooltip,
														} }
													/>
												}
											>
												{ ICONS.questionMark }
											</Tooltip>
										</div>
									</li>
								);
							}
						) }
					{ Object.keys( requirementWarning ).length > 0 &&
						Object.values( requirementWarning ).map(
							( value, index ) => {
								return (
									<li key={ index }>
										<div className="requirement-list-item">
											{ value.title }
											<Tooltip
												interactive={ true }
												content={
													<span
														dangerouslySetInnerHTML={ {
															__html: value.tooltip,
														} }
													/>
												}
											>
												{ ICONS.questionMark }
											</Tooltip>
										</div>
									</li>
								);
							}
						) }
				</ul>
				<button
					className="submit-survey-btn button-text d-flex-center-align"
					onClick={ handleRequirementCheck }
					disabled={
						Object.keys( requirementsErrors ).length > 0
							? true
							: false
					}
				>
					<span className="leading-[15px]">
						{ __( 'Skip & Continue', 'astra-sites' ) }
					</span>
					{ ICONS.arrowRight }
				</button>
			</div>
		);
	};

	const fileSystemPermissionRequirement = () => {
		const {
			is_readable: isReadable,
			is_writable: isWritable,
			is_wp_filesystem: isFilesystem,
		} = fileSystemPermissions.permissions;

		return (
			<div className="requirement-check-wrap">
				<h1>{ __( "We're Almost There!", 'astra-sites' ) }</h1>

				<p>
					{ __(
						'The import process was interrupted due to the lack of file-system permissions from your host. It is required to import images, XML files, and more required for the template to work.',
						'astra-sites'
					) }
				</p>

				<p className="current-file-system-permissions">
					<strong>
						{ __(
							'Current file-system permissions:',
							'astra-sites'
						) }
					</strong>
				</p>

				<ul className="requirement-check-list">
					<li>
						<div className="requirement-list-item">
							{ __( 'Read Permissions:', 'astra-sites' ) }
							<span
								className={ `dashicons ${
									isReadable
										? 'dashicons-yes'
										: 'dashicons-no'
								}` }
							/>
						</div>
					</li>
					<li>
						<div className="requirement-list-item">
							{ __( 'Write Permissions:', 'astra-sites' ) }
							<span
								className={ `dashicons ${
									isWritable
										? 'dashicons-yes'
										: 'dashicons-no'
								}` }
							/>
						</div>
					</li>
					<li>
						<div className="requirement-list-item">
							{ __(
								'WP_Filesystem Permissions:',
								'astra-sites'
							) }
							<span
								className={ `dashicons ${
									isFilesystem
										? 'dashicons-yes'
										: 'dashicons-no'
								}` }
							/>
						</div>
					</li>
				</ul>

				<p>
					{ __(
						'Please contact the hosting service provider to help you update the permissions so that you can successfully import a complete template.',
						'astra-sites'
					) }
				</p>
			</div>
		);
	};

	let defaultStepContent = surveyForm();

	if ( pluginInstallationAttempts > 2 && allPuginList.length > 0 ) {
		// If plugin installation fails more than 3 times.
		defaultStepContent = manualPluginInstallation();
	} else if (
		fileSystemPermissions !== null &&
		! Object.values( fileSystemPermissions.permissions ).every(
			( value ) => value === true
		)
	) {
		defaultStepContent = fileSystemPermissionRequirement();
	} else if (
		null !== requiredPlugins &&
		Object.keys( requiredPlugins.incompatible_plugins ).length > 0
	) {
		defaultStepContent = hardRequirement();
	} else if ( showRequirementCheck ) {
		defaultStepContent = optionalRequirement();
	} else if ( skipPlugins ) {
		defaultStepContent = thirdPartyPluginList();
	}

	return (
		<DefaultStep
			content={
				<>
					<div className="my-4">
						<h1 className="mb-4 text-3xl font-bold text-zip-app-heading max-md:!text-3xl max-sm:!text-2xl">
							{ __( 'Okay, just one last step…', 'astra-sites' ) }
						</h1>
					</div>
					<div className="survey-container">
						{ ' ' }
						{ defaultStepContent }{ ' ' }
					</div>
					<PreviousStepLink>
						<div className="flex text-center justify-center items-center gap-2">
							<ArrowLeftIcon height={ 12.5 } strokeWidth={ 2 } />
							{ __( 'Back', 'astra-sites' ) }
						</div>
					</PreviousStepLink>
				</>
			}
		/>
	);
};

export default Survey;
