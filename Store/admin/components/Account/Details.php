<?php

require_once 'Site/admin/components/Account/Details.php';
require_once 'Store/StoreAddressCellRenderer.php';
require_once 'Store/StorePaymentMethodCellRenderer.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/dataobjects/StoreAccount.php';
require_once 'Store/dataobjects/StoreInvoiceWrapper.php';

/**
 * Details page for accounts
 *
 * @package   Store
 * @copyright 2006-2011 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountDetails extends SiteAccountDetails
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Account/details.xml';

	// }}}
	// {{{ protected function initInternal()
	protected function initInternal()
	{
		parent::initInternal();

		// set a default order on the login history table view
		$view = $this->ui->getWidget('orders_view');
		$view->setDefaultOrderbyColumn(
			$view->getColumn('createdate'),
			SwatTableViewOrderableColumn::ORDER_BY_DIR_DESCENDING);

		// set a default order on the invoice table view
		$view = $this->ui->getWidget('invoices_view');
		$view->setDefaultOrderbyColumn(
			$view->getColumn('createdate'),
			SwatTableViewOrderableColumn::ORDER_BY_DIR_DESCENDING);
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($view->id) {
		case 'invoices_view':
			$this->processInvoiceActions($view, $actions);
			return;

		case 'addresses_view':
			$this->processAddressActions($view, $actions);
			return;

		case 'payment_methods_view':
			$this->processPaymentMethodActions($view, $actions);
			return;
		}
	}

	// }}}
	// {{{ private function processInvoiceActions()

	private function processInvoiceActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'invoice_delete':
			$this->app->replacePage('Invoice/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}
	// {{{ private function processAddressActions()

	private function processAddressActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'address_delete':
			$this->app->replacePage('Account/AddressDelete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}
	// {{{ private function processPaymentMethodActions()

	private function processPaymentMethodActions($view, $actions)
	{
		switch ($actions->selected->id) {
		case 'payment_method_delete':
			$this->app->replacePage('Account/PaymentMethodDelete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->ui->hasWidget('invoice_toolbar')) {
			$toolbar = $this->ui->getWidget('invoice_toolbar');
			$toolbar->setToolLinkValues($this->id);
		}

		$toolbar = $this->ui->getWidget('address_details_toolbar');
		$toolbar->setToolLinkValues($this->id);

		// set default time zone for orders & invoices
		$this->setTimeZone();
	}

	// }}}
	// {{{ protected function setTimeZone()

	protected function setTimeZone()
	{
		$date_column =
			$this->ui->getWidget('orders_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		if ($this->ui->hasWidget('invoice_toolbar')) {
			$date_column =
				$this->ui->getWidget('invoices_view')->getColumn('createdate');

			$date_renderer = $date_column->getRendererByPosition();
			$date_renderer->display_time_zone = $this->app->default_time_zone;
		}
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
		case 'invoices_view':
			return $this->getInvoicesTableModel($view);
		case 'orders_view':
			return $this->getOrdersTableModel($view);
		case 'addresses_view':
			return $this->getAddressesTableModel($view);
		case 'payment_methods_view':
			return $this->getPaymentMethodsTableModel($view);
		}
	}

	// }}}
	// {{{ protected function getInvoicesTableModel()

	protected function getInvoicesTableModel(SwatTableView $view)
	{
		$sql = 'select * from Invoice where account = %s order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view,
				'Invoice.createdate desc, Invoice.id'));

		$invoices =  SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('StoreInvoiceWrapper'));

		$store = new SwatTableStore();

		foreach ($invoices as $invoice) {
			$ds = new SwatDetailsStore($invoice);
			$ds->subtotal = $invoice->getSubtotal();
			$ds->is_pending = $invoice->isPending();
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getOrdersTableModel()

	protected function getOrdersTableModel(SwatTableView $view)
	{
		$sql = 'select Orders.id,
					Orders.account as account_id,
					Orders.total,
					Orders.createdate
				from Orders
				where Orders.account = %s
				order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'),
			$this->getOrderByClause($view,
				'Orders.createdate desc, Orders.id'));

		$store = SwatDB::query($this->app->db, $sql);

		return $store;
	}

	// }}}
	// {{{ protected function getAddressesTableModel()

	protected function getAddressesTableModel(SwatTableView $view)
	{
		$account = $this->getAccount();
		$billing_id = $account->getInternalValue('default_billing_address');
		$shipping_id = $account->getInternalValue('default_shipping_address');

		$ts = new SwatTableStore();

		foreach ($account->addresses as $address) {
			$ds = new SwatDetailsStore($address);
			$ds->address = $address;
			$ds->default_billing_address = ($address->id == $billing_id);
			$ds->default_shipping_address = ($address->id == $shipping_id);
			$ts->add($ds);
		}

		return $ts;
	}

	// }}}
	// {{{ protected function getPaymentMethodsTableModel()

	protected function getPaymentMethodsTableModel(SwatTableView $view)
	{
		$wrapper = SwatDBClassMap::get('StoreAccountPaymentMethodWrapper');

		$sql = sprintf('select * from AccountPaymentMethod
			where account = %s',
			$this->app->db->quote($this->id, 'integer'));

		$payment_methods = SwatDB::query($this->app->db, $sql, $wrapper);

		$store = new SwatTableStore();
		foreach ($payment_methods as $method) {
			$ds = new SwatDetailsStore($method);
			$ds->payment_method = $method;
			$store->add($ds);
		}

		return $store;
	}

	// }}}
}

?>
