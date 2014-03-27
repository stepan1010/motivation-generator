/*  The way this whole thing works is as follows:
*   1) User selects a picture and clicks "Upload" button.
*   2) Once the button is clicked we start processing his picture.
*       We create a canvas, considering picture's dimensions.
*       Draw a frame on it.
*       And finally draw users' picture on it.
*       That's how we get a poster.
*   3) After the poster has been created we store it in an invisible div element.
*   4) We display the form for main and sub lines.
*   5) When the user starts typing, we must draw each letter he types in on the fly.
*       So we need to redraw everything we have on our canvas and draw a letter.
*       We can't just draw on top of the canvas without emptying it everytime because then removing a letter would be impossible.
*       That's why we have saved our poster to an invisible div element.
*       So now, we can use it as a background and type text on top of it.
*   6) One the user is done, he submits the picture (as base64 encoded string) and both lines to the server.
*/

// This function is fired when user clicks "Upload"
function motgen_load_image() {
    
    // First, we make our loading icon visible.
    var loading_icon = document.getElementById('motgen_loading_icon');
    loading_icon.style.display = "inline";

    var canvas = document.getElementById("motgen_poster_canvas");
    var context = canvas.getContext("2d");
    canvas.style.display = "none";

    var input, file, fr, img;

    // Then we check if FileReader is supported
    if (typeof window.FileReader !== 'function') {
        motgen_print("Hey, Your browser seems out of date.");
        return;
    }

    // After that we check if there is a file input
    input = document.getElementById('motgen_imgfile');
    if (!input) {
        // If an error is encountered we hide the loading icon and print an appropriate error message
        loading_icon.style.display = "none";
        motgen_print("Um, it seems that there is no file input element.");
    }

    // We then check if browser supports file inputs
    else if (!input.files) {
        loading_icon.style.display = "none";
        motgen_print("Hey, Your browser seems out of date.");
    }

    // And finally we check whether the file has been selected
    else if (!input.files[0]) {
        loading_icon.style.display = "none";
        motgen_print("Please select a file first.");
    } 

    else if (!input.files[0].type.match('image.*')) {
        loading_icon.style.display = "none";
        motgen_print("This is not am image. Please select an image file.");

        var div = document.getElementById('motgen_poster_placeholder');

        // Remove all previous children before appending (in case the user uploaded image and then uploaded a non-image)
        while (div.firstChild) {
            div.removeChild(div.firstChild);
        }

        // Make form invisbile (in case it is)
        document.getElementById('motgen_generator_form').style.display = "none";
    } 
    // If everything is in place, we proceed further
    else {

        // First, hide our error area (in case there were any errors prior to this point)
        document.getElementById('motgen_error_message_area').style.display = "none";

        file = input.files[0];
        // Invoke the FileReader.
        fr = new FileReader();
        // Create a new image onload
        fr.onload = motgen_create_image;
        fr.readAsDataURL(file);
    }

    // Pass created image to the FileReader
    function motgen_create_image() {
        img = new Image();
        img.onload = motgen_image_loaded;
        img.src = fr.result;
    }

    // Once our file is loaded we start to create a image out of it.
    function motgen_image_loaded() {
    	var maintext_font_size = parseInt(document.getElementById("motgen_mainline_font_size").value);
    	var subtext_font_size = parseInt(document.getElementById("motgen_subline_font_size").value);

        // First, calculate dimensions for our future image
        motgen_calculate_canvas_dimensions(function(){

            var picture_x = canvas.width / 2 - (img.width / 2);
            // Then draw background
            motgen_draw_frame(picture_x, function(){
                // And then draw the picture on top of it
                motgen_draw_picture(picture_x, function(){

                    // After we are done, show the canvas and show the form for text inputs.
                    canvas.style.display = "block";
                    document.getElementById('motgen_generator_form').style.display = "block";
                    // Also we got to hide that loading icon
                    loading_icon.style.display = "none";

                });
            });    
        });

        function motgen_calculate_canvas_dimensions(callback) {

            /* To calculate correct dimensions we need to consider the following:
            *   1) Dimensions of the picture user has uploaded.
            *   2) Left and right paddings (for the frame).
            *   3) Top and bottom paddings (also for the frame)
            *   4) Space for text at the bottom.
            */
            canvas.style.position = 'inherit';

            // First we calculate required space for image + left and right paddings.
            var ratio = img.width / img.height;
            // If our picture is landscape orieneted, than side paddings should be smaller
            if(ratio > 1){
                    canvas.width = img.width + img.width / 5;
                }else{
                    canvas.width = img.width + img.width / 3;
                }

            // Then we calculate top and bottom paddings
            var top_padding = img.height / 10;
            var bottom_padding = maintext_font_size / 2;

            // After that we calculate space for the text. Here we account for our font size
            var space_for_text = (maintext_font_size * 2) + subtext_font_size;

            canvas.height = img.height + space_for_text + top_padding + bottom_padding;

            callback();
        }

        function motgen_draw_frame(picture_x, callback) {

            // Draw black background first
            context.fillStyle = "#000000";
            context.fillRect(0, 0, canvas.width, canvas.height);

            // Then draw a white frame
            var frame_offset, frame_width = 0;
            if (img.height > img.width) {
                frame_offset = parseInt(img.height / 90);
                frame_width = parseInt(img.height / 167);
            } else {
                frame_offset = parseInt(img.width / 90);
                frame_width = parseInt(img.width / 167);
            }
            
            if (frame_offset < 11) {frame_offset = 11};
            if (frame_width < 3) {frame_width = 3};
            
            context.lineWidth = frame_width;
            context.strokeStyle = "white";
            context.rect(picture_x - frame_offset, (img.height / 10) - frame_offset, img.width + frame_offset * 2, img.height + frame_offset * 2); 
            context.stroke();

            callback();
        }
        
        function motgen_draw_picture(picture_x, callback) {

            /* Finally we draw a picture that user has uploaded. Since we already know all the coordinates
            *   we can start drawing right away.
            */
            context.drawImage(img, picture_x, img.height / 10);

            // Now we need to place our image to the placeholder to use it everytime we need to redraw it.
            var image = new Image();
            image.id = "motgen_background_picture";
            image.src = canvas.toDataURL();
            image.style.display = "none";
            var div = document.getElementById('motgen_poster_placeholder');

            // Remove all previous children before appending (just in case the user pressed "Upload" button twice or change font)
            while (div.firstChild) {
                div.removeChild(div.firstChild);
            }

            div.insertBefore(image, div.firstChild);

            callback();
        }
    }

    // This function is called everytime our application would like to print an error message. 
    function motgen_print(msg) {

        var error_area = document.getElementById('motgen_error_message_area');

        // First, remove all previous errors. 
        while (error_area.firstChild) {
            error_area.removeChild(error_area.firstChild);
        }

        // Create a new paragraph with desired error message as contents
        var p = document.createElement('p');
        p.innerHTML = msg;

        // Append paragraph to the error area
        error_area.appendChild(p);

        // Make error area visible
        error_area.style.display = "block";
    }
}

