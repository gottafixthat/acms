<?php
/*
 * $Id: openSRS.php,v 1.1 2004/07/16 00:42:33 joe Exp $
 *
 * OpenSRS Protocol Extended Client Class
 *
 */

/* We require the base class */

require_once 'openSRS_base.php';

class openSRS extends openSRS_base {

    global $ACMSCfg;

	var $USERNAME				= $ACMSCfg['mod_domains']['userName'];

	var $TEST_PRIVATE_KEY		= $ACMSCfg['mod_domains']['testPrivateKey'];
	var $LIVE_PRIVATE_KEY		= $ACMSCfg['mod_domains']['livePrivateKey'];

	var $HRS_host				= $ACMSCfg['mod_domains']['hrsHost'];
	var $HRS_port				= $ACMSCfg['mod_domains']['hrsPort'];
	var $HRS_USERNAME			= $ACMSCfg['mod_domains']['hrsUsername'];
	var $HRS_PRIVATE_KEY		= $ACMSCfg['mod_domains']['hrsPrivateKey'];

	var $environment			= $ACMSCfg['mod_domains']['environment'];	// 'TEST' or 'LIVE' or 'HRS'
	var $crypt_type				= 'DES';	// 'DES' or 'BLOWFISH';
	var $protocol				= 'XCP';	// 'XCP' for domains, 'TPP' for email and certs


	var $connect_timeout		= 20;		// seconds
	var $read_timeout			= 20;		// seconds

	var $EXP_DATE				= 0;
	var $ERROR_TEXT				= "";
	var $DEBUG					= 1;
	var $OUR_DOMAIN  			= 0;

    $ns = split(':', $ACMSCfg['mod_domains']['nameservers']);
    var $NAMESERVER_LIST = array();
    for ($i = 0; $i < count($ns); $i++) {
        $NAMESERVER_LIST[] = array('sortorder' => $i+1, 'name' => $ns[$i]);
    }

