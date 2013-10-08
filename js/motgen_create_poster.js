function motgen_create_poster(){

	// Say "Loading" in the div where search results will be displayed to demonstrate that the search is going on

	jQuery('.yawp_search_results').append("<br /><b>Loading</b>"); 

	var yawp_city_to_find = jQuery("#yawp_city").val();
	var yawp_search_results = {};

	jQuery.ajax({
		type:"POST",
		url: "admin-ajax.php",
		data:
		{
		action: "yawp_find_city", yawp_city_to_find : yawp_city_to_find
		},
		dataType: "json",

	    success: function(yawp_data){

			jQuery(".yawp_search_results").empty();

			if (yawp_data.no_city)
			{
				jQuery(".yawp_search_results").append("<br /> No city found. Please check your spelling.");
				return 0;
			}
			else
			{

				// We create a list of radio buttons in search results div in case there is more than one city with a given name

				for(i=1; i<=yawp_data[0].count; i++)
				{

				var radioBtn = jQuery('<br /> <input type="radio" name="yawp_list_of_possible_cities" value="'+i+'"> ' 
										+yawp_data[i].name+', ' 
										+yawp_data[i].country+ 
										', Latitude: '+yawp_data[i].latitude+ 
										', Longitude: '+yawp_data[i].longitude+ 
										', Current temperature: '+yawp_data[i].temperature+'&deg, '+ 
										'Current conditions: '+yawp_data[i].conditions+'</input><br />');
				radioBtn.appendTo('.yawp_search_results');

				};

				yawp_search_results.data = yawp_data; // Save our data to a variable for later use
			}	
		},  

        error: function(jqXHR, textStatus, errorThrown) { 

            jQuery(".yawp_search_results").append("<br /> Connection error. Please retry in a few minutes.");
			return 0;

        }
	});

	return yawp_search_results;

}; 
	 
jQuery(document).ready(function() {
			
	var yawp_city_list;
		
	jQuery("#find_city").click( function() { 

		jQuery(".yawp_search_results").empty(); // Clean the search results div from any previous results
		yawp_city_list = yawp_find_city_script(); // Start the search

	});

		if (yawp_city_list != 0){

			jQuery("input:radio[name=yawp_list_of_possible_cities]").live('click', function() { //Add a live listener to get id and name of a city which user intersted in

				jQuery("#yawp_city_display_name").val(yawp_city_list.data[jQuery(this).val()].name);
				jQuery("input#yawp_city_id").val(yawp_city_list.data[jQuery(this).val()].id);
	
			});	
		};	
});