<?php
// src/utils.php

require_once dirname(__DIR__) . '/src/db.php';

/**
 * Starts a session securely.
 */
function secure_session_start() {
    $session_name = 'sec_session_id';   // Set a custom session name
    $secure = false; // Set to true if using https.
    $httponly = true; // This stops JavaScript being able to access the session id.
    
    // Set cookie params
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
        exit();
    }
    
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params(
        $cookieParams["lifetime"],
        $cookieParams["path"],
        $cookieParams["domain"],
        $secure,
        $httponly
    );
    
    session_name($session_name);
    session_start();
    // session_regenerate_id(true); // regenerated the session, delete the old one. 
}

/**
 * Checks if the user is logged in.
 * If not, redirects to the login page.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Encrypts data using AES-256-CBC.
 *
 * @param string $data The plaintext data to encrypt.
 * @return string The base64-encoded string containing the IV and ciphertext.
 */
function encrypt($data) {
    $key = hex2bin(ENCRYPTION_KEY);
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypts data encrypted with the encrypt function.
 *
 * @param string $data The base64-encoded string.
 * @return string|false The decrypted plaintext, or false on failure.
 */
function decrypt($data) {
    $key = hex2bin(ENCRYPTION_KEY);
    $data = base64_decode($data);
    $iv_length = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}

/**
 * Checks for the presence of the S21AF credit in the footer.
 * Sets a session variable if the credit is not found.
 */
/*
function check_s21af_credit() {
    $footer_path = dirname(__DIR__) . '/templates/footer.php';
    if (file_exists($footer_path)) {
        $footer_content = file_get_contents($footer_path);

        // Obfuscated parts of the credit string
        $part1 = 'This system is devel';
        $part2 = 'oped by <a href="http';
        $part3 = 's://www.qrz.com/db/S21AF" target="_blank">S21AF</a>.';

        // Reconstruct the expected credit text dynamically
        $expected_credit_text_full = $part1 . $part2 . $part3;

        // Check for the specific div ID (less obfuscated, but a good anchor)
        $expected_credit_id = '<div id="s21af-credit"';

        // Check for both the div ID and the reconstructed credit text
        if (strpos($footer_content, $expected_credit_id) === false || strpos($footer_content, $expected_credit_text_full) === false) {
            $_SESSION['_s21af_credit_removed'] = true;
        } else {
            if (isset($_SESSION['_s21af_credit_removed'])) {
                unset($_SESSION['_s21af_credit_removed']); // Clear if it was restored
            }
        }
    } else {
        // If footer.php doesn't exist, also flag as removed or problematic
        $_SESSION['_s21af_credit_removed'] = true;
    }
}
*/

// Call the credit check function on every page load that includes utils.php
// This should be done after secure_session_start() is called or session is active.
// For now, it will be called once this file is included.
// If session is not active, it will be handled when secure_session_start() is called.
if (session_status() === PHP_SESSION_NONE) {
    // Session might not be started yet for initial page loads
    // We defer the check until secure_session_start() or ensure session is active
    // For simplicity, we'll check if a session is already active or started by a preceding call
}

function get_settings() {
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT name, value FROM settings');
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Ensure session is active before trying to set session variables
// This assumes secure_session_start() is called before utils.php is fully processed
// in most relevant page contexts. A more robust solution might integrate this
// directly into secure_session_start or require explicit call after session is active.
// For now, we'll assume a session is available when utils.php is included.

// Temporarily, we'll call it here. For a cleaner approach, this might be called
// within secure_session_start() or right after it in the main scripts.
// Given that secure_session_start() is often called before other operations,
// placing this check after its definition in utils.php makes sense if utils.php is included early.

function get_band_from_frequency($frequency) {
    $freq_mhz = (float)$frequency;
    if ($freq_mhz >= 1.8 && $freq_mhz <= 2.0) return '160m';
    if ($freq_mhz >= 3.5 && $freq_mhz <= 4.0) return '80m';
    if ($freq_mhz >= 5.0 && $freq_mhz <= 5.4) return '60m';
    if ($freq_mhz >= 7.0 && $freq_mhz <= 7.3) return '40m';
    if ($freq_mhz >= 10.1 && $freq_mhz <= 10.15) return '30m';
    if ($freq_mhz >= 14.0 && $freq_mhz <= 14.35) return '20m';
    if ($freq_mhz >= 18.068 && $freq_mhz <= 18.168) return '17m';
    if ($freq_mhz >= 21.0 && $freq_mhz <= 21.45) return '15m';
    if ($freq_mhz >= 24.89 && $freq_mhz <= 24.99) return '12m';
    if ($freq_mhz >= 28.0 && $freq_mhz <= 29.7) return '10m';
    if ($freq_mhz >= 50.0 && $freq_mhz <= 54.0) return '6m';
    if ($freq_mhz >= 144.0 && $freq_mhz <= 148.0) return '2m';
    if ($freq_mhz >= 222.0 && $freq_mhz <= 225.0) return '1.25m';
    if ($freq_mhz >= 420.0 && $freq_mhz <= 450.0) return '70cm';
    return null;
}