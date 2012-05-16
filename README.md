# BrowserID Authentication

This project is a completely separate BrowserID verifier that lives outside
your application and is controlled through a PHP API.

The API can be used to retrieve a verified email address.

To use from your application:

    <?php
        require_once "/path/to/php-browserid/lib/BrowserIDVerifier.php";

        $auth = new BrowserIDVerifier();
        $email = $auth->authenticate();

        echo $email;
    ?>

That's all! The library will take care of the redirects required
and verifying the BrowserID response.
