<?php
/*
 * Plugin Name: Slack Linkit
 * Plugin URI: https://github.com/JamesTheHacker/slack-linkit
 * Version: 1.0
 * Author: James Jeffery <jameslovescode@gmail.com>
 * Description: Save links from Slack to Wordpress using the /linkit slash command.
 */

require __DIR__ . '/vendor/autoload.php';

use LinkPreview\LinkPreview;
use Screen\Capture;

/*
 * Configuration
 * TODO: Create admin options page to set these values.
 */
define('SLACK_TOKEN', '');
define('PHANTOM_PATH', '/usr/bin/');        // Include trailing slash!
define('DEFAULT_CATEGORY', 'Slack Links');  // Category to post all links under
define('DEFAULT_USER', 0);                  // The user ID of user to be listed as author for all links. Default: 0 (admin)
define('NO_PERMISSIONS_MESSAGE', 'You must be be registered to use LikeIt. Join #bot to request access.');

/*
 * Array of Slack members who can use the /linkit slash command
 */
$allowedUsers = array(
    'admin',
    'jamesthehacker' // I IZ IN ALL UR MASHINEZ ┌∩┐(◣_◢)┌∩┐
);

/*
 * When shit hits the fan pick a random joke from below ...
 */
$jokes = array(
    'Do you expect me to pull this out of my ass? That webpage does not exist!',
    'What is next to ecstasy? Pain. What is next to pain. Nothingness.',
    'We can regard our life as a uselessly disturbing episode in the blissful repose of nothingness.',
    'Absolute equals nothingness.',
    'Trump is an ass.',
    'Are you seriously trying to piss me off?',
    'Keep going. Time has no meaning to me, but your clock is ticking.'
);

/*
 * If everything goes to plan pick a random success message from below ...
 */
$successMessages = array(
    'Saved: ',
    'Saved ...',
    'Added: ',
    'Added ...'
);

/*
 * This function will take a screenshot of a webpage and save the image in the Wordpress uploads directory
 */
function screenshot($url, $savePath, $filename) {
    $screenCapture = new Capture($url);
    $screenCapture->setHeight(400);
    $screenCapture->output->setLocation($savePath);
    $screenCapture->binPath = PHANTOM_PATH;
    $screenCapture->save($filename);
    $screenCapture->jobs->clean();
}

/*
 * If the category exists return the category ID, otherwise create it and return the category ID.
 */
function categoryID($category) {
    if(term_exists($category))
        return get_cat_ID($category);
    else
        return wp_create_category($category);
}

/*
 * Pick a random joke and pass it to Slack
 */
function joke() {
    global $jokes; // Trust me, this makes me cringe too. In this context it's ok.
    return print($jokes[array_rand($jokes)]);
}

/*
 * Display responses in public
 */
function channelResponse($url, $text) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'payload=' . json_encode(array(
        "response_type" => "in_channel",
        "text" => $text,
    )));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

/*
 * When a user uses the /linkit slash command a POST request is sent from Slack to
 * the callback address you provided on Slack. This method handles that callback.
 * This method navigates to the link, grabs the title, description, and screenshot
 * from the website and stores it on Wordpress under the default category.
 *
 * Callback URL:    http://[YOUR_DOMAIN].com/wp-admin/admin-post.php?action=linkit
 * Usage on Slack:  /linkit https://en.wikipedia.org/wiki/The_Twilight_Zone
 */
function linkit() {

    global $allowedUsers, $successMessages;
    
    $savePath = wp_upload_dir()['path'];
    $filename = uniqid(rand(), true) . '.jpg';
    $wpUploadURL = wp_upload_dir()['url'] . '/' . $filename;
    $wpUploadDir = $savePath . '/' . $filename;

    if($_SERVER['REQUEST_METHOD'] == 'POST') { 

        // If the tokens do not match then absolutely diddly squat!
        if(!isset($_POST['token']) || $_POST['token'] != SLACK_TOKEN)
            return;

        // Check to see if user has permissions to use /linkit. If not do nothing.
        if(!isset($_POST['user_name']) || !in_array(strtolower($_POST['user_name']), $allowedUsers))
            return channelResponse($_POST['response_url'], NO_PERMISSIONS_ERROR);

        // In this context text should be the URL to link. If it's not present, do nothing.
        if(!isset($_POST['text']) || empty($_POST['text']))
            return;

        $url = $_POST['text'];

        // Check if the URL is valid
        if(filter_var($url, FILTER_VALIDATE_URL) === false)
            return joke();

        // Fetch the category ID to post the link under
        $categoryID = categoryID(DEFAULT_CATEGORY);

        // Grab a preview of the link
        $preview = new LinkPreview($url);

        try {
            $preview = $preview->getParsed()['general'];
        } catch (\Exception $e) {
            // Catching general exceptions is badda than a m'urfuka!
            // This will generally fail if a domain name doesn't exist. If it fails for any other reason ... fuck it.
            // TODO: Logging!
            return joke();
        }

        // If there's no page title, and description, ignore it. Tell a joke.
        if(!$preview->getTitle() && !$preview->getDescription())
            return joke();
 
        $title = esc_html(parse_url($url)['host'] . ' - ' . $preview->getTitle());
        $description = esc_html($preview->getDescription());
        
        // Insert the wordpress post
        $postID = wp_insert_post([
            'post_status'   => 'publish',
            'post_author'   => 0,
            'post_content'  => "{$description} <a href=\"{$url}\">{$url}</a>",
            'post_title'    => $title,
            'post_category' => [$catID]
        ]);

        // Take a screenshot of website
        screenshot($url, $savePath, $filename);

        // Insert attatchment
        $attachmentID = wp_insert_attachment([
            'guid'              => $wpUploadURL,
            'post_mime_type'    => wp_check_filetype($filename, null)['type'],
            'post_title'        => $title,
            'post_description'  => $description,
            'post_status'       => 'inherit' 
        ], $wpUploadDir, $postID);
        
        // Required. Obviously, otherwise it wouldn't be here!
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Generate the metadata for the attachment, and update the database record.
        $attachmentData = wp_generate_attachment_metadata($attachmentID, $wpUploadDir);
        wp_update_attachment_metadata($attachmentID, $attachmentData);

        // Set the post thumbnail
        set_post_thumbnail($postID, $attachmentID);
    }
     
    // Return some shit to slack ...
    $permalink = get_permalink($postID);
    $msg = $successMessages[array_rand($successMessages)];

    channelMessage($_POST['response_url'], "{$msg} {$permalink}");
};

add_action('admin_post_linkit', 'linkit');
add_action('admin_post_nopriv_linkit', 'linkit');
