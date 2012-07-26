<?php

/**************************************************************
	 REGISTER EVENTSPACE VARS
 **************************************************************/

use Lagger\Action\ChromeConsole;
use Lagger\Action\FileLog;
use Lagger\Action\Mail;
use Lagger\Action\Printing;
use Lagger\Action\Sms;
use Lagger\Eventspace;
use Lagger\ExpireList;
use Lagger\Handler;
use Lagger\Handler\Debug;
use Lagger\Handler\Errors;
use Lagger\Handler\Exceptions;
use Lagger\Skiper;
use Lagger\Tagger;

$laggerES = new Eventspace();
$laggerES->registerReference('host', $_SERVER['HTTP_HOST']);
$laggerES->registerReference('uri', $_SERVER['REQUEST_URI']);
$laggerES->registerReference('post', $_POST);
$laggerES->registerReference('session', $_SESSION); // Session must be already started!
$laggerES->registerCallback('date', 'date', array('Y-m-d'));
$laggerES->registerCallback('time', 'date', array('H:i:s'));
$laggerES->registerCallback('microtime', 'microtime', array(true));
$laggerES->registerVar('session_id', session_id());
$laggerES->registerVar('process_id', substr(md5(mt_rand()), 25));

/**************************************************************
	 REGISTER EVENTSPACE MODIFIERS
 **************************************************************/

function varToStringLine($value) {
	return str_replace(array("\r\n", "\r", "\n"), ' ', is_scalar($value) ? $value : var_export($value, 1));
}
$laggerES->registerModifier('line', 'varToStringLine');

function quoteCSV($string) {
	return varToStringLine(str_replace(';', '\\;', $string));
}
$laggerES->registerModifier('csv', 'quoteCSV');

/**************************************************************
	 SKIPER
 **************************************************************/

$daylySkiper = new Skiper($laggerES, SKIPER_HASH_TEMPLATE, SKIPER_EXPIRE, new ExpireList(SKIPER_DIR, '.dayly_skiper'));

/**************************************************************
	 LAGGER INTERNAL ERRORS AND EXCEPTIONS HANDLING
 **************************************************************/

$emailAction = new Mail(ERRORS_EMAIL_FROM, ERRORS_EMAIL_TO, ERRORS_EMAIL_SUBJECT, ERRORS_EMAIL_MESSAGE);
$emailAction->setSkiper($daylySkiper, 'errors_email');

Handler::addInternalErrorAction($emailAction);

/**************************************************************
	 DEBUG HANDLER
 **************************************************************/

$debug = new Debug($laggerES);

function toDebug($message, $tags = null) {
	if(isset($GLOBALS['debug'])) {
		$GLOBALS['debug']->handle($message, $tags);
	}
}

if(DEBUG_STDOUT) {
	// Allows to rewrite DEBUG_STDOUT_TAGS. Try $_GET['__debug'] = 'high' or $_GET['__debug'] = ''
	$debugTagger = new Tagger('__debug');
	
	$debug->addAction(new Printing(DEBUG_STDOUT_TEMPLATE), DEBUG_STDOUT_TAGS, $debugTagger);
	// check Lagger/Action/ChromeConsole.php about how you can use it
	$debug->addAction(new ChromeConsole(dirname(__FILE__)), DEBUG_STDOUT_TAGS, $debugTagger);
}
if(DEBUG_LOGING) {
	$debug->addAction(new FileLog(DEBUG_LOGING_TEMPLATE, DEBUG_LOGING_FILEPATH, DEBUG_LOGING_LIMIT_SIZE), DEBUG_LOGING_TAGS);
}

/**************************************************************
	 ERRORS AND EXCEPTIONS HANDLERS
 **************************************************************/

$errors = new Errors($laggerES);
$exceptions = new Exceptions($laggerES);

if(ERRORS_STDOUT) {
	$printAction = new Printing(ERRORS_STDOUT_TEMPLATE, false);
	$errors->addAction($printAction, ERRORS_STDOUT_TAGS);
	$exceptions->addAction($printAction, ERRORS_STDOUT_TAGS);
	
	// check Lagger/Action/ChromeConsole.php about how you can use it
	$errorsChromeAction = new ChromeConsole(dirname(__FILE__));
	$errors->addAction($errorsChromeAction, ERRORS_STDOUT_TAGS);
	$exceptions->addAction($errorsChromeAction, ERRORS_STDOUT_TAGS);
}

$fatalPrintAction = new Printing('<br /><font color="red">Our site is FATALY dead, please check it again when we will fix it... in next summer :)', false);
$errors->addAction($fatalPrintAction, 'fatal');

if(ERRORS_LOGING) {
	$logAction = new FileLog(ERRORS_LOGING_TEMPLATE, ERRORS_LOGING_FILEPATH, ERRORS_LOGING_LIMIT_SIZE);
	$errors->addAction($logAction, ERRORS_LOGING_TAGS);
	$exceptions->addAction($logAction, ERRORS_LOGING_TAGS);
}

if(ERRORS_SMS) {
	$smsAction = new Sms(ERRORS_SMS_FROM, ERRORS_SMS_TO, ERRORS_SMS_MESSAGE, true);
	$smsAction->setSkiper($daylySkiper, 'errors_sms');
	$errors->addAction($smsAction, ERRORS_SMS_TAGS);
	$exceptions->addAction($smsAction, ERRORS_SMS_TAGS);
}

if(ERRORS_EMAIL) {
	$errors->addAction($emailAction, ERRORS_EMAIL_TAGS);
	$exceptions->addAction($emailAction, ERRORS_EMAIL_TAGS);
}