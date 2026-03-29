<?php
/**
 * Currency helper functions.
 *
 * @package Forminator
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Currency list
 *
 * @return mixed
 */
function forminator_pp_currency_list() {
	return apply_filters(
		'forminator_pp_currency_list',
		array(
			'AUD' => array( 'Australia, Dollars', '24' ),
			'BRL' => array( 'Brazil, Reais', '52, 24' ),
			'CAD' => array( 'Canada, Dollars', '24' ),
			'CZK' => array( 'Czech Republic, Koruny', '4b, 10d' ),
			'DKK' => array( 'Denmark, Kroner', '6b, 72' ),
			'EUR' => array( 'Euro', '20ac' ),
			'HKD' => array( 'Hong Kong, Dollars', '24' ),
			'HUF' => array( 'Hungary, Forint', '46, 74' ),
			'CNY' => array( 'China, Yuan Renminbi', '5143' ),
			'ILS' => array( 'Israel, New Shekels', '20aa' ),
			'JPY' => array( 'Japan, Yen', 'a5' ),
			'MYR' => array( 'Malaysia, Ringgits', '52, 4d' ),
			'MXN' => array( 'Mexico, Pesos', '24' ),
			'TWD' => array( 'Taiwan, New Dollars', '4e, 54, 24' ),
			'NZD' => array( 'New Zealand, Dollars', '24' ),
			'NOK' => array( 'Norway, Krone', '6b, 72' ),
			'PHP' => array( 'Philippines, Pesos', '50, 68, 70' ),
			'PLN' => array( 'Poland, Zlotych', '7a, 142' ),
			'GBP' => array( 'United Kingdom, Pounds', 'a3' ),
			'RUB' => array( 'Russia, Rubles', '440, 443, 431' ),
			'SGD' => array( 'Singapore, Dollars', '24' ),
			'SEK' => array( 'Sweden, Kronor', '6b, 72' ),
			'CHF' => array( 'Switzerland, Francs', '43, 48, 46' ),
			'THB' => array( 'Thailand, Baht', 'e3f' ),
			'USD' => array( 'United States of America, Dollars', '24' ),
		)
	);
}

/**
 * Return list of currencies for Stripe
 * updated: Oct 21, 2020
 * Removed currencies but might be supported back later (18):
 *  BHD, BYR, CUP, SVC, EEK, GHC, GGP, IRR, IMP, JEP, KWD, LVL, LTL, KPW, OMR, SYP, TVD, VEF
 * Added currencies (31):
 *  AMD, AOA, BDT, BIF, CDF, CVE, DJF, DZD, ETB, GMD, GNF, HTG, KMF, LSL, MDL, MGA, MMK, MOP,
 *  MRO, MVR, MWK, PGK, RWF, SLL, STD, SZL, TJS, TOP, TZS, UGX, VUV, WST, XPF
 *
 * @since  1.0
 * @since  1.15 Added new supported currencies and removed unsupported
 * @since  1.20 Added JOD
 *
 * @url    Stripe Currencies: https://stripe.com/docs/currencies
 * @url    Unicode hex table: https://gist.github.com/mroyce/a7e37ced540f45104de4cc03224937c7
 *
 * @return array
 */
