import {
	useState,
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useReducer,
} from '@wordpress/element';
import { twMerge } from 'tailwind-merge';
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import NavigationButtons from '../components/navigation-buttons';
import { siteLogoDefault } from '../store/reducer';
import { classNames, toastBody } from '../helpers';
import { STORE_KEY } from '../store';
import { ColumnItem } from '../components/column-item';
import Input from '../components/input';
import {
	ChevronUpIcon,
	MagnifyingGlassIcon,
	XMarkIcon,
} from '@heroicons/react/24/outline';
import { useForm } from 'react-hook-form';
import { useDebounce } from '../hooks/use-debounce';
import ColumnSkeleton from '../components/column-skeleton';
import {
	clearSessionStorage,
	getFromSessionStorage,
	setToSessionStorage,
} from '../utils/helpers';
import { useNavigateSteps } from '../router';
import Button from '../components/button';
import LoadingSpinner from '../components/loading-spinner';
import { __ } from '@wordpress/i18n';
import toast from 'react-hot-toast';
import Heading from '../components/heading';
import { SelectTemplatePageBuilderDropdown } from '../components/page-builder-dropdown';
import { debounce } from 'lodash';
import SortByDropdown from '../components/sort-by-dropdown';
export const USER_KEYWORD = 'st-template-search';
export const getRandomUniqueId = () =>
	Math.random().toString( 16 ).substring( 3 );

const DESIGN_LOAD_BATCH_COUNT = 4; // how many templates to load at once

// Determine default page builder
const { supportedPageBuilders = [] } = aiBuilderVars;
const defaultPageBuilder = ! [ 'block-editor', 'gutenberg' ].includes(
	supportedPageBuilders?.[ 0 ]
)
	? supportedPageBuilders?.[ 0 ] // Could be 'elementor'
	: 'spectra';

