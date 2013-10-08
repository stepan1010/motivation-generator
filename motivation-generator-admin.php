<?php

$settings_form = "<h2>" . __( 'Motivation Generator Settings' ) . "</h2><hr />"; // Header for our settings page

// Pagination implementation. pn -> Page number
if(!isset($_GET['pn']) || $_GET['pn'] == 0 || !is_numeric($_GET['pn']))
{
	$current_page_number = 1;
}else{
	$current_page_number = $_GET['pn'];
}

// Delete a poster (by id) from settings page. di -> delete item
if(isset($_GET['di']) && is_numeric($_GET['di']))
{
	motgen_delete_poster($_GET['di']);
}

// Delete all posters from the database. da -> delete all
if(isset($_GET['da']) && is_numeric($_GET['da']))
{
	motgen_delete_all_posters();
}

// If this is a form submission, update options
if(isset($_POST['motgen_save_changes']) && $_POST['motgen_save_changes'] == 'Y'){

	if(isset($_POST['motgen_posters_per_page']) && is_numeric(trim($_POST['motgen_posters_per_page']))){
		update_option('motgen_posters_per_page', $_POST['motgen_posters_per_page']);
	}

	if(isset($_POST['motgen_image_width_limit']) && is_numeric(trim($_POST['motgen_image_width_limit']))){
		update_option('motgen_image_width_limit', $_POST['motgen_image_width_limit']);
	}

	if(isset($_POST['motgen_image_height_limit']) && is_numeric(trim($_POST['motgen_image_height_limit']))){
		update_option('motgen_image_height_limit', $_POST['motgen_image_height_limit']);
	}

	if(isset($_POST['motgen_maintext_font_size']) && is_numeric(trim($_POST['motgen_maintext_font_size']))){
		update_option('motgen_maintext_font_size', $_POST['motgen_maintext_font_size']);
	}

	if(isset($_POST['motgen_subtext_font_size']) && is_numeric(trim($_POST['motgen_subtext_font_size']))){
		update_option('motgen_subtext_font_size', $_POST['motgen_subtext_font_size']);
	}

	if(isset($_POST['motgen_font'])) {
		update_option('motgen_font', $_POST['motgen_font']);
	}

	if(isset($_POST['motgen_destination_folder'])) {
		$destination_folder = $_POST['motgen_destination_folder'][strlen($_POST['motgen_destination_folder'])-1] == '/' ? $_POST['motgen_destination_folder'] : $_POST['motgen_destination_folder'].'/';
		$destination_folder = $destination_folder[0] == '/' ? $destination_folder : '/'.$destination_folder;
		update_option('motgen_destination_folder', $destination_folder);
		update_option('motgen_destination_folder_url', $destination_folder);
	}

	// Say that settings are saved.
	$settings_form .= '<div class="updated"> <p>Changes saved</p></div>';
}

// Prepare the settings form
$settings_form .= '<div class="wrap">';
$settings_form .= '<form name="motgen_form" method="post" action="'.str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'">';
$settings_form .= '<p>'.__( 'How many rows to display per page ' );
$settings_form .= '<select name="motgen_posters_per_page">';
$settings_form .= '<option value="10">10</option><option value="50">50</option><option value="200">200</option></select></p>';
$settings_form .= '<br><p>When a user uploads an image to make a poster from the image gets resized. Below you can specify max dimensions for the resized image. Enter 0 to disable resizing</p>';
$settings_form .= '<p>Enter max height: <input type="text" name="motgen_image_height_limit" value="'.get_option('motgen_image_height_limit').'" /></p>';
$settings_form .= '<p>Enter max width: <input type="text" name="motgen_image_width_limit" value="'.get_option('motgen_image_width_limit').'" /></p>';
$settings_form .= '<p>Font (.ttf only): <input type="text" style="width:400px;" name="motgen_font" value="'.get_option('motgen_font').'" /></p>';
if(!file_exists(get_option('motgen_font'))){
	$settings_form .= '<font color=red>File '.get_option('motgen_font').' not found.</font>';
}else{
	$file_parts = pathinfo(get_option('motgen_font'));
	if ($file_parts['extension'] != 'ttf') {
		$settings_form .= '<font color=red>File '.get_option('motgen_font').' is not a .ttf file.</font>';
	}
}
$settings_form .= '<p>Save posters to: '.get_option('motgen_default_upload_path').'<input type="text" style="width:400px;" name="motgen_destination_folder" value="'.get_option('motgen_destination_folder').'" /></p>';
if(!is_dir(get_option('motgen_default_upload_path').get_option('motgen_destination_folder'))) {

	if (!mkdir(get_option('motgen_default_upload_path').get_option('motgen_destination_folder'))) {
	$settings_form .= '<font color=red>'.get_option('motgen_default_upload_path').get_option('motgen_destination_folder').' Can not be created. Please check your file permissions.</font>';

	}
}
$settings_form .= '<br><p>Enter maintext font size: <input type="text" name="motgen_maintext_font_size" value="'.get_option('motgen_maintext_font_size').'" /></p>';
$settings_form .= '<p>Enter subtext font size: <input type="text" name="motgen_subtext_font_size" value="'.get_option('motgen_subtext_font_size').'"></p>';
$settings_form .= '<input type="hidden" name="motgen_save_changes" value="Y" />';
$settings_form .= '<input type="submit" value="Save Options" />';
$settings_form .= '</form><br /><br />';

