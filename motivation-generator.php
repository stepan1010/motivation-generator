<?php
/*
Plugin Name: Motivation Generator
Plugin URI: http://stepasyuk.com/motivation/
Description: Allows to create (de)motivational posters.
Version: 1.3.2
Author: Stepan Stepasyuk
Author URI: http://stepasyuk.com
License: GPLv2
*/

add_action('init', 'motgen_check_version');
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
		$wpdb->query("DELETE FROM $table_name");
		$wpdb->query("ALTER TABLE $table_name ADD poster_url varchar(255) NOT NULL");

		update_option('motgen_version', 131);

		$upload_dir = wp_upload_dir();
		update_option('motgen_default_upload_path', $upload_dir['basedir']);
		update_option('motgen_default_upload_url', $upload_dir['baseurl']);
		update_option('motgen_destination_folder', '/motgen-posters/');
		update_option('motgen_font', plugin_dir_path( __FILE__ ).'fonts/FreeSerif.ttf'); 
	}
}

register_uninstall_hook(__FILE__, "motgen_uninstall");

function motgen_uninstall() // Let's clean up after ourselves
{
 	global $wpdb;

    $table_name = $wpdb->prefix . "mot_gen";
	$wpdb->query("DROP TABLE IF EXISTS $table_name");

	delete_option('motgen_posters_per_page');
	delete_option('motgen_image_width_limit');
	delete_option('motgen_image_height_limit');
	delete_option('motgen_maintext_font_size');
	delete_option('motgen_subtext_font_size');
	delete_option('motgen_version');
	delete_option('motgen_font');
	delete_option('motgen_destination_folder');
	delete_option('motgen_default_upload_url');
	delete_option('motgen_default_upload_path');
}


register_activation_hook(__FILE__, "motgen_activate");

function motgen_activate() // Create a table for our plugin to store a list of all created posters
{
	global $wpdb;
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
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	if(!get_option('motgen_posters_per_page')){ // This options specifies how many db records to display per page in settings section
		update_option('motgen_posters_per_page', 10); // Set default value on activation
	}

	// When user uploads an image it is being cut in size and printed as a poster. Values below set max height and width for the cut image
	if(!get_option('motgen_image_height_limit')){
		update_option('motgen_image_height_limit', 450); 
	}

	if(!get_option('motgen_image_width_limit')){ 
		update_option('motgen_image_width_limit', 545); 
	}

	if(!get_option('motgen_maintext_font_size')){
		update_option('motgen_maintext_font_size', 28); 
	}

	if(!get_option('motgen_subtext_font_size')){
		update_option('motgen_subtext_font_size', 22); 
	}

	// Default path to font
	if(!get_option('motgen_font')){
		update_option('motgen_font', 'wp-content/plugins/motivation-generator/fonts/FreeSerif.ttf'); 
	}

	// Default destination foler to store posters
	if(!get_option('motgen_destination_folder')){
		update_option('motgen_destination_folder', 'wp-content/plugins/motivation-generator/images/posters/'); 
	}

	update_option('motgen_version', 132);
}

add_action('wp_enqueue_scripts', 'register_motgen_styles');

function register_motgen_styles() // Register stylesheets
{
	wp_register_style('motgen_style', plugins_url( 'css/motgen_style.css', __FILE__ ));
	wp_enqueue_style('motgen_style');
}


