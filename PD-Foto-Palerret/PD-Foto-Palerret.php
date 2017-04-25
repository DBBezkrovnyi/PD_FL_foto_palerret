<?php
session_start();
/*
 * Plugin Name: PD Foto Palerret
 * Description: Implementation of a PD Foto Palerret
 * Version: 1.0
 * Author: Bezkrovnyi Dmytro
 * Author URI: dimonchik1000@gmail.com
 */
/*
 * ads plugins constants
 */

if(!defined('PD_FP_DIR'))
{
    define('PD_FP_DIR', plugin_dir_path( __FILE__ ));
}

if(!defined('PD_FP_URL'))
{
    define('PD_FP_URL', plugin_dir_url( __FILE__ ));
}

/*
 * actions & filters
 */
add_action("admin_menu", "foto_palerret_admin_page");
add_action("admin_enqueue_scripts", "PD_FL_scripts_admin");
add_action("wp_enqueue_scripts", "PD_FL_scripts");
add_shortcode("Foto_Palerret", "PD_FL_choose_image_file");
add_action('wp_ajax_PD_FL_upload_image_action', 'PD_FL_upload_image_function');
add_action('wp_ajax_nopriv_PD_FL_upload_image_action', 'PD_FL_upload_image_function');

add_action('wp_ajax_PD_FL_upload_image_info', 'PD_FL_upload_image_info_function');
add_action('wp_ajax_nopriv_PD_FL_upload_image_info', 'PD_FL_upload_image_info_function');

add_action('wp_ajax_PD_FL_add_item_cart', 'PD_FL_add_item_cart');
add_action('wp_ajax_nopriv_PD_FL_add_item_cart', 'PD_FL_add_item_cart');

function PD_FL_scripts_admin()
{
    wp_enqueue_script("jquery");

    //including bootstrap.js
    wp_register_script("bootstrap-lib-js",  PD_FP_URL . "includes/js/bootstrap.js", false, "1.0.0", array("jquery"));
    wp_enqueue_script("bootstrap-lib-js");

    //including bootstrap.min.css
    wp_enqueue_style("bootstrap-lib-css",  PD_FP_URL . "includes/css/bootstrap.css");

    wp_register_script("pd_fl_admin_js",  PD_FP_URL . "includes/js/PD_FL_js_admin.js", false, '0.0.1');
    wp_enqueue_script("pd_fl_admin_js");

    wp_enqueue_style("pd-foto-palerret-css", PD_FP_URL . "includes/css/PD-Foto-Palerret.css");
}

/*
 * load scripts
 */
function PD_FL_scripts()
{
    //including jquery
    wp_enqueue_script("jquery");

    //including bootstrap.js
    wp_register_script("bootstrap-lib-js",  PD_FP_URL . "includes/js/bootstrap.js", false, "1.0.0", array("jquery"));
    wp_enqueue_script("bootstrap-lib-js");

    //including bootstrap.min.css
    wp_enqueue_style("bootstrap-lib-css",  PD_FP_URL . "includes/css/bootstrap.css");

    //including PD-Foto-Palerret.css
    wp_enqueue_style("pd-foto-palerret-css", PD_FP_URL . "includes/css/PD-Foto-Palerret.css", array("cropper-lib-css"));

    //including cropper.js
    wp_register_script("cropper-lib-js",  PD_FP_URL . "includes/js/cropper.js", false, '1.0.0', array("bootstrap-lib-js"));
    wp_enqueue_script("cropper-lib-js");

    //including cropper.css
    wp_enqueue_style("cropper-lib-css",  PD_FP_URL . "includes/css/cropper.css" );

    //including loader css & js
    wp_enqueue_style("loader-lib-css",  PD_FP_URL . "includes/css/nouislider.css", array("pd-foto-palerret-css"));

    wp_register_script("loader-lib-js",  PD_FP_URL . "includes/js/nouislider.js", false, '1.0.0', array("cropper-lib-js"));
    wp_enqueue_script("loader-lib-js");

    wp_register_script("jquery-ui-js",  PD_FP_URL . "includes/js/jquery-ui.js", false, '1.0.0', array("jquery"));
    wp_enqueue_script("jquery-ui-js");

    wp_register_script("jquery-form-js",  PD_FP_URL . "includes/js/jquery.form.js", false, '1.0.0', array("jquery"));
    wp_enqueue_script("jquery-form-js");

    wp_enqueue_style("jquery-ui-css",  PD_FP_URL . "includes/css/jquery-ui.css");

    wp_enqueue_script( 'ajax-script', PD_FP_URL . "includes/js/PD_FL_js.js", array('jquery') );

    if(!is_admin())
    {

        wp_enqueue_style("fakeLoader-css", PD_FP_URL . "includes/css/fakeLoader.css");

        wp_register_script("fakeLoader-js", PD_FP_URL . "includes/js/fakeLoader.js", false, '1.0.0', array("jquery"));
        wp_enqueue_script("fakeLoader-js");
    }

    wp_localize_script('ajax-script', "pd_fl_ajax", array("ajax_url" => admin_url('admin-ajax.php')));
}

