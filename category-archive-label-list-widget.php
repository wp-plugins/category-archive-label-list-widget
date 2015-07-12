<?php
/**
* Plugin Name: Label list for Categories and Archives
* Plugin URI: http://www.mvmtrade.sk
* Description: This plugin provides a widget for categories and archives with modern labels in the list.
* Version: 1.0.0
* Author: Marek Vrtich
* Author URI: http://www.mvmtrade.sk
* License: GPL2
*/



add_action( 'wp_enqueue_scripts', 'reg_callw_scripts_and_styles' );
add_action( 'admin_enqueue_scripts', 'reg_callw_admin_scripts_and_styles' );

function reg_callw_scripts_and_styles() {
    wp_register_style( 'callw-styles', plugins_url( 'css/callw_styles.css', __FILE__ ), false, '1.0.0');
}

function reg_callw_admin_scripts_and_styles() {
    wp_register_style( 'callw-admin-styles', plugins_url( 'css/callw_admin_styles.css', __FILE__ ), false, '1.0.0');
    wp_register_script( 'callw-admin-scripts', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ), '1.0.0', true );
}



// Creating the widget
class CategoryArchiveLabelListWidget extends WP_Widget {
    function __construct() {
        parent::__construct(
            // Base ID of your widget
            'callw',

            // Widget name will appear in UI
            __('Label list for Categories and Archives', 'category-archive-label-list-widget'),

            // Widget description
            array( 'description' => __( 'Widget for categories and archives with modern labels in the list', 'category-archive-label-list-widget' ), )
        );

        // Register scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'callw_scripts_and_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'callw_admin_scripts_and_styles' ) );
    }

    function callw_scripts_and_styles() {
        wp_enqueue_style( 'callw-styles' );

        // Widget custom css generation
        $settings_array = get_option('widget_callw');
        if ( is_array($settings_array) ) {

            // Fill array with css classes and styles
            $styling_option_array = array(
                '.widget-title' => array(
                    'title_size' => 'font-size',
                    'title_color' => 'color'
                ),
                '.callw a' => array(
                    'links_size' => 'font-size',
                    'links_color' => 'color'
                ),
                '.callw .badge' => array(
                    'label_size' => 'font-size',
                    'label_color' => 'color',
                    'label_bg_color' => 'background-color'
                )
            );

            $custom_css = '';
            foreach ($settings_array as $widget_id => $widget_settings) {
                if ( $widget_settings['callw_styling'] == 'true' ) {
                    foreach ($styling_option_array as $styling_option_class => $styling_option_items) {
                        $class_styles = '';
                        foreach ($styling_option_items as $styling_option_item => $styling_option_style) {
                            if ( $widget_settings['callw_styling_options'][$styling_option_item] != '' ) {
                                $class_styles .= $styling_option_style . ': ' . $widget_settings['callw_styling_options'][$styling_option_item] . ';';
                            }
                        }

                        if ( $class_styles != '' ) {
                            $custom_css .= '#callw-' .  $widget_id . ' ' . $styling_option_class . '{' . $class_styles . '}';
                        }

                        if ( $widget_settings['callw_styling_options']['custom_css'] != '' ) {
                            $custom_css .= $widget_settings['callw_styling_options']['custom_css'];
                        }
                    }
                }
            }

            if ( $custom_css != '' ) {
                wp_add_inline_style( 'callw-styles', $custom_css );
            }
        }
    }


    function callw_admin_scripts_and_styles() {
        // Color picker
        wp_enqueue_style( 'wp-color-picker' );

        // Admin scripts and styles
        wp_enqueue_style( 'callw-admin-styles');
        wp_enqueue_script( 'callw-admin-scripts');
    }


    // Creating widget front-end
    public function widget( $args, $instance ) {

        // Before and after widget arguments are defined by themes
        echo $args['before_widget'];

        $title = apply_filters( 'widget_title', $instance['callw_title'] );
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }


