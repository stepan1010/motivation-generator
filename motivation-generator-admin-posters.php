<div class="wrap">
<h2>Motivational Posters</h2><hr />
<?php

//This file is responsible for dislaying information about all the posters as a table.

// First, we check if any parameters we passed in
// Delete a poster (by id)
if(isset($_GET['delete_poster']) && is_numeric($_GET['delete_poster']))
{
	motgen_delete_poster($_GET['delete_poster']);
	motgen_post_redirect_get();
}

// Delete all posters from the database
if(isset($_GET['delete_all_posters']) && is_numeric($_GET['delete_all_posters']))
{
	motgen_delete_all_posters();
	motgen_post_redirect_get();
}

// Create a WordPress post with existing poster as a content
if(isset($_GET['create_wp_post']))
{
	motgen_turn_poster_to_wp_post($_GET['create_wp_post']);
	motgen_post_redirect_get();
}

//Now we get info about all the posters we have and start formatting it
$posters_list = motgen_get_posters();

// Display appropriate message if we don't have any posters
if (empty($posters_list)) {
	echo '<p><h2> No posters :( </h2></p>';
	exit;
}

// "Delete all" button
echo '<a href="'.$_SERVER['PHP_SELF'].'?page=motivation-generator-posters&delete_all_posters=1"><span class="motgen_delete_all"><img src="' . plugins_url( 'images/delete_button.png' , __FILE__ ) . '">Delete All</span></a></div>';
?>
<table>
	<tr>
		<td><?php echo "<b>" . __( 'Id' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Creation Date' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Author' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Poster File Name' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Maintext' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Subtext' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'WP Post ID' ) . "</b>"; ?></td>
		<td><?php echo "<b>" . __( 'Delete' ) . "</b>"; ?></td>
	</tr>
		<?php
		$admin_url = admin_url();
			foreach ( $posters_list as $poster ) // Print info about posters
				{					
					$poster_info = '<tr>';
					$poster_info .= '<td>'.$poster->id.'</td>';
					$poster_info .= '<td>'.$poster->creation_date.'</td>';
					$poster_info .= '<td>'.$poster->author.'</td>';
					$poster_info .= '<td><a href="' .$poster->poster_url.$poster->poster_file_name.'.jpg' . '" target="_blank">'.$poster->poster_file_name.'</a></td>';
					$poster_info .= '<td>'.$poster->main_text.'</td>';
					$poster_info .= '<td>'.$poster->sub_text.'</td>';
					
					// If there is no WP post with this poster, create a button to make one
					if ($poster->poster_wp_post_id == 0)
					{
						$poster_info .= '<td>N/A <a href="' . $_SERVER['PHP_SELF'] . '?page=motivation-generator-posters&create_wp_post=' . $poster->id . '"> Publish </a></td>';
					} else {

						// Otherwise, get poster status
						$post_status = get_post_status($poster->poster_wp_post_id);

						// Empty status means that the poster was deleted from Edit Post screen, so we should make appropriate changes to our db table
						if ($post_status == ''){
							$poster_info .= '<td>N/A <a href="' . $_SERVER['PHP_SELF'] . '?page=motivation-generator-posters&create_wp_post=' . $poster->id . '"> Publish </a></td>';
							motgen_reset_poster_wp_post_id($poster->id);
						} else {
							// If status is not empty, display it alongside with id of corresponding WP post
							$poster_info .= '<td><a href="' . $admin_url . 'post.php?post=' . $poster->poster_wp_post_id . '&action=edit">' . $poster->poster_wp_post_id . '</a> - ' . $post_status . ' </td>';		
						}		
					}

					// Delete poster button 
					$poster_info .= '<td><a href="' . $_SERVER['PHP_SELF'] . '?page=motivation-generator-posters&delete_poster=' . $poster->id . '">';
					$poster_info .= '<img class="motgen_delete_poster_image" src="' . plugins_url( 'images/delete_button.png' , __FILE__ ) . '" >';
					$poster_info .= '</a></td>';
					$poster_info .= '</tr>';

					echo $poster_info;
				}
		?>
	</tr>
</table>