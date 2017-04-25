<?php

    /*
    * for numerating txt-inputs with sizes & empty fields
    */
    $count_fields = 0;
    $count_empty_fields = 0;

    /*
     * @param $var - input string
     * @return $var - output string
     */
    function PD_FP_get_true_value($var)
    {
        $var = stripslashes($var);
        $var = htmlentities($var);
        $var = htmlspecialchars($var);

        return $var;
    }

    /*
     * sorting sizes in DB
     * @param $width, $height, $price, $existing_sizes
     * @return array with sizes
     */
    function PD_FP_calculation_of_dimensions($width, $height, $price, $existing_sizes)
    {
        /*
         * options for widescreen params
         */
        $widescreen_width = 16;
        $widescreen_height = 9;
        $check_widescreen_settings = get_option("default_values", array());

        if(!isset($check_widescreen_settings["default_widescreen_values"]))
        {
            $check_widescreen_settings["default_widescreen_values"]["width_param"] = $widescreen_width;
            $check_widescreen_settings["default_widescreen_values"]["height_param"] = $widescreen_height;

            update_option("default_values", $check_widescreen_settings);
        }
        else
        {
            $widescreen_width = $check_widescreen_settings["default_widescreen_values"]["width_param"];
            $widescreen_height = $check_widescreen_settings["default_widescreen_values"]["height_param"];
        }

        if($width > 0 && $height > 0 && $price > 0)
        {

            if (($width / intval($widescreen_width)) > ($height / intval($widescreen_height)))
            {
                $existing_sizes["widescreen"][] = array("width" => $width, "height" => $height, "price" => $price);
            }

            else if ($width == $height)
            {
                $existing_sizes["square"][] = array("width" => $width, "height" => $height, "price" => $price);
            }

            else if ($width > $height)
            {
                $existing_sizes["landscape"][] = array("width" => $width, "height" => $height, "price" => $price);
            }

            else if ($width < $height)
            {
                $existing_sizes["portrait"][] = array("width" => $width, "height" => $height, "price" => $price);
            }

            return $existing_sizes;
        }
        else
        {
            global $error_message;
            $error_message = "One of the parameters is not valid. Check your dimensions and try again.";
        }
    }

    /*
     * add new size
     */
    if(isset($_POST["confirm_add_new_size"]))
    {
        /*
         * check sizes from DB
         * if <false> -> creating new array with aspect_ratio
         * if <true> -> push in existing array new values
         */
        $existing_sizes = get_option("sizes");

        $new_width  = floatval(PD_FP_get_true_value($_POST["width"]));
        $new_height = floatval(PD_FP_get_true_value($_POST["height"]));
        $new_price  = floatval(PD_FP_get_true_value($_POST["price"]));

        if(is_array($existing_sizes))
        {
            $existing_sizes = PD_FP_calculation_of_dimensions($new_width, $new_height, $new_price, $existing_sizes);

            update_option("sizes", $existing_sizes);
        }

        if(!$existing_sizes)
        {
            $existing_sizes = PD_FP_calculation_of_dimensions($new_width, $new_height, $new_price, $existing_sizes);

            add_option("sizes", $existing_sizes);
        }
    }

    /*
     * add new canvas sort
     */
    if(isset($_POST["confirm_add_canvas_sort"]))
    {
        $old_canvas_sort = get_option("canvas_option", array());
        $new_canvas_sort = $_POST["canvas_sort"];

        $old_canvas_sort["canvas_sort"][] = $new_canvas_sort;

        update_option("canvas_option", $old_canvas_sort);
    }

    if(isset($_POST["save_cart_pages"]) || isset($_POST["save_cart_posts"]))
    {
        $default_cart_location = get_option("default_cart_location", array());
        $selected_new_location = $_POST["page-dropdown"];

        update_option("default_cart_location", $selected_new_location);
    }

    /*
     * add new canvas edges
     */
    if(isset($_POST["confirm_add_canvas_edges"]))
    {
        $old_canvas_edges = get_option("canvas_option", array());
        $new_canvas_edges = $_POST["canvas_edges"];

        $old_canvas_edges["canvas_edges"][] = $new_canvas_edges;

        update_option("canvas_option", $old_canvas_edges);
    }

    /*
    * add new canvas strech bar
    */
    if(isset($_POST["confirm_add_strech_bar"]) && isset($_FILES["canvas_strech_bar_image"]["name"]))
    {
        $uploaded_image_max_size = 500000; // max size 500Kb
        $uploaded_image_name = $_FILES["canvas_strech_bar_image"]["name"];
        $uploaded_image_type = $_FILES["canvas_strech_bar_image"]["type"];

        $supported_image_type = array("image/jpeg", "image/png", "image/jpg");
        $path_to_images_thumb = PD_FP_DIR . "assets/strech_bar_images/";

        if(in_array($uploaded_image_type, $supported_image_type) && $_FILES["canvas_strech_bar_image"]["size"] < $uploaded_image_max_size)
        {
            if(copy($_FILES["canvas_strech_bar_image"]["tmp_name"], $path_to_images_thumb . $uploaded_image_name))
            {
                $strech_bar_thumbnail = $path_to_images_thumb . $uploaded_image_name;
                $_POST["canvas_strech_bar"]["strech_bar_img"] =  $strech_bar_thumbnail;

                $old_canvas_strech_bar = get_option("canvas_option", array());
                $new_canvas_strech_bar = $_POST["canvas_strech_bar"];
                $old_canvas_strech_bar["canvas_strech_bar"][] = $new_canvas_strech_bar;

                update_option("canvas_option", $old_canvas_strech_bar);
            }
            else
            {
                global $error_message;
                $error_message = "Ooops, something went wrong...";
            }
        }
        else
        {
            global $error_message;
            $error_message = "Unsupported image type or too large size of the uploaded image. Try again.";
        }
    }

    /*
     * deleting some items
     * get required group and row for deleting
     */
    if(isset($_POST["delete_group"]) && isset($_POST["delete_row"]))
    {
        $delete_group = $_POST["delete_group"]; //get deleting group
        $delete_row = $_POST["delete_row"]; //get deleting row

        $sizes = get_option("sizes"); // get existing sizes
        unset($sizes["$delete_group"]["$delete_row"]); // delete
        update_option("sizes", $sizes); //update
    }

    /*
     * editing some items & save changes
     */
    if(isset($_POST["submit_save_changes_sizes"]))
    {
        $new_sizes = array();
        $get_new_sizes = $_POST["sizes"];

        foreach ($get_new_sizes as $key => $value)
        {
            $new_sizes = PD_FP_calculation_of_dimensions($value["width"], $value["height"], $value["price"], $new_sizes);
        }

        update_option("sizes", $new_sizes);
    }

    /*
     * editing widescreen params (width & height)
     */
    if(isset($_POST["confirm_change_wss"]))
    {
        $default_values = get_option("default_values", array());
        $default_values["default_widescreen_values"] = $_POST["widescreen_defaults"];

        update_option("default_values", $default_values);
    }

    if(isset($error_message))
    {
        ?>
            <br>
            <div class="alert alert-warning">
                <a href="#" class="close" data-dismiss="alert">&times;</a>
                <strong>Warning!</strong> <?php echo $error_message; ?>
            </div>
        <?php
    }

    if(isset($_POST["option_name"]) && isset($_POST["option_row"]) && isset($_POST["option_operation"]))
    {
        $options = get_option("canvas_option");

        $option_name = $_POST["option_name"];
        $option_row = $_POST["option_row"];
        $operation = $_POST["option_operation"];

        if($operation == "delete")
        {
            unset($options["$option_name"]["$option_row"]);
        }

        if($operation == "save")
        {
            $options["$option_name"]["$option_row"] = $_POST["option"]["$option_name"]["$option_row"];
        }

        update_option("canvas_option", $options);
    }

    /*
     * save image quality option
     */
    if(isset($_POST["save_image_quality"]))
    {
        $default_values = get_option("default_values", array());
        $default_values["default_image_quality"] = $_POST["image_quality"];

        update_option("default_values", $default_values);
    }

    if(isset($_POST["save_interior_images"]) && isset($_FILES["interior_images_preview"]["name"]))
    {
        $uploaded_image_name = $_FILES["interior_images_preview"]["name"];
        $uploaded_image_type = $_FILES["interior_images_preview"]["type"];

        $supported_image_type = array("image/jpeg", "image/png", "image/jpg");

        if(in_array($uploaded_image_type, $supported_image_type))
        {
            $dir_name = "interior_images";

            if(!is_dir(PD_FP_DIR . "assets/". $dir_name))
            {
                mkdir(PD_FP_DIR . "assets/". $dir_name);
            }

            $path_to_dir = PD_FP_DIR . "assets/" . $dir_name . "/" . $uploaded_image_name;

            if(copy($_FILES["interior_images_preview"]["tmp_name"], $path_to_dir))
            {
                $interior_images_preview = get_option("interior_images_preview", array());
                $interior_images_preview[] = $path_to_dir;

                update_option("interior_images_preview", $interior_images_preview);
            }
            else
            {
                global $error_message;
                $error_message = "Ooops, something went wrong...";
            }
        }
        else
        {
            global $error_message;
            $error_message = "Unsupported image type or too large size of the uploaded image. Try again.";
        }
    }

    /*
     * deleting preview image
     */
    if(isset($_POST["delete_img_preview"]))
    {
        $id_image = $_POST["delete_img_preview"];
        $del_image_prev = get_option("interior_images_preview");

        unset($del_image_prev[$id_image]);
        update_option("interior_images_preview", $del_image_prev);
    }

    if(isset($_POST["add_default_cropp_img"]) && isset($_FILES["default_cropp_img"]["name"]))
    {
        $supported_image_type = array("image/jpeg", "image/png", "image/jpg");
        $uploaded_image_name = $_FILES["default_cropp_img"]["name"];
        $uploaded_image_type = $_FILES["default_cropp_img"]["type"];

        if(in_array($uploaded_image_type, $supported_image_type))
        {
            $path_to_dir = PD_FP_DIR . "assets/other_images/" . $uploaded_image_name;
            $path_to_url = PD_FP_URL . "assets/other_images/" . $uploaded_image_name;

            if(copy($_FILES["default_cropp_img"]["tmp_name"], $path_to_dir))
            {
                $default_cropp_img = get_option("default_cropp_img", array());

                $default_cropp_img["default_cropp_img_dir"] = $path_to_dir;
                $default_cropp_img["default_cropp_img_url"] = $path_to_url;

                update_option("default_cropp_img", $default_cropp_img);
            }
            else
            {
                global $error_message;
                $error_message = "Ooops, something went wrong...";
            }
        }
        else
        {
            global $error_message;
            $error_message = "Unsupported image type or too large size of the uploaded image. Try again.";
        }
    }

    /*
     * get sizes from DB
     */
    $show_sizes = get_option("sizes");

    /*
     * get canvas options
     */
    $canvas_options = get_option("canvas_option");

    /*
     * get default values for all plugin
     */
    $default_values = get_option("default_values");

    /*
     * interior images preview
     */
    $images_preview = get_option("interior_images_preview");

    /*
     * dafault cart location
     */
    $default_cart_loc = get_option("default_cart_location", "No value...");

    $default_cropp_img = get_option("default_cropp_img");