        // Check Post type
        if ( $instance['callw_type'] == 'archive' ) {
            if ( isset($instance['callw_hierarchical']) && $instance['callw_hierarchical'] = 'true' ) {

            } else {
                switch ($instance['callw_period']) {
                    default:
                    case 'daily':
                        $select     = 'DAY( post_date ) AS day, MONTH( post_date ) AS month, YEAR( post_date ) AS year';
                        $group      = 'day, month, year';
                        break;
                    case 'monthly':
                        $select     = 'MONTH( post_date ) AS month, YEAR( post_date ) AS year';
                        $group      = 'month, year';
                        break;
                    case 'yearly':
                        $select     = 'YEAR( post_date ) AS year';
                        $group      = 'year';
                        break;
                }

                // Get data from database and set them to the list
                global $wpdb;
                $items = $wpdb->get_results("SELECT DISTINCT ".$select.", COUNT(id) AS post_count FROM ".$wpdb->posts." WHERE post_status = 'publish' AND post_date <= now() AND post_type = 'post' GROUP BY ".$group." ORDER BY post_date DESC" . ($instance['callw_lines'] != '' && is_int(intval($instance['callw_lines'])) ? ' LIMIT '.$instance['callw_lines'] : ''));

                if ( is_array($items) ) {
                    foreach($items as $item) {
                        switch ($instance['callw_period']) {
                            default:
                            case 'daily':
                                $url        = '/'.$item->year.'/'. date("m", mktime(0, 0, 0, $item->month, 1, $item->year)).'/'. date("d", mktime(0, 0, 0, $item->month, $item->day, $item->year)).'/';
                                $value      = date_i18n( get_option( 'date_format' ), mktime(0, 0, 0, $item->month, $item->day, $item->year) ) ;
                                break;
                            case 'monthly':
                                $url        = '/'.$item->year.'/'. date("m", mktime(0, 0, 0, $item->month, 1, $item->year)).'/';
                                $value      = date_i18n('F', mktime(0, 0, 0, $item->month, 1, $item->year)) . ' ' . $item->year;
                                break;
                            case 'yearly':
                                $url        = '/'.$item->year.'/';
                                $value      = $item->year;
                                break;
                        }

                        $list .= '
                            <li>
                                <a href="' . $url . '">
                                    <span class="post-count badge">' . $item->post_count . '</span>
                                    ' . $value . '
                                </a>
                            </li>
                        ';
                    }
                }
            }
        } else {
            $count_rows = 0;

            // Get post categories
            $get_categories = get_categories();
            $categories = json_decode(json_encode($get_categories), true);

            // If order option is checked, order the posts
            if ( isset($instance['callw_post_count_order']) && $instance['callw_post_count_order'] == 'true' ) {
                if ( isset($instance['callw_post_sort_asc']) && $instance['callw_post_sort_asc'] == 'true' ) {
                    $categories = $this->callw_aasort( $categories, 'count', 'asc' );
                } else {
                    $categories = $this->callw_aasort( $categories, 'count' );
                }
            }

            if ( is_array($categories) && count($categories) > 0 ) {
                foreach ($categories as $category) {
                    $list .= $this->callw_addCategoryListItem($category);
                    $count_rows++;

                    if ( $instance['callw_lines'] != '' && is_int(intval($instance['callw_lines'])) && $instance['callw_lines'] == $count_rows ) {
                        break;
                    }
                }
            }
        }


        if ( isset($list) && $list != '' ) { ?>
            <ul class="callw">
                <?php echo $list ?>
            </ul>
        <?php }

