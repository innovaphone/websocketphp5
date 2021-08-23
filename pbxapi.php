<?php

/*
 * sample code to access PBX APIs
 * (c) innovaphone AG 2020
 */

require_once './classes/websocket.class.php';
print "<pre>";

// turn on log output
AppPlatform\Log::logon();

// turn off all log messages
AppPlatform\Log::setLogLevel("", "", false);
// turn on log messages of some major categories for all sources
AppPlatform\Log::setLogLevel("", array("error", "runtime"), true);
// if you want to see a message trace, uncomment next line
AppPlatform\Log::setLogLevel("", array("smsg", /* "debug" */), true);
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

$app = new PbxAppLoginAutomaton("sindelfingen.sample.dom", new AppPlatform\AppServiceCredentials("pbxadminapi", "ip411"));
$app->run();


// the class utilizes the PbxApi
class PbxApiSample extends AppPlatform\FinitStateAutomaton {

    public function ReceiveInitialStart(\AppPlatform\Message $msg) {
        $this->sendMessage(new AppPlatform\Message("GetStun", "api", "PbxAdminApi"));
    }

    public function ReceiveInitialGetStunResult(\AppPlatform\Message $msg) {
        return "Dead";
    }

}

// AppPlatform\Log::setLogLevel("", "debug", true);
$pbxapi = new PbxApiSample($app->getWs(), "PBX");
$pbxapi->run();

