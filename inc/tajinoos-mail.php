<?php
/**
 * Tajinoos email transport configuration and safe failure logging.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('phpmailer_init', 'tajinoos_configure_phpmailer');
add_action('wp_mail_failed', 'tajinoos_log_wp_mail_failure');

/**
 * Confirm that the private Gmail SMTP constants are present and usable.
 */
function tajinoos_smtp_configuration_is_valid(): bool
{
    if (
        !defined('TAJINOOS_SMTP_USERNAME')
        || !defined('TAJINOOS_SMTP_APP_PASSWORD')
    ) {
        return false;
    }

    $username = sanitize_email(trim((string) TAJINOOS_SMTP_USERNAME));
    $password = trim((string) TAJINOOS_SMTP_APP_PASSWORD);

    return $username !== '' && is_email($username) && $password !== '';
}

/**
 * Configure the PHPMailer instance bundled with WordPress.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer
 */
function tajinoos_configure_phpmailer($phpmailer): void
{
    if (!tajinoos_smtp_configuration_is_valid()) {
        tajinoos_mail_safe_log('Gmail SMTP configuration is missing or invalid.');
        return;
    }

    $username = sanitize_email(trim((string) TAJINOOS_SMTP_USERNAME));

    $phpmailer->isSMTP();
    $phpmailer->Host = 'smtp.gmail.com';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 587;
    $phpmailer->SMTPSecure = defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_STARTTLS')
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
        : 'tls';
    $phpmailer->Username = $username;
    $phpmailer->Password = (string) TAJINOOS_SMTP_APP_PASSWORD;
    $phpmailer->From = $username;
    $phpmailer->FromName = 'Tajinoos Orders';
}

/**
 * Log only a redacted technical summary from wp_mail_failed.
 */
function tajinoos_log_wp_mail_failure(WP_Error $error): void
{
    $error_code = sanitize_key((string) $error->get_error_code());
    $error_message = sanitize_text_field((string) $error->get_error_message());
    $error_message = preg_replace(
        '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
        '[redacted email]',
        $error_message
    );
    $error_message = preg_replace(
        '/(?<![A-Za-z0-9])\+?[0-9][0-9\s().\-]{7,}[0-9](?![A-Za-z0-9])/',
        '[redacted number]',
        is_string($error_message) ? $error_message : ''
    );
    $error_message = function_exists('mb_substr')
        ? mb_substr((string) $error_message, 0, 240)
        : substr((string) $error_message, 0, 240);

    tajinoos_mail_safe_log(sprintf(
        'WordPress mail failure. Error code: %s. Message: %s',
        $error_code !== '' ? $error_code : 'unknown',
        $error_message !== '' ? $error_message : 'unavailable'
    ));
}

/**
 * Write a technical mail event without including message or credential data.
 */
function tajinoos_mail_safe_log(string $message): void
{
    error_log('[Tajinoos Mail] ' . sanitize_text_field($message));
}