const SelectTemplate = () => {
	const { previousStep } = useNavigateSteps();

	const {
		setWebsiteTemplatesAIStep,
		setWebsiteSelectedTemplateAIStep,
		setWebsiteTemplateSearchResultsAIStep,
		setSelectedTemplateIsPremium,
		setSelectedPageBuilder,
		setWebsiteColorPalette,
		setWebsiteTypography,
		setWebsiteLogo,
		setSiteTitleVisible,
	} = useDispatch( STORE_KEY );

	const {
		stepsData: {
			businessName,
			businessType,
			templateSearchResults,
			templateList: allTemplates,
			templateKeywords: keywords = [],
		},
	} = useSelect( ( select ) => {
		const { getAIStepData, getAllPatternsCategories, getOnboardingAI } =
			select( STORE_KEY );

		const onboardingAI = getOnboardingAI();

		return {
			stepsData: getAIStepData(),
			allPatternsCategories: getAllPatternsCategories(),
			isNewUser: onboardingAI?.isNewUser,
		};
	}, [] );

	const {
		register,
		handleSubmit,
		formState: { errors },
		reset,
		setFocus,
		watch,
		getValues,
	} = useForm( {
		defaultValues: {
			keyword:
				getFromSessionStorage( USER_KEYWORD ) ??
				keywords?.join( ', ' ) ??
				'',
		},
	} );
	const watchedKeyword = watch( 'keyword' );
	const debouncedKeyword = useDebounce( watchedKeyword, 300 );

	const [ isFetching, setIsFetching ] = useState( false );
	const [ backToTop, setBackToTop ] = useState( false );
	const [ selectedBuilder, setSelectedBuilder ] =
		useState( defaultPageBuilder );

	const [ sortBy, setSortBy ] = useState( 'most-used' );
	const isDefaultSort = sortBy === 'most-used';
	const buildSortPayload = () =>
		isDefaultSort ? {} : { sort: { by: sortBy, order: 'desc' } };

	useEffect( () => {
		setSelectedPageBuilder(
			selectedBuilder === 'spectra' ? 'gutenberg' : selectedBuilder
		);
	}, [ selectedBuilder ] );

	const parentContainer = useRef( null );
	const templatesContainer = useRef( null );
	const abortRequest = useRef( [] );

	// Batch template loading state
	const [ designLoadState, setDesignLoadState ] = useState( {
		toLoad: [],
		loaded: [],
	} );

	const designToLoadList = designLoadState.toLoad;

	const [ loadMoreTemplates, setLoadMoreTemplates ] = useReducer(
		( state, updatedState ) => {
			return {
				...state,
				...updatedState,
			};
		},
		{
			page: 1,
			loading: false,
			showLoadMore: false,
		}
	);

	const TEMPLATE_TYPE = {
		RECOMMENDED: 'recommended',
		PARTIAL: 'partial',
		GENERIC: 'generic',
	};

	const refinedSearchResults = useMemo( () => {
		if ( ! templateSearchResults?.length ) {
			return [];
		}

		return templateSearchResults.reduceRight( ( acc, item, index ) => {
			if ( ! item.designs?.length ) {
				return acc;
			}
			const otherDesigns = acc
				.filter( ( designItem ) => item.match !== designItem.match )
				.flatMap( ( otherItem ) => otherItem.designs );

			const updatedDesigns = item.designs.filter(
				( designItem ) =>
					! otherDesigns.find(
						( otherDesign ) => otherDesign.uuid === designItem.uuid
					)
			);

			acc[ index ] = { ...item, designs: updatedDesigns };
			return acc;
		}, templateSearchResults );
	}, [ templateSearchResults ] );

	const getTemplates = useCallback(
		( type ) => {
			const { RECOMMENDED, GENERIC, PARTIAL } = TEMPLATE_TYPE;
			switch ( type ) {
				case RECOMMENDED:
					return refinedSearchResults?.[ 0 ]?.designs || [];
				case PARTIAL:
					return refinedSearchResults?.[ 1 ]?.designs || [];
				case GENERIC:
					return refinedSearchResults?.[ 2 ]?.designs || [];
			}
		},
		[ refinedSearchResults ]
	);

	const getInitialUserKeyword = () => {
		const type = businessType.toLowerCase();
		if ( type !== 'others' ) {
			return type;
		} else if ( keywords?.length > 0 ) {
			return keywords[ 0 ];
		}
		return businessName;
	};

	const handleHiddenTemplates = ( result ) => {
		// Hide ecommerce templates if ecommerce is disabled in the AI Builder settings.
		const hideEcommerceTemplates =
			aiBuilderVars?.hide_site_features?.includes( 'ecommerce' );
		// Hide premium templates if `show_premium_template` is false.
		const hidePremiumTemplates = ! aiBuilderVars?.show_premium_templates;

		if ( hidePremiumTemplates ) {
			result = result.map( ( item ) => {
				return {
					...item,
					designs: item?.designs?.filter(
						( template ) => ! template.is_premium
					),
				};
			} );
		}

		if ( hideEcommerceTemplates ) {
			result = result.map( ( item ) => {
				return {
					...item,
					designs: item?.designs?.filter(
						( template ) => template.features.ecommerce !== 'yes'
					),
				};
			} );
		}

		return result;
	};

	// Called when an iframe (template preview) finishes loading
	const onIframeLoaded = ( uniqueId ) => {
		setDesignLoadState( ( state ) => {
			const updatedToLoad = state.toLoad.filter(
				( id ) => id !== uniqueId
			);
			const updatedLoaded = [ ...state.loaded, uniqueId ];

			// Load next batch if current batch is fully loaded
			if ( updatedToLoad.length === 0 ) {
				const remainingToLoad = allTemplates
					.filter(
						( template ) =>
							! updatedLoaded.includes( template.uniqueId ) &&
							template.uniqueId !== uniqueId
					)
					.map( ( template ) => template.uniqueId )
					.slice( 0, DESIGN_LOAD_BATCH_COUNT );

				return {
					toLoad: remainingToLoad,
					loaded: updatedLoaded,
				};
			}

			return {
				toLoad: updatedToLoad,
				loaded: updatedLoaded,
			};
		} );
	};

	const fetchTemplates = async (
		keyword = getInitialUserKeyword(),
		isPageBuilderChanged = false,
		_selectedBuilder = selectedBuilder
	) => {
		if ( ! keyword ) {
			return;
		}

		try {
			setIsFetching( true );
			if ( abortRequest.current.length ) {
				abortRequest.current.forEach( ( controller ) => {
					controller.abort();
				} );
				abortRequest.current = [];
			}

			setWebsiteTemplatesAIStep( [] );

			const finalKeywords = [
				...new Set(
					keyword
						.split( ',' )
						.map( ( item ) => item.trim()?.toLowerCase() )
				),
			];

			let results = [];
			const allTemplatesList = [];

			// Create a single abort controller for the combined request
			const abortController = new AbortController();
			abortRequest.current.push( abortController );

			// Use search endpoint for default sort, all-templates for active sort
			let result = [];
			if ( isDefaultSort ) {
				const response = await apiFetch( {
					path: 'zipwp/v1/templates',
					method: 'POST',
					data: {
						keywords: finalKeywords,
						business_name: businessName,
						page_builder: _selectedBuilder,
					},
					signal: abortController.signal,
				} );
				result = response?.data?.data || [];
			} else {
				const response = await apiFetch( {
					path: 'zipwp/v1/all-templates',
					method: 'POST',
					data: {
						business_name: businessName,
						page_builder: _selectedBuilder,
						keyword,
						per_page: 20,
						page: 0,
						...buildSortPayload(),
					},
					signal: abortController.signal,
				} );
				result = response?.data?.data?.result || [];
			}

			// Filter out Hidden templates based on the settings.
			result = handleHiddenTemplates( result );

			results = result.map( ( item ) => {
				if ( Array.isArray( item.designs ) ) {
					item.designs = item.designs.map( ( design ) => ( {
						...design,
						uniqueId: getRandomUniqueId(),
					} ) );
					return item;
				}
				return {
					...item,
					designs: [],
				};
			} );

			// Get the designs in sequence
			result.forEach( ( item ) => {
				if ( Array.isArray( item.designs ) ) {
					allTemplatesList.push(
						...item.designs.filter(
							( template ) =>
								! allTemplatesList.find(
									( existingTemplate ) =>
										existingTemplate.uuid === template.uuid
								)
						)
					);
				}
			} );

			setWebsiteTemplatesAIStep( [ ...allTemplatesList ] );
			setWebsiteTemplateSearchResultsAIStep( [ ...results ] );
			setIsFetching( false );
			const isEmptyResults = allTemplatesList.length === 0;

			let showLoadMoreTemplates = true;

			if ( isEmptyResults ) {
				showLoadMoreTemplates = false;
			}
			setLoadMoreTemplates( {
				showLoadMore: showLoadMoreTemplates,
				page: 1,
			} );

			// Add templates to load list
			const templateIdList = allTemplatesList.map(
				( { uniqueId } ) => uniqueId
			);

			setDesignLoadState( ( state ) => {
				return {
					...state,
					loaded: isPageBuilderChanged ? [] : state.loaded,
					toLoad: templateIdList.slice( 0, DESIGN_LOAD_BATCH_COUNT ),
				};
			} );

			if ( isDefaultSort && allTemplatesList.length < 4 ) {
				fetchAllTemplatesByPage( 1, {
					searchResults: results,
					templateList: allTemplatesList,
					showLoadMoreTemplates,
				} );
			}
		} catch ( error ) {
			if ( error?.name === 'AbortError' ) {
				return;
			}
			setIsFetching( false );
		}
	};

	const fetchAllTemplatesByPage = async (
		page = 1,
		{
			searchResults = templateSearchResults,
			templateList = allTemplates,
			_showLoadMoreTemplates = loadMoreTemplates,
		} = {}
	) => {
		try {
			if ( loadMoreTemplates.loading || ! _showLoadMoreTemplates ) {
				return;
			}

			setLoadMoreTemplates( { loading: true } );

			const response = await apiFetch( {
				path: 'zipwp/v1/all-templates',
				method: 'POST',
				data: {
					business_name: businessName,
					page_builder: selectedBuilder,
					per_page: 9,
					page,
					...( ! isDefaultSort && {
						keyword: debouncedKeyword || getInitialUserKeyword(),
					} ),
					...buildSortPayload(),
				},
			} );

			if ( ! response.success ) {
				throw new Error(
					response?.data?.data ??
						__( 'Error while fetching templates', 'ai-builder' )
				);
			}

			let result = response?.data?.data?.result || [];
			const lastPage = response?.data?.data?.lastPage || 1;

			// Filter out Hidden templates based on the settings.

			result = handleHiddenTemplates( result );

			// Get the the designs in sequence
			const allTemplatesList = templateList ? [ ...templateList ] : [];
			const newSearchResults = searchResults ? [ ...searchResults ] : [];

			const allTemplatesListUUID = allTemplatesList.map(
				( template ) => template.uuid
			);

			// There might be duplicates with same uuid, so add a uniqueId for each
			result = result.map( ( item ) => {
				if ( Array.isArray( item.designs ) ) {
					item.designs = item.designs.map( ( design ) => {
						if ( allTemplatesListUUID.includes( design.uuid ) ) {
							return false;
						}

						// else return the design with a uniqueId
						return {
							...design,
							uniqueId: getRandomUniqueId(),
						};
					} );

					item.designs = item.designs.filter( ( design ) => design );
					return item;
				}

				return {
					...item,
					designs: [],
				};
			} );

			result.forEach( ( item ) => {
				if ( Array.isArray( item.designs ) ) {
					allTemplatesList.push( ...item.designs );
				}
				newSearchResults.map( ( searchResult, ind ) => {
					if ( searchResult.match === item.match ) {
						searchResult.designs = [
							...searchResult.designs,
							...item.designs.filter(
								( itm ) =>
									! searchResult.designs.some(
										( existingItem ) =>
											existingItem.uuid === itm.uuid
									)
							),
						];
					}
					newSearchResults[ ind ] = searchResult;
					return searchResult;
				} );
			} );

			setWebsiteTemplatesAIStep( allTemplatesList );
			setWebsiteTemplateSearchResultsAIStep( newSearchResults );

			const templateIdList = allTemplatesList.map(
				( { uniqueId } ) => uniqueId
			);
			// If toLoad list is empty, add new templates to load
			setDesignLoadState( ( state ) => {
				const newLoadList = templateIdList
					.filter(
						( uniqueId ) => ! state.loaded.includes( uniqueId )
					)
					.slice( 0, DESIGN_LOAD_BATCH_COUNT );

				const isToLoadListEmpty = state.toLoad.length === 0;
				const toLoad = isToLoadListEmpty ? newLoadList : state.toLoad;
				return {
					...state,
					toLoad,
				};
			} );

			const isEmptyResults = result.every(
				( item ) => item.designs.length === 0
			);

			if ( page === lastPage || isEmptyResults ) {
				setLoadMoreTemplates( { showLoadMore: false } );
			}
			setIsFetching( false );

			setLoadMoreTemplates( {
				page: loadMoreTemplates.page + 1,
			} );
		} catch ( error ) {
			setIsFetching( false );
			toast.error(
				toastBody( {
					message: error?.message?.toString(),
				} )
			);
		} finally {
			setLoadMoreTemplates( { loading: false } );
		}
	};

	useEffect( () => {
		setFocus( 'keyword' );

		// Save the manually entered keyword to session storage.
		return () => {
			const keyword = getValues( 'keyword' );
			if (
				! keyword ||
				keywords.some(
					( item ) => item?.toLowerCase() === keyword?.toLowerCase()
				)
			) {
				return clearSessionStorage( USER_KEYWORD );
			}
			setToSessionStorage( USER_KEYWORD, keyword );
		};
	}, [] );

	const resetState = () => {
		// clear the state before calling the API
		setLoadMoreTemplates( {
			page: 1,
			loading: false,
			showLoadMore: false,
		} );
		setDesignLoadState( {
			toLoad: [],
			loaded: [],
		} );
		setWebsiteTemplatesAIStep( [] );
		setWebsiteTemplateSearchResultsAIStep( [] );
		setSelectedTemplateIsPremium( '' );
		setWebsiteSelectedTemplateAIStep( '' );
		setBackToTop( false );
	};

	const fetchNewTemplates = ( isPageBuilderChanged = false ) => {
		resetState();
		fetchTemplates(
			debouncedKeyword ? debouncedKeyword : getInitialUserKeyword(),
			isPageBuilderChanged
		);
	};

	useEffect( () => {
		fetchNewTemplates( false );
	}, [ debouncedKeyword, sortBy ] );

	useEffect( () => {
		// if there's a uuid in the query params, find the template
		const urlParams = new URLSearchParams( window.location.search );
		const templateUuid = urlParams.get( 'uuid' );

		if ( templateUuid ) {
			const selectedTemplateItem = allTemplates?.find(
				( item ) => item?.uuid === templateUuid
			);
			if ( selectedTemplateItem ) {
				setWebsiteSelectedTemplateAIStep( selectedTemplateItem.uuid );
				setSelectedTemplateIsPremium( selectedTemplateItem.is_premium );
				setWebsiteLogo( siteLogoDefault );
				setWebsiteTypography( null );
				setWebsiteColorPalette( null );
				setSiteTitleVisible( true );
			}
		}
	}, [] );

	const handleSubmitKeyword = ( { keyword } ) => {
		onChangeKeyword( keyword );
	};

	const handleClearSearch = () => {
		if ( ! watchedKeyword ) {
			return;
		}
		reset( { keyword: '' } );
		onChangeKeyword( getInitialUserKeyword() );
	};

	const onChangeKeyword = ( value = '' ) => {
		resetState();
		fetchTemplates( value );
	};

	const renderTemplates = useMemo( () => {
		const recommendedTemplates = getTemplates( TEMPLATE_TYPE.RECOMMENDED );
		const partialTemplates = getTemplates( TEMPLATE_TYPE.PARTIAL );
		const genericTemplates = getTemplates( TEMPLATE_TYPE.GENERIC );

		if (
			! recommendedTemplates?.length &&
			! partialTemplates?.length &&
			! genericTemplates?.length
		) {
			return (
				<div className="col-span-full flex flex-col items-center justify-center py-16">
					<div className="text-base font-semibold text-app-heading">
						{ __( 'No templates found', 'ai-builder' ) }
					</div>
					<div className="text-sm mt-2 text-zip-body-text">
						{ __(
							'Try changing your search keyword or sort options.',
							'ai-builder'
						) }
					</div>
				</div>
			);
		}

		const recommendedTemplateIdSet = new Set(
			recommendedTemplates.map( ( { uuid } ) => uuid )
		);
		const partialTemplateIdSet = new Set(
			partialTemplates.map( ( { uuid } ) => uuid )
		);

		const filteredGenericTemplates = genericTemplates.filter(
			( { uuid } ) =>
				! recommendedTemplateIdSet.has( uuid ) &&
				! partialTemplateIdSet.has( uuid )
		);

		return (
			<>
				{ recommendedTemplates?.map( ( template, index ) => (
					<ColumnItem
						key={ template.uniqueId }
						template={ template }
						position={ index + 1 }
						onIframeLoaded={ onIframeLoaded } // callback to track load
						shouldLoad={ designToLoadList.includes(
							template.uniqueId
						) }
					/>
				) ) }
				{ partialTemplates?.map( ( template, index ) => (
					<ColumnItem
						key={ template.uniqueId }
						template={ template }
						position={
							index + 1 + ( recommendedTemplates?.length || 0 )
						}
						onIframeLoaded={ onIframeLoaded } // callback to track load
						shouldLoad={ designToLoadList.includes(
							template.uniqueId
						) }
					/>
				) ) }
				{ filteredGenericTemplates?.map( ( template, index ) => (
					<ColumnItem
						key={ template.uniqueId }
						template={ template }
						position={
							index +
							1 +
							( ( recommendedTemplates?.length || 0 ) +
								( partialTemplates?.length || 0 ) )
						}
						onIframeLoaded={ onIframeLoaded } // callback to track load
						shouldLoad={ designToLoadList.includes(
							template.uniqueId
						) }
					/>
				) ) }
			</>
		);
	}, [ getTemplates, designToLoadList ] );

	const handleClickBackToTop = () => {
		parentContainer.current.scrollTo( {
			top: 0,
			behavior: 'smooth',
		} );
	};

	const lastVisibleTemplateCheckTop = useRef( 0 );
	const checkForVisibleTemplates = ( target ) => {
		const { scrollTop, clientHeight } = target;

		if ( lastVisibleTemplateCheckTop.current === scrollTop ) {
			return;
		}
		lastVisibleTemplateCheckTop.current = scrollTop;

		const designs = target.querySelectorAll( '.design-template' );

		const TOP_SEARCHBAR_HEIGHT = 80;
		const BOTTOM_NAVBAR_HEIGHT = 80;

		const actualClientHeight = clientHeight - TOP_SEARCHBAR_HEIGHT;
		const actualScrollTop = scrollTop - TOP_SEARCHBAR_HEIGHT;
		const actualScrollBottom =
			actualScrollTop + actualClientHeight - BOTTOM_NAVBAR_HEIGHT;

		const designList = Array.from( designs ).map( ( design ) => ( {
			middlePoint: design.offsetTop + design.clientHeight / 2,
			uniqueId: design.getAttribute( 'data-template-unique-id' ),
		} ) );

		const visibleTemplateIdList = designList
			.filter( ( design ) => {
				return (
					design.middlePoint >= actualScrollTop &&
					design.middlePoint <= actualScrollBottom // Middle point is within the range
				);
			} )
			.map( ( { uniqueId } ) => uniqueId );

		setDesignLoadState( ( state ) => {
			const filterVisibleList = visibleTemplateIdList.filter(
				( uniqueId ) =>
					! (
						state.loaded.includes( uniqueId ) &&
						! state.toLoad.includes( uniqueId )
					)
			);

			return {
				...state,
				toLoad: [ ...state.toLoad, ...filterVisibleList ],
			};
		} );
	};

	// Wait X amount of time after user scrolls and then checkForVisibleTemplates
	const debouncedCheckForVisibleTemplates = debounce( ( target ) => {
		checkForVisibleTemplates( target );
	}, 2000 );

	const handleScroll = ( event ) => {
		const SCROLL_THRESHOLD = 100;
		const { scrollTop } = event.target;

		if ( scrollTop > SCROLL_THRESHOLD && ! backToTop ) {
			setBackToTop( true );
		}
		if ( scrollTop <= SCROLL_THRESHOLD && backToTop ) {
			setBackToTop( false );
		}

		debouncedCheckForVisibleTemplates( event.target );
	};

	return (
		<div
			ref={ parentContainer }
			className={ twMerge(
				`mx-auto flex flex-col overflow-x-hidden`,
				'w-full'
			) }
			onScroll={ handleScroll }
		>
			<Heading
				heading={ __( 'Choose the Design', 'ai-builder' ) }
				className="w-full px-5 md:px-10 lg:px-14 xl:px-15 pt-12 max-w-fit mx-auto text-[28px] font-semibold leading-9"
			/>
			<form
				className="w-full pt-4 pb-4  max-w-[37.5rem] mx-auto"
				onSubmit={ handleSubmit( handleSubmitKeyword ) }
			>
				<div
					className={ classNames(
						'flex w-full bg-white gap-2 items-center rounded-md shadow-sm border border-border-tertiary flex-col-reverse md:flex-row'
					) }
				>
					<SelectTemplatePageBuilderDropdown
						selectedBuilder={ selectedBuilder }
						onChange={ ( builder ) => {
							setSelectedBuilder( builder.id );
							fetchTemplates(
								watchedKeyword
									? watchedKeyword
									: getInitialUserKeyword(),
								true,
								builder.id
							);
						} }
					/>
					<span className="hidden md:block h-6 w-px bg-gray-900/10"></span>
					<Input
						name="keyword"
						inputClassName={ 'pr-11 pl-2 !text-base' }
						register={ register }
						placeholder={ __( 'Add a keyword', 'ai-builder' ) }
						height="12"
						className="w-full h-12"
						noBorder={ true }
						error={ errors?.keyword }
						suffixIcon={
							<div className="absolute right-4 flex items-center">
								<button
									type="button"
									className="w-auto h-auto p-0 flex items-center justify-center cursor-pointer bg-transparent border-0 focus:outline-none"
									onClick={ handleClearSearch }
								>
									{ watchedKeyword ? (
										<XMarkIcon className="w-5 h-5 text-zip-app-inactive-icon" />
									) : (
										<MagnifyingGlassIcon className="w-5 h-5 text-zip-app-inactive-icon" />
									) }
								</button>
							</div>
						}
					/>
				</div>
			</form>

			<div className="flex justify-end px-5 md:px-10 lg:px-14 xl:px-15 mt-2">
				<SortByDropdown
					value={ sortBy }
					onChange={ ( option ) => setSortBy( option.id ) }
				/>
			</div>

			<div
				ref={ templatesContainer }
				className={ classNames(
					'custom-confirmation-modal-scrollbar', // class for thin scrollbar
					'relative',
					'px-5 md:px-10 lg:px-14 xl:px-15',
					'xl:max-w-full'
				) }
			>
				<div
					ref={ templatesContainer }
					className={ classNames(
						'grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 auto-rows-auto items-start justify-center gap-4 sm:gap-6 mb-10 mt-5'
					) }
				>
					{ ! isFetching
						? renderTemplates
						: Array.from( { length: 6 } ).map( ( _, index ) => (
								<ColumnSkeleton key={ `skeleton-${ index }` } />
						  ) ) }
				</div>
			</div>

			{ loadMoreTemplates.showLoadMore && (
				<div className="align-center flex justify-center sm:pb-0 pb-40">
					<Button
						className="min-w-[188px] min-h-[50px]"
						variant="primary"
						onClick={ () => {
							if ( loadMoreTemplates.loading ) {
								return;
							}
							fetchAllTemplatesByPage( loadMoreTemplates.page );
						} }
						disabled={ loadMoreTemplates.loading }
					>
						{ loadMoreTemplates.loading ? (
							<LoadingSpinner />
						) : (
							__( 'Load More Designs', 'ai-builder' )
						) }
					</Button>
				</div>
			) }

			{ backToTop && (
				<div className="absolute right-20 bottom-28 ml-auto">
					<button
						type="button"
						className="absolute bottom-0 right-0 z-10 w-8 h-8 rounded-full bg-accent-st border-0 border-solid text-white flex items-center justify-center shadow-sm cursor-pointer"
						onClick={ handleClickBackToTop }
					>
						<ChevronUpIcon className="w-5 h-5" />
					</button>
				</div>
			) }

			<div className="fixed sm:sticky bottom-0 w-full bg-container-background py-4.75 px-5 md:px-10 lg:px-14 xl:px-15">
				<NavigationButtons
					onClickPrevious={ previousStep }
					hideContinue
				/>
			</div>
		</div>
	);
};

export default SelectTemplate;
