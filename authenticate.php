<?php

    //require_once (dirname(dirname(dirname(__FILE__))) . "/engine/start.php");
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/Client.php');
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/GrantType/IGrantType.php');
    require_once (dirname(__FILE__) .'/vendor/PHP-OAuth2/GrantType/AuthorizationCode.php');

    $CLIENT_ID     = elgg_get_plugin_setting('client_id', 'wordpress_oauth2');
    $CLIENT_SECRET = elgg_get_plugin_setting('client_secret', 'wordpress_oauth2');

        $state = md5(elgg_get_site_url() . dirname(__FILE__));

    $REDIRECT_URI           = elgg_get_site_url() . 'wordpress/';
    if (($friend_guid = get_input('friend_guid')) && ($invitecode = get_input('invitecode')))
        $REDIRECT_URI .= "$friend_guid/$invitecode/";
    
    $AUTHORIZATION_ENDPOINT = 'https://public-api.wordpress.com/oauth2/authorize';
    $TOKEN_ENDPOINT         = 'https://public-api.wordpress.com/oauth2/token';

    $client = new OAuth2\Client($CLIENT_ID, $CLIENT_SECRET);
    
    if (!get_input('code'))
    {
        $params = array('response_type' => 'code', 'client_id' => $CLIENT_ID);
        
        $auth_url = $client->getAuthenticationUrl($AUTHORIZATION_ENDPOINT, $REDIRECT_URI, $params);
        header('Location: ' . $auth_url);
        
        die('Redirect');
    }
    else
    {
        $params = array('code' => $_GET['code'], 'redirect_uri' => $REDIRECT_URI, 'client_secret' => $CLIENT_SECRET);
        $response = $client->getAccessToken($TOKEN_ENDPOINT, 'authorization_code', $params);
    
        $access_token = $response['result']['access_token'];
    
        $client->setAccessToken($access_token);
    
mail('marcus@dushka.co.uk', 'Wordpress login', print_r($response, true));

        $profile = $response['result'];

        if ((!$profile) || (!$profile['blog_id'])) {register_error('Invalid response from server, try again in a bit.'); forward();}
        
        $ia = elgg_set_ignore_access(); // Ensure we get disabled users as well.
        $users = elgg_get_entities_from_metadata(array(
            'types' => 'user',
            'metadata_name_value_pairs' => array('name' => 'wordpress_id', 'value' => $profile['blog_id']),
            'limit' => 1
        ));
        elgg_set_ignore_access($ia);
        
        if ($users) {
	    $user = $users[0];
	                
            try {
                if (elgg_trigger_plugin_hook('wordpress_oauth2', 'user', array(
                    'oauth_client' =>$client,
                    'user' => $user,
                    'profile' => $profile,
                    'oauth_access_token' => $access_token
                ), true))
                    login($user);
            } catch(Exception $e) {
                register_error($e->getMessage());
            }
        }
	else
        {
            // New user
            
            $password = generate_random_cleartext_password();
            $username = "wordpress_user_{$profile['blog_id']}"; //strtolower(preg_replace("/[^a-zA-Z0-9\s]/", "", $profile['firstName'] . $profile['lastName']));
            
            if (get_user_by_username($username)) {
                $n = 1;
                while (get_user_by_username($username . $n)) {$n++;}
                $username = $username . $n; 
            }
            
            $user = new ElggUser();
            $user->subtype = 'wordpress';
            $user->username = $username;
            $user->name = $profile['blog_url']; // TODO, nicer name?
            //$user->email = $email;
            $user->access_id = ACCESS_PUBLIC;
            $user->salt = generate_random_cleartext_password();
            $user->password = generate_user_password($user, $password);
            $user->owner_guid = 0;
            $user->container_guid = 0;
            
            $user->save();
            
            
            $user->wordpress_id = $profile['blog_id'];        
            $user->wordpress_url  = $profile['blog_url'];
            
            // Wordpress validates emails, so we're going to assume they did a good job.
            elgg_set_user_validation_status($user->guid, true, 'wordpress_oauth2');   
            
            // Trigger register hook
            $params = array(
                'user' => $user,
                'password' => $password,
                'friend_guid' => get_input('friend_guid'),
                'invitecode' => get_input('invitecode'),
            );
            if (!elgg_trigger_plugin_hook('register', 'user', $params, TRUE)) {
                    $user->delete();
                    throw new RegistrationException(elgg_echo('registerbad'));
            }
            
            // If $friend_guid has been set, make mutual friends
            if ($friend_guid = get_input('friend_guid')) {
                    if ($friend_user = get_user($friend_guid)) {
                            if (get_input('invitecode') == generate_invite_code($friend_user->username)) {
                                    $user->addFriend($friend_guid);
                                    $friend_user->addFriend($user->guid);

                                    add_to_river('river/relationship/friend/create', 'friend', $user->getGUID(), $friend_guid);
                                    add_to_river('river/relationship/friend/create', 'friend', $friend_guid, $user->getGUID());
                            }
                    }
            }
            
            try {
                if (elgg_trigger_plugin_hook('wordpress_oauth2', 'user', array(
                    'user' => $user,
                    'profile' => $profile,
                    'oauth_client' =>$client,
                    'oauth_access_token' => $access_token
                ), true)) 
                        login($user);
            } catch(Exception $e) {
                register_error($e->getMessage());
            }
        }

        ?>
<html>
		<head>
		<script>
			function init() {
				window.opener.location.reload();
				window.close();
			}
		</script>
		</head>
		<body onload="init();">
		</body>
		</html>
                <?php

    }

