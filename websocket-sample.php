<?php

require_once './classes/websocket.class.php';
print "<pre>";

// turn on log output
AppPlatform\Log::logon();

// turn off all log messages
AppPlatform\Log::setLogLevel("", "", false);
// turn on log messages of some major categories for all sources
AppPlatform\Log::setLogLevel("", array("error", "runtime"), true);
// if you want to see a message trace, uncomment next line
AppPlatform\Log::setLogLevel("", "smsg", true);
// turn on all catgeories for the calling script
AppPlatform\Log::setLogLevel("script", "", true);

// login to PBX and devices and users
$connector = new AppPlatform\AppServiceLogin(
        "sindelfingen.sample.dom", new AppPlatform\AppUserCredentials("ckl", "pwd"), array(
    $devicesspec = new AppPlatform\AppServiceSpec("innovaphone-devices"),
    $usersspec = new AppPlatform\AppServiceSpec("innovaphone-users"),
        ),
        true
);
$connector->connect();

// look at the PBX login
if ($connector->getPbxA()->getIsLoggedIn()) {
    AppPlatform\Log::log("Logged in to PBX");
    $pbxws = $connector->getPbxWS();
    $pbxloginresult = ($connector->getPbxA()->getResults());
} else {
    AppPlatform\Log::log("Failed to log in to PBX");
    exit;
}

// look at devices
if ($connector->getAppAutomaton($devicesspec)->getIsLoggedIn()) {
    AppPlatform\Log::log("Logged in to Devices");
    $devicesws = $connector->getAppAutomaton($devicesspec)->getWs();
} else {
    AppPlatform\Log::log("Failed to log in to Devices");
    exit;
}

// look at users
if ($connector->getAppAutomaton($usersspec)->getIsLoggedIn()) {

    AppPlatform\Log::log("Logged in to Users");
    $usersws = $connector->getAppAutomaton($usersspec)->getWs();
} else {
    AppPlatform\Log::log("Failed to log in to Users");
    exit;
}

// now we have the authenticated websockets to our AppServices, so we can release the connector
$connector = null;

// an automaton which lists all devices in Devices
class DeviceLister extends AppPlatform\FinitStateAutomaton {

    protected $devices = array();

    public function getDevices() {
        return $this->devices;
    }

    public function ReceiveInitialStart(\AppPlatform\Message $msg) {
        AppPlatform\Log::log("Requesting Device List");
        $this->sendMessage(new AppPlatform\Message("GetDevices"));
    }

    public function ReceiveInitialGetDevicesResult(\AppPlatform\Message $msg) {
        AppPlatform\Log::log("got " . count($msg->devices) . " Device Info(s)");
        $this->devices = array_merge($this->devices, $msg->devices);
        if (isset($msg->last) && $msg->last) {
            AppPlatform\Log::log("Last chunk");
            return "Dead";
        }
    }

}

// an automaton which lists all visible users
class UserLister extends AppPlatform\FinitStateAutomaton {

    protected $users = array();
    protected $myInfo = null;

    public function __construct($ws, AppPlatform\Message $myInfo, $nickname = null) {
        parent::__construct($ws, $nickname);
        $this->myInfo = $myInfo;
    }

    public function getUsers() {
        return $this->users;
    }

    /**
     * @var AppPlatform\Message
     */
    protected $numusers;

    public function ReceiveInitialStart(\AppPlatform\Message $msg) {
        AppPlatform\Log::log("Requesting User List");
        $me = $this->myInfo->info->user;
        $this->sendMessage(new AppPlatform\Message("NumUsers", "user", $me->sip, "domain", "@{$me->domain}", "visible", true, "filter", "%"));
    }

    private function requestUser() {
        $me = $this->myInfo->info->user;
        if (count($this->users) < $this->numusers->numUsers) {
            $this->sendMessage(new AppPlatform\Message("ReadUser", "username", "", "user", $me->sip, "domain", "@{$me->domain}", "offset", count($this->users), "filter", "%"));
        } else
            return false;
        return true;
    }

    public function ReceiveInitialNumUsersResult(\AppPlatform\Message $msg) {
        // {"mt":"ReadUser","username":"","user":"ckl","domain":"@sample.dom","offset":0,"filter":"%","src":"users"}
        $this->numusers = $msg;
        $this->requestUser();
    }

    public function ReceiveInitialReadUserInfo(\AppPlatform\Message $msg) {
        AppPlatform\Log::log("got User Info");
        $this->users[] = $msg;
    }

    public function ReceiveInitialReadUserResult(\AppPlatform\Message $msg) {
        AppPlatform\Log::log("got End of User Info");
        return "Dead";
    }

}

// get the info 
// AppPlatform\Log::setLogLevel("", "", true);
$dl = new DeviceLister($devicesws);
$ul = new UserLister($usersws, $pbxloginresult->loginResultMsg);
$t = new AppPlatform\Transitioner($dl, $ul);
$t->run();

// show results
var_dump($dl->getDevices());
var_dump($ul->getUsers());
