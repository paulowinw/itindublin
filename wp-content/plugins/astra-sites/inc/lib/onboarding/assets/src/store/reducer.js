import { STEPS } from '../steps/util';
import { getURLParmsValue } from '../utils/url-params';
import { __ } from '@wordpress/i18n';

let currentIndexKey = 0;
let builderKey = 'gutenberg';

if ( astraSitesVars?.default_page_builder ) {
	currentIndexKey = 0;
	builderKey =
		astraSitesVars?.default_page_builder === 'brizy'
			? 'gutenberg'
			: astraSitesVars?.default_page_builder;

	// If AI builder is disabled but set as default, fallback to gutenberg
	if ( builderKey === 'ai-builder' && ! astraSitesVars?.showAiBuilder ) {
		builderKey = 'gutenberg';
	}
}

export const siteLogoDefault = {
	id: '',
	thumbnail: '',
	url: '',
	width: 120,
};

export const initialState = {
	siteFeatures: [
		// {
		// 	title: __( 'Blog', 'astra-sites' ),
		// 	id: 'blog',
		// 	description: __(
		// 		'Display a well-designed blog on your website',
		// 		'astra-sites'
		// 	),
		// 	enabled: false,
		// 	compulsory: false,
		// 	icon: 'blog',
		// },
		{
			title: __( 'Page Builder', 'astra-sites' ),
			id: 'page-builder',
			description: __(
				'Design pages with visual website builder',
				'astra-sites'
			),
			enabled: true,
			compulsory: true,
			icon: 'page-builder',
		},
		{
			title: __( 'Contact Form', 'astra-sites' ),
			id: 'contact-form',
			description: __(
				'Allow your visitors to get in touch with you',
				'astra-sites'
			),
			enabled: true,
			compulsory: true,
			icon: 'contact-form',
		},
		{
			title: __( 'eCommerce', 'astra-sites' ),
			id: 'ecommerce',
			description: __( 'Sell your products online', 'astra-sites' ),
			enabled: false,
			compulsory: false,
			icon: 'ecommerce',
			plugins: [ 'woocommerce', 'surecart' ],
		},
		{
			title: __( 'SEO & Search Visibility', 'astra-sites' ),
			id: 'seo',
			description: __(
				'Optimize your website for search engines',
				'astra-sites'
			),
			enabled: true,
			compulsory: false,
			icon: 'arrow-trending-up',
			plugins: [ 'surerank' ],
		},
		// Will be added back.
		// {
		// 	title: __( 'Automation & Integrations', 'astra-sites' ),
		// 	id: 'automation-integrations',
		// 	description: __( 'Automate your website & tasks', 'astra-sites' ),
		// 	enabled: false,
		// 	compulsory: false,
		// 	icon: 'squares-plus',
		// 	plugins: [ 'suretriggers' ],
		// },
		// Removing
		// {
		// 	title: __( 'Appointment & Bookings', 'astra-sites' ),
		// 	id: 'appointment-bookings',
		// 	description: __(
		// 		'Easily manage bookings for your services',
		// 		'astra-sites'
		// 	),
		// 	enabled: false,
		// 	compulsory: false,
		// 	icon: 'calendar',
		// 	plugins: [ 'latepoint' ],
		// },
		{
			title: __( 'Website Emails & SMTP', 'astra-sites' ),
			id: 'smtp',
			description: __(
				'Get emails from your website (forms, etc)',
				'astra-sites'
			),
			enabled: false,
			compulsory: false,
			icon: 'envelope',
			plugins: [ 'suremail' ],
		},
		{
			title: __( 'Free Live Chat', 'astra-sites' ),
			id: 'live-chat',
			description: __(
				'Connect with your website visitors for free',
				'astra-sites'
			),
			enabled: false,
			compulsory: false,
			icon: 'live-chat',
			plugins: [ 'wp-live-chat-support' ],
		},
	],
	formDetails: {
		first_name: '',
		email: astraSitesVars?.userDetails?.email || '',
		opt_in: true,
	},
	selectedEcommercePlugin: '',
	isEcommerce: false,
	allSitesData: astraSitesVars?.all_sites || {},
	allCategories: astraSitesVars?.allCategories || [],
	allCategoriesAndTags: astraSitesVars?.allCategoriesAndTags || [],
	aiActivePallette: null,
	aiActiveTypography: null,
	aiSiteLogo: siteLogoDefault,
	currentIndex: 'ai-builder' === builderKey ? 0 : currentIndexKey,
	currentCustomizeIndex: 0,
	siteLogo: siteLogoDefault,
	activePaletteSlug: 'default',
	activePalette: {},
	typography: {},
	typographyIndex: 0,
	stepsLength: Object.keys( STEPS ).length,

	builder: builderKey,
	siteType: '',
	siteOrder: 'popular',
	siteBusinessType: '',
	selectedMegaMenu: '',
	siteSearchTerm: getURLParmsValue( window.location.search, 's' ) || '',
	userSubscribed: false,
	showSidebar: window && window?.innerWidth < 1024 ? false : true,
	tryAgainCount: 0,
	pluginInstallationAttempts: 0,
	confettiDone: false,

	// Template Information.
	templateId: 0,
	templateResponse: null,
	requiredPlugins: null,
	fileSystemPermissions: null,
	selectedTemplateID: '',
	selectedTemplateName: '',
	selectedTemplateType: '',

	// Import statuses.
	reset: 'yes' === starterTemplates.firstImportStatus ? true : false,
	allowResetSite: true,
	themeStatus: false,
	importStatusLog: '',
	importStatus: '',
	xmlImportDone: false,
	requiredPluginsDone: false,
	notInstalledList: [],
	notActivatedList: [],
	skippedPlugins: [],
	awaitingPluginCheck: false,
	resetData: [],
	importStart: false,
	importEnd: false,
	importPercent: 0,
	importError: false,
	importErrorMessages: {
		primaryText: '',
		secondaryText: '',
		errorCode: '',
		errorText: '',
		solutionText: '',
		tryAgain: false,
	},
	importErrorResponse: [],
	importTimeTaken: {},

	customizerImportFlag:
		astraSitesVars?.default_page_builder === 'fse' ? false : true,
	themeActivateFlag:
		astraSitesVars?.default_page_builder === 'fse' ? false : true,
	widgetImportFlag: true,
	contentImportFlag: true,
	analyticsFlag: starterTemplates.analytics !== 'yes' ? true : false,
	shownRequirementOnce: false,

	// Filter Favorites.
	onMyFavorite: false,

	// All Sites and Favorites
	favoriteSiteIDs: Object.values( astraSitesVars?.favorite_data ) || [],

	// License.
	licenseStatus: astraSitesVars?.license_status,
	validateLicenseStatus: false,

	// Staging connected.
	stagingConnected:
		astraSitesVars?.staging_connected !== 'yes'
			? ''
			: '&draft=' + astraSitesVars?.staging_connected,

	// Search.
	searchTerms: [],
	searchTermsWithCount: [],
	enabledFeatureIds: [],
	dismissAINotice: astraSitesVars?.dismiss_ai_notice,

	// Sync Library.
	bgSyncInProgress: !! astraSitesVars?.bgSyncInProgress,
	sitesSyncing: false,
	syncPageCount: 0,
	syncPageInProgress: 0,

	// Limit exceed modal for AI-Builder.
	limitExceedModal: {
		open: false,
	},

	// Page builder API loading state and cache
	pageBuilderCache: {
		timestamp: null,
	},

	// Spectra Blocks Version
	spectraBlocksVersion: astraSitesVars?.spectraBlocks?.version || 'v2',
};

