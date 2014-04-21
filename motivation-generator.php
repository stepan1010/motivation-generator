<?php
/*
Plugin Name: Motivation Generator
Plugin URI: http://stepasyuk.com/motivation/
Description: Allows to create (de)motivational posters.
Version: 2.0.4
Author: Stepan Stepasyuk
Author URI: http://stepasyuk.com
License: GPLv2
*/

/*
* This is the main file of Motivation Generator plugin for WordPress.
* The plugin is run via a shortcode [motgen_plugin] which triggers form generator function.
* Once the form is generated it's all javascript from there. All the image processing is handled by motivation-generator.js
* After the poster is done it is submitted to the server.
* Poster processing (like writing it to disk) and saving its details to database is handeled by this file. 
*/

/* This hook triggers before any content has been loaded to the page in order to check if 
* the plugin is up to date. */ 
add_action('plugins_loaded', 'motgen_check_version');
function motgen_check_version()
{
	if(!get_option('motgen_version') || get_option('motgen_version') < 13){
		
		global $wpdb;
		$table_name = $wpdb->prefix . "mot_gen";

		$wpdb->query("ALTER TABLE $table_name ADD path_to_poster varchar(255) NOT NULL");

		update_option('motgen_version', 13);
	}

	if(!get_option('motgen_version') || get_option('motgen_version') < 131){
		
		global $wpdb;
		$table_name = $wpdb->prefix . "mot_gen";
		$wpdb->query("ALTER TABLE $table_name ADD poster_url varchar(255) NOT NULL");

		update_option('motgen_version', 131);

		$upload_dir = wp_upload_dir();
		update_option('motgen_default_upload_path', $upload_dir['basedir']);
		update_option('motgen_default_upload_url', $upload_dir['baseurl']);
		update_option('motgen_destination_folder', '/motgen-posters/');
		update_option('motgen_font', plugin_dir_path( __FILE__ ).'fonts/FreeSerif.ttf'); 
	}

	if(!get_option('motgen_version') || get_option('motgen_version') < 200){

		global $wpdb;
		$table_name = $wpdb->prefix . "mot_gen";
		$wpdb->query("ALTER TABLE $table_name ADD poster_wp_post_id int(4) NOT NULL");

		delete_option('motgen_image_width_limit');
		delete_option('motgen_image_height_limit');
		delete_option('motgen_posters_per_page');				
		delete_option('motgen_font');
		
		update_option('motgen_turn_posters_to_wp_posts', 1);
		update_option('motgen_thank_you_page',"<p><h2>Thank you!</h2></p>");
		update_option('motgen_version', 200);
	}
}

// This hook is triggered when the user deletes the plugin 
register_uninstall_hook(__FILE__, "motgen_uninstall");
function motgen_uninstall()
{
 	// Delete all posters first
 	motgen_delete_all_posters();

 	// Delete plugin's database table
 	global $wpdb;
    $table_name = $wpdb->prefix . "mot_gen";
	$wpdb->query("DROP TABLE IF EXISTS $table_name");

	// Delete all stored options
	delete_option('motgen_maintext_font_size');
	delete_option('motgen_subtext_font_size');
	delete_option('motgen_version');
	delete_option('motgen_font');
	delete_option('motgen_destination_folder');
	delete_option('motgen_default_upload_url');
	delete_option('motgen_default_upload_path');
	delete_option('motgen_thank_you_page');
	delete_option('motgen_turn_posters_to_wp_posts');
}