function forminator_currency_list() {
	return apply_filters(
		'forminator_currency_list',
		array(
			'AFN' => array( 'Afghanistan, Afghanis', '60b' ),
			'ALL' => array( 'Albania, Leke', '4c, 65, 6b' ),
			'DZD' => array( 'Algeria, Dinars', '44, 41' ),
			'AOA' => array( 'Angola, Kwanzas', '4b, 7a' ),
			'ARS' => array( 'Argentina, Pesos', '24' ),
			'AMD' => array( 'Armenia, Drams', '58f' ),
			'AWG' => array( 'Aruba, Guilders (also called Florins)', '192' ),
			'AUD' => array( 'Australia, Dollars', '24' ),
			'AZN' => array( 'Azerbaijan, New Manats', '43c, 430, 43d' ),
			'BSD' => array( 'Bahamas, Dollars', '24' ),
			'BDT' => array( 'Bangladesh, Takas', '9f3' ),
			'BBD' => array( 'Barbados, Dollars', '24' ),
			'BZD' => array( 'Belize, Dollars', '42, 5a, 24' ),
			'BMD' => array( 'Bermuda, Dollars', '24' ),
			'BOB' => array( 'Bolivia, Bolivianos', '24, 62' ),
			'BAM' => array( 'Bosnia and Herzegovina, Convertible Marka', '4b, 4d' ),
			'BWP' => array( 'Botswana, Pulas', '50' ),
			'BGN' => array( 'Bulgaria, Leva', '43b, 432' ),
			'BIF' => array( 'Burundi, Francs', '46, 42, 75' ),
			'MMK' => array( 'Burma, Kyats', '4b' ),
			'BRL' => array( 'Brazil, Reais', '52, 24' ),
			'BND' => array( 'Brunei Darussalam, Dollars', '24' ),
			'KHR' => array( 'Cambodia, Riels', '17db' ),
			'CAD' => array( 'Canada, Dollars', '24' ),
			'CVE' => array( 'Cabo Verde, Escudos', '45, 73, 63' ),
			'KYD' => array( 'Cayman Islands, Dollars', '24' ),
			'CLP' => array( 'Chile, Pesos', '24' ),
			'CNY' => array( 'China, Yuan Renminbi', '5143' ),
			'COP' => array( 'Colombia, Pesos', '24' ),
			'KMF' => array( 'Comoros, Francs', '43, 46' ),
			'CRC' => array( 'Costa Rica, Colones', '20a1' ),
			'HRK' => array( 'Croatia, Kuna', '6b, 6e' ),
			'CZK' => array( 'Czech Republic, Koruny', '4b, 10d' ),
			'CDF' => array( 'Democratic Republic of Congo, Francs', '46, 43' ),
			'DKK' => array( 'Denmark, Kroner', '6b, 72' ),
			'DJF' => array( 'Djibouti, Francs', '46, 64, 6a' ),
			'DOP' => array( 'Dominican Republic, Pesos', '52, 44, 24' ),
			'XCD' => array( 'East Caribbean, Dollars', '24' ),
			'EGP' => array( 'Egypt, Pounds', '45, 47, 50' ),
			'ETB' => array( 'Ethiopia, Birrs', '42, 72' ),
			'EUR' => array( 'Euro', '20ac' ),
			'FKP' => array( 'Falkland Islands, Pounds', 'a3' ),
			'FJD' => array( 'Fiji, Dollars', '24' ),
			'GMD' => array( 'Gambia, Dalasis', '44' ),
			'GEL' => array( 'Georgia, Lari', '6c, 61, 72, 69' ),
			'GIP' => array( 'Gibraltar, Pounds', 'a3' ),
			'GTQ' => array( 'Guatemala, Quetzales', '51' ),
			'GNF' => array( 'Guinea, Francs', '46, 47' ),
			'GYD' => array( 'Guyana, Dollars', '24' ),
			'HTG' => array( 'Haiti, Gourdes', '47' ),
			'HNL' => array( 'Honduras, Lempiras', '4c' ),
			'HKD' => array( 'Hong Kong, Dollars', '24' ),
			'HUF' => array( 'Hungary, Forints', '46, 74' ),
			'ISK' => array( 'Iceland, Kronur', '6b, 72' ),
			'INR' => array( 'India, Rupees', '20a8' ),
			'IDR' => array( 'Indonesia, Rupiahs', '52, 70' ),
			'ILS' => array( 'Israel, New Shekels', '20aa' ),
			'JMD' => array( 'Jamaica, Dollars', '4a, 24' ),
			'JOD' => array( 'Jordan, Dinars', '4a, 44' ),
			'JPY' => array( 'Japan, Yen', 'a5' ),
			'KZT' => array( 'Kazakhstan, Tenge', '43b, 432' ),
			'KES' => array( 'Kenyan Shillings', '4B, 73, 68, 73' ),
			'KGS' => array( 'Kyrgyzstan, Soms', '43b, 432' ),
			'LAK' => array( 'Laos, Kips', '20ad' ),
			'LBP' => array( 'Lebanon, Pounds', 'a3' ),
			'LSL' => array( 'Lesotho, Maloti', '4d' ),
			'LRD' => array( 'Liberia, Dollars', '24' ),
			'MKD' => array( 'North Macedonia, Denars', '434, 435, 43d' ),
			'MOP' => array( 'Macau, Patacas', '4d, 4f, 50, 24' ),
			'MGA' => array( 'Madagascar, Ariary', '41, 72' ),
			'MWK' => array( 'Malawi, Kwachas', '4b' ),
			'MYR' => array( 'Malaysia, Ringgits', '52, 4d' ),
			'MVR' => array( 'Maldives, Rufiyaa', '52, 66' ),
			'MRO' => array( 'Mauritania, Ouguiya', '55, 4d' ),
			'MUR' => array( 'Mauritius, Rupees', '20a8' ),
			'MXN' => array( 'Mexico, Pesos', '24' ),
			'MDL' => array( 'Moldova, Lei', '4c' ),
			'MNT' => array( 'Mongolia, Tugriks', '20ae' ),
			'MAD' => array( 'Morocco, Dirhams', '64, 68' ),
			'MZN' => array( 'Mozambique, Meticais', '4d, 54' ),
			'NAD' => array( 'Namibia, Dollars', '24' ),
			'NPR' => array( 'Nepal, Rupees', '20a8' ),
			'ANG' => array( 'Netherlands Antilles, Guilders (also called Florins)', '192' ),
			'NZD' => array( 'New Zealand, Dollars', '24' ),
			'NIO' => array( 'Nicaragua, Cordobas', '43, 24' ),
			'NGN' => array( 'Nigeria, Nairas', '20a6' ),
			'NOK' => array( 'Norway, Kroner', '6b, 72' ),
			'PKR' => array( 'Pakistan, Rupees', '20a8' ),
			'PAB' => array( 'Panama, Balboas', '42, 2f, 2e' ),
			'PGK' => array( 'Papua New Guinea, Kina', '4b' ),
			'PYG' => array( 'Paraguay, Guaranies', '47, 73' ),
			'PEN' => array( 'Peru, Nuevos Soles', '53, 2f, 2e' ),
			'PHP' => array( 'Philippines, Pesos', '20b1' ),
			'PLN' => array( 'Poland, Zlotych', '7a, 142' ),
			'QAR' => array( 'Qatar, Rials', 'fdfc' ),
			'RON' => array( 'Romania, New Lei', '6c, 65, 69' ),
			'RUB' => array( 'Russia, Rubles', '440, 443, 431' ),
			'RWF' => array( 'Rwanda, Francs', '52, 46' ),
			'SHP' => array( 'Saint Helena, Pounds', 'a3' ),
			'WST' => array( 'Samoa, Tālā', '54' ),
			'STD' => array( 'São Tomé and Príncipe, Dobras', '44, 62' ), // STN since 2018 but not yet updated in payment gateways.
			'SAR' => array( 'Saudi Arabia, Riyals', 'fdfc' ),
			'RSD' => array( 'Serbia, Dinars', '414, 438, 43d, 2e' ),
			'SCR' => array( 'Seychelles, Rupees', '20a8' ),
			'SLL' => array( 'Sierra Leone, Leone', '4c, 65' ),
			'SGD' => array( 'Singapore, Dollars', '24' ),
			'SBD' => array( 'Solomon Islands, Dollars', '24' ),
			'SOS' => array( 'Somalia, Shillings', '53' ),
			'ZAR' => array( 'South Africa, Rand', '52' ),
			'KRW' => array( 'South Korea, Won', '20a9' ),
			'LKR' => array( 'Sri Lanka, Rupees', '20a8' ),
			'SZL' => array( 'Swaziland, Emalangeni', '45' ),
			'SEK' => array( 'Sweden, Kronor', '6b, 72' ),
			'CHF' => array( 'Switzerland, Francs', '43, 48, 46' ),
			'SRD' => array( 'Suriname, Dollars', '24' ),
			'TWD' => array( 'Taiwan, New Dollars', '4e, 54, 24' ),
			'TJS' => array( 'Tajikistan, Somoni', '53, 4d' ),
			'TZS' => array( 'Tanzania, Shillings', '54, 53, 68' ),
			'THB' => array( 'Thailand, Baht', 'e3f' ),
			'TOP' => array( 'Tonga, Paʻanga', '54, 24' ),
			'TTD' => array( 'Trinidad and Tobago, Dollars', '54, 54, 24' ),
			'TRY' => array( 'Turkey, Liras', '20BA' ),
			'UGX' => array( 'Uganda, Shillings', '55, 53, 68' ),
			'UAH' => array( 'Ukraine, Hryvnia', '20b4' ),
			'AED' => array( 'United Arab Emirates, Dirhams', '64, 68' ),
			'GBP' => array( 'United Kingdom, Pounds', 'a3' ),
			'USD' => array( 'United States of America, Dollars', '24' ),
			'UYU' => array( 'Uruguay, Pesos', '24, 55' ),
			'UZS' => array( 'Uzbekistan, Sums', '43b, 432' ),
			'VUV' => array( 'Vanuatu, Vatu', '56, 54' ),
			'VND' => array( 'Vietnam, Dong', '20ab' ),
			'XAF' => array( 'BEAC, CFA Francs', '46, 43, 46, 41' ),
			'XOF' => array( 'BCEAO, CFA Francs', '46, 43, 46, 41' ),
			'XPF' => array( 'Wallis and Futuna, CFP Francs', '46' ),
			'YER' => array( 'Yemen, Rials', 'fdfc' ),
			'ZMW' => array( 'Zimbabwe, Zambian Kwacha', '5a, 24' ),
		)
	);
}

/**
 * Get currency
 *
 * @since 1.0
 * @param string $currency_code - the currency code.
 *
 * @return string
 */
function forminator_get_currency( $currency_code ) {
	$currencies = forminator_currency_list();
	if ( ! empty( $currencies ) && isset( $currencies[ $currency_code ] ) ) {
		return $currencies[ $currency_code ];
	}
	return '';
}

/**
 * Get currency symbol
 *
 * @since 1.0
 * @return string
 */
function forminator_get_currency_symbol() {
	$currency         = get_option( 'forminator_currency', 'USD' );
	$current_currency = forminator_get_currency( $currency );

	if ( is_array( $current_currency ) ) {
		$symbols = array_map( 'trim', explode( ', ', $current_currency[1] ) );

		if ( is_array( $symbols ) ) {
			$symbol = '';
			foreach ( $symbols as $temp ) {
				$symbol .= '&#x' . $temp . ';';
			}
		} else {
			$symbol = '&#x' . $symbol . ';';
		}

		return $symbol;
	}

	return $currency;
}