const reducer = ( state = initialState, { type, ...rest } ) => {
	switch ( type ) {
		case 'set':
			return { ...state, ...rest };

		// Plugin installed: move from notInstalledList to notActivatedList
		// Uses current state to avoid closure issues
		case 'plugin_installed':
			return {
				...state,
				notActivatedList: [ ...state.notActivatedList, rest.plugin ],
				notInstalledList: state.notInstalledList.filter(
					( p ) => p.slug !== rest.plugin.slug
				),
				importStatus: rest.importStatus || state.importStatus,
			};

		// Plugin activated: remove from notActivatedList
		// Uses current state to avoid closure issues
		case 'plugin_activated':
			return {
				...state,
				notActivatedList: state.notActivatedList.filter(
					( p ) => p.slug !== rest.plugin.slug
				),
				importStatus: rest.importStatus || state.importStatus,
				importPercent: rest.importPercent ?? state.importPercent,
			};

		// Plugin deferred: remove from notActivatedList (will be retried later)
		case 'plugin_deferred':
			return {
				...state,
				notActivatedList: state.notActivatedList.filter(
					( p ) => p.slug !== rest.plugin.slug
				),
				importStatus: rest.importStatus || state.importStatus,
				importPercent: rest.importPercent ?? state.importPercent,
			};

		// Deferred plugins re-queued for activation: merge into current notActivatedList.
		// Uses state.notActivatedList (not closure) to avoid overwriting activations
		// that completed between when retryDeferredPlugins() read the list and dispatch.
		case 'plugin_retry_deferred':
			return {
				...state,
				notActivatedList: [
					...state.notActivatedList,
					...rest.plugins,
				],
			};

		default:
			return state;
	}
};

export default reducer;