// This hook is triggered when the plugin is activated.
register_activation_hook(__FILE__, "motgen_activate");
function motgen_activate()
{
	// Create a table for our plugin to store info about created posters
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$table_name = $wpdb->prefix . "mot_gen";
	$sql = "CREATE TABLE $table_name (
	  id bigint(20) NOT NULL AUTO_INCREMENT,
	  creation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  poster_file_name varchar(255) NOT NULL,
	  poster_url varchar(255) NOT NULL,
	  path_to_poster varchar(255) NOT NULL,
	  main_text varchar(25) NOT NULL,
	  sub_text varchar(48) NOT NULL,
	  author varchar(55) DEFAULT '' NOT NULL,
	  poster_wp_post_id int(4) NOT NULL,
	  UNIQUE KEY id (id)
	);";

	dbDelta( $sql );

	// Default main text font size
	if(!get_option('motgen_maintext_font_size')){
		update_option('motgen_maintext_font_size', 60); 
	}

	// Default sub text font size
	if(!get_option('motgen_subtext_font_size')){
		update_option('motgen_subtext_font_size', 48); 
	}

	$upload_dir = wp_upload_dir();

	// Default path to where to upload posters
	if(!get_option('motgen_default_upload_path') || get_option('motgen_default_upload_path') == ''){
		update_option('motgen_default_upload_path', $upload_dir['basedir']);
	}

	// Same as above but as URL (needed for links)
	if(!get_option('motgen_default_upload_url') || get_option('motgen_default_upload_url') == ''){
		update_option('motgen_default_upload_url', $upload_dir['baseurl']);
	}
	
	// Default folder to upload posters to
	if(!get_option('motgen_destination_folder')){
		update_option('motgen_destination_folder', '/motgen-posters/');
	}

	/* Option to determine what to do with posters after they've been created. Possibe values:
	* 0 - Do nothing, just save poster to disk.
	* 1 - Create WordPress posts with posters as content.
	* 2 - Save poster to disk and display it on "Thank you screen".
	*/
	if(!get_option('motgen_turn_posters_to_wp_posts') || get_option('motgen_turn_posters_to_wp_posts') == ''){
		update_option('motgen_turn_posters_to_wp_posts', 1);
	}

	// Default content of "Thank you" page
	if(!get_option('motgen_thank_you_page')){
		update_option('motgen_thank_you_page',"<p><h2>Thank you!</h2></p>\r\n<p>Your poster will be published shortly</p>");
	}

	update_option('motgen_version', 200);
}

// Register plugin's css
add_action('wp_enqueue_scripts', 'register_motgen_styles');
function register_motgen_styles()
{
	wp_register_style('motgen_style', plugins_url( 'css/motgen_style.css', __FILE__ ));
	wp_enqueue_style('motgen_style');
}

// Register plugin's css for Settings page
add_action( 'admin_enqueue_scripts', 'motgen_admin_style' ); 
function motgen_admin_style($hook) // Link our already registered script only to settings page of our plugin
{ 	
	if( 'toplevel_page_motivation-generator' == $hook || 'motivation-generator_page_motivation-generator-posters' == $hook){
     	wp_register_style( 'motgen_admin_style', plugins_url( 'css/motgen_admin_style.css', __FILE__ ) );
    	wp_enqueue_style( 'motgen_admin_style' );
    }else{
    	return;
   }
}

// Shortcode handler
add_shortcode('motgen_plugin', 'motgen_load_plugin'); 
function motgen_load_plugin()
{	
	// Check if this is a submission or not
	if(isset($_POST['motgen_created_poster'])){
		
		// If it is, validate the input first
		$poster = motgen_validate_input($_POST);

		// Then save the poster
		$poster_id = motgen_save_new_poster($poster);

		// And redirect to Thank you page
		motgen_redirect_to_thank_you_page($poster_id);

		return;
	}

	// If this is a redirect to Thank you page then return the Thank you page
	if (isset($_GET['thankyou'])) {
		return motgen_thank_you_page($_GET['poster']);
	} else {
		// Display the generator form if it's neither
		return motgen_generator();
	}
}