function motgen_type_text() {

    // Get all the values we need
	var maintext_font_size = parseInt(document.getElementById("motgen_mainline_font_size").value);
    var subtext_font_size = parseInt(document.getElementById("motgen_subline_font_size").value);
	var mainline = document.getElementById("motgen_poster_mainline").value;
	var subline = document.getElementById("motgen_poster_subline").value;
	var canvas = document.getElementById("motgen_poster_canvas");
    var background = document.getElementById("motgen_background_picture");

    // Draw poster first 
	var context = canvas.getContext("2d");
	context.clearRect(0, 0, canvas.width, canvas.height);
	context.drawImage(background, 0, 0);

	// Adjust the font
	context.font = maintext_font_size + "px FreeSerif";
	context.textAlign = "center";
	context.fillStyle = "white";

    // Calculate where to draw text
	var bottom_padding = maintext_font_size / 2;
	var space_for_text = (maintext_font_size * 2) + subtext_font_size;
	var x = canvas.width / 2;

    // If subline is empty, draw text in the center of specified area.
	if (subline.trim().length == 0){
		var y = (canvas.height - (bottom_padding + space_for_text) / 2) + maintext_font_size / 2;
		context.fillText(mainline.toUpperCase(), x, y);
	} else {
        // If mainline is empty, draw subline in the center of specified area.
		if (mainline.trim().length == 0){
			var y = (canvas.height - (bottom_padding + space_for_text) / 2) + maintext_font_size / 2;
			context.fillText(subline.toUpperCase(), x, y);
		} else {
            // If both mainline and subline are present, draw both. First, draw mainline
			var y = (canvas.height - (bottom_padding + space_for_text) / 2) + maintext_font_size / 2 - subtext_font_size;
			context.fillText(mainline.toUpperCase(), x, y);

            // Then draw subline
			var y = (canvas.height - (bottom_padding + space_for_text) / 2) + maintext_font_size / 2 + subtext_font_size / 2;
			context.font = subtext_font_size + "px FreeSerif";
			context.fillText(subline, x, y);
		}		
	}
}

// This function is called when user submits a poster
function motgen_submit_poster(){

    var mainline = document.getElementById("motgen_poster_mainline").value;

    // Do not submit if mainline is empty
    if (mainline.trim().length != 0){

        // Move the poster from canvas to our hidden input tag
        document.getElementById("motgen_created_poster").value = document.getElementById("motgen_poster_canvas").toDataURL("image/jpeg");
        // Submit the form.
        document.forms["motgen_generator_form"].submit();
    }
}