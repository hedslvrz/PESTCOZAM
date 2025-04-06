<?php
class AppointmentSession {
    public static function initialize($user_id) {
        if (!isset($_SESSION['appointment'])) {
            $_SESSION['appointment'] = [
                'user_id' => $user_id,
                'data' => []
            ];
        }
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

    public static function getProgress() {
        return true;
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
        return true;
    }

    // Get the next step in the appointment flow
    private static function getNextStep($currentStep) {
        return true;
    }

    public static function clear() {
        unset($_SESSION['appointment']);
    }

    /**
     * Clear all appointment session data
     */
    public static function clearAllData() {
        $steps = ['service', 'location', 'calendar', 'personal_info'];
        
        foreach ($steps as $step) {
            if (isset($_SESSION['appointment_' . $step])) {
                unset($_SESSION['appointment_' . $step]);
            }
        }
    }
}
?>