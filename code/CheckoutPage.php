<?php

/**
 * CheckoutPage is a CMS page-type that shows the order
 * details to the customer for their current shopping
 * cart on the site. It also lets the customer review
 * the items in their cart, and manipulate them (add more,
 * deduct or remove items completely). The most important
 * thing is that the {@link CheckoutPage_Controller} handles
 * the {@link OrderForm} form instance, allowing the customer
 * to fill out their shipping details, confirming their order
 * and making a payment.
 *
 * @see CheckoutPage_Controller->Order()
 * @see OrderForm
 * @see CheckoutPage_Controller->OrderForm()
 *
 * The CheckoutPage_Controller is also responsible for setting
 * up the modifier forms for each of the OrderModifiers that are
 * enabled on the site (if applicable - some don't require a form
 * for user input). A usual implementation of a modifier form would
 * be something like allowing the customer to enter a discount code
 * so they can receive a discount on their order.
 *
 * @see OrderModifier
 * @see CheckoutPage_Controller->ModifierForms()
 *
 * TO DO: get rid of all the messages...
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: Pages
 * @inspiration: Silverstripe Ltd, Jeremy
 **/


class CheckoutPage extends CartPage {

	/**
	 * standard SS variable
	 * @Var Boolean
	 */
	private static $hide_ancestor = 'CartPage';

	/**
	 * standard SS variable
	 * @Var string
	 */
	private static $icon = 'ecommerce/images/icons/CheckoutPage';

	/**
	 * standard SS variable
	 * @Var Array
	 */
	private static $db = array (
		'TermsAndConditionsMessage' => 'Varchar(200)'
	);

	/**
	 * standard SS variable
	 * @Var Array
	 */
	private static $has_one = array (
		'TermsPage' => 'Page'
	);

	/**
	 * standard SS variable
	 * @Var Array
	 */
	private static $defaults = array (
		'TermsAndConditionsMessage' => 'You must agree with the terms and conditions before proceeding.'
	);

	/**
	 * standard SS variable
	 * @Var String
	 */
	private static $singular_name = "Checkout Page";
		function i18n_singular_name() { return _t("CheckoutPage.SINGULARNAME", "Checkout Page");}

	/**
	 * standard SS variable
	 * @Var String
	 */
	private static $plural_name = "Checkout Pages";
		function i18n_plural_name() { return _t("CheckoutPage.PLURALNAME", "Checkout Pages");}

	/**
	 * Standard SS variable.
	 * @var String
	 */
	private static $description = "A page where the customer can view the current order (cart) and finalise (submit) the order. Every e-commerce site needs an Order Confirmation Page.";

	/**
	 * Returns the Terms and Conditions Page (if there is one).
	 * @return Page | NULL
	 */
	public static function find_terms_and_conditions_page() {
		$checkoutPage = CheckoutPage::get()->First();
		if($checkoutPage && $checkoutPage->TermsPageID) {
			return Page::get()->byID($checkoutPage->TermsPageID);
		}
	}

	/**
	 * Returns the link or the Link to the Checkout page on this site
	 * @param String $action
	 * @return String (URLSegment)
	 */
	public static function find_link($action = "") {
		$page = CheckoutPage::get()->First();
		if ($page) {
			return $page->Link($action);
		}
		user_error("No Checkout Page has been created - it is recommended that you create this page type for correct functioning of E-commerce.", E_USER_NOTICE);
		return "";
	}

	/**
	 * Returns the link or the Link to the Checkout page on this site
	 * for the last step
	 * @param String $step
	 * @return String (URLSegment)
	 */
	public static function find_last_step_link($step = "") {
		if(!$step) {
			$steps = EcommerceConfig::get("CheckoutPage_Controller", "checkout_steps");
			if($steps && count($steps)) {
				$step = array_pop($steps);
			}
		}
		if($step) {
			$step = "checkoutstep/".strtolower($step)."/#".$step;
		}
		return self::find_link($step);
	}

	/**
	 * Returns the link to the next step
	 * @param String - $currentStep is the step that has just been actioned....
	 * @param Boolean - $doPreviousInstead - return previous rather than next step
	 * @return String (URLSegment)
	 */
	public static function find_next_step_link($currentStep, $doPreviousInstead = false) {
		$nextStep = null;
		if($link = self::find_link()){
			$steps = EcommerceConfig::get("CheckoutPage_Controller", "checkout_steps");
			if(in_array($currentStep, $steps)) {
				$key = array_search($currentStep, $steps);
				if($key!==FALSE) {
					if($doPreviousInstead) {
						$key--;
					}
					else {
						$key++;
					}
					if(isset($steps[$key])) {
						$nextStep = $steps[$key];
					}
				}
			}
			else {
				if($doPreviousInstead) {
					$nextStep = array_shift($steps);
				}
				else {
					$nextStep = array_pop($steps);
				}
			}
			if($nextStep) {
				return $link."checkoutstep"."/".$nextStep."/";
			}
			else {

			}
			return $link;
		}
		return "";
	}

