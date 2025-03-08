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
        $_SESSION['appointment']['progress'] = $step;
    }

    public static function getAppointmentData() {
        return isset($_SESSION['appointment']) ? $_SESSION['appointment']['data'] : null;
    }

    public static function clear() {
        unset($_SESSION['appointment']);
    }
}
?>