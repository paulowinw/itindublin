import { __ } from '@wordpress/i18n';
import { ChevronDownIcon } from '@heroicons/react/24/outline';
import DropdownList from './dropdown-list';
import { classNames } from '../helpers';

export const SORT_OPTIONS = [
	{ id: 'most-used', label: __( 'Most Used', 'ai-builder' ) },
	{ id: 'newest', label: __( 'Newest', 'ai-builder' ) },
	{ id: 'premium', label: __( 'Premium', 'ai-builder' ) },
];

const SortByDropdown = ( { value, onChange } ) => {
	const selectedOption =
		SORT_OPTIONS.find( ( opt ) => opt.id === value ) ?? SORT_OPTIONS[ 0 ];

	return (
		<div className="flex items-center gap-2">
			<span className="text-sm text-zip-body-text whitespace-nowrap">
				{ __( 'Sort by', 'ai-builder' ) }
			</span>
			<DropdownList
				by="id"
				value={ selectedOption }
				onChange={ onChange }
			>
				<div className="relative">
					<DropdownList.Button className="flex items-center justify-between gap-1.5 w-[130px] h-9 px-3 border border-border-tertiary rounded-md bg-white shadow-sm text-sm font-medium text-zip-body-text cursor-pointer hover:bg-[#F4F7FB]">
						<span className="truncate">
							{ selectedOption.label }
						</span>
						<ChevronDownIcon className="w-4 h-4 text-zip-body-text shrink-0" />
					</DropdownList.Button>
					<DropdownList.Options className="py-1 px-1 w-[130px]">
						{ SORT_OPTIONS.map( ( option ) => (
							<DropdownList.Option
								key={ option.id }
								value={ option }
								className={ ( { selected } ) =>
									classNames(
										'py-2 px-3 rounded cursor-pointer',
										selected
											? 'bg-[#F4F7FB]'
											: 'hover:bg-[#F9FAFB]'
									)
								}
							>
								{ ( { selected } ) => (
									<span
										className={ classNames(
											'block text-sm',
											selected
												? 'font-semibold text-app-heading'
												: 'font-normal text-app-text'
										) }
									>
										{ option.label }
									</span>
								) }
							</DropdownList.Option>
						) ) }
					</DropdownList.Options>
				</div>
			</DropdownList>
		</div>
	);
};

export default SortByDropdown;