	var $RELATED_TLDS = array(
		array( '.ca' ),
		array( '.com', '.net', '.org' ),
		array( '.co.uk', '.org.uk' ),
		array( '.vc' ),
		array( '.cc' ),
	);

/*
** isOurDomain - Checks to see if we are the RSP of record for the
**               domain. Returns True (1) or False (0)
**               This function gets called automatically during all other
**               operations if it has not already been called.
*/

function isOurDomain($domain = "")
{
	if(empty($domain)) { return 0; }

	/* Don't check more than once */
	if($this->{'EXP_DATE'} != 0) { return $this->{'OUR_DOMAIN'}; }

	$cmd = array(
				'action'		=>	'belongs_to_rsp',
				'object'		=>	'domain',
				'attributes'	=>	array(
						'domain'	=>	$domain,
						'affiliate_id'	=>	'')
			);
	$result = $this->send_cmd($cmd);

	if($this->{'DEBUG'} == 1)
	{
		print "<pre>isOurDomain\n";
		print_r($result);
		print "</pre>\n";
	}

	$success = $result["is_success"];
	$response_text = $result["response_text"];
	$response_code = $result["response_code"];
	if( ($success == 1) && ($response_code == 200) && ($response_text == 'Query successful') )
	{
		$attributes = $result["attributes"];
		if($attributes["domain_expdate"])
		{
			$this->{'EXP_DATE'} = $attributes["domain_expdate"];
			$this->{'OUR_DOMAIN'} = $attributes["belongs_to_rsp"];
		}
		return $attributes["belongs_to_rsp"];
	} else {
		$this->{'EXP_DATE'} = -1;
		$this->{'OUR_DOMAIN'} = 0;
		$this->{'ERROR_TEXT'} = "$response_code: $response_text";
		return 0;
	}
}

/*
** renewDomain - Given a domain name and the current expiration year
**               (in four digit notation) will renew the domain for the
**               given period. Period defaults to 1 and is optional.
**               Period cannot exceed 10 (years).
**               Returns Success (1) or Failure (0)
**               Failure sets ERROR_TEXT for why it failed.
*/

function renewDomain($domain="",$expiration="",$period=1)
{
	if(empty($domain))
	{
		$this->{'ERROR_TEXT'} = '465: Missing or invalid domain name.';
		return 0;
	}
	if(empty($expiration))
	{
		$this->{'ERROR_TEXT'} = '400: Missing current expiration year.';
		return 0;
	}
	if($period > 10)
	{
		$this->{'ERROR_TEXT'} = '400: Unable to renew domain: Maximum registration period (10 years) exceeded.';
		return 0;
	}
	$curYear = strftime("%Y");
	if( (($expiration + $period) - $curYear) > 10 )
	{
		print "$curYear<br>\n";
		$this->{'ERROR_TEXT'} = '400: Unable to renew domain: Maximum registration period (10 years) exceeded.';
		return 0;
	}
	if($this->{'OUR_DOMAIN'} == 0)
	{
		$ourDomain = $this->isOurDomain($domain);
		if($ourDomain != 1)
		{
			$this->{'ERROR_TEXT'} = '400: Unable to renew domain: We are not the RSP of record for this domain.';
			return 0;
		}
	}
	$cmd = array(
				'action'		=>	'renew',
				'object'		=>	'domain',
				'attributes'	=>	array(
						'domain'	=>	$domain,
						'currentexpirationyear'	=>	$expiration,
						'period'	=>	$period,
						'auto_renew'	=>	0,
						'handle'	=>	'process'
					)
			);
	$result = $this->send_cmd($cmd);
	$success = $result["is_success"];
	$response_text = $result["response_text"];
	$response_code = $result["response_code"];
	if($this->{'DEBUG'} == 1)
	{
		print "<pre>renewDomain\n";
		print_r($result);
		print "</pre>\n";
	}
	if( ($success == 1) && ($response_code == 200) && ( $response_text == 'Command completed successfully') )
	{
		return 1;
	} else {
		$this->{'ERROR_TEXT'} = "$response_code: $response_text";
		return 0;
	}
}

/*
** sendAdminPwd - given a domain name and optional 'admin' or 'owner'
**                flag, will send the domain password to the specified
**                contact's email address.
*/

function sendAdminPwd($domain="",$send_to='admin')
{
	if(empty($domain))
	{
		$this->{'ERROR_TEXT'} = '400: Missing or invalid domain name.';
		return 0;
	}
	if($this->{'OUR_DOMAIN'} == 0)
	{
		$ourDomain = $this->isOurDomain($domain);
		if($ourDomain != 1)
		{
			$this->{'ERROR_TEXT'} = '400: Unable to process request. We are not the RSP of record for this domain.';
			return 0;
		}
	}
	$cmd = array(
				'action'		=>	'send_password',
				'object'		=>	'domain',
				'attributes'	=>	array(
						'domain_name'	=>	$domain,
						'send_to'	=>	$send_to,
						'sub_user'	=>	0
					)
			);
	$result = $this->send_cmd($cmd);
	$success = $result["is_success"];
	$response_text = $result["response_text"];
	$response_code = $result["response_code"];
	if($this->{'DEBUG'} == 1)
	{
		print "<pre>sendAdminPwd\n";
		print_r($result);
		print "</pre>\n";
	}
	if( ($success == 1) && ($response_code == 200) && ( $response_text == 'Message sent') )
	{
		return 1;
	} else {
		$this->{'ERROR_TEXT'} = "$response_code: $response_text";
		return 0;
	}
}

/*
** registerDomain - Umbrella function for the individual TLD functions
**
*/
function registerDomain($domain="",$openSRS=array(), $owner=array(), $admin=array(), $billing=array(), $tech=array() )
{
	if(empty($domain))
	{
		$this->{'ERROR_TEXT'} = '400: Missing or invalid domain name.';
		return 0;
	}
	$domain = strtolower(trim($domain));
	list($body,$tld) = split("\.",$domain);

	if($this->{'DEBUG'})
	{
		print "<pre>\nregisterDomain $body . $tld\n</pre>\n";
	}

	switch ($tld)
	{
		case 'com':
			return $this->registerCOMDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
			break;
		case 'net':
			return $this->registerNETDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
			break;
		case 'org':
			return $this->registerORGDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
			break;
		case 'biz':
			return $this->registerBIZDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
			break;
		case 'info':
			return $this->registerINFODomain($domain,$openSRS,$owner,$admin,$billing,$tech);
			break;
		default:
			$this->{'ERROR_TEXT'} = "400: Unrecognized TLD [$tld].";
			return 0;
	}
	return 0;
}

/*
** registerCOMDomain - Registers a new .com domain with OpenSRS
**
*/
function registerCOMDomain($domain="", $openSRS=array(), $owner=array(), $admin=array(), $billing=array(), $tech=array() )
{
	if(empty($domain))
	{
		$this->{'ERROR_TEXT'} = '400: Missing or invalid domain name.';
		return 0;
	}

	$username = $openSRS["username"];
	$password = $openSRS["password"];
	$period = $openSRS["period"];
	$reg_domain = $openSRS["reg_domain"];
	$cmd = array(
				'object'		=>	'domain',
				'attributes'	=>	array(
					'domain'		=> 	$domain,
					'handle'		=>	'process',
					'auto_renew'	=>	0,
					'period'		=>	$period,
					'reg_domain'	=>	$reg_domain,
					'reg_username'	=>	$username,
					'reg_password'	=>	$password,
					'reg_type'		=>	'new',
					'custom_nameservers'	=>	0,
					'custom_tech_contact'	=>	0,
					'link_domains'	=>0,
					'contact_set'	=>	array(
							'owner'		=>	$owner,
							'admin'		=>	$admin,
							'billing'	=>	$billing
						),
					'f_lock_domain'	=>	0,
					'affiliate_id'	=>	""
				),
				'action'		=>	'sw_register'
			);

	$result = $this->send_cmd($cmd);

	if($this->{'DEBUG'})
	{
		print "<pre>registerCOMDomain\n";
		print_r($result);
		print "</pre>\n";
	}
	$success = $result["is_success"];
	$response_text = $result["response_text"];
	$response_code = $result["response_code"];
	$attributes = $result['attributes'];
	$registration_text = $attributes['registration_text'];
	$registration_code = $attributes['registration_code'];
	if( ($success == 1) && ($registration_code == 200) && ($registration_text == 'Domain registration successfully completed') )
	{
		$this->{'ERROR_TEXT'} = "$response_code: $response_text ($registration_code: $registration_text).";
		return 0;
	}
	return 1;
}

/*
** registerNETDomain - Register a new .net domain with OpenSRS
**                     For now this is functionally identical to a .com
*/
function registerNETDomain($domain="", $openSRS=array(), $owner=array(), $admin=array(), $billing=array(), $tech=array() )
{
	return $this->registerCOMDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
}
/*
** registerORGDomain - Register a new .org domain with OpenSRS
**
*/
function registerORGDomain($domain="", $openSRS=array(), $owner=array(), $admin=array(), $billing=array(), $tech=array() )
{
	if(!($this->checkPhones($owner,$admin,$billing))) { return 0; }
	return $this->registerCOMDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
}

/*
** registerBIZDomain - Register a new .biz domain with OpenSRS
**
*/
function registerBIZDomain($domain="", $openSRS=array(), $owner=array(), $admin=array(), $billing=array(), $tech=array() )
{
	if(!($this->checkPhones($owner,$admin,$billing))) { return 0; }
	return $this->registerCOMDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
}

/*
** registerINFODomain - Register a new .info domain with OpenSRS
**
*/
function registerINFODomain($domain="", $openSRS=array(), $owner=array(), $admin=array(), $billing=array(), $tech=array() )
{
	if(!($this->checkPhones($owner,$admin,$billing))) { return 0; }
	return $this->registerCOMDomain($domain,$openSRS,$owner,$admin,$billing,$tech);
}

/*
** checkPhone - checks for valid EPP format telephone numbers
**              MUST be in the following format to be valid:
**              +N.NNNNNNNNNNxNNNN
**              The extension (xNNNN) is optional
**              Returns true or false
*/
function checkPhone($phone="")
{
	$numGood = 0;
	$extGood = 0;

	$phone = strtolower(trim($phone));
	list($number,$extension) = split("x",$phone);

	if(preg_match("/^\+[0-9]\.[0-9]{10,10}$/",$number)) { $numGood = 1; }
	if(empty($extension))
	{
		$extGood = 1;
	} else {
		if(preg_match("/^[0-9]{1,4}$/",$extension))
		{ 
			$extGood = 1;
		} else {
			$extGood = 0;
		}
	}

	if( ($numGood == 1) && ($extGood == 1) ) { return 1; }

	$this->{'ERROR_TEXT'} = "400: Phone [$phone] not in EPP format +n.nnnnnnnnnnxNNNN. $numGood $extGood";
	return 0;
}

/*
** checkPhones - Umbrella function to checkPhone
**
*/
function checkPhones($owner,$admin,$billing)
{
	$fax = $owner['fax'];
	$ophone = $this->checkPhone($owner['phone']);
	if($fax) { $ofax = $this->checkPhone($fax); } else { $ofax = 1; }
	$fax = $admin['fax'];
	$aphone = $this->checkPhone($admin['phone']);
	if($fax) { $afax = $this->checkPhone($fax); } else { $afax = 1; }
	$fax = $billing['fax'];
	$bphone = $this->checkPhone($billing['phone']);
	if($fax) { $bfax = $this->checkPhone($fax); } else { $bfax = 1; }

	if(($ophone)&&($aphone)&&($bphone)&&($ofax)&&($afax)&&($bfax)) { return 1; }
	return 0;
}
/*
** resetData - clears all class vars to move on to next domain name
**
*/

function resetData()
{
	$this->{'EXP_DATE'} = 0;
	$this->{'OUR_DOMAIN'} = 0;
	$this->{'ERROR_TEXT'} = "";
	return 1;
}

} /* End Class */
?>
