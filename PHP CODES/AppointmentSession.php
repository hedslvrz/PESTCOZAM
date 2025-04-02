<?php
class AppointmentSession {
    public static function initialize($user_id) {
        if (!isset($_SESSION['appointment'])) {
            $_SESSION['appointment'] = [
                'user_id' => $user_id,
                'progress' => 'service', // Track current step
                'data' => []
            ];
        }
    }

    public static function saveStep($step, $data) {
        if (!isset($_SESSION['appointment'])) {
            throw new Exception('Appointment session not initialized');
        }
        $_SESSION['appointment']['data'][$step] = $data;
        $_SESSION['appointment']['progress'] = self::getNextStep($step);
    }

    public static function getAppointmentData() {
        return isset($_SESSION['appointment']) ? $_SESSION['appointment']['data'] : null;
    }

    public static function getProgress() {
        return isset($_SESSION['appointment']) ? $_SESSION['appointment']['progress'] : null;
    }

    // Get a specific piece of data from the appointment session
    public static function getData($key, $default = null) {
        if (!isset($_SESSION['appointment']['data'][$key])) {
            return $default;
        }
        return $_SESSION['appointment']['data'][$key];
    }
    
    // Check if the user can access a specific step
    public static function canAccessStep($step) {
        if (!isset($_SESSION['appointment'])) {
            return false;
        }
        
        $steps = ['service', 'location', 'personal_info', 'calendar', 'confirmation'];
        $currentStepIndex = array_search($_SESSION['appointment']['progress'], $steps);
        $requestedStepIndex = array_search($step, $steps);
        
        // Allow access to current or previous steps
        return $requestedStepIndex <= $currentStepIndex;
    }
    
    // Get the next step in the appointment flow
    private static function getNextStep($currentStep) {
        $steps = [
            'service' => 'location',
            'location' => 'personal_info',
            'personal_info' => 'calendar',
            'calendar' => 'confirmation',
            'confirmation' => 'complete'
        ];
        
        return isset($steps[$currentStep]) ? $steps[$currentStep] : $currentStep;
    }

    public static function clear() {
        unset($_SESSION['appointment']);
    }
}
?>