<?php
/**
 * Encryption class for handling password encryption
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class UC_Encryption {
    
    /**
     * Get encryption key
     * 
     * IMPORTANT: This function uses WordPress AUTH_KEY for encryption.
     * Ensure AUTH_KEY is properly set in wp-config.php and not the default value.
     */
    private static function get_key() {
        // Use WordPress authentication unique key and salt
        if (defined('AUTH_KEY') && AUTH_KEY !== 'put your unique phrase here') {
            return AUTH_KEY;
        }
        
        // Fallback key - This should never be used in production
        // If you see this warning, configure AUTH_KEY in wp-config.php
        error_log('Update Controller: AUTH_KEY is not properly configured. Please set a unique AUTH_KEY in wp-config.php for secure password encryption.');
        return 'update-controller-default-key';
    }
    
    /**
     * Encrypt data
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $key = self::get_key();
        $method = 'AES-256-CBC';
        
        // Generate an initialization vector
        $iv = random_bytes(openssl_cipher_iv_length($method));
        
        // Encrypt the data
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        
        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        $key = self::get_key();
        $method = 'AES-256-CBC';
        
        // Decode the data
        $data = base64_decode($data);
        
        // Extract IV and encrypted data
        $iv_length = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        // Decrypt the data
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }
}