add_shortcode('motgen_plugin', 'motgen_load_plugin'); 
function motgen_load_plugin() // Main function of the plugin
{	

	if(!isset($_POST['motgen_file_uploaded']) || $_POST['motgen_file_uploaded'] != 'Y'){ // Check if this is a submission or not

		if(!isset($_GET['poster'])){
			$picture_message = 'Select a picture (.jpg, .png) Max size 2MB<font color="red">*</font>';
			$maintext_message = 'Enter maintext:<font color="red">*</font> ';

			$form = motgen_print_form($picture_message, $maintext_message, $_POST['motgen_maintext'], $_POST['motgen_subtext']); // If not print a standard form

			return $form;
		}else{

			// Check if requested poster exists
			if (file_exists(get_option('motgen_default_upload_path').get_option('motgen_destination_folder').$_GET['poster'].'.jpg')) {

				// If so, display it
				return '<div class="motgen_new_poster_div"><img class="motgen_new_poster" src="'.get_option('memeone_default_upload_url').'/'.get_option('motgen_destination_folder').$_GET['poster'].'.jpg" /></div>'; 
			}else{ 
				return '<div class="motgen_new_poster_div">404. Poster not found</div>'; 
			}
		}
	}else{

		// If this is a submission check all mandatory fields
		if(trim($_POST['motgen_maintext']) != "" ) {
			$maintext = $_POST['motgen_maintext'];
		}else{
			$hasError = true;
			$maintext_message = '<b>Enter maintext:<font color="red">*</font> </b>';
			$picture_message = '</b>Selectpicture (.jpg, .png) Max size 2MB</b>';
		}
		
		if($_FILES['motgen_picture']['tmp_name'] != "" && $_FILES['motgen_picture']['size'] < 2097152 && ($_FILES['motgen_picture']['type'] == 'image/jpeg' || $_FILES['motgen_picture']['type'] == 'image/png')){
			$picture = $_FILES['motgen_picture']['tmp_name'];
		}else{
			$hasError = true;
			$picture_message = '<font color="red"><b>Select a picture (.jpg, .png) Max size 2MB*</b></font>';
			$maintext_message = 'Enter maintext:<font color="red">*</font> ';
		}

		if($hasError != ""){ // If any errors are found print the submission form 
			$form = motgen_print_form($picture_message, $maintext_message, $_POST['motgen_maintext'], $_POST['motgen_subtext']);
			return $form;
		}else{ // If no erros were found, create a poster
			$subtext = $_POST['motgen_subtext'];
			$poster_name = motgen_create_poster($maintext, $subtext, $picture);
			motgen_add_db_record($maintext, $subtext, $poster_name); // Add a record to db about our new poster
			
			memeone_post_redirect_get('poster='.$poster_name);
		}
	}
}

// Print submission form. As input this function takes captions for inputs as well as content (i.e. in case there are file problems)
function motgen_print_form($picture_message, $maintext_message, $maintext_content = '', $subtext_content = '')
{
	$form = "";
	$form .= '<div id="motgen_form" class="widget">';
	$form .= '<form enctype="multipart/form-data" action="" method="POST">';
	$form .= '<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />';
	$form .= '<input type="hidden" name="motgen_file_uploaded" value="Y" />';

	$form .= '<div class="motgen_input">'.$picture_message;
	$form .= '<input name="motgen_picture" type="file" /> </div>';
				
	$form .= '<div class="motgen_input">'.$maintext_message;
	$form .= '<input name="motgen_maintext" type="text" maxlength="25" value="'.$maintext_content.'"/></div>';
				
	$form .= '<div class="motgen_input">Enter subtext: ';
	$form .= '<input name="motgen_subtext" type="text" maxlength="47" value="'.$subtext_content.'"/></div>';

	$form .= '<p><input type="submit" class="btn" value="Create"></p>';
			
	$form .= '</form>';
	$form .= '</div>';
	
	return $form;
	die();
}

