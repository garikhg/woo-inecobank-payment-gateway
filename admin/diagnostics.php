<?php
/**
 * Inecobank Connection Diagnostic Tool
 * 
 * Add this filter to your theme's functions.php to increase timeout:
 * add_filter('woo_inecobank_api_timeout', function() { return 120; });
 */

if (!defined('ABSPATH')) {
    exit;
}

// Only run for admins
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

echo '<h1>Inecobank Connection Diagnostics</h1>';

$test_url = 'https://pg.inecoecom.am/payment/rest/';
$test_timeout = 30;

echo '<h2>1. DNS Resolution Test</h2>';
$host = 'pg.inecoecom.am';
$ip = gethostbyname($host);
if ($ip === $host) {
    echo '<p style="color: red;">❌ DNS Resolution FAILED - Cannot resolve ' . esc_html($host) . '</p>';
} else {
    echo '<p style="color: green;">✓ DNS Resolution OK - ' . esc_html($host) . ' resolves to ' . esc_html($ip) . '</p>';
}

echo '<h2>2. PHP cURL Extension</h2>';
if (function_exists('curl_version')) {
    $curl_info = curl_version();
    echo '<p style="color: green;">✓ cURL is installed</p>';
    echo '<ul>';
    echo '<li>Version: ' . esc_html($curl_info['version']) . '</li>';
    echo '<li>SSL Version: ' . esc_html($curl_info['ssl_version']) . '</li>';
    echo '<li>Protocols: ' . esc_html(implode(', ', $curl_info['protocols'])) . '</li>';
    echo '</ul>';
} else {
    echo '<p style="color: red;">❌ cURL is NOT installed</p>';
}

echo '<h2>3. PHP Timeout Settings</h2>';
echo '<ul>';
echo '<li>max_execution_time: ' . esc_html(ini_get('max_execution_time')) . ' seconds</li>';
echo '<li>default_socket_timeout: ' . esc_html(ini_get('default_socket_timeout')) . ' seconds</li>';
echo '</ul>';

echo '<h2>4. Direct Connection Test (wp_remote_get)</h2>';
$response = wp_remote_get($test_url, array(
    'timeout' => $test_timeout,
    'sslverify' => false,
));

if (is_wp_error($response)) {
    echo '<p style="color: red;">❌ Connection FAILED</p>';
    echo '<pre>' . esc_html($response->get_error_message()) . '</pre>';
} else {
    $code = wp_remote_retrieve_response_code($response);
    echo '<p style="color: green;">✓ Connection OK - HTTP Status: ' . esc_html($code) . '</p>';
}

echo '<h2>5. Socket Connection Test</h2>';
$fp = @fsockopen('pg.inecoecom.am', 443, $errno, $errstr, 10);
if ($fp) {
    echo '<p style="color: green;">✓ Socket connection to port 443 successful</p>';
    fclose($fp);
} else {
    echo '<p style="color: red;">❌ Socket connection FAILED</p>';
    echo '<p>Error: ' . esc_html($errstr) . ' (' . esc_html($errno) . ')</p>';
}

echo '<h2>6. SSL/TLS Test</h2>';
$context = stream_context_create([
    'ssl' => [
        'capture_peer_cert' => true,
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
]);

$stream = @stream_socket_client(
    'ssl://pg.inecoecom.am:443',
    $errno,
    $errstr,
    10,
    STREAM_CLIENT_CONNECT,
    $context
);

if ($stream) {
    echo '<p style="color: green;">✓ SSL/TLS connection successful</p>';
    $params = stream_context_get_params($stream);
    if (isset($params['options']['ssl']['peer_certificate'])) {
        $cert_info = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        echo '<ul>';
        echo '<li>Issued to: ' . esc_html($cert_info['subject']['CN'] ?? 'N/A') . '</li>';
        echo '<li>Issued by: ' . esc_html($cert_info['issuer']['CN'] ?? 'N/A') . '</li>';
        echo '<li>Valid until: ' . esc_html(date('Y-m-d H:i:s', $cert_info['validTo_time_t'])) . '</li>';
        echo '</ul>';
    }
    fclose($stream);
} else {
    echo '<p style="color: red;">❌ SSL/TLS connection FAILED</p>';
    echo '<p>Error: ' . esc_html($errstr) . ' (' . esc_html($errno) . ')</p>';
}

echo '<h2>7. Server Outbound Firewall Check</h2>';
echo '<p>Testing if server can make HTTPS connections to external sites...</p>';
$external_test = wp_remote_get('https://www.google.com', array('timeout' => 10));
if (is_wp_error($external_test)) {
    echo '<p style="color: red;">❌ Cannot connect to external HTTPS sites</p>';
    echo '<p>Your server may have outbound firewall restrictions</p>';
} else {
    echo '<p style="color: green;">✓ External HTTPS connections work</p>';
}

echo '<hr><h2>Recommended Actions:</h2>';
echo '<ol>';
echo '<li><strong>If DNS fails:</strong> Contact your hosting provider - DNS resolution is blocked</li>';
echo '<li><strong>If socket/SSL fails:</strong> Your server firewall is blocking outbound connections to pg.inecoecom.am on port 443</li>';
echo '<li><strong>Contact hosting provider</strong> and request to whitelist: <code>pg.inecoecom.am</code> (IP: ' . esc_html($ip) . ') on port 443</li>';
echo '<li><strong>Check if using a CDN/Proxy:</strong> Some CDNs block certain payment gateway domains</li>';
echo '<li><strong>Try different server:</strong> Test on a different hosting environment (VPS, shared hosting, etc.)</li>';
echo '</ol>';
