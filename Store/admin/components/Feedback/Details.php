<?php

/**
 * Details page for customer feedback
 *
 * @package   Store
 * @copyright 2009-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackDetails extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var StoreFeedback
	 */
	protected $feedback;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Store', 'Store');
		$this->ui->loadFromXML($this->getUiXml());

		$this->id = SiteApplication::initVar('id');
	}

	// }}}
	// {{{ protected function getFeedback()

	protected function getFeedback()
	{
		if ($this->feedback === null) {
			$feedback_class = SwatDBClassMap::get('StoreFeedback');

			$this->feedback = new $feedback_class();
			$this->feedback->setDatabase($this->app->db);

			if (!$this->feedback->load($this->id)) {
				throw new AdminNotFoundException(
					sprintf(
						Site::_(
							'Customer feedback with an id of ‘%d’ does '.
							'not exist.'
						),
						$this->id
					)
				);
			} elseif ($this->app->hasModule('SiteMultipleInstanceModule') &&
				$this->feedback->instance->id != $this->app->instance->getId()) {
				throw new AdminNotFoundException(
					sprintf(
						Store::_(
							'Incorrect instance for customer feedback '.
							'‘%d’.'
						),
						$this->id
					)
				);
			}
		}

		return $this->feedback;
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/details.xml';
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildFeedbackDetails();

		$toolbar = $this->ui->getWidget('toolbar');
		$toolbar->setToolLinkValues($this->id);
	}

	// }}}
	// {{{ protected function buildFeedbackDetails()

	protected function buildFeedbackDetails()
	{
		$ds = $this->getFeedbackDetailsStore();

		$details_view = $this->ui->getWidget('details_view');

		// set up timezone for createdate
		$date_field    = $details_view->getField('createdate');
		$date_renderer = $date_field->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;

		// hide customer field if there is no customer info
		$customer_field = $details_view->getField('customer');
		if ($this->getFeedback()->fullname === null &&
			$this->getFeedback()->email === null) {
			$customer_field->visible = false;
		}

		// hide referrer field if there is no referrer
		$referrer_field = $details_view->getField('http_referrer');
		if ($this->getFeedback()->http_referrer === null) {
			$referrer_field->visible = false;
		}

		$details_view->data = $ds;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->navbar->addEntry(
			new SwatNavBarEntry(
				$this->getFeedback()->getTitle()
			)
		);
		$this->title = Store::_('Details');
	}

	// }}}
	// {{{ protected function getFeedbackDetailsStore()

	protected function getFeedbackDetailsStore()
	{
		$feedback = $this->getFeedback();
		$ds = new SwatDetailsStore($feedback);

		$ds->bodytext   = SiteCommentFilter::toXhtml($feedback->bodytext);
		$ds->email_link = 'mailto:'.$feedback->email;

		return $ds;
	}

	// }}}
}

?>