// Create generator form
function motgen_generator()
{
	$generator = '<div id="motivation-generator-plugin" class="widget">';
	
	// Preload our font
	$generator .= '<span id="motgen_font_loader">_</span>';

	// File input
    $generator .= '<p> Please select a picture you would like to turn into a post and click "Upload". <input type="file" id="motgen_imgfile" />';
    // Upload button and Loading icon
    $generator .= '<input type="button" id="motgen_loadimgfile" value="Upload" onclick="motgen_load_image(1);" /><div id="motgen_loading_icon_placeholder"><img id="motgen_loading_icon" src="' . plugins_url( 'images/loader.gif' , __FILE__ ) . '"></div></p>';
	
	$generator .= '<div id="motgen_raw_background_placeholder"></div>';
	$generator .= '<div id="motgen_poster_placeholder"></div>';
	$generator .= '<div id="motgen_canvas_placeholder"><p><canvas id="motgen_poster_canvas"></canvas></p></div>';
	$generator .= '<div id="motgen_error_message_area"></div>';

	// Input form (for maintext and subtext)
	$generator .= '<form id="motgen_generator_form" name="motgen_generator_form" accept-charset="UTF-8" enctype="multipart/form-data" action='.$_SERVER['REQUEST_URI'].' method="POST">';
	$generator .= '<div id="motgen_form_wrapper"><p>Enter main text:* <input type="text" id="motgen_poster_mainline" name="motgen_poster_mainline" tabindex=2 required="required" onkeyup="motgen_type_text();">';
	$generator .= ' Font size: <input type="text" id="motgen_mainline_font_size" size=3 value="'. get_option('motgen_maintext_font_size') .'" tabindex=4 onkeyup="motgen_load_image(2);">&nbsp px</p>';
	$generator .= '<p>Enter sub text: &nbsp&nbsp<input type="text" id="motgen_poster_subline" name="motgen_poster_subline" tabindex=3 onkeyup="motgen_type_text();">';
	$generator .= ' Font size: <input type="text" id="motgen_subline_font_size" size=3 value="'. get_option('motgen_subtext_font_size') .'" tabindex=5 onkeyup="motgen_load_image(2);">&nbsp px</p></div>';
    
    // Hidden input for created poster (For more info see motivation-generator.js) 
    $generator .= '<input type="hidden" id="motgen_created_poster" name="motgen_created_poster" value="">';

    // Submit button
    $generator .= '<input type="button" id="motgen_submit" tabindex=6 onclick="motgen_submit_poster()" value="Demotivate"/>';
    $generator .= '</form>';
    
    // Loading our motivation-generator.js which is responsible for all the image processing
    $generator .= '<script type="text/javascript" src="'.plugins_url( 'motivation-generator/js/motivation-generator.min.js').'"></script>';
    $generator .= '</div>';

	return $generator;
}

// Generate Thank you page ($poster_id is needed in case the poster should be displayed on Thank you page)
function motgen_thank_you_page($poster_id)
{
	// Get Thank you page content
	$page_content = str_replace("\\", "", get_option('motgen_thank_you_page'));

	// Display poster if it's needed.
	if (get_option('motgen_turn_posters_to_wp_posts') == 2){

		$page_content .= motgen_display_poster($poster_id);
	}
	
	return $page_content;
}

// Function for displaying the poster
function motgen_display_poster($poster_id)
{	
	// Just in case
	$poster_id = mysql_real_escape_string($poster_id);

	// Get info about poster
	$poster = motgen_get_poster_by_id($poster_id);

	// Generate html which will be embedded into Thank you page
	$poster_html = '<div><img class="motgen_poster" src="' . $poster->poster_url . $poster->poster_file_name . '.jpg" /></div>';

	return $poster_html;
}

