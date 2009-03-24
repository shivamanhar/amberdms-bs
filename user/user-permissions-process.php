<?php
/*
	user/user-permissions-process.php

	Access: admin users only

	Updates a user account's permissions.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('admin'))
{
	////// INPUT PROCESSING ////////////////////////

	$id				= security_form_input_predefined("int", "id_user", 1, "");
	
	// convert all the permissions input
	$permissions = array();

	$sql_perms_obj		= New sql_query;
	$sql_perms_obj->string	= "SELECT * FROM `permissions` ORDER BY value";
	$sql_perms_obj->fetch_array();

	foreach ($sql_perms_obj->data as $data_sql)
	{
		$permissions[ $data_sql["value"] ] = security_form_input_predefined("any", $data_sql["value"], 0, "Form provided invalid input!");
	}

	

	///// ERROR CHECKING ///////////////////////
	

	// make sure the user actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `users` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The user you have attempted to edit - $id - does not exist in this system.");
	}



	//// PROCESS DATA ////////////////////////////


	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["user_permissions"] = "failed";
		header("Location: ../index.php?page=user/user-permissions.php");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();

		/*
			UPDATE THE PERMISSIONS
		
			This takes quite a few SQL calls, as we need to remove old permissions
			and add new ones on a one-by-one basis.

			TODO: This code could be optimised to be a bit more efficent with it's SQL queries.
		*/

		foreach ($sql_perms_obj->data as $data_perms)
		{
			// check if any current settings exist
			$sql_user_obj		= New sql_query;
			$sql_user_obj->string	= "SELECT id FROM `users_permissions` WHERE userid='$id' AND permid='" . $data_perms["id"] . "' LIMIT 1";
			$sql_user_obj->execute();

			if ($sql_user_obj->num_rows())
			{
				// user has this particular permission set

				// if the new setting is "off", delete the current setting.
				if ($permissions[ $data_perms["value"] ] != "on")
				{
					$sql_obj		= New sql_query;
					$sql_obj->string	= "DELETE FROM `users_permissions` WHERE userid='$id' AND permid='" . $data_perms["id"] . "' LIMIT 1";

					if (!$sql_obj->execute())
					{
						log_write("error", "process", "An error occured whilst trying to delete old permissions.");
					}

				}

				// if new setting is "on", we don't need todo anything.

			}
			else
			{	// no current setting exists

				// if the new setting is "on", insert a new setting
				if ($permissions[ $data_perms["value"] ] == "on")
				{
					$sql_obj		= New sql_query;
					$sql_obj->string	= "INSERT INTO `users_permissions` (userid, permid) VALUES ('$id', '" . $data_perms["id"] . "')";

					if (!$sql_obj->execute())
					{
						log_write("error", "process", "An error occured whilst attempting to create new permissions.");
					}
				}

				// if new setting is "off", we don't need todo anything.
			}
			
		} // end of while


		// done
		$_SESSION["notification"]["message"][] = "User permissions have been updated, and are active immediately.";
		journal_quickadd_event("users", $id, "Adjusted user permissions.");

		// goto view page
		header("Location: ../index.php?page=user/user-permissions.php&id=$id");
		exit(0);


	} // if valid data input
	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