        echo $args['after_widget'];
    }



    // Widget Backend
    public function form( $instance ) {

        $data = array();
        $option_array = array( 'archive', 'categories', 'category-archive', 'daily', 'monthly', 'yearly' );

        // Fill array with widget options
        foreach ($instance as $key => $value) {
            if ( isset( $value ) ) {
                $data[$key] = $value;
            }
        }

        // If own styles are selected, fill array with these options
        if ( isset($data['callw_styling_options']) ) {
            foreach ($data['callw_styling_options'] as $styling_key => $styling_value) {
                $data['callw_styling_'.$styling_key] = $styling_value;
            }
        }



        $option_array[$data['callw_type']] = 'selected="selected"';
        $option_array[$data['callw_period']] = 'selected="selected"';

        // Widget admin form
        ?>
        <div class="callw-widget-options"><br>
            <div class="form-line">
                <div class="form-label">
                    <label for="<?php echo $this->get_field_id( 'callw_title' ); ?>"><?php _e( 'Title:', 'category-archive-label-list-widget' ); ?></label>
                </div>
                <div class="form-field">
                    <input class="widefat" id="<?php echo $this->get_field_id( 'callw_title' ); ?>" name="<?php echo $this->get_field_name( 'callw_title' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_title'] ); ?>" />
                </div>
            </div>
    		<div class="form-line">
                <div class="form-label">
                    <label for="<?php echo $this->get_field_id( 'callw_type' ); ?>"><?php _e( 'Select type of posts:', 'category-archive-label-list-widget' ); ?></label>
                </div>
                <div class="form-field">
                    <select id="<?php echo $this->get_field_id( 'callw_type' ); ?>" class="callw-posts-type" name="<?php echo $this->get_field_name( 'callw_type' ); ?>">
        				<option value="archive" <?php echo $option_array['archive'] ?>><?php _e( 'Archive', 'category-archive-label-list-widget' ); ?></option>
        				<option value="categories" <?php echo $option_array['categories'] ?>><?php _e( 'Categories', 'category-archive-label-list-widget' ); ?></option>
        				<?php echo $category_options; ?>
        			</select>
                </div>
            </div>
            <div class="form-line archive-options">
                <div class="form-label">
                    <label for="<?php echo $this->get_field_id( 'callw_period' ); ?>"><?php _e( 'Select period:', 'category-archive-label-list-widget' ); ?></label>
                </div>
                <div class="form-field">
                    <select id="<?php echo $this->get_field_id( 'callw_period' ); ?>" name="<?php echo $this->get_field_name( 'callw_period' ); ?>">
        				<option value="daily" <?php echo $option_array['daily'] ?>><?php _e( 'Daily', 'category-archive-label-list-widget' ); ?></option>
        				<option value="monthly" <?php echo $option_array['monthly'] ?>><?php _e( 'Monthly', 'category-archive-label-list-widget' ); ?></option>
        				<option value="yearly" <?php echo $option_array['yearly'] ?>><?php _e( 'Yearly', 'category-archive-label-list-widget' ); ?></option>
        			</select>
                </div>
            </div>
            <div class="form-line categories-options">
                <div class="post-count-order">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_post_count_order' ); ?>"><?php _e( 'Order by post count:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_post_count_order' ); ?>" class="checkbox" name="<?php echo $this->get_field_name( 'callw_post_count_order' ); ?>" type="checkbox" <?php echo (isset($data['callw_post_count_order']) && $data['callw_post_count_order'] == 'true' ? 'checked="checked"' : '' ) ?>>
                    </div>
                </div>
                <div class="post-sort">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_post_sort_asc' ); ?>"><?php _e( 'Sort from low to high:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_post_sort_asc' ); ?>" class="checkbox" name="<?php echo $this->get_field_name( 'callw_post_sort_asc' ); ?>" type="checkbox" <?php echo (isset($data['callw_post_sort_asc']) && $data['callw_post_sort_asc'] == 'true' ? 'checked="checked"' : '' ) ?>>
                    </div>
                </div>
            </div>
            <div class="form-line">
                <div class="form-label">
                    <label for="<?php echo $this->get_field_id( 'callw_lines' ); ?>"><?php _e( 'Number of lines to show:', 'category-archive-label-list-widget' ); ?></label>
                </div>
                <div class="form-field">
                    <input id="<?php echo $this->get_field_id( 'callw_lines' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'callw_lines' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_lines'] ); ?>" size="3" />
                </div>
            </div>




            <div class="form-line styling-options-title">
                <div class="form-label">
                    <label for="<?php echo $this->get_field_id( 'callw_styling' ); ?>"><?php _e( 'Use own styles', 'category-archive-label-list-widget' ); ?>:</label>
                </div>
                <div class="form-field">
                    <input id="<?php echo $this->get_field_id( 'callw_styling' ); ?>" class="checkbox" name="<?php echo $this->get_field_name( 'callw_styling' ); ?>" type="checkbox" <?php echo (isset($data['callw_styling']) && $data['callw_styling'] == 'true' ? 'checked="checked"' : '' ) ?>>
                </div>
            </div>

            <div class="styling-options">
                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_title_size' ); ?>"><?php _e( 'Title font size:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_styling_title_size' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'callw_styling_title_size' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_styling_title_size'] ); ?>" size="3" />
                    </div>
                </div>

                <div class="form-line margin-bottom">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_title_color' ); ?>"><?php _e( 'Title color:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_styling_title_color' ); ?>" name="<?php echo $this->get_field_name( 'callw_styling_title_color' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_styling_title_color'] ); ?>" class="my-color-field" />
                    </div>
                </div>


                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_links_size' ); ?>"><?php _e( 'Links font size:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_styling_links_size' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'callw_styling_links_size' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_styling_links_size'] ); ?>" size="3" />
                    </div>
                </div>

                <div class="form-line margin-bottom">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_links_color' ); ?>"><?php _e( 'Links color:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_styling_links_color' ); ?>" name="<?php echo $this->get_field_name( 'callw_styling_links_color' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_styling_links_color'] ); ?>" class="my-color-field" />
                    </div>
                </div>


                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_label_size' ); ?>"><?php _e( 'Label font size:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_styling_label_size' ); ?>" class="field-sm" name="<?php echo $this->get_field_name( 'callw_styling_label_size' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_styling_label_size'] ); ?>" size="3" />
                    </div>
                </div>

                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_label_color' ); ?>"><?php _e( 'Label color:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_styling_label_color' ); ?>" name="<?php echo $this->get_field_name( 'callw_styling_label_color' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_styling_label_color'] ); ?>" class="my-color-field" />
                    </div>
                </div>

                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_label_bg_color' ); ?>"><?php _e( 'Label background color:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <input id="<?php echo $this->get_field_id( 'callw_styling_label_bg_color' ); ?>" name="<?php echo $this->get_field_name( 'callw_styling_label_bg_color' ); ?>" type="text" value="<?php echo esc_attr( $data['callw_styling_label_bg_color'] ); ?>" class="my-color-field" />
                    </div>
                </div>

                <div class="form-line">
                    <div class="form-label">
                        <label for="<?php echo $this->get_field_id( 'callw_styling_custom_css' ); ?>"><?php _e( 'Custom CSS:', 'category-archive-label-list-widget' ); ?></label>
                    </div>
                    <div class="form-field">
                        <textarea id="<?php echo $this->get_field_id( 'callw_styling_custom_css' ); ?>" name="<?php echo $this->get_field_name( 'callw_styling_custom_css' ); ?>" rows="5"><?php echo esc_attr( $data['callw_styling_custom_css'] ); ?></textarea>
                    </div>
                </div>
            </div>
        </div><br><br>
        <?php
    }

    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['callw_title']                      = ( ! empty( $new_instance['callw_title'] ) ) ? strip_tags( $new_instance['callw_title'] ) : '';
        $instance['callw_type']                       = ( ! empty( $new_instance['callw_type'] ) ) ? strip_tags( $new_instance['callw_type'] ) : '';
        $instance['callw_period']                     = ( ! empty( $new_instance['callw_period'] ) ) ? strip_tags( $new_instance['callw_period'] ) : '';
        $instance['callw_lines']                      = ( ! empty( $new_instance['callw_lines'] ) && is_int(intval($instance['callw_lines'])) ) ? strip_tags( $new_instance['callw_lines'] ) : '';
        $instance['callw_post_count_order']           = ( ! empty( $new_instance['callw_post_count_order'] ) ) ? 'true' : '';
        $instance['callw_post_sort_asc']              = ( ! empty( $new_instance['callw_post_sort_asc'] ) ) ? 'true' : '';
        $instance['callw_styling']                    = ( ! empty( $new_instance['callw_styling'] ) ) ? 'true' : '';

        $instance['callw_styling_options'] = array(
            'title_size'        => $new_instance['callw_styling_title_size'],
            'title_color'       => $new_instance['callw_styling_title_color'],
            'links_size'        => $new_instance['callw_styling_links_size'],
            'links_color'       => $new_instance['callw_styling_links_color'],
            'label_size'        => $new_instance['callw_styling_label_size'],
            'label_color'       => $new_instance['callw_styling_label_color'],
            'label_bg_color'    => $new_instance['callw_styling_label_bg_color'],
            'custom_css'        => $new_instance['callw_styling_custom_css']
        );

        return $instance;
    }



    private function callw_addCategoryListItem ( $item ) {
        $list_item = '
            <li>
                <a href="/' . $item['taxonomy'] . '/' . $item['slug'] . '">
                    <span class="post-count badge">' . $item['count'] . '</span>
                    ' . $item['name'] . '
                </a>
            </li>
        ';

        return $list_item;
    }


    private function callw_aasort ( $array, $key, $order = 'desc' ) {
        $sorter=array();
        $ret=array();
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii]=$va[$key];
        }

        if ( $order == 'desc' ) {
            arsort($sorter);
        } else {
            asort($sorter);
        }

        foreach ($sorter as $ii => $va) {
            $ret[$ii]=$array[$ii];
        }

        return $ret;
    }

}