//echo "<pre>";
//    print_r($default_cropp_img);
//echo "</pre>";
    ?>
        <div class="container-fluid">
            <h1 style="text-align: center;">Hello, <?php $current_user = wp_get_current_user(); echo $current_user->user_login; ?>! You are in admin page of "Foto Palerett"!</h1>
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#dimensions">Dimensions</a></li>
                <li><a data-toggle="tab" href="#canvas">Canvas options</a></li>
                <li><a data-toggle="tab" href="#other">Other</a></li>
                <li><a data-toggle="tab" href="#preview">Preview</a></li>
            </ul>
            <div class="tab-content">
                <div id="dimensions" class="tab-pane fade in active">
                    <h3>Dimensions</h3>
                    <form action="" method="POST" id="form_size" >
                        <div class="row">
                            <div class="col-12 col-sm-3">
                                <hr/>
                                Square<br>
                                <?php
                                    if(!empty($show_sizes["square"]))
                                    {
                                        foreach ($show_sizes["square"] as $key => $value)
                                        {
                                            ?>
                                                <input type="text" value="<?php echo $value["width"]; ?>" name="sizes[<?php echo $count_fields; ?>][width]" size="1" />
                                                <input type="text" value="<?php echo $value["height"]; ?>" name="sizes[<?php echo $count_fields; ?>][height]" size="1" />
                                                <input type="text" value="<?php echo $value["price"]; ?>" name="sizes[<?php echo $count_fields; ?>][price]" size="1" />
                                                <button type="button" class="button_delete" data-group="square" data-row="<?php echo $key; ?>">Delete</button><br>
                                            <?php
                                            $count_fields++;
                                        }
                                    }
                                    else
                                    {
                                        echo "No existing sizes.<br>Add the new size.";
                                        $count_empty_fields++;
                                    }
                                ?>
                            </div>
                            <div class="col-12 col-sm-3">
                                <hr/>
                                Landscape<br>
                                <?php
                                    if(!empty($show_sizes["landscape"]))
                                    {
                                        foreach ($show_sizes["landscape"] as $key => $value)
                                        {
                                            ?>
                                                <input type="text" value="<?php echo $value["width"]; ?>" name="sizes[<?php echo $count_fields; ?>][width]" size="1" />
                                                <input type="text" value="<?php echo $value["height"]; ?>" name="sizes[<?php echo $count_fields; ?>][height]" size="1" />
                                                <input type="text" value="<?php echo $value["price"]; ?>" name="sizes[<?php echo $count_fields; ?>][price]" size="1" />
                                                <button type="button" class="button_delete" data-group="landscape" data-row="<?php echo $key; ?>">Delete</button><br>
                                            <?php
                                            $count_fields++;
                                        }
                                    }
                                    else
                                    {
                                        echo "No existing sizes.<br>Add the new size.";
                                        $count_empty_fields++;
                                    }
                                ?>
                            </div>
                            <div class="col-12 col-sm-3">
                                <hr/>
                                Portrait<br>
                                <?php
                                    if(!empty($show_sizes["portrait"]))
                                    {
                                        foreach ($show_sizes["portrait"] as $key => $value)
                                        {
                                            ?>
                                                <input type="text" value="<?php echo $value["width"]; ?>" name="sizes[<?php echo $count_fields; ?>][width]" size="1" />
                                                <input type="text" value="<?php echo $value["height"]; ?>" name="sizes[<?php echo $count_fields; ?>][height]" size="1" />
                                                <input type="text" value="<?php echo $value["price"]; ?>" name="sizes[<?php echo $count_fields; ?>][price]" size="1" />
                                                <button type="button" class="button_delete" data-group="portrait" data-row="<?php echo $key; ?>">Delete</button><br>
                                            <?php
                                            $count_fields++;
                                        }
                                    }
                                    else
                                    {
                                        echo "No existing sizes.<br>Add the new size.";
                                        $count_empty_fields++;
                                    }
                                ?>
                            </div>
                            <div class="dimensions-item col-12 col-sm-3">
                                <hr/>
                                Widescreen<br>
                                <?php
                                    if(!empty($show_sizes["widescreen"]))
                                    {
                                        foreach ($show_sizes["widescreen"] as $key => $value)
                                        {
                                            ?>
                                                <input type="text" value="<?php echo $value["width"]; ?>" name="sizes[<?php echo $count_fields; ?>][width]" size="1" />
                                                <input type="text" value="<?php echo $value["height"]; ?>" name="sizes[<?php echo $count_fields; ?>][height]" size="1" />
                                                <input type="text" value="<?php echo $value["price"]; ?>" name="sizes[<?php echo $count_fields; ?>][price]" size="1" />
                                                <button type="button" class="button_delete" data-group="widescreen" data-row="<?php echo $key; ?>">Delete</button><br>
                                            <?php
                                            $count_fields++;
                                        }
                                    }
                                    else
                                    {
                                        echo "No existing sizes.<br>Add the new size.";
                                        $count_empty_fields++;
                                    }
                                ?>
                            </div>
                        </div>
                        <input type="text" value="" hidden id="delete_group" form="form_size" name="delete_group" class="hidden_inputs" />
                        <input type="text" value="" hidden id="delete_row" form="form_size" name="delete_row" class="hidden_inputs" />
                    </form>
                    <div class="row">
                        <div class="col-xs-12">
                            <hr>
                            <button type="submit" name="submit_save_changes_sizes" form="form_size" <?php if($count_empty_fields == 4) { echo "disabled"; } ?> >Save changes</button>
                            <label id="add_new_size" >Add new size?</label>
                            <form action="" method="POST" id="form_new_size" style="display: none;">
                                <br>Width: <input type="text" name="width" placeholder="e.g. 10..." size="1" />
                                Height: <input type="text" name="height" placeholder="e.g. 6..." size="1" />
                                Price: <input type="text" name="price" placeholder=" € $ £ " size="1" />
                                <button type="submit" name="confirm_add_new_size" form="form_new_size" >Add</button> <button type="reset">Reset</button>
                            </form>
                            <hr>
                        </div>
                    </div>
                </div>
                <div id="canvas" class="tab-pane fade">
                    <h3>Canvas options</h3>
                    <input type="text" name="option_name"       value="" id="option_name"       class="hidden_inputs" form="" hidden />
                    <input type="text" name="option_row"        value="" id="option_row"        class="hidden_inputs" form="" hidden />
                    <input type="text" name="option_operation"  value="" id="option_operation"  class="hidden_inputs" form="" hidden />
                    <div class="row">
                        <div class="col-12 col-sm-4">
                            Option "Canvas sort"<hr/>
                            <?php
                                if(!empty($canvas_options["canvas_sort"]))
                                {
                                    ?>
                                        <form action="" method="POST" id="form_canvas_sort">
                                            <?php
                                                foreach ($canvas_options["canvas_sort"] as $key => $value)
                                                {
                                                    ?>
                                                        <input type="text" value="<?php echo $value["name"]; ?>" name="option[canvas_sort][<?php echo $key; ?>][name]" />
                                                        price: <input type="number" value="<?php echo $value["price"]; ?>" name="option[canvas_sort][<?php echo $key; ?>][price]" step="0.01" />

                                                        <button type="button" data-operation="delete"   data-row="<?php echo $key; ?>" data-option="canvas_sort" data-form="form_canvas_sort" class="button_option" >Delete</button>
                                                        <button type="button" data-operation="save"     data-row="<?php echo $key; ?>" data-option="canvas_sort" data-form="form_canvas_sort" class="button_option" >Save</button>
                                                        <br/><br/>
                                                    <?php
                                                }
                                            ?>
                                        </form>
                                    <?php
                                }
                                else
                                {
                                    echo "No existing options.<br>Add the new options.<br/><br/>";
                                    $count_empty_fields++;
                                }
                            ?>
                            <label id="add_new_canvas_sort">Add new canvas sort?</label><br/>
                            <form action="" method="POST" id="form_add_canvas_sort" style="display: none;" >
                                <hr/>
                                New canvas sort:<br/>
                                <input type="text" name="canvas_sort[name]" placeholder="Some sort..." />
                                <input type="number" name="canvas_sort[price]" placeholder=".. € $ £" step="0.01" /><br/><br/>
                                <button type="submit" name="confirm_add_canvas_sort" form="form_add_canvas_sort" >Add</button>
                                <button type="reset">Reset</button>
                                <hr/>
                            </form>
                        </div>
                        <div class="col-12 col-sm-4">
                            Option "Canvas edges"<hr/>
                            <?php
                                if(!empty($canvas_options["canvas_edges"]))
                                {
                                    ?>
                                        <form action="" method="POST" id="form_canvas_edges">
                                            <?php
                                                foreach ($canvas_options["canvas_edges"] as $key => $value)
                                                {
                                                    ?>
                                                        <input type="text" value="<?php echo $value["name"]; ?>" name="option[canvas_edges][<?php echo $key; ?>][name]"/>
                                                        price: <input type="number" value="<?php echo $value["price"]; ?>" name="option[canvas_edges][<?php echo $key; ?>][price]" step="0.01" />

                                                        <button type="button" data-operation="delete" data-row="<?php echo $key; ?>" data-option="canvas_edges" data-form="form_canvas_edges" class="button_option" >Delete</button>
                                                        <button type="button" data-operation="save" data-row="<?php echo $key; ?>" data-option="canvas_edges" data-form="form_canvas_edges" class="button_option" >Save</button>
                                                        <br/><br/>
                                                    <?php
                                                }
                                            ?>
                                        </form>
                                    <?php
                                }
                                else
                                {
                                    echo "No existing options.<br>Add the new options.<br/><br/>";
                                    $count_empty_fields++;
                                }
                            ?>
                            <label id="add_new_canvas_edges">Add new canvas edges?</label><br/>
                            <form action="" method="POST" id="form_add_canvas_edges" style="display: none;" >
                                <hr/>
                                New canvas edges:<br/>
                                <input type="text" name="canvas_edges[name]" placeholder="Some edges..." />
                                <input type="number" name="canvas_edges[price]" placeholder="... € $ £" step="0.01" /><br/><br/>
                                <button type="submit" name="confirm_add_canvas_edges" form="form_add_canvas_edges" >Add</button>
                                <button type="reset">Reset</button>
                                <hr/>
                            </form>
                        </div>
                        <div class="col-12 col-sm-4">
                            Option "Strech bar"<hr/>
                            <?php
                                if(!empty($canvas_options["canvas_strech_bar"]))
                                {
                                    ?>
                                        <form action="" method="POST" id="form_canvas_edges_strech_bar">
                                            <?php
                                                foreach ($canvas_options["canvas_strech_bar"] as $key => $value)
                                                {
                                                    $path_to_images_thumb = PD_FP_URL . "assets/strech_bar_images/" . basename($value["strech_bar_img"]);
                                                    ?>
                                                        Thickness: <input type="number" value="<?php echo $value["thickness"]; ?>" name="option[canvas_strech_bar][<?php echo $key; ?>][thickness]" step="0.01" />
                                                        Price: <input type="number" value="<?php echo $value["price"]; ?>" name="option[canvas_strech_bar][<?php echo $key; ?>][price]" step="0.01" />
                                                        <br/><br/>
                                                        Thumbnail:
                                                        <img src="<?php echo $path_to_images_thumb; ?>" style="max-width: 25%;"
                                                             alt="Strech bar image" >
                                                        <input type="text" value="<?php echo $value["strech_bar_img"]; ?>" name="option[canvas_strech_bar][<?php echo $key; ?>][strech_bar_img]" hidden />
                                                        <br/><br/>
                                                        <button type="button" data-operation="delete" data-row="<?php echo $key; ?>" data-option="canvas_strech_bar" data-form="form_canvas_edges_strech_bar" class="button_option" >Delete</button>
                                                        <button type="button" data-operation="save" data-row="<?php echo $key; ?>" data-option="canvas_strech_bar" data-form="form_canvas_edges_strech_bar" class="button_option" >Save</button>
                                                        <hr/>
                                                    <?php
                                                }
                                            ?>
                                        </form>
                                    <?php
                                }
                                else
                                {
                                    echo "No existing options.<br>Add the new options.<br/><br/>";
                                    $count_empty_fields++;
                                }
                            ?>
                            <label id="add_new_strech_bar">Add new strech bar?</label><br/>
                            <form action="" method="POST" id="form_add_strech_bar" enctype="multipart/form-data" style="display: none;" >
                                <hr/>
                                New canvas strech bar:<br/>
                                <input type="number" name="canvas_strech_bar[thickness]" placeholder="Thickness" step="0.01" />
                                <input type="number" name="canvas_strech_bar[price]" placeholder=".. € $ £" step="0.01" />
                                <input type="file" name="canvas_strech_bar_image" accept="image/*" id="form_strech_bar" />
                                Supported types: *.jpg, *.jpeg, *.png<br/>Max size: 500 Kb<br/><br/>
                                <button type="submit" name="confirm_add_strech_bar" form="form_add_strech_bar" >Add</button>
                                <button type="reset">Reset</button>
                                <hr/>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="other" class="tab-pane fade">
                    <h3>Other properties</h3>
                    <div class="row">
                        <div class="col-12 col-sm-3">
                            <hr/>
