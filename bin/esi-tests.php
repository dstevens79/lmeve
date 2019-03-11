<?php
$POLLER_VERSION="28";
$POLLER_MAX_TIME=900;
set_time_limit($POLLER_MAX_TIME-20); //poller can work for up to 15 minutes 
//(minus 20 seconds so the next cron cycle can work correctly), afterwards it should die
$mypath=str_replace('\\','/',dirname(__FILE__));
$mylog=$mypath."/../var/poller.txt";
$httplog=$mypath."/../var/http_errors.txt";
$mylock=$mypath."/../var/poller.lock";
$mycache=$mypath."/../var";
$mytmp=$mypath."/../tmp";
$MAX_ERRORS=10; //ignore first x errors

include_once("../config/config.php"); //API URLs are now in config.php

if (!isset($ESI_BASEURL)) {
    echo('WARNING: $ESI_BASEURL isn\'t set in config.php. Using default ESI API URL https://esi.evetech.net'  . "\r\n");
    $ESI_BASEURL="https://esi.evetech.net"; 
} 
    
$USER_AGENT="LMeve/1.0 API Poller Version/$POLLER_VERSION";

$FEED_BLOCKED="This feed is blocked due to previous errors.";
$FEED_URL_PROBLEM="Can't get CREST url from CREST root.";

date_default_timezone_set(@date_default_timezone_get());
//set_include_path("$mypath/../Modules");
include_once("../Site_Core/log.php");
include_once("../Site_Core/log.php");
include_once("../Site_Core/log.php");
include_once("../Site_Core/configuration.php");
include_once("../Site_Core/log.php");
include_once('libpoller.php');       
include_once("../Site_Core/log.php");

include_once('ESI.class.php');

/*************************************************************************************************/

$ESI = new ESI(5);
echo("Testing\r\n");

//$ESI->Characters->addCharacter(245073304);

//$ESI->Wallet->updateCorpWalletTransactions();

$ESI->Status->updateServerStatus();

?>