// Function for validating input before writing it to database when user submits a poster
function motgen_validate_input($POST)
{
	// Array to hold info about the poster
	$poster = array();

	// Decode image
	$encoded_image = substr_replace($POST['motgen_created_poster'], '', 0, strlen('data:image/jpeg;base64,'));
	$poster[0] = imagecreatefromstring(base64_decode($encoded_image)) or die ('Error processing image. Please try again.');

	// Check if there is a mainline
	if (strlen(trim($POST['motgen_poster_mainline'])) == 0 ){
		die("Please type in the mainline.");
	}

	$poster[1] = mysql_real_escape_string($POST['motgen_poster_mainline']);
	$poster[2] = mysql_real_escape_string($POST['motgen_poster_subline']);

	return $poster;
}

// This function is responsible for saving new poster to disk and to database
function motgen_save_new_poster($poster)
{
	// Get path where to save the poster
	$destination_folder = get_option('motgen_default_upload_path') . get_option('motgen_destination_folder');

	// Generate posters' file name
	$poster_filename = motgen_generate_poster_filename($destination_folder);

	// Write poster to disk
	imagejpeg($poster[0], $destination_folder . $poster_filename . '.jpg', 100) or die ('Error creating poster. Please try again.');

	// Write poster's info to db
	$poster_id = motgen_write_poster_to_db($poster, $poster_filename);

	// Create a WordPress post if needed
	if (get_option('motgen_turn_posters_to_wp_posts') == 1){
		motgen_turn_poster_to_wp_post($poster_id);
	}

	return $poster_id;
}

// This function generates a unique filename for our poster
function motgen_generate_poster_filename($destination_folder)
{	
	// Generate the name
	$poster_filename = uniqid();

	// Check if directory exists
	if (!file_exists($destination_folder)) {
    	mkdir($destination_folder, 0777, true);
	}

	// If (by chance) there is already a file with such name modify the name of our file to avoid collisions
	if (file_exists($destination_folder . $poster_filename.".jpg")){
		$file_exists = true;
		$counter = 0;
		while($file_exists){
			if(!file_exists($destination_folder . $poster_filename . "_" . $counter. ".jpg")){
				$poster_filename = $poster_filename . "_" . $counter;
				$file_exists = false; 
			} else {
				$counter++;
			}
		}
	}

	return $poster_filename;
}

// Add a record about poster to the db
function motgen_write_poster_to_db($poster, $poster_filename)
{
	$current_user = wp_get_current_user();

	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";
	$wpdb->insert($table_name, array( 
		'creation_date' => current_time('mysql'),
		'poster_file_name' => mysql_real_escape_string($poster_filename),
		'poster_url' => mysql_real_escape_string(get_option('motgen_default_upload_url') . get_option('motgen_destination_folder')),
		'path_to_poster' => mysql_real_escape_string(get_option('motgen_default_upload_path') . get_option('motgen_destination_folder')), 
		'main_text' => mysql_real_escape_string($poster[1]),
		'sub_text' => mysql_real_escape_string($poster[2]),
		'author' => mysql_real_escape_string($current_user->user_login),
		'poster_wp_post_id' => 0
	));

	return $wpdb->insert_id;
}

// Function to create a WordPress post with a given poster as content
function motgen_turn_poster_to_wp_post($poster_id)
{
	// Get info about a given poster
	$poster = motgen_get_poster_by_id($poster_id);

	// Do nothing if there is no such poster
	if(empty($poster))
	{
		return;
	}

	$new_wp_post= array(
	  'post_title'     => str_replace("\\", "", $poster->main_text),
	  'post_content'   => '<img src="'. $poster->poster_url . $poster->poster_file_name.'.jpg' . '" />',
	  'post_status'    => 'pending',
	  'post_author'    => 1
	);

	$wp_post_id = wp_insert_post($new_wp_post);

	// Update poster info. Add WordPress post id with this poster as content
	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";
	$wpdb->query($wpdb->prepare("UPDATE $table_name SET poster_wp_post_id = $wp_post_id WHERE id = $poster_id"));
}

