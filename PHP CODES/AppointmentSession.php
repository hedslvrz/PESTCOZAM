<?php
class AppointmentSession {
    public static function initialize($user_id) {
        // Clear any existing appointment session data first
        self::clear();
        
        // Start a fresh appointment session
        $_SESSION['appointment'] = [
            'user_id' => $user_id,
            'data' => [],
            'started_at' => time(), // Add timestamp for tracking session age
            'temp_id' => uniqid('app_') // Temporary ID for the appointment
        ];
    }

    // Get session ID (temporary)
    public static function getTempId() {
        return isset($_SESSION['appointment']) && isset($_SESSION['appointment']['temp_id']) 
            ? $_SESSION['appointment']['temp_id'] 
            : null;
    }

    // Check if appointment is in progress
    public static function isInProgress() {
        return isset($_SESSION['appointment']) && !empty($_SESSION['appointment']['data']);
    }

    public static function saveStep($step, $data) {
        if (!isset($_SESSION['appointment'])) {
            throw new Exception('Appointment session not initialized');
        }
        $_SESSION['appointment']['data'][$step] = $data;
    }

    public static function getAppointmentData() {
        return isset($_SESSION['appointment']) ? $_SESSION['appointment']['data'] : null;
    }

    // Get a specific piece of data from the appointment session
    public static function getData($key, $default = null) {
        if (!isset($_SESSION['appointment']) || !isset($_SESSION['appointment']['data'][$key])) {
            return $default;
        }
        
        return $_SESSION['appointment']['data'][$key];
    }

    // Check if a user can access a particular step
    public static function canAccessStep($step) {
        return true; // Simplified for now
    }

    public static function clear() {
        if (isset($_SESSION['appointment'])) {
            unset($_SESSION['appointment']);
        }
    }

    /**
     * Clear all appointment session data
     */
    public static function clearAllData() {
        self::clear();
        
        // Also remove any appointment confirmation flags
        if (isset($_SESSION['appointment_confirmed'])) {
            unset($_SESSION['appointment_confirmed']);
        }
    }
    
    /**
     * Get all appointment data ready for database insertion
     */
    public static function getAllData() {
        if (!isset($_SESSION['appointment'])) {
            return null;
        }
        
        $allData = [];
        $allData['user_id'] = $_SESSION['appointment']['user_id'];
        
        // Combine all appointment data from different steps
        if (isset($_SESSION['appointment']['data']['service'])) {
            $allData = array_merge($allData, $_SESSION['appointment']['data']['service']);
        }
        
        if (isset($_SESSION['appointment']['data']['location'])) {
            $allData = array_merge($allData, $_SESSION['appointment']['data']['location']);
        }
        
        if (isset($_SESSION['appointment']['data']['personal_info'])) {
            $allData = array_merge($allData, $_SESSION['appointment']['data']['personal_info']);
        }
        
        if (isset($_SESSION['appointment']['data']['calendar'])) {
            $allData = array_merge($allData, $_SESSION['appointment']['data']['calendar']);
        }
        
        return $allData;
    }
}
?>