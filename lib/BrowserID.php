<?php

class BrowserID {

    public function __construct() {
        session_start();
    }

    public function authenticate() {
        if(array_key_exists("browserid", $_SESSION)) {
            if($_SESSION['browserid']['status'] === 'okay') {
                return $_SESSION['browserid']['email'];
            }
        } else {
            // need to store the current URI
            $_SESSION['return_uri'] = self::selfURL();
            header("Location: https://frkosp.wind.surfnet.nl/browserid/auth.html");
            die();
        }
    }

    public function linkback() {
        if("POST" !== $_SERVER['REQUEST_METHOD']) {
            die("Request Method should by POST and not " . $_SERVER['REQUEST_METHOD']);
        }

	if(!array_key_exists('return_uri', $_SESSION)) {
		die("not called through API");
	}

	    if(!array_key_exists('browserid', $_SESSION)) { // || $_SESSION['browser']['expires'] < time()) {
	        $url = 'https://browserid.org/verify';
	        $params = 'assertion=' . $_POST['assertion'] . '&audience=' . urlencode('https://frkosp.wind.surfnet.nl');
	        $ch = curl_init();
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_POST, 2);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	        $result = json_decode(curl_exec($ch), TRUE);
	        curl_close($ch);
		$result['return_uri'] = $_SESSION['return_uri'];
	        if($result['status'] === "okay") {
	            $_SESSION['browserid'] = $result;
	        }
	    }
	    header("Content-Type: application/json");
	    echo json_encode($_SESSION['browserid']);
    }

    private static function selfURL() { 
        if(!isset($_SERVER['REQUEST_URI'])){ 
            $serverrequri = $_SERVER['PHP_SELF']; 
        } else{ 
            $serverrequri = $_SERVER['REQUEST_URI']; 
        } 
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
        $protocol = "http" . $s; 
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
        return $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri; 
    }

}
?>