<!--                            wss - widescren settings-->
                            <form action="" method="POST" id="wss">
                                <div class="hint">Widescreen settings <img style="max-width: 8%;" class="pull-right" src="<?php echo PD_FP_URL . "assets/other_images/cursor-question.png"; ?>" /><p style="display: none;" >
                                        <b>Widescreen resolution is selected if the following conditions:<br/>(image width / option width) > (image height / option height).</b></p></div><br/>
                                Width: <input type="text" value="<?php echo $default_values["default_widescreen_values"]["width_param"]; ?>" name="widescreen_defaults[width_param]" size="1" class="pull-right" />
                                <br/><br/>Height: <input type="text" value="<?php echo $default_values["default_widescreen_values"]["height_param"]; ?>" name="widescreen_defaults[height_param]" size="1" class="pull-right" />
                                <br/><br/><button type="submit" name="confirm_change_wss" form="wss" >Save</button> <button type="reset" >Reset</button>
                            </form>
                        </div>
                        <div class="col-12 col-sm-3">
                            <hr/>
                            <form action="" method="POST" id="form_image_quality" >
                                <div class="hint">Default image quality <img style="max-width: 8%;" class="pull-right" src="<?php echo PD_FP_URL . "assets/other_images/cursor-question.png"; ?>" /><p style="display: none;" >
                                        <b>If the resolution of the images below or equal to the critical resolution - the user will be notified.</b></p></div><br/>
                                Low resolution:<input type="number" name="image_quality[low]"        value="<?php echo $default_values["default_image_quality"]["low"]; ?>" size=5" class="pull-right" /><br/><br/>
                                Critical resolution:<input type="number" name="image_quality[critical]"   value="<?php echo $default_values["default_image_quality"]["critical"]; ?>" size="5" class="pull-right" /><br/><br/>
                                High resolution:<input type="number" name="image_quality[high]"       value="<?php echo $default_values["default_image_quality"]["high"]; ?>" size="5" class="pull-right" /><br/><br/>

                                <button type="submit" name="save_image_quality" form="form_image_quality">Save</button>
                                <button type="reset" >Reset</button>
                            </form>
                        </div>
                        <div class="col-12 col-sm-3">
                            <hr/>
                            Choose your cart page <br/><br/>
                            <form action="" method="POST">
                                Pages: <select name="page-dropdown" class="text-center">
                                    <option value=""><?php echo esc_attr( __( 'Select page' ) ); ?></option>
                                    <?php
                                        $pages = get_pages();
                                        foreach ( $pages as $page ) {
                                            $option = '<option value="' . get_page_link( $page->ID ) . '">';
                                            $option .= $page->post_title;
                                            $option .= '</option>';
                                            echo $option;
                                        }
                                    ?>
                                </select>
                                <button type="submit" name="save_cart_pages" class="pull-right">Save</button>
                                <button type="reset" class="pull-right">Reset</button>
                            </form>
                            <br/>
                            <form action="" method="POST">
                                Posts: <select name="page-dropdown" class="text-center" >
                                    <option value=""><?php echo esc_attr( __( 'Select post' ) ); ?></option>
                                    <?php
                                        $posts = get_posts();
                                        foreach ( $posts as $post ) {
                                            $option = '<option value="' . get_permalink( $post->ID ) . '">';
                                            $option .= $post->post_title;
                                            $option .= '</option>';
                                            echo $option;
                                        }
                                    ?>
                                </select>
                                <button type="submit" name="save_cart_posts" class="pull-right">Save</button>
                                <button type="reset" class="pull-right">Reset</button>
                            </form>
                            <hr/>
                            Current location: <a href="<?php echo $default_cart_loc; ?>"><?php echo $default_cart_loc; ?></a>
                        </div>
                        <div class="col-12 col-sm-3">
                            <hr/>
                            Default image in cropper
                            <form action="" method="POST" enctype="multipart/form-data">
                                <input type="file" accept="image/*" name="default_cropp_img" />
                                <button type="submit" name="add_default_cropp_img" >Save</button>
                                <button type="reset" >Reset</button>
                            </form>
                            <hr/>
                            <img src="<?php echo $default_cropp_img["default_cropp_img_url"]; ?>" style="width: 100%;" />
                        </div>
                    </div>
                </div>
                <div id="preview" class="tab-pane fade">
                    <h3>Images interior preview</h3>
                    <div class="row">
                        <div class="col-xs-3">
                            <div class="row">
                                <form action="" method="post" enctype="multipart/form-data" id="interior_images_form">
                                    Choose your image for interior preview:
                                    <input type="file" name="interior_images_preview" form="interior_images_form" accept="image/*" />
                                    <button type="submit" name="save_interior_images">Add</button>
                                    <button type="reset">Reset</button>
                                </form>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
                                    <hr/>
                                    List of your images:<br/>
                                    <form action="" method="post" id="form_delete_image_preview" style="display: none;">
                                        <input type="text" value="" name="delete_img_preview" id="input_delete_image_preview" hidden />
                                    </form>
                                    <?php
                                        if(!empty($images_preview))
                                        {
                                            foreach($images_preview as $key => $value)
                                            {
                                                ?>
                                                    &#10026; <?php echo basename($value); ?>
                                                    <a class="delete_image_preview" data-delete-image="<?php echo $key; ?>">&times</a><br/>
                                                <?php
                                            }
                                            ?>
                                                <div id="imagesModal" class="modal fade">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header"><button class="close" type="button" data-dismiss="modal">&times</button>
                                                                <h4 class="modal-title"></h4>
                                                            </div>
                                                            <div class="modal-body">
                                                                <img src="" alt="Image interior preview" class="img-responsive center-block" />
                                                            </div>
                                                            <div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Закрыть</button></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                        }
                                        else
                                        {
                                            echo "Not existing items...";
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-9">
                            <?php
                                if(!empty($images_preview))
                                {
                                    $count = 0;
                                    echo "<div class='row'>";
                                    foreach($images_preview as $key => $value)
                                    {
                                        $file_name = basename($value);
                                        $path_to_file = PD_FP_URL . "assets/interior_images/" . $file_name;
                                        ?>
                                            <div class="col-xs-4" >
                                                <div style="position: relative;">
                                                    <img
                                                        style="max-width: 100%;"
                                                        data-toggle="modal"
                                                        data-path="<?php echo $path_to_file; ?>"
                                                        data-target="#imagesModal"
                                                        data-file-name="<?php echo $file_name; ?>"
                                                        class="images_preview"
                                                        src="<?php echo $path_to_file; ?>" alt="Image interior preview" class="img-responsive center-block"
                                                    />
                                                </div>
                                            </div>

                                        <?php
                                        $count++;

                                        if($count % 3 == 0)
                                        {
                                            echo "</div>";
                                            echo "<div class='row'>";
                                        }
                                    }

                                    echo "</div>";
                                }
                                else
                                {
                                    echo "Not existing items...";
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>