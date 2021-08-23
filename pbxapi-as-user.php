<?php

/*
 * sample code to access the Users and Devices service on an App platform using PHP
 * (c) innovaphone AG 2020
 * @author ckl
 */

// pbx data, you can override it by placing similar next 4 lines into file my-pbx-data.php
$pbxdns = "sindelfingen.sample.dom";
$pbxuser = "ckl";
$pbxpw = "pwd";
$pbxapp = "pbxadminapi";
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
AppPlatform\Log::setLogLevel("", "smsg", true);
// turn on all catgeories for the calling script
AppPlatform\Log::setLogLevel("script", "", true);

// login to PBX and devices and users
$connector = new AppPlatform\AppServiceLogin(
        $pbxdns, new AppPlatform\AppUserCredentials($pbxuser, $pbxpw), array(
    $apispec = new AppPlatform\AppServiceSpec("websocket", "APPS", "PBX0"),
        ),
        true
);
$connector->connect();

// look at the PBX login
if ($connector->getPbxA()->getIsLoggedIn()) {
    AppPlatform\Log::log("Logged in to PBX (user $pbxuser)");
    $apiws = $connector->getPbxWS();
    $pbxloginresult = ($connector->getPbxA()->getResults());
} else {
    AppPlatform\Log::log("Failed to log in to PBX (user $pbxuser)");
    exit;
}

// look at the PBX login
if ($connector->getAppAutomaton($apispec)->getIsLoggedIn()) {
    AppPlatform\Log::log("Logged in to API");
    $apiws = $connector->getAppAutomaton($apispec)->getWs();
} else {
    AppPlatform\Log::log("Failed to log in to API ($pbxapp)");
    exit;
}

// now we have the authenticated websockets to our AppServices, so we can release the connector
$connector = null;

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
$pbxapi = new PbxApiSample($apiws, "PBX");
$pbxapi->run();