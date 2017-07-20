/**
 * @version		3.7.4 media/plg_j2xml_users/js/j2xml.js
 * 
 * @package		J2XML
 * @subpackage	plg_j2xml_users
 * @since		3.7.4
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

// Avoid `console` errors in browsers that lack a console.
(function () {
	var methods = [
		'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
		'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
		'profile', 'profileEnd', 'table', 'time', 'timeEnd', 'timeStamp',
		'trace', 'warn'
	];
	console = window.console = window.console || {};
	methods.forEach(function (method) {
		if (!console[method]) {
			console[method] = function () {};
		}
	});
}());
  
if (typeof(eshiol) === 'undefined') {
	eshiol = {};
}

if (typeof(eshiol.j2xml) === 'undefined') {
	eshiol.j2xml = {};
}

if (typeof(eshiol.j2xml.convert) === 'undefined') {
	eshiol.j2xml.convert = [];
}

eshiol.j2xml.users = {};
eshiol.j2xml.users.version = '3.7.4';
eshiol.j2xml.users.requires = '17.7.301';

console.log('Users for J2XML v'+eshiol.j2xml.users.version);

/**
 * 
 * @param {} root
 * @return  {}
 */ 
eshiol.j2xml.convert.push(function(xml)
{   
	console.log('eshiol.j2xml.convert.users');
	if (versionCompare(eshiol.j2xml.version, eshiol.j2xml.users.requires) < 0)
	{
		eshiol.renderMessages({
			'error': ['Users for J2XML v'+eshiol.j2xml.users.version+' requires J2XML v3.7.173']
		});
		return false;
	}

//	var lines = xml.split(/\r?\n/);
//	var header = CSVToArray(lines[0], ";");
	var csv = CSVToArray(xml, ";");
	var header = csv[0];
	console.log(header);
	var cols = [];
	
    for(var i = 0; i < header.length; i++) 
    {
    	console.log('header['+i+']: '+header[i]);
    	cols[header[i]] = i;
    }

	console.log('cols[\'username\']: '+cols['username']);
	console.log('cols[\'name\']: '+cols['name']);
	console.log('cols[\'email\']: '+cols['email']);

	if (
    	(cols['username'] == undefined) ||
    	(cols['name'] == undefined) ||
    	(cols['email'] == undefined)
    )
    {
    	console.log('invalid CSV file');
		return xml;
	}

	xml = '';
    for (var i = 1; i < csv.length; i++)
    {    	
		console.log(csv[i]);

		var x = '';
		x += "\t<user>\n";
		x += "\t\t<id>0</id>\n";
		x += "\t\t<name><![CDATA["+csv[i][cols['name']]+"]]></name>\n";
		x += "\t\t<username><![CDATA["+csv[i][cols['username']]+"]]></username>\n";
		x += "\t\t<email><![CDATA["+csv[i][cols['email']]+"]]></email>\n";
		if ((cols['password'] != undefined) && (csv[i][cols['password']] != undefined))
		{
			x += "\t\t<password><![CDATA[{csv[i][cols['password']]}]]></password>\n";
		}
		else if ((cols['password_clear'] != undefined) && (csv[i][cols['password_clear']] != undefined))
		{
			x += "\t\t<password_clear><![CDATA[{csv[i][cols['password_clear']]}]]></password_clear>\n";
		}
		else
		{
			password_clear = Math.random()				// Generate random number, eg: 0.123456
									.toString(36)		// Convert to base-36 : "0.4fzyo82mvyr"
										.slice(-8);		// Cut off last 8 characters : "yo82mvyr"
			x += "\t\t<password_clear><![CDATA["+password_clear+"]]></password_clear>\n";
		}
		x += "\t\t<block>0</block>\n";
		x += "\t\t<sendEmail>0</sendEmail>\n";
		x += "\t\t<registerDate><![CDATA["+(new Date().toLocaleString())+"]]></registerDate>\n";
		x += "\t\t<lastvisitDate><![CDATA[0000-00-00 00:00:00]]></lastvisitDate>\n";
		x += "\t\t<activation/>\n";
		x += "\t\t<params><![CDATA[{\"admin_style\":\"\",\"admin_language\":\"\",\"language\":\"\",\"editor\":\"\",\"helpsite\":\"\",\"timezone\":\"\"}]]></params>\n";
		x += "\t\t<lastResetTime><![CDATA[0000-00-00 00:00:00]]></lastResetTime>\n";
		x += "\t\t<resetCount>0</resetCount>\n";
		x += "\t\t<otpKey/>\n";
		x += "\t\t<otep/>\n";
		x += "\t</user>\n";
		console.log(x);
		xml += x;
    }
    
	return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<j2xml version=\"15.9.0\">\n"+xml+"</j2xml>";
});
