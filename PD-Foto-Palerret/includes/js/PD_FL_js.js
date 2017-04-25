function PD_FL_get_dataoptions()
{
    var image_width     = jQuery("input[name='img_sizes']:checked").data("image-width");
    var image_height    = jQuery("input[name='img_sizes']:checked").data("image-height");

    var image_square    = parseFloat(image_width * image_height);

    var canvas_size_price       = parseFloat(jQuery("input[name='img_sizes']:checked").val());
    var canvas_sort_price       = parseFloat(jQuery("input[name='canvas_sort']:checked").val());
    var canvas_edges_price      = parseFloat(jQuery("input[name='canvas_edges']:checked").val());
    var canvas_strech_bar_price = parseFloat(jQuery("input[name='canvas_strech_bar']:checked").val());

    var total_price = canvas_size_price + image_square * (canvas_sort_price + canvas_edges_price + canvas_strech_bar_price);

    var canvas_sort_option       = jQuery("input[name='canvas_sort']:checked").data("canvas-sort");
    var canvas_edges_option      = jQuery("input[name='canvas_edges']:checked").data("canvas-edges");
    var canvas_strech_bar_option = jQuery("input[name='canvas_strech_bar']:checked").data("canvas-strech");

    var data_options_array = {
        "image_width" : image_width,
        "image_height" : image_height,
        "canvas_sort_option" : canvas_sort_option,
        "canvas_edges_option" : canvas_edges_option,
        "canvas_strech_bar_option" : canvas_strech_bar_option,
        "total_price" : total_price,
    };

    return data_options_array;
}

function PD_FL_addParameter(url, parameterName, parameterValue, atStart/*Add param before others*/){
    replaceDuplicates = true;
    if(url.indexOf('#') > 0){
        var cl = url.indexOf('#');
        urlhash = url.substring(url.indexOf('#'),url.length);
    } else {
        urlhash = '';
        cl = url.length;
    }
    sourceUrl = url.substring(0,cl);

    var urlParts = sourceUrl.split("?");
    var newQueryString = "";

    if (urlParts.length > 1)
    {
        var parameters = urlParts[1].split("&");
        for (var i=0; (i < parameters.length); i++)
        {
            var parameterParts = parameters[i].split("=");
            if (!(replaceDuplicates && parameterParts[0] == parameterName))
            {
                if (newQueryString == "")
                    newQueryString = "?";
                else
                    newQueryString += "&";
                newQueryString += parameterParts[0] + "=" + (parameterParts[1]?parameterParts[1]:'');
            }
        }
    }
    if (newQueryString == "")
        newQueryString = "?";

    if(atStart){
        newQueryString = '?'+ parameterName + "=" + parameterValue + (newQueryString.length>1?'&'+newQueryString.substring(1):'');
    } else {
        if (newQueryString !== "" && newQueryString != '?')
            newQueryString += "&";
        newQueryString += parameterName + "=" + (parameterValue?parameterValue:'');
    }
    return urlParts[0] + newQueryString + urlhash;
};

