<?php

/**
 * @description: This is a page that shows the cart content,
 * without "leading to" checking out. That is, there is no "next step" functionality
 * or a way to submit the order.
 * NOTE: both the Account and the Checkout Page extend from this class as they
 * share some functionality.
 *
 * @authors: Silverstripe, Jeremy, Nicolaas
 *
 * @package: ecommerce
 * @sub-package: pages
 *
 **/

class CartPage extends Page{

	public static $db = array(
		'ProceedToCheckoutMessage' => 'Varchar(100)',
		'ContinueShoppingMessage' => 'Varchar(100)'
	);

	public static $has_one = array(
		'CheckoutPage' => 'CheckoutPage',
		'ContinuePage' => 'SiteTree'
	);

	public static $icon = 'ecommerce/images/icons/CartPage';

	function canCreate($member = null) {
		return !DataObject :: get_one("SiteTree", "\"ClassName\" = 'CartPage'");
	}
	
	/**
	 *@return Fieldset
	 **/
	function getCMSFields(){
		$fields = parent::getCMSFields();
		if($this->ClassName == "CartPage") {
			if($checkouts = DataObject::get('CheckoutPage')) {
				$fields->addFieldToTab('Root.Content.Links',new TextField('ProceedToCheckoutMessage','Proceed to checkout message (e.g. click here to go through to checkout)'));
				$fields->addFieldToTab('Root.Content.Links',new DropdownField('CheckoutPageID','Checkout Page',$checkouts->toDropdownMap()));
			}
			$fields->addFieldToTab('Root.Content.Links',new TreeDropdownField('ContinuePageID','Continue Page',"SiteTree"));
			$fields->addFieldToTab('Root.Content.Links',new TextField('ContinueShoppingMessage','Continue shopping link (click here to continue shopping'));
		}
		return $fields;
	}

	/**
	 *@return String (HTML Snippet)
	 **/
	function getEcommerceMenuTitle() {
		$count = 0;
		$order = ShoppingCart::current_order();
		if($order) {
			$count = $order->TotalItems();
			if($count) {
				$html = $this->renderWith("AjaxNumItemsInCart");
				$js = '
					var html = jQuery("a[href=\''.$this->Link().'\']").html()
					jQuery("a[href=\''.$this->Link().'\']").html(html + " '.$html.'");
				';
				Requirements::customscript($js, "NumItemsInCart".$this->ID);
			}
		}
		return parent::getMenuTitle();
	}

	/**
	 * Returns the Link to the CartPage on this site
	 * @return String (URLSegment)
	 */
	public static function find_link() {
		if($page = DataObject::get_one('CartPage', "\"ClassName\" = 'CartPage'")) {
			return $page->Link();
		}
		else {
			return CheckoutPage::find_link();
		}
	}

	/**
	 * Returns the "new order" link
	 * @return String (URLSegment)
	 */
	public static function new_order_link() {
		return self::find_link()."startneworder/";
	}

	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public static function get_order_link($orderID) {
		return self::find_link(). 'showorder/' . $orderID . '/';
	}

	/**
	 * Return a link to view the order on this page.
	 * @return String (URLSegment)
	 * @param int|string $orderID ID of the order
	 */
	public function getOrderLink($orderID) {
		return self::get_order_link($orderID);
	}

}

class CartPage_Controller extends Page_Controller{


	/**
	 * This DataObjectSet holds DataObjects with a Link and Title each....
	 *@var $actionLinks DataObjectSet 
	 **/
	protected $actionLinks = null;

	/**
	 * to ensure messages and actions links are only worked out once...
	 *@var $workedOutMessagesAndActions Boolean 
	 **/
	protected $workedOutMessagesAndActions = false;

	/**
	 * order currently being shown on this page
	 *@var $order DataObject
	 **/ 
	protected $currentOrder = null;

	/**
	 * Message shown (e.g. no current order, could not find order, order updated, etc...)
	 *
	 *@var $message String
	 **/ 
	protected $message = "";

