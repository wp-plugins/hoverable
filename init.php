<?php
/*
Plugin Name: Hoverable
Plugin URI: http://forumone.com/
Description: Attach hoverable context to phrases within your posts.
Version: 1.0.3
Author: Matt Gibbs
Author URI: http://forumone.com/

Copyright 2010  Matt Gibbs  (email : mgibbs@forumone.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
class Hoverable
{
    /**
     * Load jQuery and Facebox
     */
    function init() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('facebox', WP_PLUGIN_URL . '/hoverable/assets/facebox.js');
        wp_deregister_script('autosave');
    }

    /**
     * Add the settings box to the admin area
     */
    function admin_init() {
        add_meta_box('hoverable', __('Add Hover Text', 'hoverable'), array($this, 'load_meta_box'), 'post', 'advanced');
        add_meta_box('hoverable', __('Add Hover Text', 'hoverable'), array($this, 'load_meta_box'), 'page', 'advanced');
        add_action('save_post', array($this, 'save_meta_box'), 11, 2);
    }

    function load_meta_box() {
?>
<script type="text/javascript">
jQuery(function() {
    jQuery(".hov-add").click(function() {
        jQuery(".hov-container").append(jQuery(".hov-template").html());
    });
    jQuery(".hov-remove").live('click', function() {
        jQuery(this).parent().parent().remove();
    });
});
</script>
<span class="hov-add">
    <img src="<?php echo WP_PLUGIN_URL; ?>/hoverable/assets/add.png" title="Add" alt="Add" /> Add New
</span>
<div class="hov-template">
<?php
        // Store input boxes into a variable
        ob_start();
?>
    <div class="hov-item">
        <div class="hov-row1">
            <input type="text" name="hover_title[]" class="hov-title" value="@TITLE" />
            <img class="hov-remove" src="<?php echo WP_PLUGIN_URL; ?>/hoverable/assets/remove.png" title="Remove" alt="Remove" />
        </div>
        <div class="hov-row2">
            <textarea name="hover_desc[]" class="hov-desc">@DESC</textarea>
        </div>
    </div>
<?php
        /**
         * Stick a hidden copy of the input boxes into the HTML.
         * This is used to dynamically generate new input boxes when
         * the "Add New" button is clicked
         */
        $hover_form = ob_get_clean();
        $output = str_replace('@TITLE', '', $hover_form);
        echo str_replace('@DESC', '', $output);
?>
</div>
<div class="hov-container">
<?php
        global $post;
        $saved_data = get_post_meta($post->ID, 'hoverable', true);

        // Output input boxes for each item loaded
        if (!empty($saved_data)) {
            foreach ($saved_data as $key => $item) {
                $output = str_replace('@TITLE', $item['hover_title'], $hover_form);
                echo str_replace('@DESC', htmlspecialchars($item['hover_desc']), $output);
            }
        }
?>
</div>
<p>Enter a search string (top box) and its hover caption (bottom box).</p>
<?php
    }

    /**
     * Combine hover_title and hover_desc into an associative array.
     * Then, save the array using update_post_meta. WP automagically
     * converts the array to a serialized string.
     *
     * Note: In WP < 3.0, global $post is unavailable
     */
    function save_meta_box($post_id) {
        $output = array();
        $hover_title = $_POST['hover_title'];
        $hover_desc = $_POST['hover_desc'];

        // Stick the input into an associative array (skip the first element)
        for ($i = 1, $z = count($hover_title); $i < $z; $i++) {
            $output[] = array('hover_title' => $hover_title[$i], 'hover_desc' => $hover_desc[$i]);
        }

        // Save the array as postmeta
        update_post_meta($post_id, 'hoverable', $output);
    }

    /**
     * This is a glorified search/replace. It creates a link for every
     * matching phrase, and sticks the description into a separate, hidden
     * DIV that gets triggered by Facebox.
     */
    function the_content($content) {
        if (is_single() || is_page()) {
            global $post;
            $saved_data = get_post_meta($post->ID, 'hoverable', true);

            if (!empty($saved_data)) {
                foreach ($saved_data as $key => $item) {
                    $hover_title = $item['hover_title'];
                    $hover_desc = $item['hover_desc'];
                    $content = str_replace($hover_title, "<a href=\"#hov$key\" class=\"facebox\">$hover_title</a>", $content);
                    $hidden_divs[$key] = "<div id=\"hov$key\">$hover_desc</div>";
                }
            }
            if (isset($hidden_divs)) {
                return $content . '<div class="hov-modal">' . implode('', $hidden_divs) . '</div>';
            }
        }
        return $content;
    }

    /**
     * Load the public styles
     */
    function wp_head() {
?>
<script type="text/javascript">
jQuery(function() {
    jQuery("a.facebox").facebox({
        loadingImage: "<?php echo WP_PLUGIN_URL; ?>/hoverable/assets/loading.gif",
        closeImage: "<?php echo WP_PLUGIN_URL; ?>/hoverable/assets/closelabel.gif"
    });
    jQuery.facebox.settings.opacity = 0.4;
});
</script>
<link rel="stylesheet" type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/hoverable/style.css" />
<?php
        // Include an override stylesheet
        if (file_exists(STYLESHEETPATH . '/hoverable.css')) {
?>
<link rel="stylesheet" type="text/css" href="<?php echo STYLESHEETPATH; ?>/hoverable.css" />
<?php
        }
    }
}

// Fire up the hooks
$hoverable = new Hoverable();
add_action('init', array($hoverable, 'init'));
add_action('wp_head', array($hoverable, 'wp_head'));
add_action('admin_init', array($hoverable, 'admin_init'));
add_action('admin_head', array($hoverable, 'wp_head'));
add_filter('the_content', array($hoverable, 'the_content'));
