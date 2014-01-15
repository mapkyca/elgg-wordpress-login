<p>
    <label>
        Wordpress Client ID:
        <?php echo elgg_view('input/text', array('name' => 'params[client_id]', 'value' => elgg_get_plugin_setting('client_id', 'elgg-wordpress-login')));?>
    </label>
</p>
    
<p>
    <label>
        Wordpress Client Secret:
        <?php echo elgg_view('input/text', array('name' => 'params[client_secret]', 'value' => elgg_get_plugin_setting('client_secret', 'elgg-wordpress-login')));?>
    </label>
</p>
    


