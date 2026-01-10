<?php

/**
 * Generates a random password.
 *
 * @param int $length The desired length of the password.
 * @return string The randomly generated password.
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_+=';
    $password = '';
    $charCount = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, $charCount)];
    }
    return $password;
}

?>