/*
* function of adding page in admin panel
* @param
* @return
*/
function foto_palerret_admin_page()
{
    add_menu_page("Foto Palerret", "Foto Palerret", "manage_options", PD_FP_DIR . "PD-Foto-Palerret-admin-page.php");
}

/*
 * function for proper operation of uploading image
 */
function PD_FL_add_post_enctyping()
{
    echo 'enctype="multipart/form-data"';
}

function PD_FL_add_item_cart()
{
    $product_name = $_POST["wspsc_product"];
    $image_info = $_SESSION["cropper_recovery"]["users_image_info"];

    $original_image = explode(".", $image_info["image_name"]);

    $full_copied_name = $original_image[0] . "_copy." . $original_image[1];

    if(copy($image_info["image_path"] . $image_info["image_name"], $image_info["image_path"] . $full_copied_name))
    {
        $image_info["image_name"] = $full_copied_name;

        foreach ($_SESSION["simpleCart"] as $key => $value)
        {
            if($product_name == $value["name"])
            {
                $current_key = $key;
                break;
            }
        }

        $_SESSION["simpleCart"]["$current_key"]["users_image_info"] = $image_info;
        $_SESSION["simpleCart"]["$current_key"]["cropper_data"]     = $_SESSION["cropper_recovery"]["cropper_data"];
        $_SESSION["simpleCart"]["$current_key"]["options"]          = $_SESSION["cropper_recovery"]["options"];

        unset($_SESSION["cropper_recovery"]);
        wp_die();
    }

    wp_die();
}

/*
 * function: get ajax request - upload users image
 */
function PD_FL_upload_image_function()
{
    if(!empty($_FILES["get_custom_image"]["name"]))
    {
        $uploaded_image_name = $_FILES["get_custom_image"]["name"];
        $uploaded_image_type = $_FILES["get_custom_image"]["type"];

        $supported_images_types = array("image/jpeg", "image/png", "image/jpg");

        $path_to_users_images = PD_FP_DIR . "assets/users_images/";

        $original_image = explode(".", $uploaded_image_name);

        $full_original_name = $original_image[0] . "_original_" . date("Y-m-d H:i:s") . "." . $original_image[1];
        //echo json_encode($uploaded_image_name);

        if(!file_exists($path_to_users_images) || !is_dir($path_to_users_images))
        {
            mkdir($path_to_users_images);
        }

        elseif (in_array($uploaded_image_type, $supported_images_types))
        {
            if (copy($_FILES["get_custom_image"]["tmp_name"], $path_to_users_images . $uploaded_image_name))
            {
                if(copy($path_to_users_images . $uploaded_image_name, $path_to_users_images . $full_original_name))
                {
                    $answer = array("error_code" => 2,
                        "image_info_path" => $path_to_users_images,
                        "image_info_name" => $uploaded_image_name,
                        "image_info_type" => $uploaded_image_type,
                        "image_info_path_url" => PD_FP_URL . "assets/users_images/",
                        "original_image"  => $full_original_name
                    );

                    echo json_encode($answer); //copy OK
                    wp_die();
                }
            }
            else
            {
                $answer = array("error_code" => 1);
                echo json_encode($answer); //copy no-OK
                wp_die();
            }
        }
        else
        {
            $answer = array("error_code" => 0);
            echo json_encode($answer); //error file type
            wp_die();
        }
    }
}

/*
 * function: get ajax request - upload image info: crop, rotate & flip img
 */