	/**
	 * Returns the link to the checkout page on this site, using
	 * a specific Order ID that already exists in the database.
	 *
	 * @param int $orderID ID of the {@link Order}
	 * @return string Link to checkout page
	 */
	public static function get_checkout_order_link($orderID) {
		if($page = self::find_link()) {
			return $page->Link("showorder") . "/" . $orderID . "/";
		}
		return "";
	}

	/**
	 * Standard SS function, we only allow for one checkout page to exist
	 * but we do allow for extensions to exist at the same time.
	 * @param Member $member
	 * @return Boolean
	 **/
	function canCreate($member = null) {
		return CheckoutPage::get()->Filter(array("ClassName" => "CheckoutPage"))->Count() ? false : $this->canEdit($member);
	}

	function caView($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Shop Admins can edit
	 * @param Member $member
	 * @return Boolean
	 */
	function canEdit($member = null) {
		if(Permission::checkMember($member, Config::inst()->get("EcommerceRole", "admin_permission_code"))) {return true;}
		return parent::canEdit($member);
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canDelete($member = null) {
		return false;
	}

	/**
	 * Standard SS method
	 * @param Member $member
	 * @return Boolean
	 */
	public function canPublish($member = null) {
		return $this->canEdit($member);
	}

	/**
	 * Standard SS function
	 * @return FieldList
	 **/
	function getCMSFields() {
		$fields = parent :: getCMSFields();
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"ProceedToCheckoutLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"ContinueShoppingLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"ContinuePageID");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"LoadOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"CurrentOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"SaveOrderLinkLabel");
		$fields->removeFieldFromTab('Root.Messages.Messages.Actions',"DeleteOrderLinkLabel");
		$termsPageIDField = OptionalTreeDropdownField::create(
			'TermsPageID',
			_t("CheckoutPage.TERMSANDCONDITIONSPAGE", "Terms and conditions page"),
			'SiteTree'
		);
		$termsPageIDField->setRightTitle(_t("CheckoutPage.TERMSANDCONDITIONSPAGE_RIGHT", "This is optional. To remove this page clear the reminder message below."));
		$fields->addFieldToTab('Root.Terms', $termsPageIDField);
		$fields->addFieldToTab(
			'Root.Terms',
			$termsPageIDFieldMessage = new TextField(
				'TermsAndConditionsMessage',
				_t("CheckoutPage.TERMSANDCONDITIONSMESSAGE", "Reminder Message")
			)
		);
		$termsPageIDFieldMessage->setRightTitle(
			_t("CheckoutPage.TERMSANDCONDITIONSMESSAGE_RIGHT", "Shown if the user does not tick the 'I agree with the Terms and Conditions' box. Leave blank to allow customer to proceed without ticking this box")
		);
		//The Content field has a slightly different meaning for the Checkout Page.
		$fields->removeFieldFromTab('Root.Main', "Content");
		$fields->addFieldToTab('Root.Messages.Messages.AlwaysVisible', $htmlEditorField = new HTMLEditorField('Content', _t("CheckoutPage.CONTENT", 'General note - always visible on the checkout page')));
		$htmlEditorField->setRows(3);
		if(OrderModifier_Descriptor::get()->count()) {
			$fields->addFieldToTab('Root.Messages.Messages.OrderExtras',$this->getOrderModifierDescriptionField());
		}
		if(CheckoutPage_StepDescription::get()->count()) {
			$fields->addFieldToTab('Root.Messages.Messages.CheckoutSteps',$this->getCheckoutStepDescriptionField());
		}
		return $fields;
	}

	/**
	 *
	 * @return GridField
	 */
	protected function getOrderModifierDescriptionField(){
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldToolbarHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldEditButton(),
			new GridFieldDetailForm()
		);
		$title = _t("CheckoutPage.ORDERMODIFIERDESCRIPTMESSAGES", "Messages relating to order form extras (e.g. tax or shipping)");
		$source = OrderModifier_Descriptor::get();
		return new GridField("OrderModifier_Descriptor", $title, $source , $gridFieldConfig);
	}

	/**
	 *
	 * @return GridField
	 */
	protected function getCheckoutStepDescriptionField(){
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldToolbarHeader(),
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldEditButton(),
			new GridFieldDetailForm()
		);
		$title =  _t("CheckoutPage.CHECKOUTSTEPESCRIPTIONS", "Checkout Step Descriptions");
		$source = CheckoutPage_StepDescription::get();
		return new GridField("CheckoutPage_StepDescription", $title, $source , $gridFieldConfig);
	}

	public function requireDefaultRecords(){
		parent::requireDefaultRecords();
		$checkoutPage = CheckoutPage::get()->first();
		if(!$checkoutPage) {
			$checkoutPage = CheckoutPage::create();
			$checkoutPage->Title = "Checkout";
			$checkoutPage->MenuTitle = "Checkout";
			$checkoutPage->URLSegment = "checkout";
			$checkoutPage->writeToStage("Stage");
			$checkoutPage->publish("Stage", "Live");
		}
	}

}

