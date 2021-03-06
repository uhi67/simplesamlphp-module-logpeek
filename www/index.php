<?php

/**
 * @param $objFile
 * @param $tag
 * @param $cut
 * @return array|string -- array on success, string (error message) on failure
 */
function logFilter($objFile, $tag, $cut){
	if (!preg_match('/^[a-f0-9]{10}$/D', $tag)) {
		return '<div class="alert-danger">Invalid search tag! Search tag must be exactly 10 characters long hexadecimal number.</div>';
	}

	$i = 0;
	$results = array();
	$line = $objFile->getPreviousLine();
	while($line !== FALSE && ($i++ < $cut)){
		if(strstr($line, '[' . $tag . ']')){
			$results[] = $line;
		}
		$line = $objFile->getPreviousLine();
	}
	$results[] = 'Searched ' . $i . ' lines backward. ' . count($results) . ' lines found.';
	$results = array_reverse($results);
	return $results;
}


$config = SimpleSAML\Configuration::getInstance();
$session = SimpleSAML\Session::getSessionFromRequest();
$logpeekconfig = SimpleSAML\Configuration::getConfig('module_logpeek.php');
$requireAdmin = $logpeekconfig->getValue('requireAdmin', true);
$requireAuth = $logpeekconfig->getValue('requireAuth', false);

if($requireAdmin) SimpleSAML\Utils\Auth::requireAdmin();

$error = '';
$authorized = true;
if($requireAuth) {
	$as = new SimpleSAML\Auth\Simple($requireAuth);
	if(!$as->isAuthenticated()) $as->requireAuth();
	$attributes = $as->getAttributes();
	$requiredAttrs = $logpeekconfig->getValue('requiredAttrs', []);

	foreach($requiredAttrs as $name=>$value) {
		$attrValues = isset($attributes[$name]) ? $attributes[$name] : array();
		if(!is_array($attrValues)) $attrValues = array($attrValues);
		$hasValue = false;
		foreach($attrValues as $av) {
			if(preg_match('/^~[^~]*~$/', $value)) $hasValue = preg_match($value, $av);
			else $hasValue = $value == $av;
			if($hasValue) break;
		}
		if(!$hasValue) {
			$authorized = false;
			SimpleSAML\Utils\Auth::requireAdmin();
		}
	}
}

$logfile = $logpeekconfig->getValue('logfile', '/var/simplesamlphp.log');
$blockSize = $logpeekconfig->getValue('blocksz', 8192);

$myLog = new sspmod_logpeek_File_reverseRead($logfile, $blockSize);
if(!$myLog) throw new Exception("Cannot open stream '$logfile'");

$results = NULL;
$tag = $session->getTrackID();

if (isset($_REQUEST['tag'])) {
	$tag = $_REQUEST['tag'];
	if (!preg_match('/^[a-f0-9]{10}$/D', $tag)) {
		$error = '<div class="alert-danger">Invalid search tag! Search tag must be exactly 10 characters long hexadecimal number.</div>';
		$results = array();
	}
	else {
		$error = '';
		$results = logFilter($myLog, $tag, $logpeekconfig->getValue('lines', 500));
		if(!is_array($results)) {
			$error = $results;
			$results = [];
		}
	}
}


$fileModYear = date("Y", $myLog->getFileMtime());
$firstLine = $myLog->getFirstLine();
$firstTimeEpoch = sspmod_logpeek_Syslog_parseLine::getUnixTime($firstLine, $fileModYear);
$lastLine = $myLog->getLastLine();
$lastTimeEpoch = sspmod_logpeek_Syslog_parseLine::getUnixTime($lastLine, $fileModYear);
$fileSize = $myLog->getFileSize();

$t = new SimpleSAML\XHTML\Template($config, 'logpeek:logpeek.php');
$t->data['error'] = $error;
$t->data['results'] = $results;
$t->data['trackid'] = $tag;
$t->data['timestart'] = date(DATE_RFC822, $firstTimeEpoch);
$t->data['endtime'] = date(DATE_RFC822, $lastTimeEpoch);
$t->data['filesize'] = $fileSize;
$t->data['logfile'] = $logfile;
$t->show();
