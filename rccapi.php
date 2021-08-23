<?php

/*
 * sample code to access PBX RCC API
 * (c) innovaphone AG 2020
 */
date_default_timezone_set("Europe/Berlin");

// pbx data, you can override it by placing similar next 4 lines into file my-pbx-data.php
$pbxdns = "sindelfingen.sample.dom";
$pbxpw = "ip411";
$pbxapp = "rccapi";
// end of local pbx data
@include 'my-pbx-data.php';

require_once './classes/websocket.class.php';
print "<pre>";

// turn on log output
AppPlatform\Log::logon();

// turn off all log messages
AppPlatform\Log::setLogLevel("", "", false);
// turn on log messages of some major categories for all sources
AppPlatform\Log::setLogLevel("", array("error", "runtime"), true);
// if you want to see a message trace, uncomment next line
AppPlatform\Log::setLogLevel("", array("smsg", "debug"), true);
// turn on all catgeories for the calling script
AppPlatform\Log::setLogLevel("script", "", true);


/*
 * authenticate towards the PBX as an App service
 */
class PbxAppLoginAutomaton extends AppPlatform\AppLoginAutomaton {

    /**
     * @var string PBX IP address
     */
    protected $pbxUrl;

    /**
     * @var \WebSocket\WSClient websocket to PBX
     */
    protected $pbxWS;

    function __construct($pbx, AppPlatform\AppServiceCredentials $cred, $useWS = false) {
        $this->pbxUrl = (strpos($pbx, "s://") !== false) ? $pbx :
                $this->pbxUrl = ($useWS ? "ws" : "wss") . "://$pbx/PBX0/APPS/websocket";
        // create websocket towards the well known PBX URI
        $this->pbxWS = new AppPlatform\WSClient("PBXWS", $this->pbxUrl);
        parent::__construct($this->pbxWS, $cred);
    }

}

/*
use the PbxAdminApi at PBX sindelfingen.sample.dom
There must be an App object with name "pbxadminapi" and password "ip411"
it needs to have the "Admin" and "PbxApi" flags set
*/

$app = new PbxAppLoginAutomaton($pbxdns, new AppPlatform\AppServiceCredentials($pbxapp, $pbxpw));
$app->run();


// the class utilizes the PbxApi
class PbxApiSample extends AppPlatform\FinitStateAutomaton {

    public function ReceiveInitialStart(\AppPlatform\Message $msg) {
        $this->sendMessage(new AppPlatform\Message("Initialize", "api", "RCC"));
    }

    public function ReceiveInitialUserInfo(\AppPlatform\Message $msg) {
        $this->log("UserInfo");
    }
    
    public function ReceiveInitialInitializeResult(\AppPlatform\Message $msg) {
        $this->log("InitializeResult");
        return "Dead";
    }

}

// AppPlatform\Log::setLogLevel("", "debug", true);
$pbxapi = new PbxApiSample($app->getWs(), "PBX");
$pbxapi->run();

