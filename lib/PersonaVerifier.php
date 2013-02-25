<?php

class PersonaException extends Exception
{
}

class PersonaVerifier
{
    private $_verifyUri;

    public function __construct($verifyUri = "https://verifier.login.persona.org/verify")
    {
        $this->_verifyUri = $verifyUri;
        session_start();
    }

    public function authenticate($requiredEmail = NULL)
    {
        if (array_key_exists("persona", $_SESSION) && array_key_exists("return_uri", $_SESSION)) {
            // there was already an attempt at authentication...
            if ($_SESSION["persona"]['status'] === 'failure') {
                // but it didn't succeed
                $error = $_SESSION['persona']['reason'];
                unset($_SESSION['persona']);
                unset($_SESSION['return_uri']);
                throw new PersonaException("authentication failure: $error");
            }
            // authentication succeeded
            if (NULL !== $requiredEmail && strcasecmp($requiredEmail, $_SESSION['persona']['email']) !== 0) {
                // but someone authenticated with an unexpected address
                unset($_SESSION['persona']);
                unset($_SESSION['return_uri']);
                throw new PersonaException("authentication failure: wrong address");
            }
            // FIXME: make sure it didn't expire!
            return $_SESSION["persona"]['email'];
        } else {
            // no previous authentication attempt
            $_SESSION["return_uri"] = self::getCallUri();
            $authUri = self::getAuthUri();
            if (NULL !== $requiredEmail) {
                $authUri .= "#required_email=" . $requiredEmail;
            }
            header("Location: " . $authUri);
            exit;
        }
    }

    public function linkback()
    {
        if ("POST" !== $_SERVER['REQUEST_METHOD']) {
            // only POST is allowed
            header("HTTP/1.0 405 Method Not Allowed");
            exit;
        }

        if (!array_key_exists("return_uri", $_SESSION)) {
            // need to use the API
            header("HTTP/1.0 400 Bad Request");
        }

        if (!array_key_exists("persona", $_SESSION)) {
            $params = 'assertion=' . $_POST['assertion'] . '&audience=' . urlencode(self::getAudience());
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->_verifyUri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            $result = json_decode(curl_exec($ch), TRUE);
            // FIXME: deal with cURL errors
            curl_close($ch);
            $_SESSION["persona"] = array_merge($result, array("return_uri" => $_SESSION['return_uri']));
        }
        header("Content-Type: application/json");
        echo json_encode($_SESSION["persona"]);
    }

    // HELPER FUNCTIONS TO DETERMINE URLs
    public static function getAudience()
    {
        return $_SERVER['SERVER_NAME'];
    }

    public static function getUri()
    {
        $scheme = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';
        $host = $_SERVER['SERVER_NAME'];
        $port = (int) $_SERVER['SERVER_PORT'];
        $uri = $scheme . "://" . $host;
        if (($scheme === "http" && $port !== 80) || ($scheme === "https" && $port !== 443)) {
            $uri .= ":" . $port;
        }

        return $uri;
    }

    public static function getCallUri()
    {
        return self::getUri() . $_SERVER['REQUEST_URI'];
    }

    public static function getAuthUri()
    {
        $pathInfo = substr(dirname(__DIR__), strlen($_SERVER['DOCUMENT_ROOT']));
        if (strpos($pathInfo, '?') !== FALSE) {
            $pathInfo = substr_replace($pathInfo, '', strpos($pathInfo, '?'));
        }
        $pathInfo = '/' . ltrim($pathInfo, '/');

        return self::getUri() . $pathInfo . "/auth.html";
    }

}