// Function for poster creation. As input it takes the mainline, subline and a picture
function motgen_create_poster($maintext, $subtext, $picture)
{
	$imageproperties = getimagesize($picture);

	// Get original picture dimensions.	
	$original_width = $imageproperties[0];  
	$original_height = $imageproperties[1]; 

	// Prepare mainline and subline
	$maintext = mb_strtoupper($maintext, "utf-8");
	$subtext = mb_strtolower($subtext, "utf-8");

	// Get all neccessary settings
	$maintext_font_size = get_option('motgen_maintext_font_size') != '' ? get_option('motgen_maintext_font_size') : 28;
	$subtext_font_size = get_option('motgen_subtext_font_size') != '' ? get_option('motgen_subtext_font_size') : 22;
	$font_face = get_option('motgen_font');
	$destination_folder = get_option('memeone_default_upload_path').get_option('motgen_destination_folder');

	// Write original picture to memory to work with
	switch($imageproperties[2]){
	case IMAGETYPE_JPEG:
		$image = imagecreatefromjpeg($picture);
		break;
	
	case IMAGETYPE_PNG:
	
		$image = imagecreatefromPNG($picture);
		break;
	default:
		return "";
			
	}

	/*
	* Lines below draw mainline and subline in a given font with a given font-size. This is done in order
	* to determine the size (in pixels) of both lines. We will use these values later for determining our poster final width
	*/
	$bbox = imagettfbbox($maintext_font_size, 0, $font_face, $maintext);
	$bbox2 = imagettfbbox($subtext_font_size, 0, $font_face, $subtext);
	
	if (get_option('motgen_image_width_limit') != 0 || get_option('motgen_image_height_limit') != 0){

		$new_width = get_option('motgen_image_width_limit') != '' ? get_option('motgen_image_width_limit') : 545;
		$new_height = get_option('motgen_image_height_limit') != '' ? get_option('motgen_image_height_limit') : 450;

		$source_ratio = $original_width/$original_height;

		if ($new_height < $original_height || $new_width < $original_width) {
			if ($new_height > $new_width){
				$new_height = $new_width / $source_ratio;
			} else {
				$new_width = $new_height / $source_ratio;
			}
		}else{
			$new_width = $original_width;
			$new_height = $original_height;
		}
	}else{

		// Set image original size
		$new_width = $original_width;
		$new_height = $original_height;

	}

		// The upcoming block determines width for the final canvas on which the poster will be drawn
		if($bbox[4] > $bbox2[4]){
			if($bbox[4] > $new_width){
				$canvas_width = $bbox[4] + $maintext_font_size*2;				
			}else{
				if($ratio > 1){
					$canvas_width = $new_width + $new_width/5;
				}else{
					$canvas_width = $new_width + $new_width/3;
				}
			}
		}else{
		if($bbox2[4] > $new_width){
				$canvas_width = $bbox2[4] + $maintext_font_size*2;				
			}else{
				if($ratio > 1){
					$canvas_width = $new_width + $new_width/5;
				}else{
					$canvas_width = $new_width + $new_width/3;
				}			
			}
		}

		$canvas_height = $new_height + ($maintext_font_size*2) + $subtext_font_size + ($new_height/10) + ($maintext_font_size/2);

		// Create canvas
		$canvas = imagecreatetruecolor($canvas_width, $canvas_height);		
		
		// Select colors for text and background
		$text_color_white = imagecolorallocate($canvas, 255, 255, 255);
		$text_color_black = imagecolorallocate($canvas, 0, 0, 0);

		// Draw background of the poster
		imagefilledrectangle($canvas, 0, 0, $canvas_width, $canvas_height, $text_color_black);
		
		// Resize original image
		$resized_image = imagecreatetruecolor($new_width, $new_height);	
		imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height)
				 or die ("Image creation failed. Please retry.");	

		// Determine coordinated for white frame around the image and draw it
		$imageX = $canvas_width/2 - ($new_width/2);
		imagecopyresampled($canvas,$resized_image, $imageX,$new_height/10,0,0,$new_width, $new_height, $new_width, $new_height) or die ("Image creation failed. Please retry.");
		imagerectangle($canvas, $imageX-5, $new_height/10 - 5, $canvas_width/2 + ($new_width/2)+5, $new_height+($new_height/10)+5, $text_color_white) or die ("Image creation failed. Please retry.");
		imagerectangle($canvas, $imageX-6, $new_height/10 - 6, $canvas_width/2 + ($new_width/2)+6, $new_height+($new_height/10)+6, $text_color_white) or die ("Image creation failed. Please retry.");
		
		// Determine coordinates for mainline and subline
		$x = $canvas_width/2 - ($bbox[4]/2);
		$y = $canvas_height-((($maintext_font_size*2)+($subtext_font_size) + ($maintext_font_size/2))/2);
		
		// Draw both lines
		if(strlen($subtext)!=0){
			imagettftext($canvas, $maintext_font_size, 0, $x, $y, $text_color_white, $font_face, stripcslashes($maintext));	
			$x = $canvas_width/2 - ($bbox2[4]/2);
			imagettftext($canvas, $subtext_font_size, 0, $x, $y + $maintext_font_size, $text_color_white, $font_face, stripcslashes($subtext));
		}else{
			imagettftext($canvas, $maintext_font_size, 0, $x, $y + $maintext_font_size/2, $text_color_white, $font_face, stripcslashes($maintext));	
		}
		
		// Write poster to disk
		$new_picture_name = uniqid();
		imagejpeg($canvas, $destination_folder.$new_picture_name.".jpg", 100) or die ('Error writing poster to file. Please check if directory exists and if you are allowed to write there.');

		// Free up memory
		imagedestroy($image);
		imagedestroy($canvas);
		imagedestroy($resized_image);

		// Return poster new name to insert it in database and exit
		return $new_picture_name;
}

