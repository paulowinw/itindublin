import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
const { imageDir } = starterTemplates;

// Plugin icon path mapper - O(1) lookup performance.
const PLUGIN_ICON_MAP = {
	'astra-addon': 'astra.svg',
	cartflows: 'cartflows.svg',
	elementor: 'elementor.svg',
	'header-footer-elementor': 'uae.svg',
	latepoint: 'latepoint.svg',
	'presto-player': 'presto-player.svg',
	'spectra-pro': 'spectra.svg',
	surecart: 'surecart.svg',
	sureforms: 'sureforms.svg',
	suremails: 'suremails.svg',
	surerank: 'surerank.svg',
	suretriggers: 'ottokit.svg',
	'ultimate-addons-for-gutenberg': 'spectra.svg',
	'ultimate-elementor': 'uae.svg',
	'variation-swatches-woo': 'variation-swatches-woo.svg',
	woocommerce: 'woocommerce.svg',
	'woocommerce-payments': 'woopayments.png',
	'woo-cart-abandonment-recovery': 'cartflows-ca.png',
	wpforms: 'wpforms.png',
	'wpforms-lite': 'wpforms.png',
	'wp-live-chat-support': '3cx.png',
	'power-coupons': 'power-coupons.svg',
};

const RequiredPlugins = ( { pluginsList } ) => {
	const [ loadedImages, setLoadedImages ] = useState( {} );

	// Initialize loadedImages state for each plugin in pluginsList.
	useEffect( () => {
		pluginsList?.forEach( ( { slug } ) => {
			loadedImages[ slug ] = loadedImages?.[ slug ] ?? false;
		} );
	}, [ pluginsList ] );

	// Handle image load event to update local state.
	const handleImageLoad = ( slug ) => {
		// Update local state.
		setLoadedImages( ( prev ) => ( {
			...prev,
			[ slug ]: true,
		} ) );
	};

	return (
		pluginsList?.length && (
			<div className="pt-3 pb-2 !max-w-[55rem] w-full mx-auto flex flex-col gap-4 text-left bg-st-background-secondary border-[1px] border-solid border-button-disabled rounded">
				<div className="px-3 flex flex-col md:flex-row items-start md:items-center gap-2 justify-between">
					<p className="text-sm !font-medium">
						{ __(
							'The following plugins will be installed and activated for the selected features:',
							'astra-sites'
						) }
					</p>

					<p className="!text-xs opacity-90 self-end">
						<span className="text-alert-error">{ '* ' }</span>
						{ __(
							'Required plugins for the website',
							'astra-sites'
						) }
					</p>
				</div>

				<div className="px-3 flex flex-nowrap overflow-x-auto gap-2 pb-[2px] plugin-list">
					{ pluginsList?.map( ( { compulsory, name, slug } ) => (
						<div
							key={ slug }
							className="px-1.5 py-1 flex items-center gap-0.5 border-[0.5px] border-solid border-button-disabled rounded cursor-pointer"
						>
							{ PLUGIN_ICON_MAP?.[ slug ] && (
								<div className="relative w-4 h-4">
									{ /* Skeleton/Loading state */ }
									{ ! loadedImages?.[ slug ] && (
										<div className="absolute inset-0 bg-[#6B7280]/50 rounded animate-pulse" />
									) }

									{ /* Actual image */ }
									<img
										className={ `w-4 h-4 transition-opacity duration-200 ${
											loadedImages?.[ slug ]
												? 'opacity-100'
												: 'opacity-0'
										}` }
										src={ `${ imageDir }/grayscale/${ PLUGIN_ICON_MAP[ slug ] }` }
										alt={ name }
										onLoad={ () => handleImageLoad( slug ) }
									/>
								</div>
							) }

							<span className="px-1 text-sm font-medium text-[#1F2937] whitespace-nowrap">
								{ name }
								{ compulsory && (
									<span className="text-alert-error">
										{ ' *' }
									</span>
								) }
							</span>
						</div>
					) ) }
				</div>
			</div>
		)
	);
};

export default RequiredPlugins;
