<?php
/**
 * Scheduling Admin Interface
 *
 * @package PopupMaker
 * @subpackage Modules\Scheduling\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PUM_Scheduling_Admin {

    private static $instance;

    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'pum_popup_settings_fields', array( $this, 'add_scheduling_tab' ) );
        add_action( 'save_post_popup', array( $this, 'save_schedules' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Add scheduling tab to popup settings
     */
    public function add_scheduling_tab( $tabs ) {
        $tabs['scheduling'] = array(
            'label' => __( 'Scheduling', 'popup-maker' ),
            'icon'  => 'dashicons-calendar-alt',
            'fields' => $this->get_scheduling_fields(),
        );

        return $tabs;
    }

    /**
     * Get scheduling fields
     */
    private function get_scheduling_fields() {
        return array(
            'scheduling_enabled' => array(
                'label'   => __( 'Enable Scheduling', 'popup-maker' ),
                'type'    => 'checkbox',
                'desc'    => __( 'Enable schedule-based display for this popup', 'popup-maker' ),
                'std'     => false,
            ),
            'schedules' => array(
                'type'     => 'html',
                'callback' => array( $this, 'render_schedules_interface' ),
            ),
        );
    }

    /**
     * Render schedules interface
     */
    public function render_schedules_interface( $args ) {
        global $post;
        $schedules = get_post_meta( $post->ID, '_pum_schedules', true ) ?: array();
        ?>
        <div id="pum-scheduling-interface" class="pum-field">
            <div class="pum-schedules-list">
                <?php foreach ( $schedules as $index => $schedule ) : ?>
                    <?php $this->render_schedule_row( $schedule, $index ); ?>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="button pum-add-schedule">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e( 'Add Schedule', 'popup-maker' ); ?>
            </button>

            <!-- Schedule Template (hidden) -->
            <script type="text/template" id="pum-schedule-template">
                <?php $this->render_schedule_row( array(), '{{INDEX}}' ); ?>
            </script>
        </div>
        <?php
    }

    /**
     * Render individual schedule row
     */
    private function render_schedule_row( $schedule = array(), $index = 0 ) {
        $type = isset( $schedule['type'] ) ? $schedule['type'] : 'date_range';
        ?>
        <div class="pum-schedule-row" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="pum-schedule-header">
                <select name="_pum_schedules[<?php echo esc_attr( $index ); ?>][type]" class="pum-schedule-type">
                    <option value="start_date" <?php selected( $type, 'start_date' ); ?>><?php _e( 'Start Date', 'popup-maker' ); ?></option>
                    <option value="end_date" <?php selected( $type, 'end_date' ); ?>><?php _e( 'End Date', 'popup-maker' ); ?></option>
                    <option value="date_range" <?php selected( $type, 'date_range' ); ?>><?php _e( 'Date Range', 'popup-maker' ); ?></option>
                    <option value="chosen_dates" <?php selected( $type, 'chosen_dates' ); ?>><?php _e( 'Chosen Dates', 'popup-maker' ); ?></option>
                    <option value="office_hours" <?php selected( $type, 'office_hours' ); ?>><?php _e( 'Office Hours', 'popup-maker' ); ?></option>
                </select>
                
                <button type="button" class="button pum-remove-schedule">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>

            <div class="pum-schedule-fields">
                <!-- Start Date/Time -->
                <div class="pum-schedule-field start-date-field">
                    <label><?php _e( 'Start Date', 'popup-maker' ); ?></label>
                    <input type="date" 
                           name="_pum_schedules[<?php echo esc_attr( $index ); ?>][start_date]" 
                           value="<?php echo esc_attr( isset( $schedule['start_date'] ) ? $schedule['start_date'] : '' ); ?>" />
                </div>

                <div class="pum-schedule-field start-time-field">
                    <label><?php _e( 'Start Time', 'popup-maker' ); ?></label>
                    <input type="time" 
                           name="_pum_schedules[<?php echo esc_attr( $index ); ?>][start_time]" 
                           value="<?php echo esc_attr( isset( $schedule['start_time'] ) ? $schedule['start_time'] : '' ); ?>" />
                </div>

                <!-- End Date/Time -->
                <div class="pum-schedule-field end-date-field">
                    <label><?php _e( 'End Date', 'popup-maker' ); ?></label>
                    <input type="date" 
                           name="_pum_schedules[<?php echo esc_attr( $index ); ?>][end_date]" 
                           value="<?php echo esc_attr( isset( $schedule['end_date'] ) ? $schedule['end_date'] : '' ); ?>" />
                </div>

                <div class="pum-schedule-field end-time-field">
                    <label><?php _e( 'End Time', 'popup-maker' ); ?></label>
                    <input type="time" 
                           name="_pum_schedules[<?php echo esc_attr( $index ); ?>][end_time]" 
                           value="<?php echo esc_attr( isset( $schedule['end_time'] ) ? $schedule['end_time'] : '' ); ?>" />
                </div>

                <!-- Office Hours Days -->
                <div class="pum-schedule-field office-hours-field">
                    <label><?php _e( 'Days of Week', 'popup-maker' ); ?></label>
                    <?php
                    $days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
                    $selected_days = isset( $schedule['days'] ) ? $schedule['days'] : array();
                    
                    foreach ( $days as $day ) :
                        $checked = in_array( $day, $selected_days );
                        ?>
                        <label>
                            <input type="checkbox" 
                                   name="_pum_schedules[<?php echo esc_attr( $index ); ?>][days][]" 
                                   value="<?php echo esc_attr( $day ); ?>"
                                   <?php checked( $checked ); ?> />
                            <?php echo ucfirst( $day ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save schedules
     */
    public function save_schedules( $post_id, $post ) {
        if ( ! isset( $_POST['_pum_schedules'] ) ) {
            delete_post_meta( $post_id, '_pum_schedules' );
            return;
        }

        $schedules = $_POST['_pum_schedules'];
        
        // Sanitize schedules
        $sanitized_schedules = array();
        foreach ( $schedules as $schedule ) {
            $sanitized_schedules[] = array(
                'type'       => sanitize_text_field( $schedule['type'] ),
                'start_date' => sanitize_text_field( $schedule['start_date'] ?? '' ),
                'start_time' => sanitize_text_field( $schedule['start_time'] ?? '' ),
                'end_date'   => sanitize_text_field( $schedule['end_date'] ?? '' ),
                'end_time'   => sanitize_text_field( $schedule['end_time'] ?? '' ),
                'days'       => isset( $schedule['days'] ) ? array_map( 'sanitize_text_field', $schedule['days'] ) : array(),
            );
        }

        update_post_meta( $post_id, '_pum_schedules', $sanitized_schedules );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        global $post;
        if ( ! $post || $post->post_type !== 'popup' ) {
            return;
        }

        wp_enqueue_style( 
            'pum-scheduling-admin', 
            plugins_url( 'assets/css/scheduling-admin.css', dirname( dirname( __FILE__ ) ) ),
            array(),
            PUM_VERSION 
        );

        wp_enqueue_script( 
            'pum-scheduling-admin', 
            plugins_url( 'assets/js/scheduling-admin.js', dirname( dirname( __FILE__ ) ) ),
            array( 'jquery' ),
            PUM_VERSION,
            true 
        );
    }
}