function PD_FL_upload_image_info_function()
{
    header('Content-Type: application/json');
    ini_set('memory_limit', '-1');

    $cropper_data   = $_POST["cropper_data"];
    $image_info     = $_POST["users_image_info"];
    $options        = $_POST["options"];

    $_SESSION["cropper_recovery"] = array("users_image_info" => $image_info, "cropper_data" => $cropper_data, "options" => $options);

    $path_to_image  = $image_info["image_path"] . $image_info["image_name"];
    $total_price    = round(floatval($options["total_price"]), 2);

    $thumbnail_preview_url = PD_FP_URL . "assets/users_images/" . $image_info["image_name"];
    
    $product_name   = "Foto Palerret product " . $image_info["image_name"];
    $cart_count = count($_SESSION["simpleCart"]);

    if($cart_count > 0)
        $product_name .=  " (" . $cart_count .")";

    $product_card_info = "[wp_cart_display_product name=\"" . $product_name . "\" price=\"" . $total_price. "\" thumbnail=\"" . $thumbnail_preview_url . "\" description=\"You have selected the following options:<br/>- sizes: ". $options['image_width'] ." x " . $options["image_height"] . " cm<br/>- canvas sort: " . $options["canvas_sort_option"] . "<br/>- canvas edges: " . $options["canvas_edges_option"] . "<br/>- canvas thickness: " . $options["canvas_strech_bar_option"] . "<br/>To add goods to the shopping cart, click 'Add to card'.<br/>Thank you for cooperation with us!\"]";

    $upload_info_answer["action"] = do_shortcode($product_card_info);

    if(($image_info["image_type"] == "image/jpeg") || ($image_info["image_type"] == "image/jpg"))
    {
        /*
         * FLIP
         */
        $check_flip = false;
        $img_flip = imagecreatefromjpeg($path_to_image);

        if(($cropper_data["scaleX"] == -1) && ($cropper_data["scaleY"] == -1))
        {
            $check_flip = imageflip($img_flip, IMG_FLIP_BOTH);
        }

        else if($cropper_data["scaleX"] == -1)
            $check_flip = imageflip($img_flip, IMG_FLIP_HORIZONTAL);

        else if($cropper_data["scaleY"] == -1)
            $check_flip = imageflip($img_flip, IMG_FLIP_VERTICAL);

        if($check_flip)
        {
            imagejpeg($img_flip, $path_to_image, 100);
            imagedestroy($img_flip);
        }

        /*
         * ROTATE
         */
        if(intval($cropper_data["rotate"]) != 0)
        {
            $img_rotate = imagecreatefromjpeg($path_to_image);
            $white = imagecolorallocate($img_rotate, 255, 255, 255);
            $rotate_degree = 360 - intval($cropper_data["rotate"]);

            $rotated_image = imagerotate($img_rotate, $rotate_degree, $white);

            imagejpeg($rotated_image, $path_to_image, 100);

            imagedestroy($rotated_image);
            imagedestroy($img_rotate);
        }

        /*
         * CROPP
         */
        $img_cropp = imagecreatefromjpeg($path_to_image);
        $cropped_image = imagecrop($img_cropp, ["x" => $cropper_data["x"], "y" => $cropper_data["y"], "width" => $cropper_data["width"], "height" => $cropper_data["height"]]);

        if($cropped_image !== false)
        {
            imagejpeg($cropped_image, $path_to_image, 100);
            imagedestroy($cropped_image);
            imagedestroy($img_cropp);
            $upload_info_answer["status"] = "no_error";
            echo json_encode($upload_info_answer);
        }

        wp_die();
    }

    else if(($image_info["image_type"] == "image/png"))
    {
        /*
         * FLIP
         */
        $img_flip = imagecreatefrompng($path_to_image);

        if(($cropper_data["scaleX"] == -1) && ($cropper_data["scaleY"] == -1))
        {
            imageflip($img_flip, IMG_FLIP_BOTH);
            imagepng($img_flip, $path_to_image, 100);

            imagedestroy($img_flip);
        }

        if($cropper_data["scaleX"] == -1)
        {
            imageflip($img_flip, IMG_FLIP_HORIZONTAL);
            imagepng($img_flip, $path_to_image, 100);

            imagedestroy($img_flip);
        }

        if($cropper_data["scaleY"] == -1)
        {
            imageflip($img_flip, IMG_FLIP_VERTICAL);
            imagepng($img_flip, $path_to_image, 100);

            imagedestroy($img_flip);
        }

        /*
         * ROTATE
         */
        if(intval($cropper_data["rotate"]) != 0)
        {
            $img_rotate = imagecreatefrompng($path_to_image);
            $white = imagecolorallocate($img_rotate, 255, 255, 255);
            $rotate_degree = 360 - intval($cropper_data["rotate"]);

            $rotated_image = imagerotate($img_rotate, $rotate_degree, $white);
            imagepng($rotated_image, $path_to_image, 100);

            imagedestroy($rotated_image);
            imagedestroy($img_rotate);
        }

        /*
         * CROPP
         */
        $img_cropp = imagecreatefrompng($path_to_image);

        $cropped_image = imagecrop($img_cropp, ["x" => $cropper_data["x"], "y" => $cropper_data["y"], "width" => $cropper_data["width"], "height" => $cropper_data["height"]]);

        if($cropped_image !== false)
        {
            imagepng($cropped_image, $path_to_image, 100);
            imagedestroy($cropped_image);
            imagedestroy($img_cropp);
            $upload_info_answer["status"] = "no_error";
            echo json_encode($upload_info_answer);
        }

        wp_die();
    }
    else
    {
        $upload_info_answer["status"] = "error";
        echo json_encode($upload_info_answer);
    }
}

