<?php
/**
 * @version		3.7.5 plugins/j2xml/users/users.php
*
* @package		J2XML
* @subpackage	plg_j2xml_users
* @since		3.0.0
*
* @author		Helios Ciancio <info@eshiol.it>
* @link		http://www.eshiol.it
* @copyright	Copyright (C) 2013, 2017 Helios Ciancio. All Rights Reserved
* @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
* J2XML is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License
* or other free or open source software licenses.
*/

// no direct access
defined('_JEXEC') or die('Restricted access.');

use Joomla\Registry\Registry;

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.file');
jimport('joomla.user.helper');
jimport('eshiol.j2xml.version');

class plgJ2xmlUsers extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param  object  $subject  The object to observe
	 * @param  array   $config   An array that holds the plugin configuration
	 */
	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if ($this->params->get('debug') || defined('JDEBUG') && JDEBUG)
		{
			JLog::addLogger(array('text_file' => $this->params->get('log', 'eshiol.log.php'), 'extension' => 'plg_j2xml_users_file'), JLog::ALL, array('plg_j2xml_users'));
		}

		if (PHP_SAPI == 'cli')
		{
			JLog::addLogger(array('logger' => 'echo', 'extension' => 'plg_j2xml_users'), JLOG::ALL & ~JLOG::DEBUG, array('plg_j2xml_users'));
		}
		else
		{
			JLog::addLogger(array('logger' => $this->params->get('logger', 'messagequeue'), 'extension' => 'plg_j2xml_users'), JLOG::ALL & ~JLOG::DEBUG, array('plg_j2xml_users'));
			if ($this->params->get('phpconsole') && class_exists('JLogLoggerPhpconsole'))
			{
				JLog::addLogger(array('logger' => 'phpconsole', 'extension' => 'plg_j2xml_users_phpconsole'),  JLOG::DEBUG, array('plg_j2xml_users'));
			}
		}

		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_j2xml_users'));
	}

	/**
	 * Runs on content preparation
	 *
	 * @param   string  $context  The context for the data
	 * @param   string  $data     An object containing the data for the form.
	 *
	 * @return  boolean
	 */
	public function onContentPrepareData($context, &$data)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'plg_j2xml_users'));

		if (version_compare(J2XMLVersion::getFullVersion(), '17.7.300') == -1)
		{
			JLog::add(new JLogEntry(JText::_('PLG_J2XML_USERS').' '.JText::_('PLG_J2XML_USERS_MSG_REQUIREMENTS_LIB')),JLOG::WARNING,'plg_j2xml_users');
			return true;
		}

		libxml_use_internal_errors(true);
		$doc = simplexml_load_string($data);
		if ($doc)
		{
			JLog::add('XML file', JLog::DEBUG, 'plg_j2xml_users');
			return true;
		}

		// Rearrange this array to change the search priority of delimiters
		$delimiters = array(
				'tab'       => "\t",
				'semicolon' => ";",
				'colon'     => ","
		);
		$lines = explode(PHP_EOL, $data);
		$line = array();
		$header = array_shift($lines);
		foreach($delimiters as $key => $value)
		{
			$line[$value] = count(explode($value,$header))-1;
		}
		$delimiter = array_search(max($line),$line);

		$cols = array();
		$item = str_getcsv($header, $delimiter);
		foreach($item as $i => $v)
		{
			$cols[strtolower($v)] = $i;
		}
		if (!isset($cols['username']) || (!isset($cols['name'])) || (!isset($cols['email'])))
		{
			JLog::add('invalid CSV file', JLog::DEBUG, 'plg_j2xml_users');
			return true;
		}
		JLog::add('CSV format: '.print_r($cols, true), JLog::DEBUG, 'plg_j2xml_users');

		$new_usertype = $this->params->get('new_usertype',
				JComponentHelper::getParams('com_users')->get('new_usertype')
				);
		JLog::add('new usertype: '.$new_usertype, JLog::DEBUG, 'plg_j2xml_users');

		$xml = '';
		foreach ($lines as $line)
		{
			if ($line)
			{
				JLog::add('line: '.$line, JLog::DEBUG, 'plg_j2xml_users');
				$item = str_getcsv($line, $delimiter);

				foreach ($cols as $k => $v)
				{
					JLog::add($k.': '.$item[$v], JLog::DEBUG, 'plg_j2xml_users');
				}
				$xml .= "\t<user>\n";
				$xml .= "\t\t<id>0</id>\n";
				$xml .= "\t\t<name><![CDATA[".$item[$cols['name']]."]]></name>\n";
				$xml .= "\t\t<username><![CDATA[".$item[$cols['username']]."]]></username>\n";
				$xml .= "\t\t<email><![CDATA[".$item[$cols['email']]."]]></email>\n";
				if (isset($cols['password']) && isset($item[$cols['password']]) && $item[$cols['password']])
				{
					$xml .= "\t\t<password><![CDATA[{$item[$cols['password']]}]]></password>\n";
				}
				elseif (isset($cols['password_clear']) && isset($item[$cols['password_clear']]) && $item[$cols['password_clear']])
				{
					$xml .= "\t\t<password_clear><![CDATA[{$item[$cols['password_clear']]}]]></password_clear>\n";
				}
				else
				{
					$password_clear = JUserHelper::genRandomPassword();
					$xml .= "\t\t<password_clear><![CDATA[{$password_clear}]]></password_clear>\n";
				}
				$xml .= "\t\t<block>0</block>\n";
				$xml .= "\t\t<sendEmail>0</sendEmail>\n";
				$xml .= "\t\t<registerDate><![CDATA[".date("Y-m-d H:i:s")."]]></registerDate>\n";
				$xml .= "\t\t<lastvisitDate><![CDATA[0000-00-00 00:00:00]]></lastvisitDate>\n";
				$xml .= "\t\t<activation/>\n";
				$xml .= "\t\t<params><![CDATA[{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}]]></params>\n";
				$xml .= "\t\t<lastResetTime><![CDATA[0000-00-00 00:00:00]]></lastResetTime>\n";
				$xml .= "\t\t<resetCount>0</resetCount>\n";
				$xml .= "\t\t<otpKey/>\n";
				$xml .= "\t\t<otep/>\n";
				if (isset($cols['requirereset']) && !is_null($item[$cols['requirereset']]))
				{
					$xml .= "\t\t<requireReset>{$item[$cols['requirereset']]}</requireReset>\n";
				}
				else
				{
					$xml .= "\t\t<requireReset>{$this->params->get('requireReset', 1)}</requireReset>\n";
				}
				if (!isset($cols['grouplist']) && isset($cols['groups']))
				{
					$cols['grouplist'] = $cols['groups'];
				}
				if (isset($cols['grouplist']))
				{
					JLog::add('grouplist: '.$item[$cols['grouplist']], JLog::DEBUG, 'plg_j2xml_users');
					if (!isset($item[$cols['grouplist']]))
					{
						$xml .= "\t\t<group>{$new_usertype}</group>\n";
					}
					else
					{
						$xml .= "\t\t<grouplist>\n";
						foreach(explode(";", $item[$cols['grouplist']]) as $group)
						{
							JLog::add('group: '.$group, JLog::DEBUG, 'plg_j2xml_users');
							if (is_numeric($group_id = trim($group,'"')))
							{
								$xml .= "\t\t\t<group>{$group_id}</group>\n";
							}
							else
							{
								$xml .= "\t\t\t<group><![CDATA[[{$group}]]]></group>\n";
							}
						}
						$xml .= "\t\t</grouplist>\n";
					}
				}
				elseif (isset($cols['group']))
				{
					JLog::add('group', JLog::DEBUG, 'plg_j2xml_users');
					if (!isset($item[$cols['group']]))
					{
						$xml .= "\t\t<group>{$new_usertype}</group>\n";
					}
					elseif (is_numeric($item[$cols['group']]))
					{
						$xml .= "\t\t<group>{$item[$cols['group']]}</group>\n";
					}
					else
					{
						$xml .= "\t\t<group><![CDATA[[{$item[$cols['group']]}]]]></group>\n";
					}
				}
				else
				{
					$xml .= "\t\t<group>{$new_usertype}</group>\n";
				}

				$xml .= "\t</user>\n";
			}
		}

		$xml =
		'<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
				.'<j2xml version="'.J2XMLVersion::$DOCVERSION.'">' . "\n"
						.$xml
						.'</j2xml>';

						$data = $xml;
						JLog::add(new JLogEntry('xml: '.$xml, JLog::DEBUG, 'plg_j2xml_users'));
						return true;
	}


	/**
	 * Method is called by index.php and administrator/index.php
	 *
	 * @access	public
	 */
	public function onAfterDispatch()
	{
		JLog::add(new JLogEntry(__METHOD__, JLOG::DEBUG, 'plg_j2xml_users'));

		$app = JFactory::getApplication();
		if ($app->getName() != 'administrator')
		{
			return true;
		}

		$enabled = JComponentHelper::getComponent('com_j2xml', true);
		if (!$enabled->enabled)
		{
			return true;
		}

		$option = JRequest::getVar('option');
		$view = JRequest::getVar('view');

		if (($option == 'com_j2xml') && (!$view || $view == 'cpanel'))
		{
			$doc = JFactory::getDocument();
			JLog::add(new JLogEntry('loading CSVToArray.min.js...', JLOG::DEBUG, 'plg_j2xml_users'));
			$doc->addScript("../media/plg_j2xml_users/js/CSVToArray.min.js");
			JLog::add(new JLogEntry('loading j2xml.js...', JLOG::DEBUG, 'plg_j2xml_users'));
			$doc->addScript("../media/plg_j2xml_users/js/j2xml.js");
		}
		return true;
	}
}