// This function is a javascript workaround for Post - Redirect - Get pattern
function motgen_redirect_to_thank_you_page($poster_id)
{
	$url = $_SERVER['REQUEST_URI'];
	
	if (strpos($url, '?') !== false) {
    	$url .= '&';
	} else {
		$url .= '?';
	}

	$string = '<script type="text/javascript">';
	$string .= 'window.location = "' . $url . 'thankyou=&poster=' . $poster_id . '"';
	$string .= '</script>';

	echo $string;
	exit;
}

// Function to reset WordPress post id back to 0 (in case poster was deleted from Edit Post screen)
function motgen_reset_poster_wp_post_id($poster_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";
	$wpdb->query($wpdb->prepare("UPDATE $table_name SET poster_wp_post_id = 0 WHERE id = $poster_id"));
}

// Function to gen info about poster by id
function motgen_get_poster_by_id($poster_id)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";

	return $wpdb->get_row("SELECT * FROM $table_name WHERE id = $poster_id");
}

/*
* Code below is responsible for creating a link to the settings page
*/
add_action('admin_menu', 'motgen_admin_actions');
function motgen_admin_actions()
{
	add_menu_page("Motivation Generator", "Motivation Generator", "edit_others_posts", "motivation-generator", "motgen_include_admin_settings_page");
}

function motgen_include_admin_settings_page() 
{ 
	include('motivation-generator-admin-settings.php');
}

add_action('admin_menu', 'motgen_init_admin_settings_page');
function motgen_init_admin_settings_page()
{
	add_submenu_page( 'motivation-generator', 'Motivation Generator Settings', 'Settings', 'edit_others_posts', 'motivation-generator', 'motgen_include_admin_settings_page' ); 
}

add_action('admin_menu', 'motgen_init_admin_posters_page');
function motgen_init_admin_posters_page()
{
	add_submenu_page( 'motivation-generator', 'Motivation Generator Posters', 'Posters', 'edit_others_posts', 'motivation-generator-posters', 'motgen_include_admin_posters_page' ); 
}

function motgen_include_admin_posters_page()
{	
	include('motivation-generator-admin-posters.php');
}

// Delete the poster from database and from server
function motgen_delete_poster($poster_id)
{
	// Get the info about it first (so we now where is the file which we want to delete)
	global $wpdb;

	$table_name = $wpdb->prefix . "mot_gen";
	$poster = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $poster_id");

	// Delete the file
	if(unlink($poster->path_to_poster . $poster->poster_file_name . '.jpg')){

		// If file was deleted successfully, delete info about the poster
		$wpdb->delete($table_name, array('id' => $poster_id));

		// If there was a WordPress post with this poster, delete it as well
		if ($poster->poster_wp_post_id != 0){
			wp_delete_post($poster->poster_wp_post_id, true);
		}

	}else{
		die('Couldnt delete a file. Please check your file permissions and try again.');
	}
}

// Function to delete all posters
function motgen_delete_all_posters()
{	
	// Get info about all the posters we have
	global $wpdb;

	$table_name = $wpdb->prefix . "mot_gen";
	$list_of_posters = $wpdb->get_results("SELECT * FROM $table_name");
	
	// Loop through every poster and delete the file, info and WP post
	foreach($list_of_posters as $poster){ 
		if (unlink($poster->path_to_poster . $poster->poster_file_name . '.jpg')){
			
			$wpdb->delete($table_name, array('id' => $poster->id));

			if ($poster->poster_wp_post_id != 0) {
				wp_delete_post($poster->poster_wp_post_id, true);
			}
		}
	}
}

// Get information about all posters that we have (from newest to oldest)
function motgen_get_posters()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";

	return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
}

// JS workaround for 'post redirect get'
function motgen_post_redirect_get()
{
	
	$uri = $_SERVER['REQUEST_URI'];
	$string = '<script type="text/javascript">';
	$string .= 'window.location = "' . substr($uri, 0, strpos($uri, '&')) . '"';
	$string .= '</script>';

	echo $string;
	exit;
}

?>