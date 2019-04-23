<?php
$mypath = str_replace('\\', '/', dirname(__FILE__));
include_once("../config/config.php"); //API URLs are now in config.php
//set_include_path("$mypath/../Modules");
include_once("libpoller.php");
include_once("../Site_Core/log.php");
include_once("../Site_Core/log.php");
include_once("../Site_Core/configuration.php");
//include_once("../Site_Core/log.php");
include_once("../Site_Core/log.php");
//ESI Routes:
require_once('Corporations.php');
require_once('Characters.class.php');
require_once('Stations.class.php');
require_once('MemberTracking.class.php');
require_once('Industry.php');
require_once('Facilities.php');
require_once('Markets.php');
require_once('Universe.php');
require_once('Contracts.php');
require_once('Wallet.php');
require_once('Assets.php');
require_once('Killmails.php');
require_once('Status.php');

class ESI {
    public static $VERSION = 1;
    private $mypath;
    private $mylog;
    private $httplog;
    private $mycache;
    private $mytmp;
    private $MAX_ERRORS = 10; //retry the first x errors
    private $USER_AGENT = "LMeve/2.0 ESI Poller";
    private $tokenID;
    private $refresh_token;
    private $access_token;
    private $access_token_expire;
    private $characterID = null;
    private $corporationID = null;
    private $ESI_BASEURL;
    private $DEBUG = TRUE;
    private $DATASOURCE = 'tranquility';
    
    private $EsiErrorLimitRemain = 0;
    private $EsiErrorLimitReset = 0;

     /**
     * Corporations route instance
     * @var Corporations 
     */
    public $Corporations;
    
    /**
     * MemberTracking route instance
     * @var MemberTracking 
     */
    public $MemberTracking;
    
    /**
     * Characters route instance
     * @var Characters
     */
    public $Characters;
    
    /**
     * Stations route instance
     * @var Stations
     */
    public $Stations;
    
    /**
     * IndustryJobs route instance
     * @var Industry
     */
    public $Industry;
    
    /**
     * Facilities route instance
     * @var Facilities
     */
    public $Facilities;
    
    /**
     * Markets route instance
     * @var Markets
     */
    public $Markets;
    
    /**
     * Universe route instance
     * @var Universe
     */
    public $Universe;
    
    /**
     * Contracts route instance
     * @var Contracts
     */
    public $Contracts;
    
    /**
     * Assets route instance
     * @var Assets
     */
    public $Assets;
    
    /**
     * Wallet route instance
     * @var Wallet
     */
    public $Wallet;
    
    /**
     * Killmails route instance
     * @var Killmails
     */
    public $Killmails;
    
    /**
     * Status route instance
     * @var Status
     */
    public $Status;
    
    /**
     * $tokenID int - which refresh_token from cfgesitoken to use for this instance
     */
    public function __construct($tokenID) {
        global $ESI_BASEURL;
        //set up runtime variables
        $this->mypath = str_replace('\\', '/', dirname(__FILE__));
        $this->mylog = $this->mypath."/../var/poller.txt";
        $this->httplog = $this->mypath."/../var/http_errors.txt";
        $this->mycache = $this->mypath."/../var";
        $this->mytmp = $this->mypath."/../tmp";
        $this->USER_AGENT = "LMeve/2.0 ESI Poller Version/" . ESI::$VERSION;
        $this->DATASOURCE = getConfigItem('ESIdatasource', 'tranquility');
        $this->DEBUG = getConfigItem('ESIdebug', 'enabled') == 'enabled' ? TRUE : FALSE;
        
        
        //set up ESI URL
        if (!isset($ESI_BASEURL)) {
            warning('ESI','$ESI_BASEURL isn\'t set in config.php. Using default ESI API URL https://esi.evetech.net');
            $this->ESI_BASEURL = "https://esi.evetech.net";
        }  else {
            $this->ESI_BASEURL = $ESI_BASEURL;
        }
        
        $this->tokenID = $tokenID;
        
        if (!is_null($tokenID)) {
            //Instantiate routes here
            $this->getAccessToken();
        }
        $this->instantiateAll();
    }
    
    PUBLIC function setRefreshToken($refresh_token) {
        $this->refresh_token = $refresh_token;
        $this->getAccessToken();
        $this->instantiateAll();
    }
    
