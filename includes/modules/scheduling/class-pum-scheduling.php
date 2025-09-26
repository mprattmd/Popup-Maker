<?php
/**
 * Popup Scheduling Module
 *
 * @package PopupMaker
 * @subpackage Modules\Scheduling
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PUM_Scheduling {

    /**
     * Instance
     */
    private static $instance;

    /**
     * Get instance
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_filter( 'pum_popup_is_loadable', array( $this, 'check_schedule' ), 10, 2 );
        
        if ( is_admin() ) {
            require_once dirname( __FILE__ ) . '/admin.php';
            PUM_Scheduling_Admin::instance();
        }
    }

    /**
     * Check if popup should load based on schedule
     *
     * @param bool $is_loadable
     * @param int  $popup_id
     * @return bool
     */
    public function check_schedule( $is_loadable, $popup_id ) {
        if ( ! $is_loadable ) {
            return $is_loadable;
        }

        $schedules = $this->get_popup_schedules( $popup_id );
        
        if ( empty( $schedules ) ) {
            return $is_loadable;
        }

        $current_time = current_time( 'timestamp' );
        $matches_schedule = false;

        foreach ( $schedules as $schedule ) {
            if ( $this->matches_schedule( $schedule, $current_time ) ) {
                $matches_schedule = true;
                break;
            }
        }

        return $matches_schedule;
    }

    /**
     * Get popup schedules
     *
     * @param int $popup_id
     * @return array
     */
    public function get_popup_schedules( $popup_id ) {
        return get_post_meta( $popup_id, '_pum_schedules', true ) ?: array();
    }

    /**
     * Check if schedule matches current time
     *
     * @param array $schedule
     * @param int   $current_time
     * @return bool
     */
    private function matches_schedule( $schedule, $current_time ) {
        $type = isset( $schedule['type'] ) ? $schedule['type'] : 'date_range';

        switch ( $type ) {
            case 'start_date':
                return $this->check_start_date( $schedule, $current_time );
            
            case 'end_date':
                return $this->check_end_date( $schedule, $current_time );
            
            case 'date_range':
                return $this->check_date_range( $schedule, $current_time );
            
            case 'chosen_dates':
                return $this->check_chosen_dates( $schedule, $current_time );
            
            case 'office_hours':
                return $this->check_office_hours( $schedule, $current_time );
            
            default:
                return true;
        }
    }

    /**
     * Check start date schedule
     */
    private function check_start_date( $schedule, $current_time ) {
        if ( empty( $schedule['start_date'] ) ) {
            return true;
        }

        $start = strtotime( $schedule['start_date'] );
        
        if ( ! empty( $schedule['start_time'] ) ) {
            $start = strtotime( $schedule['start_date'] . ' ' . $schedule['start_time'] );
        }

        return $current_time >= $start;
    }

    /**
     * Check end date schedule
     */
    private function check_end_date( $schedule, $current_time ) {
        if ( empty( $schedule['end_date'] ) ) {
            return true;
        }

        $end = strtotime( $schedule['end_date'] );
        
        if ( ! empty( $schedule['end_time'] ) ) {
            $end = strtotime( $schedule['end_date'] . ' ' . $schedule['end_time'] );
        }

        return $current_time <= $end;
    }

    /**
     * Check date range schedule
     */
    private function check_date_range( $schedule, $current_time ) {
        $start_valid = $this->check_start_date( $schedule, $current_time );
        $end_valid = $this->check_end_date( $schedule, $current_time );

        return $start_valid && $end_valid;
    }

    /**
     * Check chosen dates schedule
     */
    private function check_chosen_dates( $schedule, $current_time ) {
        if ( empty( $schedule['chosen_dates'] ) || ! is_array( $schedule['chosen_dates'] ) ) {
            return false;
        }

        $current_date = date( 'Y-m-d', $current_time );

        foreach ( $schedule['chosen_dates'] as $date ) {
            if ( $date === $current_date ) {
                // Check time if specified
                if ( ! empty( $schedule['start_time'] ) && ! empty( $schedule['end_time'] ) ) {
                    $start = strtotime( $current_date . ' ' . $schedule['start_time'] );
                    $end = strtotime( $current_date . ' ' . $schedule['end_time'] );
                    
                    return $current_time >= $start && $current_time <= $end;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Check office hours schedule
     */
    private function check_office_hours( $schedule, $current_time ) {
        if ( empty( $schedule['days'] ) || ! is_array( $schedule['days'] ) ) {
            return false;
        }

        $current_day = strtolower( date( 'l', $current_time ) );

        if ( ! in_array( $current_day, $schedule['days'] ) ) {
            return false;
        }

        // Check time range
        if ( ! empty( $schedule['start_time'] ) && ! empty( $schedule['end_time'] ) ) {
            $current_date = date( 'Y-m-d', $current_time );
            $start = strtotime( $current_date . ' ' . $schedule['start_time'] );
            $end = strtotime( $current_date . ' ' . $schedule['end_time'] );
            
            return $current_time >= $start && $current_time <= $end;
        }

        return true;
    }
}

// Initialize
PUM_Scheduling::instance();