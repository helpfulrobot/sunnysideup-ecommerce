<?php

/**
 * @description: each order has a shipping address.
 *
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: address
 *
 **/

class ShippingAddress extends OrderAddress {

	/**
	 * what variables are accessible through  http://mysite.com/api/v1/ShippingAddress/
	 * @var array
	 */
	public static $api_access = array(
		'view' => array(
			'ShippingPrefix',
			'ShippingFirstName',
			'ShippingSurname',
			'ShippingAddress',
			'ShippingAddress2',
			'ShippingCity',
			'ShippingPostalCode',
			'ShippingCountry',
			'ShippingPhone',
			'ShippingMobilePhone'
		)
	);

	public static $db = array(
		'ShippingPrefix' => 'Varchar(10)',
		'ShippingFirstName' => 'Varchar(100)',
		'ShippingSurname' => 'Varchar(100)',
		'ShippingAddress' => 'Varchar(200)',
		'ShippingAddress2' => 'Varchar(200)',
		'ShippingCity' => 'Varchar(100)',
		'ShippingPostalCode' => 'Varchar(30)',
		'ShippingCountry' => 'Varchar(4)',
		'ShippingPhone' => 'Varchar(100)',
		'ShippingMobilePhone' => 'Varchar(100)'
	);


	/**
	 * standard SS static definition
	 **/
	public static $has_one = array(
		"ShippingRegion" => "EcommerceRegion"
	);

	/**
	 * standard SS static definition
	 **/
	public static $belongs_to = array(
		"Order" => "Order"
	);

	/**
	 * standard SS static definition
	 */
	public static $default_sort = "\"ShippingAddress\".\"ID\" DESC";


	static $indexes = array(
		// "SearchFields" => "fulltext (Address, Address2, City, PostalCode, Phone)"
		array(
			'name' => 'SearchFields',
			'type' => 'fulltext',
			'value' => 'ShippingAddress, ShippingAddress2, ShippingCity, ShippingPostalCode, ShippingPhone'
		)
	);

	public static $casting = array(
		"ShippingFullCountryName" => "Varchar(200)"
	);

	public static $searchable_fields = array(
		"OrderID" => array(
			"field" => "NumericField",
			"title" => "Order Number"
		),
		"ShippingSurname" => "PartialMatchFilter",
		"ShippingAddress" => "PartialMatchFilter",
		"ShippingCity" => "PartialMatchFilter",
		"ShippingCountry" => "PartialMatchFilter"
	);

	public static $summary_fields = array(
		"Order.Title",
		"ShippingSurname",
		"ShippingCity"
	);

	public static $singular_name = "Shipping Address";
		function i18n_singular_name() { return _t("OrderAddress.SHIPPINGADDRESS", "Shipping Address");}

	public static $plural_name = "Shipping Addresses";
		function i18n_plural_name() { return _t("OrderAddress.SHIPPINGADDRESSES", "Shipping Addresses");}


	/**
	 *
	 *@return FieldSet
	 **/
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->replaceField("OrderID", new ReadonlyField("OrderID"));
		return $fields;
	}



	/**
	 * returns the full name for the shipping country code saved.
	 * @return String
	 **/
	function ShippingFullCountryName() {return $this->getShippingFullCountryName();}
	function getShippingFullCountryName() {
		return EcommerceCountry::find_title($this->ShippingCountry);
	}

	/**
	 * Puts together the fields for the Order Form (and other front-end purposes).
	 * @return Fieldset
	 **/
	public function getFields() {
		$fields = parent::getEcommerceFields();
		if(OrderAddress::get_use_separate_shipping_address()) {
			$shippingFields = new CompositeField(
				new HeaderField(_t('OrderAddress.SENDGOODSTODIFFERENTADDRESS','Send goods to different address'), 3),
				new LiteralField('ShippingNote', '<p class="message warning">'._t('OrderAddress.SHIPPINGNOTE','Your goods will be sent to the address below.').'</p>'),
				new LiteralField('Help', '<p>'._t('OrderAddress.SHIPPINGHELP','You can use this for gift giving. No billing information will be disclosed to this address.').'</p>'),
				new TextField('ShippingName', _t('OrderAddress.NAME','Name')),
				new TextField('ShippingAddress', _t('OrderAddress.ADDRESS','Address')),
				new TextField('ShippingAddress2', _t('OrderAddress.ADDRESS2','')),
				new TextField('ShippingCity', _t('OrderAddress.CITY','City')),
				$this->getPostalCodeField("ShippingPostalCode"),
				$this->getRegionField("ShippingRegionID"),
				$this->getCountryField("ShippingCountry")
			);
			$shippingFields->SetID('ShippingFields');
			$this->makeSelectedFieldsReadOnly($shippingFields);
			$fields->push($shippingFields);
		}
		$this->extend('augmentEcommerceShippingAddressFields', $fields);
		return $fields;
	}


	/**
	 * Return which shipping fields should be required on {@link OrderFormAddress}
	 *
	 * @return array
	 */
	function getRequiredFields() {
		$requiredFieldsArray = array(
			'ShippingAddress',
			'ShippingCity',
			'ShippingCountry'
		);
		$this->extend('augmentEcommerceShippingAddressRequiredFields', $requiredFieldsArray);
		return $requiredFieldsArray;
	}

	/**
	 * standard SS method
	 * sets the country to the best known country {@link EcommerceCountry}
	 **/
	function populateDefaults() {
		parent::populateDefaults();
		$this->ShippingCountry = EcommerceCountry::get_country();
	}



}