/*
 * function of uploading customers image
 * @param
 * @return
 */
function PD_FL_choose_image_file()
{
    $sizes = get_option("sizes");
    $canvas_options = get_option("canvas_option");
    $default_values = get_option("default_values");
    $images_preview = get_option("interior_images_preview");
    $default_cart_location = get_option("default_cart_location");
    $default_cropp_img = get_option("default_cropp_img");

    if(isset($_GET["product_name"]))
    {
        $product_name = $_GET["product_name"];
        $cropper_recovery = $_SESSION["simpleCart"];

        foreach($cropper_recovery as $key => $value)
        {
            if($product_name == $value["name"])
            {
                $current_key = $key;
                break;
            }
        }

        $cropper_recovery = $cropper_recovery[$current_key];
    }
//    echo "<pre>";
//        print_r($_SESSION);
//    echo "</pre>";
    ?>
    <script>
        var default_cart_location = <?php echo json_encode($default_cart_location); ?>;
        var default_image_quality = <?php echo json_encode($default_values["default_image_quality"]); ?>;
        var default_plugin_location = location.href;
        var cropper_recovery_data = <?php echo json_encode($cropper_recovery); ?>;
    </script>
    <div class="row">
        <div class="col-xs-12">
            Choose your image:
            <form action="" method="post" id="form_upload_image" enctype="multipart/form-data" >
                <input type="file" name="get_custom_image" accept="image/*" id="get_custom_image"/>
            </form>
        </div>
    </div>
    <div class="cropper_div">
        <div class="row">
            <div class="col-xs-12 col-md-5">
                <div class="user_menu">
                    <h2>Format</h2>
                    <ul style="margin:0;" class="nav nav-tabs">
                        <?php
                            if(!empty($sizes["square"]) && isset($sizes["square"]))
                                echo '<li class="active"><a data-toggle="tab" data-aspectratio="1/1" href="#square" class="img_aspRatio">Square</a></li>';

                            if(!empty($sizes["landscape"]) && isset($sizes["landscape"]))
                                echo '<li><a data-toggle="tab" data-aspectratio="4/3" href="#landscape" class="img_aspRatio">Landscape</a></li>';

                            if(!empty($sizes["portrait"]) && isset($sizes["portrait"]))
                                echo '<li><a data-toggle="tab" data-aspectratio="3/12" href="#portrait" class="img_aspRatio">Portrait</a></li>';

                            if(!empty($sizes["widescreen"]) && isset($sizes["widescreen"]))
                                echo '<li><a data-toggle="tab" data-aspectratio="3/1" href="#widescreen" class="img_aspRatio">Widescreen</a></li>';
                        ?>
                    </ul>
                    <?php
                    echo "<div class='tab-content'><br/>";
                        foreach ($sizes as $key => $value)
                        {
                            ?>
                                <div id='<?php echo $key; ?>'
                                     class='tab-pane fade <?php if ($key == "square") echo "in active"; ?>'>
                                    <?php
                                        foreach ($value as $key_ => $value_)
                                        {
                                            ?>
                                            <input type="radio" name="img_sizes"
                                                   data-image-width="<?php echo $value_["width"]; ?>"
                                                   data-image-height="<?php echo $value_["height"]; ?>"
                                                   data-image-price="<?php echo $value_["price"]; ?>"
                                                   value="<?php echo $value_["price"]; ?>"
                                                <?php
                                                    if(isset($cropper_recovery["options"]["image_width"]) && isset($cropper_recovery["options"]["image_height"]) )
                                                    {
                                                        if(($value_["width"] == $cropper_recovery["options"]["image_width"])
                                                            && (($value_["height"] == $cropper_recovery["options"]["image_height"])))
                                                        {
                                                            echo " checked";
                                                        }
                                                    }
                                                    elseif($key == "square" && $key_ == 0) {
                                                        echo " checked";
                                                    } else { }
                                                ?> /> <?php echo $value_["width"]; ?> &times <?php echo $value_["height"]; ?> cm<br/>
                                            <?php
                                        }
                                    ?>
                                </div>
                            <?php
                        }
                    echo "</div>";
                    ?>
                    <div class="canvas_options">
                        <?php
                        echo "<br/><h2>Canvas options</h2>";
    
                        echo "<div class='item'>";
                        echo "<h4>Canvas sort</h4>";
                        if(!empty($canvas_options["canvas_sort"]))
                        {
                            foreach ($canvas_options["canvas_sort"] as $key => $value)
                            {
                                ?>
                                <input type="radio" name="canvas_sort" data-canvas-sort="<?php echo $value["name"]; ?>"
                                       value="<?php echo $value["price"]; ?>"
                                        <?php
                                            if(isset($cropper_recovery["options"]["canvas_sort_option"])){
                                                if($value["name"] == $cropper_recovery["options"]["canvas_sort_option"]){
                                                    echo "checked";
                                                }
                                            }
                                            elseif ($key == 0)
                                            {
                                                echo "checked";
                                            }
                                            else { }
                                        ?> /> <?php echo $value["name"]; ?>
                                <br/>
                                <?php
                            }
                        }
                        else{
                            echo "No existing options.<br>Add the new option.";
                        }
                        echo "</div>";
    
                        echo "<div class='item'>";
                        echo "<h4>Canvas edges</h4>";
                        if(!empty($canvas_options["canvas_edges"]))
                        {
                            foreach ($canvas_options["canvas_edges"] as $key => $value)
                            {
                                ?>
                                <input type="radio" name="canvas_edges" data-canvas-edges="<?php echo $value["name"]; ?>"
                                       value="<?php echo $value["price"]; ?>"
                                    <?php
                                        if (isset($cropper_recovery["options"]["canvas_edges_option"]))
                                        {
                                            if($value["name"] == $cropper_recovery["options"]["canvas_edges_option"])
                                            {
                                                echo "checked";
                                            }
                                        }
                                        elseif($key == 0)
                                        {
                                            echo "checked";
                                        }
                                        else { }
                                    ?> /> <?php echo $value["name"]; ?>
                                <br/>
                                <?php
                            }
                        }
                        else{
                            echo "No existing options.<br>Add the new option.";
                        }
                        echo "</div>";
    
                        echo "<div class='item strech_bar'>";
                        echo "<h4>Strech bar</h4>";
                        if(!empty($canvas_options["canvas_strech_bar"]))
                        {
                            foreach ($canvas_options["canvas_strech_bar"] as $key => $value)
                            {
                                $path_to_images_thumb = PD_FP_URL . "assets/strech_bar_images/" . basename($value["strech_bar_img"]);
                                ?>
                                <label style="position: relative;">
                                    <img src="<?php echo $path_to_images_thumb; ?>"/>
                                    <input type="radio" name="canvas_strech_bar" data-canvas-strech="<?php echo $value["thickness"]; ?>"
                                           value="<?php echo $value["price"]; ?>"
                                            <?php
                                                if(isset($cropper_recovery["options"]["canvas_strech_bar_option"]))
                                                {
                                                    if($value["thickness"] == $cropper_recovery["options"]["canvas_strech_bar_option"])
                                                    {
                                                        echo "checked";
                                                    }
                                                }
                                                elseif($key == 0)
                                                {
                                                    echo "checked";
                                                }
                                                else { }
                                            ?> />
                                </label>
                                <?php
                            }
                        }
                        else{
                            echo "No existing options.<br>Add the new option.";
                        }
                        echo "</div>";
                        ?>
                        <button type="button" id="cropper_start">Crop start</button>
                        <button type="button" id="cropper_clear">Cropper clear</button>
                        <button type="button" id="button_show_preview" data-target="#cropper_image_preview" data-toggle="modal" disabled >Go to preview</button><br/><br/>
                        <div id="cropper_image_preview" class="modal fade" role="dialog">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button class="close" type="button" data-dismiss="modal">&times</button>
                                        <h4 class="modal-title">Cropped image preview</h4>
                                    </div>
                                    <div class="modal-body">
                                        <img src="" id="cropper_img_prev" class="img-responsive center-block"/>
                                        <div class="cropper_img_border"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-default" type="button" data-dismiss="modal">Close</button>
                                        <button class="btn btn-primary" id="go_interior_preview" type="button" data-dismiss="modal">Go to interior preview</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-md-7">
                <img id="users_custom_image" src="<?php echo $default_cropp_img["default_cropp_img_url"]; ?>"/>
                <div class="img_options">
                    <br/>
                    <div class="btn-group btn-group-lg crop_zoom">
                        <button type="button" data-zoom_val="-0.5" class="fa fa-search-plus"></button>
                        <button type="button" data-zoom_val="0.5" class="fa fa-search-minus"></button>
                    </div>
                    <div class="btn-group btn-group-lg crop_rotate">
                        <button type="button" data-rotate_val="-45" class="fa fa-rotate-left"></button>
                        <button type="button" data-rotate_val="45" class="fa fa-rotate-right"></button>
                    </div>
                    <div class="btn-group btn-group-lg crop_scale">
                        <button type="button" data-scale_val="x" class="fa fa-arrows-h"></button>
                        <button type="button" data-scale_val="y" class="fa fa-arrows-v"></button>
                    </div>
                    <div class="btn-group btn-group-lg crop_lock">
                        <button type="button" data-lock_val="1" class="fa fa-lock"></button>
                        <button type="button" data-lock_val="0" class="fa fa-unlock"></button>
                    </div>
                    <div class="btn-group btn-group-lg crop_destroy pull-right">
                        <button type="button" class="fa fa-power-off"></button>
                    </div>
                    <br/><br/>
                    <div class="alert" style="display: none;" id="image_uploading_info"><p></p></div>
                    <div class="alert" style="display: none;" id="image_info"><p></p></div>
                    <label style="display: none;" id="show_image_resolution"></label>
                    <div id="image_quality_slider"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="image_interior_preview" style="display: none;">
        <div class="row">
            <h2>Interior preview</h2>
            <div class="col-sm-3">
                <div class="preview_options">
                    <?php
                        if (!empty($images_preview))
                        {
                            foreach ($images_preview as $key => $value)
                            {
                                $file_name = basename($value);
                                $path_to_file = PD_FP_URL . "assets/interior_images/" . $file_name;
                                ?>
                                    <img src="<?php echo $path_to_file; ?>" class="images_interior_preview" />
                                <?php
                            }
                        } else
                        {
                            echo "Not existing items...";
                        }
                    ?>
                    <br/><br/>
                    <div class="interior_image_controls">
                        <hr/>Choose background color:
                        <input type="color" id="interior_background_color" value="#ffffff" /><br/><br/><hr/>
                        
                        <div class="cropper_zoom">
                            <button type="button" data-zoomvalue="-10" class="fa fa-search-minus"></button>
                            <button type="button" data-zoomvalue="10" class="fa fa-search-plus"></button>
                        </div>
                        <br/>
                        <hr/>
                        <h2 id="diplay_sizes"></h2>
                        <hr/>
                        <button type="button" id="go_back">Back</button>
                        <button type="button" id="make_order">Make an order</button>
                        <br/><br/>
                    </div>
                </div>
            </div>
            <div class="col-sm-9">
                <div class="interior_image_preview">
                    <img src="" id="cropped_image_interior_preview" class="ui-widget-content" />
                    <img src="<?php echo $path_to_file; ?>" id="image_interior_preview" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="get_checkout" style="display: none;">
            <div class="col-sm-12"></div>
        </div>
    </div>
    <div style="display: none;" id="fakeLoader"></div>
    <?php
}