<?php

elgg_register_event_handler('init', 'system', function () {

    // Walled garden bypasss
    elgg_register_plugin_hook_handler('public_pages', 'walled_garden', function($hook, $type, $return_value, $params) {
        // add to the current list of public pages that should be available from the walled garden
        $return_value[] = 'wordpress';
        $return_value[] = 'wordpress/Redirect';
        $return_value[] = 'wordpress/.*/.*';


        // return the modified value
        return $return_value;
    });

    // Authentication page handler
    elgg_register_page_handler('wordpress', function($pages) {

        set_input('friend_guid', $pages[0]);
        set_input('invitecode', $pages[1]);

        require_once(dirname(__FILE__) . '/authenticate.php');

        return true;
    });
});