class CheckoutPage_Controller extends CartPage_Controller {


	private static $allowed_actions = array(
		"checkoutstep",
		"OrderFormAddress",
		'saveorder',
		'CreateAccountForm',
		'retrieveorder',
		'loadorder',
		'startneworder',
		'showorder',
		'LoginForm',
		'OrderForm'
	);


	/**
	 * FOR STEP STUFF SEE BELOW
	 **/

	/**
	 * Standard SS function
	 * if set to false, user can edit order, if set to true, user can only review order
	 **/
	public function init() {
		parent::init();
		Requirements::themedCSS('CheckoutPage', 'ecommerce');
		$ajaxifyArray = EcommerceConfig::get("CheckoutPage_Controller", "ajaxify_steps");
		if(count($ajaxifyArray)) {
			foreach($ajaxifyArray as $js) {
				Requirements::javascript($js);
			}
		}
		Requirements::javascript('ecommerce/javascript/EcomPayment.js');
		Requirements::customScript('
			if (typeof EcomOrderForm != "undefined") {
				EcomOrderForm.set_TermsAndConditionsMessage(\''.convert::raw2js($this->TermsAndConditionsMessage).'\');
			}',
			"TermsAndConditionsMessage"
		);
		$this->steps = EcommerceConfig::get("CheckoutPage_Controller", "checkout_steps");
		$this->currentStep = $this->request->Param("ID");
		if($this->currentStep && in_array($this->currentStep, $this->steps)) {
			//do nothing
		}
		else {
			$this->currentStep = array_shift($this->steps);
		}
		//redirect to current order -
		// this is only applicable when people submit order (start to pay)
		// and then return back
		if($checkoutPageCurrentOrderID = Session::get("CheckoutPageCurrentOrderID")) {
			if((!$this->currentOrder) || ($this->currentOrder->ID != $checkoutPageCurrentOrderID)) {
				if($order = Order::get_by_id_if_can_view(intval($checkoutPageCurrentOrderID))) {
					Session::clear("CheckoutPageCurrentOrderID");
					Session::set("CheckoutPageCurrentOrderID", 0);
					Session::save();
					return $this->redirect($order->Link());
				}
			}
		}
		if($this->currentOrder) {
			//we make sure all the OrderModifiers are up to date....

			Session::set("CheckoutPageCurrentOrderID", $this->currentOrder->ID);
		}

	}


	/**
	 * Returns a ArrayList of {@link OrderModifierForm} objects. These
	 * forms are used in the OrderInformation HTML table for the user to fill
	 * in as needed for each modifier applied on the site.
	 *
	 * @return ArrayList (ModifierForms) | Null
	 */
	function ModifierForms() {
		if ($this->currentOrder) {
			return $this->currentOrder->getModifierForms();
		}
	}

	/**
	 * Returns a form allowing a user to enter their
	 * details to checkout their order.
	 *
	 * @return OrderForm object
	 */
	function OrderFormAddress() {
		$form = OrderFormAddress::create($this, 'OrderFormAddress');
		$this->data()->extend('updateOrderFormAddress', $form);
		//load session data
		if ($data = Session::get("FormInfo.{$form->FormName()}.data")) {
			$form->loadDataFrom($data);
		}
		return $form;
	}

	/**
	 * Returns a form allowing a user to enter their
	 * details to checkout their order.
	 *
	 * @return OrderForm object
	 */
	function OrderForm() {
		$form = OrderForm::create($this, 'OrderForm');
		$this->data()->extend('updateOrderForm', $form);
		//load session data
		if ($data = Session :: get("FormInfo.{$form->FormName()}.data")) {
			$form->loadDataFrom($data);
		}
		return $form;
	}

	/**
	 * Can the user proceed? It must be an editable order (see @link CartPage)
	 * and is must also contain items.
	 *
	 * @return boolean
	 */
	function CanCheckout() {
		return $this->currentOrder->getTotalItems() && !$this->currentOrder->IsSubmitted();
	}

	/**
	 * Catch for incompatable coding only....
	 */
	function ModifierForm($request) {
		user_error("Make sure that you set the controller for your ModifierForm to a controller directly associated with the Modifier", E_USER_WARNING);
		return array ();
	}

	/**
	 * STEP STUFF ---------------------------------------------------------------------------
	 *


	/**
	 *@var $currentStep String
	 **/
	protected $currentStep = "";

	/**
	 *@var Array
	 **/
	protected $steps = Array();

	/**
	 * returns a dataobject set of the steps.
	 * Or just one step if that is more relevant.
	 * @param Int $number - if set, it returns that one step.
	 * @return Null | DataObject (CheckoutPage_Description) | ArrayList (CheckoutPage_Description)
	 */
	function CheckoutSteps($number = 0) {
		$where = '';
		$dos = CheckoutPage_StepDescription::get()
			->Sort("ID", "ASC");
		if($number) {
			$dos = $dos->Filter(array("ID" => $number));
		}
		if($number) {
			if($dos->count()) {
				return $dos->First();
			}
		}
		$returnData = new ArrayList(array());
		$completed = 1;
		$completedClass = "completed";
		foreach($dos as $do) {
			if($this->currentStep && $do->Code() == $this->currentStep) {
				$do->LinkingMode = "current";
				$completed = 0;
				$completedClass = "notCompleted";
			}
			else {
				if($completed) {
					$do->Link = $this->Link("checkoutstep")."/".$do->Code."/";
				}
				$do->LinkingMode = "link $completedClass";
			}
			$do->Completed = $completed;
			$returnData->push($do);
		}
		if(EcommerceConfig::get("OrderConfirmationPage_Controller", "include_as_checkout_step")) {
			$orderConfirmationPage = OrderConfirmationPage::get()->First();
			if($orderConfirmationPage) {
				$do = $orderConfirmationPage->CurrentCheckoutStep(false);
				if($do) {
					$returnData->push($do);
				}
			}
		}
		return $returnData;
	}

	/**
	 * returns the heading for the Checkout Step
	 * @param Int $number
	 * @return String
	 */
	function StepsContentHeading($number) {
		$do = $this->CheckoutSteps($number);
		if($do) {
			return $do->Heading;
		}
		return "";
	}


	/**
	 * returns the top of the page content for the Checkout Step
	 * @param Int $number
	 * @return String
	 */
	function StepsContentAbove($number) {
		$do = $this->CheckoutSteps($number);
		if($do) {
			return $do->Above;
		}
		return "";
	}

	/**
	 * returns the bottom of the page content for the Checkout Step
	 * @param Int $number
	 * @return String
	 */
	function StepsContentBelow($number) {
		$do = $this->CheckoutSteps($number);
		if($do) {
			return $do->Below;
		}
		return "";
	}

	/**
	 * sets the current checkout step
	 * if it is ajax it returns the current controller
	 * as the inner for the page.
	 * @param SS_HTTPRequest $request
	 * @return Array
	 */
	function checkoutstep(SS_HTTPRequest $request) {
		if($this->request->isAjax()) {
			Requirements::clear();
			return $this->renderWith("LayoutCheckoutPageInner");
		}
		return array();
	}

	/**
	 * when you extend the CheckoutPage you can change this...
	 * @return Boolean
	 */
	function HasCheckoutSteps(){
		return true;
	}

	/**
	 * @param String $step
	 * @return Boolean
	 **/
	public function CanShowStep($step) {
		if ($this->ShowOnlyCurrentStep()) {
			return ($step == $this->currentStep);
		}
		else {
			return in_array($step, $this->steps);
		}
	}

	/**
	 * Is this the final step in the process
	 * @return Boolean
	 */
	public function ShowOnlyCurrentStep(){
		return $this->currentStep ? true : false;
	}

	/**
	 * Is this the final step in the process?
	 * @return Boolean
	 */
	public function IsFinalStep(){
		foreach($this->steps as $finalStep) {
			//do nothing...
		}
		return ($this->currentStep == $finalStep);
	}


	/**
	 * returns the percentage of steps done (0 - 100)
	 * @return Integer
	 */
	public function PercentageDone(){
		return round($this->currentStepNumber() / $this->numberOfSteps(), 2) * 100;
	}

	/**
	 * returns the number of the current step (e.g. step 1)
	 * @return Integer
	 */
	protected function currentStepNumber(){
		$key = 1;
		if($this->currentStep) {
			$key = array_search($this->currentStep, $this->steps);
			$key++;
		}
		return $key;
	}

	/**
	 * returns the total number of steps (e.g. 3)
	 * we add one for the confirmation page
	 * @return Integer
	 */
	protected function numberOfSteps(){
		return count($this->steps) + 1;
	}

	/**
	 * Here are some additional rules that can be applied to steps.
	 * If you extend the checkout page, you canm overrule these rules
	 *
	 */
	protected function applyStepRules(){
		//no items, back to beginning.
		//has step xxx been completed? if not go back one?
		//extend
		//reset current step if different
	}


}

