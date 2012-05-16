<?php

class BrowserIDVerifier {

    private $_verifyUri;

    public function __construct($verifyUri = "https://browserid.org/verify") {
        $this->_verifyUri = $verifyUri;        
        session_start();
    }

    public function authenticate() {
        if(array_key_exists("browser_id", $_SESSION)) {
            if($_SESSION["browser_id"]['status'] === 'okay') {
                return $_SESSION["browser_id"]['email'];
            }
        } else {
            $_SESSION["return_uri"] = self::getCallUri();
            header("Location: " . self::getAuthUri());
            exit;
        }
    }

    public function linkback() {
        if("POST" !== $_SERVER['REQUEST_METHOD']) {
            // only POST is allowed
            header("HTTP/1.0 405 Method Not Allowed");
            exit;
        }

        if(!array_key_exists("return_uri", $_SESSION)) {
            // need to use the API
            header("HTTP/1.0 400 Bad Request");
        }

        if(!array_key_exists("browser_id", $_SESSION)) {
            $params = 'assertion=' . $_POST['assertion'] . '&audience=' . urlencode(self::getAudience());
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->_verifyUri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $result = json_decode(curl_exec($ch), TRUE);
            curl_close($ch);
            $result["return_uri"] = $_SESSION["return_uri"];
            if($result['status'] === "okay") {
                $_SESSION["browser_id"] = $result;
            }
        }
        header("Content-Type: application/json");
        echo json_encode($_SESSION["browser_id"]);
    }

    // HELPER FUNCTIONS TO DETERMINE URLs

    public static function getAudience() {
        return $_SERVER['SERVER_NAME'];
    }

    public static function getUri() {
        $scheme = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';
        $host = $_SERVER['SERVER_NAME'];
        $port = (int)$_SERVER['SERVER_PORT'];
        $uri = $scheme . "://" . $host;
        if(($scheme === "http" && $port !== 80) || ($scheme === "https" && $port !== 443)) {
            $uri .= ":" . $port;
        }
        return $uri;
    }

    public static function getCallUri() {
        return self::getUri() . $_SERVER['REQUEST_URI'];
    }

    public static function getAuthUri() {
        $pathInfo = substr(dirname(__DIR__), strlen($_SERVER['DOCUMENT_ROOT']));
        if (strpos($pathInfo, '?') !== FALSE) {
            $pathInfo = substr_replace($pathInfo, '', strpos($pathInfo, '?'));
        }
        $pathInfo = '/' . ltrim($pathInfo, '/');
        return self::getUri() . $pathInfo . "/auth.html";
    }

}
?>