    /**
     * Initialize instances for all routes
     */
    private function instantiateAll() {
        $this->Corporations = new Corporations($this);
        $this->MemberTracking = new MemberTracking($this);
        $this->Characters = new Characters($this);
        $this->Stations = new Stations($this);
        $this->Facilities = new Facilities($this);
        $this->Industry = new Industry($this);
        $this->Markets = new Markets($this);
        $this->Universe = new Universe($this);
        $this->Contracts = new Contracts($this);
        $this->Wallet = new Wallet($this);
        $this->Assets = new Assets($this);
        $this->Killmails = new Killmails($this);
        $this->Status = new Status($this);
    }
    
    /**
     * Update private corporation data for all routes
     */
    public function updateAll() {
        $this->Corporations->update();
        $this->MemberTracking->update();
        $this->Facilities->update();
        $this->Industry->update();
        $this->Markets->update();
        $this->Contracts->update();
        $this->Wallet->update();
        $this->Assets->update();
        $this->Killmails->update();
    }
    
    /**
     * Update all public information
     */
    public function updatePublic() {
        $this->Industry->updateSystemIndices();
        $this->Markets->updatePublic();
    }
    
    /**
     * Get the ESI refresh token
     * 
     * @return mixed returns refresh token string or boolean FALSE
     */
    private function getRefreshToken() {
        if (!empty($this->refresh_token)) {
            return $this->refresh_token;
        } else {
            $api_keys=db_asocquery("SELECT * FROM cfgesitoken WHERE `tokenID` = '$this->tokenID';");
            if (count($api_keys) == 1) {
                if (isset($api_keys[0]['token'])) {
                    $this->refresh_token = $api_keys[0]['token'];
                    return $this->refresh_token;
                }
            }
        }
	return FALSE;
    }
    
    /**
     * Get an ESI access token
     * 
     * @return mixed returns a valid access token string or boolean FALS
     */
    public function getAccessToken() {
        if (is_null($this->tokenID) || empty($this->tokenID)) return FALSE;
        if (!empty($this->access_token) && ($this->access_token_expire > time())) {
            return $this->access_token;
        } else {
            //first, obtain auth token using saved refresh_token
            $token = get_access_token($this->getRefreshToken());
            //check if we've got a valid Bearer token
            if ($this->DEBUG) var_dump($token);
            if (!(isset($token->access_token) && isset($token->token_type) && isset($token->expires_in) && $token->token_type=='Bearer' && $token->expires_in>0)) {
                //problem with token, bail!
                warning("ESI","EVE SSO: Invalid Bearer token received from SSO login site.");
                return FALSE;
            }
            //we've got a valid token
            $this->access_token = $token->access_token;
            $this->access_token_expire = time() + $token->expires_in;
            //let's fetch the characterID
            $verify=verify_token($token);
            //check if required fileds are set
            if (!(isset($verify->CharacterID) && isset($verify->CharacterName) && isset($verify->TokenType))) {
                //problem with verify, bail!
                warning("ESI","EVE SSO: Invalid Verify response received from SSO login site.");
                return FALSE;
            }
            if ($this->DEBUG) var_dump($verify);
            //we have characterID
            $this->characterID = $verify->CharacterID;
        }
        return $this->access_token;
    }
    
    public function getCharacterID() {
        return $this->characterID;
    }

    public function getCorporationID() {
        if (is_null($this->corporationID)) $this->Corporations->update();
        return $this->corporationID;
    }

    public function setCorporationID($corporationID) {
        $this->corporationID = $corporationID;
    }
    
    public function getESI_BASEURL() {
        return $this->ESI_BASEURL;
    }

    public function getTokenID() {
        return $this->tokenID;
    }

    public function getMAX_ERRORS() {
        return $this->MAX_ERRORS;
    }

    public function getHttplog() {
        return $this->httplog;
    }

    public function getDEBUG() {
        return $this->DEBUG;
    }

    public function setDEBUG($DEBUG) {
        $this->DEBUG = $DEBUG;
    }

    public function getMycache() {
        return $this->mycache;
    }

    public function getUSER_AGENT() {
        return $this->USER_AGENT;
    }

    public function getEsiErrorLimitRemain() {
        return $this->EsiErrorLimitRemain;
    }

    public function setXEsiErrorLimitRemain($EsiErrorLimitRemain) {
        $this->EsiErrorLimitRemain = $EsiErrorLimitRemain;
        if ($EsiErrorLimitRemain == 0) {
            $e = new Exception("ESI error limit exhausted!");
            throw $e;
        }
    }

    public function setXEsiErrorLimitReset($EsiErrorLimitReset) {
        $this->EsiErrorLimitReset = $EsiErrorLimitReset;
    }
    
    public function getDatasource() {
        return $this->DATASOURCE;
    }

    public function setDatasource($ds) {
        $this->DATASOURCE=$ds;
    }

}
