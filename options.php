<?php

$fields = array(
    'slack-token' => array(
        'label' => 'Slack Token',
        'render' => function() {
            $val = esc_html(get_option('slack-token'));
            print "<input class=\"regular-text\" type=\"text\" name=\"slack-token\" value=\"{$val}\" />";
        }
    ),
    'allowed-members' => array(
        'label' => 'Allowed Members',
        'render' => function() {
            $val = esc_html(get_option('allowed-members'));
            $textarea = "<textarea cols=\"40\" rows=\"10\" name=\"allowed-members\">{$val}</textarea>";
            $desc = "<br><small class=\"description\">List of Slack members who have permission to use <pre>/linkit</pre>. One per line.</small>";
            print $textarea . $desc;
        }    
    ),
    'default-author' => array(
        'label' => 'Default Author',
        'render' => function() {
            $val = esc_html(get_option('default-author', DEFAULT_AUTHOR));
            print "<input class=\"regular-text\" type=\"text\" name=\"default-author\" value=\"{$val}\" />";
        }
    ),
    'default-category' => array(
        'label' => 'Default Category',
        'render' => function() {
            $val = esc_html(get_option('default-category', DEFAULT_CATEGORY));
            print "<input class=\"regular-text\" type=\"text\" name=\"default-category\" value=\"{$val}\" />";
        }
    ),
    'phantomjs-path' => array(
        'label' => 'PhatomJS Path',
        'render' => function() {
            $val = esc_html(get_option("phantomjs-path", PHANTOMJS_PATH));
            $input = "<input class=\"regular-text\" type=\"text\" name=\"phantomjs-path\" value=\"{$val}\" />";
            $desc = "<br><small class=\"description\">Always include a trailing slash /</small>";
            print $input . $desc;
        }
    ),
);


function linkit_create_menu() {
    
    global $fields;

    // Register custom settings page
    add_options_page(PLUGIN_NAME . ' Settings', PLUGIN_NAME . ' Settings', 'manage_options', SETTINGS, 'linkit_options_page');
    
    // Register settings
    foreach($fields as $key => $val) {
        register_setting(SETTINGS_GROUP, $key);
    }

    // Create configuration section
    add_settings_section(SETTINGS_SECTION, 'Configuration', 'linkit_settings_options', SETTINGS);
}

/*
 * This function is called by add_settings_section when the section is created. This method will add the
 * settings fields for the given section.
 */
function linkit_settings_options() {

    global $fields;
    
    foreach($fields as $key => $val) {
        add_settings_field($key, $val['label'], $val['render'], SETTINGS, SETTINGS_SECTION);
    }  
};


/*
 * Renders the HTML for the LinkIt settings page in the Wordpress admin settings.
 */
function linkit_options_page() { ?>
    
    <style>
        #instructions { margin: 10px 0; }
        pre { color: maroon; display: inline; }
    </style>

    <div class="wrap">
        <h1>Slack LinkIt Settings</h1>
    
        <div id="instructions"><strong>Important:</strong> For this plugin to work you must create a <i>slash command</i>. Navigate to your team's custom integrations to configure the slash command in order to get a token. You can find this page at: <pre>https://[YOUR-TEAM-NAME].slack.com/apps/manage/custom-integrations</pre>. For full installation instructions <a href="https://github.com/jamesthehacker/slack-linkit">click here</a>.</div>

        <form method="POST" enctype="multipart/form-data" action="options.php">    
            <?php do_settings_sections(SETTINGS); ?>
            <?php settings_fields(SETTINGS_GROUP); ?>
            <div><input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></div>
        </form>
    </div>
<?php } 

add_action('admin_menu', 'linkit_create_menu');
