jQuery(document).ready(function()
{
    console.log(123);
    /*
     ====================================================================================================================
     admin
     */
    jQuery("input").not(".hidden_inputs").prop('required',true);

    jQuery(".button_delete").click(function()
    {
        var del_group = jQuery(this).data("group");
        var del_row = jQuery(this).data("row");

        jQuery("#delete_group").val(del_group);
        jQuery("#delete_row").val(del_row);

        jQuery("#form_size").submit();
    });

    jQuery("#add_new_size").toggle(function()
        {
            jQuery("#form_new_size").show("slow");
        },
        function()
        {
            jQuery("#form_new_size").hide("slow");
        });

    jQuery("#add_new_canvas_sort").toggle(function()
        {
            jQuery("#form_add_canvas_sort").show("slow");
        },
        function()
        {
            jQuery("#form_add_canvas_sort").hide("slow");
        });

    jQuery("#add_new_canvas_edges").toggle(function()
        {
            jQuery("#form_add_canvas_edges").show("slow");
        },
        function()
        {
            jQuery("#form_add_canvas_edges").hide("slow");
        });

    jQuery("#add_new_strech_bar").toggle(function()
        {
            jQuery("#form_add_strech_bar").show("slow");
        },
        function()
        {
            jQuery("#form_add_strech_bar").hide("slow");
        });

    jQuery(".button_option").click(function()
    {
        var delete_option = jQuery(this).data("option");
        var delete_row = jQuery(this).data("row");
        var operation = jQuery(this).data("operation");
        var req_form = jQuery(this).data("form");

        jQuery("#option_name").val(delete_option);
        jQuery("#option_row").val(delete_row);
        jQuery("#option_operation").val(operation);

        jQuery("#option_name").attr("form", req_form);
        jQuery("#option_row").attr("form", req_form);
        jQuery("#option_operation").attr("form", req_form);

        if(operation == "delete")
        {
            if(confirm("Are you sure to delete this item? This action can not be undone!"))
            {
                jQuery("#" + req_form).submit();
            }

            else
            {
                location.reload(false);
            }
        }

        if(operation == "save")
        {
            jQuery("#" + req_form).submit();
        }
    });

    jQuery(".hint").click(function()
    {
        jQuery(this).find("p").toggle("show");
    });

    jQuery(".images_preview").click(function()
    {
        var path_to_image = jQuery(this).data("path");
        var file_name = jQuery(this).data("file-name");

        jQuery("#imagesModal").find("img").attr("src" , path_to_image);
        jQuery("#imagesModal").find("h4").text("Image interior preview " + file_name);
    });

    jQuery("a.delete_image_preview").click(function()
    {
        var delete_image_id = jQuery(this).data("delete-image");
        jQuery("#input_delete_image_preview").attr("value", delete_image_id);
        jQuery("#form_delete_image_preview").submit();
    });
});
