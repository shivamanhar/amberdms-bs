<?php
//
// user/login-process.php
//
// logs the user in
//


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");

// erase any data - gets rid of stale errors and user sessions.
$_SESSION["error"] = array();
$_SESSION["user"] = array();


if (user_online())
{
	// user is already logged in!
	$_SESSION["error"]["message"][] = "You are already logged in!";
	$_SESSION["error"]["username_amberdms_bs"] = "error";
	$_SESSION["error"]["password_amberdms_bs"] = "error";

}
else
{
	// check & convert input
	$username	= security_form_input("/^[A-Za-z0-9.]*$/", "username_amberdms_bs", 4, "Please enter a username.");
	$password	= security_form_input("/^\S*$/", "password_amberdms_bs", 4, "Please enter a password.");


	// call the user functions to authenticate the user and handle blacklisting
	if (user_login($username, $password))
	{
		// login succeded

		// if user has been redirected to login from a previous page, lets take them to that page.
		if ($_SESSION["login"]["previouspage"])
		{	
			header("Location: ../index.php?" . $_SESSION["login"]["previouspage"] . "");
			$_SESSION["login"] = array();
			exit(0);
		}
		else
		{
			// no page? take them to home.
			header("Location: ../index.php?page=home.php");
			exit(0);
		}
	}
	else
	{
		// login failed

		// if no errors were set for other reasons (eg: the user forgetting to input any password at all)
		// then display the incorrect username/password error.
		if (!$_SESSION["error"]["message"])
		{
			$_SESSION["error"]["message"][] = "That username and/or password is invalid!";
			$_SESSION["error"]["username_amberdms_bs-error"] = 1;
			$_SESSION["error"]["password_amberdms_bs-error"] = 1;
		}
		
		// errors occured
		header("Location: ../index.php?page=user/login.php");
		exit(0);

	} // end of errors.
	
} // end of "is user logged in?"



?>