// Code below is responsible for pagination. 

// Get the total amount of posters we have
$total_rows_count = motgen_poster_count();

	if($total_rows_count == 0){ // If no posters are found, say so
		echo $settings_form.'</div>';
		echo "<h3>" . __( 'Motivational posters' ) . "</h3><hr />";
		echo '<div class="motgen_admin_error">No posters found.</div>';
		return;
	}else{
		$rows_per_page = get_option('motgen_posters_per_page'); 
		$total_page_count = ceil($total_rows_count / $rows_per_page); // Calculate how many posters per page should be displayed
		$posters_list = motgen_get_posters($current_page_number, $total_rows_count); // Get a specific number of posters
	}

	echo $settings_form; // Pring settings form

  echo '<div><span class="motgen_poster_table_caption">' . __( 'Motivational posters' ) . '</span>';
  echo '<a href="'.$_SERVER['PHP_SELF'].'?page=motivation-generator&da=1"><span class="motgen_delete_all"><img src="' . plugins_url( 'images/delete_button.jpg' , __FILE__ ) . '">Delete All</span></a></div>';   
 ?>

	<table>
		<tr>
			<td><?php echo "<b>" . __( 'Id' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Creation Date' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Author' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Poster File Name' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Maintext' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Subtext' ) . "</b>"; ?></td>
			<td><?php echo "<b>" . __( 'Delete' ) . "</b>"; ?></td>
		</tr>
			<?php
				foreach ( $posters_list as $poster ) // Print info about posters
					{					
						$poster_info = '<tr>';
						$poster_info .= '<td>'.$poster->id.'</td>';
						$poster_info .= '<td>'.$poster->creation_date.'</td>';
						$poster_info .= '<td>'.$poster->author.'</td>';
						$poster_info .= '<td><a href="' .$poster->poster_url.$poster->poster_file_name.'.jpg' . '" target="_blank">'.$poster->poster_file_name.'</a></td>';
						$poster_info .= '<td>'.$poster->main_text.'</td>';
						$poster_info .= '<td>'.$poster->sub_text.'</td>';
						$poster_info .= '<td><a href="'.$_SERVER['PHP_SELF'].'?page=motivation-generator&pn='.$current_page_number.'&di='.$poster->id.'">';
						$poster_info .= '<img class="motgen_delete_poster_image" src="' . plugins_url( 'images/delete_button.jpg' , __FILE__ ) . '" >';
						$poster_info .= '</a></td>';
						$poster_info .= '</tr>';

						echo $poster_info;
					}

			?>
		</tr>
	</table>   

<?php
	
	$pagination = '<div class="motgen_pagination">'; // Pring page numbers
	for($i = 1;$i<=$total_page_count;$i++){

		$pagination .= '<a class="motgen_admin_link" href="'.$_SERVER['PHP_SELF'].'?page=motivation-generator&pn='.$i.'">'.$i.' &nbsp </a>';
	}
	$pagination .= '</div>';
	echo $pagination;

?>
</div> 