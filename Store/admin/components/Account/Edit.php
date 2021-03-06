<?php

require_once 'Site/admin/components/Account/Edit.php';

/**
 * Edit page for Accounts
 *
 * @package   Store
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAccountEdit extends SiteAccountEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Store/admin/components/Account/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->mapClassPrefixToPath('Store', 'Store');

		parent::initInternal();
	}

	// }}}

	// process phase
	// {{{ protected function updateAccount()

	protected function updateAccount()
	{
		parent::updateAccount();

		$this->account->phone = $this->ui->getWidget('phone')->value;
		$this->account->company = $this->ui->getWidget('company')->value;
	}

	// }}}
}

?>
