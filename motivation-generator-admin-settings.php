<?php

	// This file is responsible for generating plugin's Settings Page.

	// Header
	$settings_form = '<div class="wrap"><h2>' . __( 'Motivation Generator Settings' ) . '</h2><hr />';

	// If this is a form submission, update options
	if(isset($_POST['motgen_save_changes']) && $_POST['motgen_save_changes'] == 'Y'){

		if(isset($_POST['motgen_maintext_font_size']) && is_numeric(trim($_POST['motgen_maintext_font_size']))){
			update_option('motgen_maintext_font_size', $_POST['motgen_maintext_font_size']);
		}

		if(isset($_POST['motgen_subtext_font_size']) && is_numeric(trim($_POST['motgen_subtext_font_size']))){
			update_option('motgen_subtext_font_size', $_POST['motgen_subtext_font_size']);
		}

		if(isset($_POST['motgen_thank_you_page'])) {
			update_option('motgen_thank_you_page', $_POST['motgen_thank_you_page']);
		}

		update_option('motgen_turn_posters_to_wp_posts', $_POST['motgen_turn_posters_to_wp_posts']);
		

		if(isset($_POST['motgen_destination_folder'])) {
			$destination_folder = $_POST['motgen_destination_folder'][strlen($_POST['motgen_destination_folder'])-1] == '/' ? $_POST['motgen_destination_folder'] : $_POST['motgen_destination_folder'].'/';
			$destination_folder = $destination_folder[0] == '/' ? $destination_folder : '/'.$destination_folder;
			update_option('motgen_destination_folder', $destination_folder);
			update_option('motgen_destination_folder_url', $destination_folder);
		}

		// Say that settings are saved.
		$settings_form .= '<div class="updated"><p>Changes saved</p></div>';
	}

	// Generate settings form
	$settings_form .= '<form name="motgen_form" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
	$settings_form .= '<p>Save posters to: ' . get_option('motgen_default_upload_path') . '<input type="text" style="width:400px;" name="motgen_destination_folder" value="'.get_option('motgen_destination_folder').'" /></p>';

	// Check if directory for saving posters exists
	if(!is_dir(get_option('motgen_default_upload_path') . get_option('motgen_destination_folder'))) {
		// Create it if it doesn't
		if (!mkdir(get_option('motgen_default_upload_path').get_option('motgen_destination_folder'))) {
			$settings_form .= '<font color=red>'.get_option('motgen_default_upload_path').get_option('motgen_destination_folder').' Can not be created. Please check your file permissions.</font>';
		}
	}
	// Form inputs
	$settings_form .= '<p>Enter maintext font size: <input type="text" name="motgen_maintext_font_size" value="'.get_option('motgen_maintext_font_size').'" /></p>';
	$settings_form .= '<p>Enter subtext font size: <input type="text" name="motgen_subtext_font_size" value="'.get_option('motgen_subtext_font_size').'"></p>';
	$settings_form .= '<p>"Thank you" page:</p> <textarea cols="70" rows="5" name="motgen_thank_you_page" id="motgen_thank_you_page" >' . str_replace("\\", "", get_option('motgen_thank_you_page')) . '</textarea>';

	// Assigning "selected" value to appropriate option in select input below
	$display_poster_on_thank_you_screen = get_option('motgen_turn_posters_to_wp_posts') == "2" ? 'selected' : '' ;
	$do_turn_to_wp_post = get_option('motgen_turn_posters_to_wp_posts') == "1" ? 'selected' : '' ;
	$dont_turn_to_wp_post = get_option('motgen_turn_posters_to_wp_posts') == "0" ? 'selected' : '' ;

	// Select input
	$settings_form .= '<p>What to do after poster has been created:</p><p><select name="motgen_turn_posters_to_wp_posts" id="motgen_turn_posters_to_wp_posts">';
	$settings_form .= '<option value=1 ' . $do_turn_to_wp_post . '>Create a WordPress post with poster as content. Post will be marked as "pending".</option>';
	$settings_form .= '<option value=2 ' . $display_poster_on_thank_you_screen . '>Display poster to the user on "Thank you" screen and save the poster to disk.</option>';
	$settings_form .= '<option value=0 ' . $dont_turn_to_wp_post . '>Nothing. Just save poster to disk.</option></select></p>';

	$settings_form .= '<input type="hidden" name="motgen_save_changes" value="Y" />';
	$settings_form .= '<input type="submit" value="Save Options" />';
	$settings_form .= '</form><br /><br />';

	echo $settings_form; // Pring settings form   
?>
</div>