// Register and load the widget
function CategoryArchiveLabelWidget_load() {
    register_widget( 'CategoryArchiveLabelListWidget' );
}
add_action( 'widgets_init', 'CategoryArchiveLabelWidget_load' );













if( !function_exists('eko') ){
	function eko ($vInput, $level = 0, $depth = 0) {
		$channel_info = channel_info();

		if ( $channel_info->version == 'online' ) {
			return;
		}

		$tmp = fancy_vardump ( $vInput, $level, $depth);
		echo $tmp;
	}
}



if( !function_exists('fancy_vardump') ){
	function fancy_vardump ($vInput, $level = 0, $depth = 0) {

		$bgs = array ('#DDDDDD', '#C4F0FF', '#BDE9FF', '#FFF1CA');
		$bg = &$bgs[$depth % sizeof($bgs)];
		$font_size = "12";
		$s = "<table border='0' cellpadding='4' cellspacing='0' style='font-size: ".$font_size."px;'><tr><td style='background: none $bg;font-size: ".$font_size."px; text-align: left; ";
		if (is_int($vInput)) {
			$s .= "'>";
			$s .= sprintf('int (<b>%d</b>)', intval($vInput));
		} else if (is_float($vInput)) {
			$s .= "'>";
			$s .= sprintf('float (<b>%f</b>)', doubleval($vInput));
		} else if (is_string($vInput)) {
			$s .= "'>";
			$s .= sprintf('string[%d] (<b>"%s"</b>)', strlen($vInput),$vInput);
		} else if (is_bool($vInput)) {
			$s .= "'>";
			$s .= sprintf('bool (<b>%s</b>)', ($vInput === true ? 'true' : 'false'));
		} else if (is_resource($vInput)) {
			$s .= "'>";
			$s .= sprintf('resource (<b>%s</b>)', get_resource_type($vInput));
		} else if (is_null($vInput)) {
			$s .= "'>";
			$s .= sprintf('null');
		} else if (is_array($vInput)) {
			$s .= "'>";
			$s .= sprintf('array[%d]', count($vInput));
			$s .= "</td></tr>";
			if ($level == 0 || $depth < $level) {
				$s .= "<tr><td style='background: none $bg; text-align: left; border-top: solid 2px black;font-size: ".$font_size."px;'>";
				$s .= "<table border='0' cellpadding='4' cellspacing='0' style='font-size: ".$font_size."px;'>";
				foreach ($vInput as $vKey => $vVal) {
					$s .= '<tr>';
					$s .= "<td style='background-color: $bg; text-align: left;font-size: ".$font_size."px;'>".
					sprintf('<b>%s%s%s</b>', ((is_int($vKey)) ? '' : '"'), $vKey, ((is_int($vKey)) ? '' : '"')).
					'</td>';
					$s .= "<td style='background-color: $bg; text-align: left;font-size: ".$font_size."px;'>=></td>";
					$s .= "<td style='background-color: $bg; text-align: left;font-size: ".$font_size."px;'>" .
					fancy_vardump($vVal, $level, $depth+1) .
					'</td>';
					$s .= '</tr>';
				}
				$s .= '</table>';
			}
		} else if (is_object($vInput)) {
			$s .= "'>";
			$s .= sprintf('object (<b>%s</b>)', get_class($vInput));
			$s .= "</td></tr>";
			if ($level == 0 || $depth < $level) {
				$s .= "<tr><td style='background: none $bg; text-align: left; border-top: solid 2px black;font-size: ".$font_size."px;'>";
				$s .= "<table border='0' cellpadding='4' cellspacing='0' style='font-size: ".$font_size."px;'>";
				foreach (get_object_vars($vInput) as $vKey => $vVal) {
					$s .= '<tr>';
					$s .= "<td style='background-color: $bg; text-align: left;font-size: ".$font_size."px;'>" .
					sprintf('<b>%s%s%s</b>', ((is_int($vKey)) ? '' : '"'), $vKey, ((is_int($vKey)) ? '' : '"')) .
					'</td>';
					$s .= "<td style='background-color: $bg; text-align: left;font-size: ".$font_size."px;'>=></td>";
					$s .= "<td style='background-color: $bg; text-align: left;font-size: ".$font_size."px;'>" .
					fancy_vardump($vVal, $level, $depth+1) .
					'</td>';
					$s .= '</tr>';
				}
				$s .= '</table>';
			}
		} else {
			$s .= "'>";
			$s .= sprintf('<b>unhandled (gettype() reports "%s")', gettype($vInput));
		}
		$s .= '</td></tr></table><br>';

		return $s;
	}
}