jQuery(document).ready(function()
{
    var check_create_slider;
    var cropper;
    var users_image_info = {
        image_name : '',
        image_path : '',
        image_type : '',
        orig_image : '',
        image_path_url : '',
    };
    var borders_coef_prop = 0;

    if(cropper_recovery_data !== null && typeof(cropper_recovery_data) !== "undefined")
    {
        console.log(cropper_recovery_data);
        var path_to_image = cropper_recovery_data["users_image_info"]["image_path_url"] + cropper_recovery_data["users_image_info"]["orig_image"];
    
        jQuery("#users_custom_image").attr("src", path_to_image);
    
        PD_FL_cropper_init(function () {
            PD_FL_recalculate();
        });
    
        jQuery("button").prop("disabled", false);
    
        var cropper_data_restore = { };
    
        for(key in cropper_recovery_data["cropper_data"]){
            cropper_data_restore[key] = Number(cropper_recovery_data["cropper_data"][key]);
        }
    
        for(key in cropper_recovery_data["users_image_info"]) {
            for(key_ in users_image_info) {
                if(key === key_) {
                    users_image_info[key_] = cropper_recovery_data["users_image_info"][key];
                }
            }
        }
    
        cropper.setData(cropper_data_restore);
    }

    function PD_FL_slider_init(q, min_s, max_s) {
        if (typeof(check_create_slider) === 'undefined') {
            var slider_data = {
                start: [q],
                connect: false,
                range: {
                    'min': min_s,
                    'max': max_s,
                },
                pips: {
                    mode: 'steps',
                    density: 10
                }
            };

            var slider = document.getElementById('image_quality_slider');

            check_create_slider = noUiSlider.create(slider, slider_data);
            slider.setAttribute('disabled', true);
        }
        else {
            check_create_slider.set(q);
        }
    }

    function PD_FL_recalculate(K) {
        var inch = 2.54;
        var current_size = parseFloat(jQuery("input[name='img_sizes']:checked").data("image-width") * inch);
        var image_data = cropper.getImageData();
        var cropbox_data = cropper.getCropBoxData();
        var canvas_data = cropper.getCanvasData();

        if (typeof K === 'undefined') {
            var Wo = image_data["naturalWidth"];
            var Wc = parseFloat(canvas_data["width"]);
            var K = parseFloat(Wo / Wc);
        }

        //console.log(Wc);


        var Wcw = parseFloat(cropbox_data["width"]);
        var Wco = parseFloat(Wcw * K);
        // console.log(Wcw, Wco, K, Wo, Wc);
        var q = Math.round(Wco / current_size);
        // console.log(q);

        var current_cropper_width = Number(jQuery("input[name='img_sizes']:checked").data("image-width"));
        var border_width = jQuery("input[name='canvas_strech_bar']:checked").data("canvas-strech"); // толщина рамки
        //console.log(current_cropper_width, border_width);

        borders_coef_prop = Wco / (2 * Number(border_width) + current_cropper_width);
        //console.log(borders_coef_prop);

        if(q > 0 && q <= Number(default_image_quality["low"]))
        {
            jQuery("#go_interior_preview").prop("disabled", true);
            jQuery("#image_info").addClass("alert-warning");
            jQuery("#image_info").find("p").text("The resolution of your image is below the critical level. Please increase your image size. Button \"Go to interior preview\" is now disabled.");
            jQuery("#image_info").show("slow")
        }
        else if (q > Number(default_image_quality["low"]) && q <= Number(default_image_quality["critical"]))
        {
            jQuery("#go_interior_preview").prop("disabled", false);
            jQuery("#image_info").addClass("alert-warning");
            jQuery("#image_info").find("p").text("The resolution of your image is below the critical level. Please increase your image size.");
            jQuery("#image_info").show("slow")
        }
        else
        {
            jQuery("#image_info").removeClass("alert-warning");
            jQuery("#image_info").hide("slow")
            jQuery("#go_interior_preview").prop("disabled", false);
        }

        jQuery("#show_image_resolution").text("Your image resolution: " + q).show("slow");
        // console.log(q);


        jQuery(".cropper-line.line-e").css( { "width" : borders_coef_prop, } ).html("<p class='rotate_p90' style='font-size: 35px; top: 50%; transform: translate(0%, -50%) rotate(90deg)' >Borders</p>");
        jQuery(".cropper-line.line-w").css( { "width" : borders_coef_prop, } ).html("<p class='rotate_m90' style='font-size: 35px; top: 50%; transform: translate(0%, -50%) rotate(-90deg)' >Borders</p>");
        jQuery(".cropper-line.line-s").css( { "height" : borders_coef_prop, } ).html("<p style='font-size: 35px;' class='border_text'>Borders</p>");
        jQuery(".cropper-line.line-n").css( { "height" : borders_coef_prop, } ).html("<p style='font-size: 35px;' class='border_text'>Borders</p>");

        PD_FL_slider_init(q, Number(default_image_quality["low"]), Number(default_image_quality["high"]));
    }

    function PD_FL_cropper_init(callback) {

        var div_height = Math.min(document.documentElement.clientHeight, window.innerWidth || 0) * 0.7;

        var cropper_data =
        {
            aspectRatio: 1 / 1,
            viewMode: 1,
            cropBoxResizable: true,
            minContainerHeight: div_height,
            autoCropArea: 0.7,
        };

        image = document.getElementById("users_custom_image");

        cropper = new Cropper(image, cropper_data);

        image.addEventListener("crop", function (e) {
            PD_FL_recalculate();
        });

        image.addEventListener("cropstart", function (e) {
            PD_FL_recalculate();
        });

        image.addEventListener("cropmove", function (e) {
            PD_FL_recalculate();
        });

        image.addEventListener("cropend", function (e) {
            PD_FL_recalculate();
        });

        image.addEventListener("zoom", function (e) {
            PD_FL_recalculate(1 / e.detail.ratio);
        });

        if (typeof (callback) == "function") {
            callback();
        }
    }

    function PD_FL_ajax_image_upload(event)
    {
        event.stopPropagation(); // Stop stuff happening
        event.preventDefault();

        var data = new FormData(jQuery('#form_upload_image'));

        data.append("action", "PD_FL_upload_image_action");
        data.append("get_custom_image", jQuery('#get_custom_image').prop("files")[0]);

        jQuery.ajax(
        {
            type : 'POST',
            url : pd_fl_ajax.ajax_url,
            data : data,
            dataType : 'json',
            processData : false,
            contentType : false,
            cache : false,

            success: function (response_data)
            {
                switch(response_data.error_code)
                {
                    case 2:
                        jQuery("#image_uploading_info").find("p").text("Image loading complete...");
                        setTimeout(function() { jQuery("#image_uploading_info").hide("slow"); }, 10000);

                        users_image_info.image_name = response_data.image_info_name;
                        users_image_info.image_path = response_data.image_info_path;
                        users_image_info.image_type = response_data.image_info_type;
                        users_image_info.orig_image = response_data.original_image;
                        users_image_info.image_path_url = response_data.image_info_path_url;
                        
                        jQuery("#button_show_preview").prop("disabled", false);
                        break;
                    case 1:
                        jQuery("#image_uploading_info").addClass("alert-warning");
                        jQuery("#image_uploading_info").find("p").text("Ooops...Something went wrong: the image is not loaded.");
                        jQuery("#image_uploading_info").show("slow");
                        break;
                    case 0:
                        jQuery("#image_uploading_info").addClass("alert-warning");
                        jQuery("#image_uploading_info").find("p").text("Ooops...Something went wrong: the image is not loaded.");
                        jQuery("#image_uploading_info").show("slow");
                        break;
                }
            },
        });
    }

    function PD_FL_readURL(input, callback)
    {
        if (input.files && input.files[0])
        {
            var reader = new FileReader();

            reader.onload = function (e)
            {
                if (typeof(cropper) === "undefined")
                {
                    jQuery('#users_custom_image').attr('src', e.target.result);

                    if (typeof (callback) == "function")
                    {
                        callback();
                    }

                    jQuery("#image_uploading_info").addClass("alert-success");
                    jQuery("#image_uploading_info").find("p").text("Image loading in progress...");
                    jQuery("#image_uploading_info").show("slow");

                    jQuery('.cropper_div').show("slow");
                }
                else
                {
                    cropper.replace(e.target.result);
                    PD_FL_recalculate();
                }

                jQuery('#form_upload_image').submit();
            }

            reader.readAsDataURL(input.files[0]);
        }
    }

    jQuery('#form_upload_image').on("submit", PD_FL_ajax_image_upload);

    jQuery("#get_custom_image").change(function () {
        PD_FL_readURL(this, function () {
            PD_FL_cropper_init(function () {
                PD_FL_recalculate();
            });
        });
    });

    jQuery("input[name='img_sizes']").change(function ()
    {
        PD_FL_recalculate();
    });

    jQuery("input[name='canvas_strech_bar']").change(function ()
    {
        PD_FL_recalculate();
    });

    jQuery(".images_interior_preview").click(function()
    {
        var this_image = jQuery(this).attr("src");
        jQuery("#image_interior_preview").attr("src", this_image);
    });

    jQuery(".images_preview").click(function () {
        var path_to_image = jQuery(this).data("path");
        jQuery("#imagesModal").find("img").attr("src", path_to_image);
        jQuery("#image_interior_preview").attr("src", path_to_image);
    });

    jQuery("#button_show_preview").click(function () 
    {
        /*
         //TODO
         */
        jQuery(".cropper_img_border").css({
            "border" : "3px solid blue",
            "left" : borders_coef_prop + "px",
            "right" : borders_coef_prop + "px",
            "bottom" : borders_coef_prop + "px",
            "top" : borders_coef_prop + "px",
            "position" : "absolute",
        });
        var cropped_image_preview = cropper.getCroppedCanvas().toDataURL('image/jpeg');

        jQuery("#cropper_image_preview").find("img").attr("src", cropped_image_preview);
    });

    jQuery("#go_interior_preview").click(function () {

        jQuery(".cropper_div").hide("slow");
        jQuery(".image_interior_preview").show("slow");

        var cropped_image_preview = cropper.getCroppedCanvas();
        jQuery("#cropped_image_interior_preview").attr("src", cropped_image_preview.toDataURL());

        jQuery("#cropped_image_interior_preview").draggable({
            containment: jQuery("#image_interior_preview"),
        });

        var image_width = jQuery("input[name='img_sizes']:checked").data("image-width");
        var image_height = jQuery("input[name='img_sizes']:checked").data("image-height");

        jQuery("#diplay_sizes").html("Size: " + image_width + " x " + image_height + " cm");
    });

    jQuery("#interior_background_color").change(function()
    {
        var bg_color = jQuery("#interior_background_color").val();
        jQuery(".interior_image_preview").css("background-color", bg_color);
    });

    jQuery(".cropper_zoom button").click(function()
    {
        var zoom_value = parseFloat(jQuery(this).data("zoomvalue"));
        jQuery("#cropped_image_interior_preview").width(function(i, value) { return value + zoom_value; } );
    });

    jQuery(".img_aspRatio").click(function () {
        var img_aspectRatio_value = jQuery(this).data("aspectratio");
        var img_aspectRatio_value = img_aspectRatio_value.split("/");
        var v1 = img_aspectRatio_value[0];
        var v2 = img_aspectRatio_value[1];
        var result = v1 / v2;

        cropper.setAspectRatio(result);
    });

    jQuery("#cropper_start").click(function()
    {
        cropper.crop();
    });

    jQuery("#cropper_clear").click(function () {
        if(confirm("Are you sure?"))
        {
            cropper.clear();
        }
        else{

        }
    });

    jQuery(".crop_zoom button").click(function()
    {
        var zoom_val = jQuery(this).data("zoom_val");
        cropper.zoom(zoom_val);
    });

    jQuery(".crop_rotate button").click(function()
    {
        var rotate_val = jQuery(this).data("rotate_val");
        cropper.rotate(rotate_val);
    });

    jQuery(".crop_scale button").click(function()
    {
        var scale_val = jQuery(this).data("scale_val");
        var some_scale_info = cropper.getImageData();

        if(scale_val == "x" && some_scale_info["scaleX"] == "-1") {
            cropper.scaleX(1);
            return;
        }

        if(scale_val == "x" && some_scale_info["scaleX"] == "1") {
            cropper.scaleX(-1);
            return;
        }

        if(scale_val == "y" && some_scale_info["scaleY"] == "-1") {
            cropper.scaleY(1);
            return;
        }

        if(scale_val == "y" && some_scale_info["scaleY"] == "1") {
            cropper.scaleY(-1);
            return;
        }
    });

    jQuery(".crop_lock button").click(function()
    {
        var scale_val = jQuery(this).data("lock_val");

        if(scale_val == "1")
            cropper.disable();

        if(scale_val == "0")
            cropper.enable();
    });

    jQuery(".crop_destroy button").click(function()
    {

        if(confirm("Are you sure?"))
        {
            cropper.destroy();
            jQuery("#button_show_preview").prop("disabled", true);
        }
        else
        {
            jQuery("#button_show_preview").prop("disabled", false);
        }
    });

    jQuery("#go_back").click(function(){
        jQuery(".image_interior_preview").hide("slow");
        jQuery(".cropper_div").show("slow");
    });

    jQuery("#make_order").click(function(){
        jQuery(this).prop("disabled", true);

        console.log(cropper.getData());
        var cropper_data = cropper.getData(true);
        console.log(cropper_data);
        cropper_data["width"] -= 2 * borders_coef_prop;
        cropper_data["height"] -= 2 * borders_coef_prop;
        cropper_data["x"] += borders_coef_prop;
        cropper_data["y"] += borders_coef_prop;
        console.log(cropper_data);

        /*
        x + coef
        y + coef
        width - 2 * coef
        height - 2 * coef
         */
        jQuery.ajax(
        {
            type : 'POST',
            url : pd_fl_ajax.ajax_url,
            data : {
                action : "PD_FL_upload_image_info",
                cropper_data : cropper_data,
                users_image_info : users_image_info,
                options : PD_FL_get_dataoptions(),
            },
            dataType : 'json',

            beforeSend: function(){
                jQuery("#fakeLoader").fakeLoader();
                jQuery("#fakeLoader").show("slow");
            },

            success: function (response_data)
            {
                jQuery(".image_interior_preview").hide("slow");
                jQuery(".get_checkout").append(response_data["action"]);
                jQuery(".wp_cart_product_display_box").on("click", "input.wspsc_add_cart_submit", PD_FL_ajax_show_cart);
                jQuery(".get_checkout").show("slow");
            }
        }).done(function() { jQuery("#fakeLoader").hide("slow"); });
    });

    function PD_FL_ajax_show_cart(event)
    {
        event.stopPropagation();
        event.preventDefault();
        
        jQuery(this).prop("disabled", true);

        jQuery(".wp-cart-button-form").removeAttr("action");

        var data_cart = {};
        
        jQuery(".wp-cart-button-form input").each(function(e, i)
        {
            for(var i = 0; i < e+1; i++)
            {
                data_cart[jQuery(this).attr("name")] = jQuery(this).val();
            }
        });

        data_cart["cartLink"] = PD_FL_addParameter(default_plugin_location, 'product_name', data_cart["wspsc_product"], false);

        jQuery.ajax({
            type : "POST",
            url : location.href,
            data : data_cart,
            success: function()
            {
                jQuery.ajax({
                    type : "POST",
                    url : pd_fl_ajax.ajax_url,
                    data : {
                        action : "PD_FL_add_item_cart",
                        wspsc_product : data_cart["wspsc_product"],
                    },
                    success: function()
                    {
                        location.href = default_cart_location;
                    }
                });
            }
        });
    }
});
