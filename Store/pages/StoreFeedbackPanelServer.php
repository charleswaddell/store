<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Swat/SwatUI.php';

/**
 * @package   Store
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreFeedbackPanelServer extends SiteXMLRPCServer
{
	// {{{ public function getContent()

	/**
	 * Returns the XHTML required to display the feedback panel
	 *
	 * @param string $method the HTTP method. 'GET or 'POST'.
	 * @param string $data the raw HTTP POST data of the form if the method is
	 *                     'POST'.
	 * @param string $uri the URI of the page making the request (referrer).
	 *
	 * @return array an array containing the following elements:
	 *               - <kbd>content</kbd>      - the XHTML required to display
	 *                                           the feedback panel.
	 *               - <kbd>head_entries</kbd> - a list of required external
	 *                                           JS and CSS files.
	 *               - <kbd>success</kbd>      - if post data was provided,
	 *                                           this contains true if the data
	 *                                           submitted successfully.
	 *                                           otherwise, false.
	 */
	public function getContent($method, $data, $uri)
	{
		if (strtolower($method) == 'post') {
			$data_exp = explode('&', $data);
			$args = array();
			foreach ($data_exp as $parameter) {
				if (strpos($parameter, '=')) {
					list($key, $value) = explode('=', $parameter, 2);
				} else {
					$key   = $parameter;
					$value = null;
				}

				$key   = urldecode($key);
				$value = urldecode($value);

				$regs = array();
				if (preg_match('/^(.+)\[(.*)\]$/', $key, $regs)) {
					$key = $regs[1];
					$array_key = ($regs[2] == '') ? null : $regs[2];
					if (!isset($args[$key]))
						$args[$key] = array();

					if ($array_key === null) {
						$args[$key][] = $value;
					} else {
						$args[$key][$array_key] = $value;
					}
				} else {
					$args[$key] = $value;
				}
			}

			foreach ($args as $key => $value) {
				$_POST[$key] = $value;
			}
		}

		$return = array();

		ob_start();

		$ui = new SwatUI();
		$ui->loadFromXml($this->getXmlUi());

		$ui->init();
		$ui->process();
		$ui->display();

		$return['content'] = ob_get_clean();
		$return['head_entries'] = '';
		$return['success'] = (!$ui->getRoot()->hasMessage());

		return $return;
	}

	// }}}
	// {{{ protected function getXmlUi()

	protected function getXmlUi()
	{
		return 'Store/feedback-panel.xml';
	}

	// }}}
}

?>