// Add a record about poster to the db
function motgen_add_db_record($maintext, $subtext, $postername)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";
	$current_user = wp_get_current_user();
	$wpdb->insert($table_name, array( 
		'creation_date' => current_time('mysql'),
		'poster_file_name' => mysql_real_escape_string($postername),
		'poster_url' => mysql_real_escape_string(get_option('motgen_default_upload_url').get_option('motgen_destination_folder')),
		'path_to_poster' => mysql_real_escape_string(get_option('motgen_default_upload_path').get_option('motgen_destination_folder')), 
		'main_text' => mysql_real_escape_string($maintext),
		'sub_text' => mysql_real_escape_string($subtext),
		'author' => mysql_real_escape_string($current_user->user_login)
	));
}

/*
* Code below is responsible for creating a link to the settings page
*/
add_action('admin_menu', 'motgen_admin_actions'); // Displays link to our settings page in the admin menu
function motgen_admin_actions()
{
    add_options_page("motivation-generator", "Motivation Generator", 1, "motivation-generator", "motgen_admin");    
}

add_action( 'admin_enqueue_scripts', 'motgen_admin_style' ); 
function motgen_admin_style($hook) // Link our already registered script only to settings page of our plugin
{ 
	 if( 'settings_page_motivation-generator' != $hook ){
     	return;
     }else{
    	wp_register_style( 'motgen_admin_style', plugins_url( 'css/motgen_admin_style.css', __FILE__ ) );
    	wp_enqueue_style( 'motgen_admin_style' );
   }
}

function motgen_admin() // Function that includes the actual settings page
{ 
	include('motivation-generator-admin.php');
}

// Delete a poster from the database and disk
function motgen_delete_poster($poster_id)
{
	global $wpdb;

	$table_name = $wpdb->prefix . "mot_gen";
	$poster_name = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $poster_id");

	if(unlink($poster_name->path_to_poster.$poster_name->poster_file_name.'.jpg')){
		$wpdb->delete($table_name, array( 'id' => $poster_id));
	}else{
		die('Couldnt delete a file. Please check your file permissions and try again.');
	}
}

// Clear plugins db table
function motgen_delete_all_posters()
{
	global $wpdb;

	$table_name = $wpdb->prefix . "mot_gen";
	$list_of_posters = $wpdb->get_results( "SELECT poster_file_name, path_to_poster FROM " . $table_name);
	
	foreach($list_of_posters as $poster){ // Iterate files
		if (unlink($poster->path_to_poster.$poster->poster_file_name.'.jpg')){ // Delete file
			$wpdb->delete($table_name, array( 'poster_file_name' => $poster->poster_file_name));
		}
	}
}

// Count all the posters in the database (for pagination on the settings page)
function motgen_poster_count()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";

	$wpdb->get_results("SELECT * FROM $table_name");
	return $wpdb->num_rows;
}

// Get specific amout of posters (also for pagination on the settings page)
function motgen_get_posters($current_page_number, $total_rows_count)
{
	global $wpdb;
	$table_name = $wpdb->prefix . "mot_gen";
	$rows_per_page = get_option('motgen_posters_per_page');

	$sql_limit = ($current_page_number == 1) ? 0 : (($current_page_number - 1) * $rows_per_page);
	return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT $sql_limit, $rows_per_page");
}

function motgen_post_redirect_get($args)
{
	// JS workaround for 'post redirect get'
	$args = strpos($_SERVER['REQUEST_URI'],'?') !== false ? '&' : '?';
	$string = '<script type="text/javascript">';
	$string .= 'window.location = "' . $_SERVER['REQUEST_URI'] .$args.$to.'"';
	$string .= '</script>';

	echo $string;
	exit;
}

?>