	protected static $session_code = "CartPageMessage";
		static function set_session_code($s) {self::$session_code = $s;}
		static function get_session_code() {return self::$session_code;}

	public static function set_message($s) {Session::set(self::get_session_code(), $s);}

	public function init() {
		parent::init();
		//add requirements
		Requirements::themedCSS('Cart'); 
		Requirements::javascript('ecommerce/javascript/EcomCart.js');
		// find the current order if any
		$orderID = 0;
		//WE HAVE THIS FOR SUBMITTING FORMS!
		if(isset($_REQUEST['OrderID'])) {
			$orderID = intval($_REQUEST['OrderID']);
		}
		elseif(Director::urlParam('ID') && Director::urlParam('Action') == "showorder"){
			$orderID = intval(Director::urlParam('ID'));
		}
		if($orderID) {
			$this->currentOrder = Order::get_by_id_if_can_view($orderID);
		}
		else {
			//if there is no order
			$this->currentOrder = ShoppingCart::current_order();
		}
	}

	/**
	 * This returns a DataObjectSet, each dataobject has two vars: Title and Link
	 *@return DataObjectSet | Null 
	 **/
	function ActionLinks() {
		$this->workOutMessagesAndActions();
		if ($this->actionLinks && $this->actionLinks->count()) {
			return $this->actionLinks;
		}
		return null;
	}


	/**
	 *@return String
	 **/
	function Message() {
		$this->workOutMessagesAndActions();
		if(!$this->message) {
			if($sessionMessage = Session::get(self::get_session_code())) {
				$this->message = $sessionMessage;
				Session::set(self::get_session_code(), "");
				Session::clear(self::get_session_code());
			}
		}
		return $this->message;
	}

	/**
	 *
	 *@return DataObject | Null - Order
	 **/
	public function Order() {
		return $this->currentOrder;
	}

	/**
	 *
	 *@return Boolean
	 **/
	function CanEditOrder() {
		if($this->currentOrder) {
			return $this->currentOrder->canEdit();
		}
		return false;
	}

	/**
	 *@return array just so that template shows -  sets CurrentOrder variable
	 **/
	function showorder($request) {
		if(!$this->currentOrder) {
			$this->message = _t('CartPage.ORDERNOTFOUND', 'Order can not be found.');
		}
		return array();
	}

	/**
	 * Loads either the current order from the shopping cart or
	 * by the specified Order ID in the URL.
	 *
	 * TO DO: untested
	 * TO DO: what to do with old order
	 *
	 */
	function loadorder($request) {
		if ($orderID = intval($request->param('ID'))) {
			self::set_message(_t("CartPage.ORDERLOADED", "Order has been loaded."));
			$this->currentOrder = ShoppingCart::singleton()->loadOrder($orderID);
			Director :: redirect($this->Link());
			exit();
		}
		return array();
	}

	/**
	 * Start a new order
	 *
	 * TO DO: untested
	 */
	function startneworder($request) {
		ShoppingCart::singleton()->clear();
		self::set_message(_t("CartPage.NEWORDERSTARTED", "New order has been started."));
		Director::redirect($this->Link());
		return array();
	}

	protected function workOutMessagesAndActions(){
		if(!$this->workedOutMessagesAndActions) {
			$this->actionLinks = new DataObjectSet();
			if($this->ProceedToCheckoutMessage && $this->CheckoutPageID) {
				$this->actionLinks->push(new ArrayData(array (
					"Title" => $this->ProceedToCheckoutMessage,
					"Link" => $this->CheckoutPage()->Link()
				)));
			}
			if($this->ContinueShoppingMessage && $this->ContinuePageID) {
				$this->actionLinks->push(new ArrayData(array (
					"Title" => $this->ContinueShoppingMessage,
					"Link" => $this->ContinuePage()->Link()
				)));
			}
			$this->workedOutMessagesAndActions = true;
			//does nothing at present....
		}
	}

}



