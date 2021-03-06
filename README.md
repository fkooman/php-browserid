# Mozilla Persona Authentication

This project is a completely separate Mozilla Persona verifier that lives outside
your application and is controlled through a PHP API.

The API can be used to retrieve a verified email address.

To use from your application:

    <?php
        require_once "/path/to/php-browserid/lib/PersonaVerifier.php";

	    $auth = new PersonaVerifier();
	    try { 
            $email = $auth->authenticate();
            echo $email;
        } catch (PersonaException $e) {
            die($e->getMessage());
        }
    ?>

If you want to require a specific email address the authenticate call can be
given an optional parameter:

    $email = $auth->authenticate("user@example.org");

This will preselect the email address and require the user to use this address.

That's all! The library will take care of the redirects required and verifying 
the Mozilla Persona response.
