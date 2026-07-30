<?php
/**
 * Private Tajinoos order storage and notification pipeline.
 */

if (!defined('ABSPATH')) {
    exit;
}

const TAJINOOS_ORDER_POST_TYPE = 'tajinoos_order';
const TAJINOOS_ORDER_UNIT_PRICE = 249;
const TAJINOOS_ORDER_MARRAKECH_DELIVERY_FEE = 0;
const TAJINOOS_ORDER_OTHER_CITY_DELIVERY_FEE = 20;
const TAJINOOS_ORDER_PRODUCT = 'Tajine artisanal Tajinoos Premium';
const TAJINOOS_ORDER_DUPLICATE_TTL = 180;
const TAJINOOS_ORDER_RECEIPT_TTL = 1800;

add_action('init', 'tajinoos_register_order_post_type');
add_action('wp_ajax_nopriv_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_action('wp_ajax_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_action('admin_post_nopriv_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_action('admin_post_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_filter('manage_tajinoos_order_posts_columns', 'tajinoos_order_admin_columns');
add_action('manage_tajinoos_order_posts_custom_column', 'tajinoos_order_admin_column_value', 10, 2);
add_action('add_meta_boxes_tajinoos_order', 'tajinoos_order_add_details_meta_box');
add_action('admin_head-edit.php', 'tajinoos_order_admin_styles');

/**
 * Register administrator-only storage. Orders have no public route or REST exposure.
 */
function tajinoos_register_order_post_type(): void
{
    $admin_caps = [
        'edit_post' => 'manage_options',
        'read_post' => 'manage_options',
        'delete_post' => 'manage_options',
        'edit_posts' => 'manage_options',
        'edit_others_posts' => 'manage_options',
        'publish_posts' => 'manage_options',
        'read_private_posts' => 'manage_options',
        'delete_posts' => 'manage_options',
        'delete_private_posts' => 'manage_options',
        'delete_published_posts' => 'manage_options',
        'delete_others_posts' => 'manage_options',
        'edit_private_posts' => 'manage_options',
        'edit_published_posts' => 'manage_options',
        'create_posts' => 'do_not_allow',
    ];

    register_post_type(TAJINOOS_ORDER_POST_TYPE, [
        'labels' => [
            'name' => 'Commandes Tajinoos',
            'singular_name' => 'Commande Tajinoos',
            'menu_name' => 'Commandes',
            'all_items' => 'Toutes les commandes',
            'edit_item' => 'Voir la commande',
            'search_items' => 'Rechercher une commande',
            'not_found' => 'Aucune commande trouvée',
        ],
        'description' => 'Commandes privées reçues depuis le formulaire Tajinoos.',
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_admin_bar' => false,
        'show_in_nav_menus' => false,
        'show_in_rest' => false,
        'query_var' => false,
        'rewrite' => false,
        'has_archive' => false,
        'hierarchical' => false,
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title'],
        'capabilities' => $admin_caps,
        'map_meta_cap' => false,
    ]);
}

/**
 * Return the single server-side source of truth for current pricing.
 */
function tajinoos_get_order_unit_price(): int
{
    return (int) apply_filters('tajinoos_order_unit_price', TAJINOOS_ORDER_UNIT_PRICE);
}

/**
 * Normalize a submitted delivery city without using loose substring matching.
 */
function tajinoos_normalize_delivery_city(string $city): string
{
    $city = trim($city);
    $city = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $city);
    $city = is_string($city) ? preg_replace('/\s+/u', ' ', $city) : '';
    $city = is_string($city) ? trim($city) : '';

    if (function_exists('remove_accents')) {
        $city = remove_accents($city);
    }

    return function_exists('mb_strtolower')
        ? mb_strtolower($city, 'UTF-8')
        : strtolower($city);
}

function tajinoos_delivery_city_is_valid(string $city): bool
{
    $normalized = tajinoos_normalize_delivery_city($city);

    return $normalized !== ''
        && tajinoos_string_length($normalized) <= 80
        && preg_match('/\p{L}/u', $normalized) === 1;
}

function tajinoos_is_marrakech_delivery(string $city): bool
{
    $normalized = tajinoos_normalize_delivery_city($city);
    $marrakech_variants = [
        'marrakech',
        'marrakesh',
        'marrakech city',
        'marrakesh city',
        'ville de marrakech',
        'ville de marrakesh',
        'marrakech ville',
        'marrakesh ville',
        'مراكش',
    ];

    return in_array($normalized, $marrakech_variants, true);
}

function tajinoos_get_delivery_fee(string $city): int
{
    $fee = tajinoos_is_marrakech_delivery($city)
        ? TAJINOOS_ORDER_MARRAKECH_DELIVERY_FEE
        : TAJINOOS_ORDER_OTHER_CITY_DELIVERY_FEE;

    return max(0, (int) apply_filters('tajinoos_order_delivery_fee', $fee, $city));
}

function tajinoos_calculate_product_subtotal(int $quantity): int
{
    return max(0, $quantity) * tajinoos_get_order_unit_price();
}

function tajinoos_calculate_order_total(int $quantity, string $city): int
{
    return tajinoos_calculate_product_subtotal($quantity) + tajinoos_get_delivery_fee($city);
}

/**
 * Process both AJAX submissions and the non-JavaScript admin-post fallback.
 */
function tajinoos_child_handle_order_submit(): void
{
    $nonce = isset($_POST['_tajinoos_order_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['_tajinoos_order_nonce']))
        : '';

    if (!wp_verify_nonce($nonce, 'tajinoos_order_submit')) {
        tajinoos_order_fail('Votre session a expiré. Actualisez la page puis réessayez.', '', 403);
    }

    $validated = tajinoos_validate_order_submission($_POST);

    if (is_wp_error($validated)) {
        $field = (string) $validated->get_error_data();
        tajinoos_order_fail($validated->get_error_message(), $field, 422);
    }

    $order_data = $validated;
    $fingerprint = tajinoos_order_fingerprint($order_data);
    $duplicate = get_transient('tajinoos_duplicate_' . $fingerprint);

    if (is_array($duplicate) && !empty($duplicate['reference'])) {
        tajinoos_set_order_receipt((string) $duplicate['reference']);
        tajinoos_order_succeed((string) $duplicate['reference'], true);
    }

    $post_id = wp_insert_post([
        'post_type' => TAJINOOS_ORDER_POST_TYPE,
        'post_status' => 'private',
        'post_title' => 'Commande Tajinoos en cours',
        'post_author' => 0,
        'post_content' => '',
    ], true);

    if (is_wp_error($post_id) || !$post_id) {
        tajinoos_debug_log('Order storage failed', $post_id);
        tajinoos_order_fail(
            'Nous n’avons pas pu enregistrer votre commande. Veuillez réessayer dans quelques instants.',
            '',
            500
        );
    }

    $reference = tajinoos_generate_order_reference((int) $post_id);
    $order_data['reference'] = $reference;
    $order_data['post_id'] = (int) $post_id;

    $updated = wp_update_post([
        'ID' => (int) $post_id,
        'post_title' => sprintf('Commande %s — %s', $reference, $order_data['name']),
    ], true);

    if (is_wp_error($updated)) {
        wp_delete_post((int) $post_id, true);
        tajinoos_debug_log('Order title update failed', $updated);
        tajinoos_order_fail(
            'Nous n’avons pas pu finaliser votre commande. Veuillez réessayer.',
            '',
            500
        );
    }

    tajinoos_store_order_meta((int) $post_id, $order_data);

    set_transient(
        'tajinoos_duplicate_' . $fingerprint,
        ['post_id' => (int) $post_id, 'reference' => $reference],
        TAJINOOS_ORDER_DUPLICATE_TTL
    );

    tajinoos_set_order_receipt($reference);

    $notifications = tajinoos_send_order_notifications($order_data);
    update_post_meta((int) $post_id, '_tajinoos_email_status', $notifications['email']);
    update_post_meta((int) $post_id, '_tajinoos_whatsapp_status', $notifications['whatsapp']);

    /**
     * Fires after an order is securely stored and notifications are attempted.
     */
    do_action('tajinoos_order_created', $order_data, (int) $post_id);

    tajinoos_order_succeed($reference, false);
}

/**
 * Validate, sanitize, normalize and recalculate every submitted value.
 *
 * @param array<string, mixed> $request
 * @return array<string, mixed>|WP_Error
 */
function tajinoos_validate_order_submission(array $request)
{
    $name = tajinoos_request_text($request, 'Nom');
    $raw_phone = tajinoos_request_text($request, 'Telephone');
    $city = tajinoos_request_text($request, 'Ville');
    $address = tajinoos_request_text($request, 'Adresse');
    $product = tajinoos_request_text($request, 'Produit');
    $message = isset($request['Message'])
        ? sanitize_textarea_field(wp_unslash((string) $request['Message']))
        : '';
    $quantity = isset($request['Quantite']) ? absint(wp_unslash((string) $request['Quantite'])) : 0;

    if ($name === '' || tajinoos_string_length($name) < 2) {
        return new WP_Error('invalid_name', 'Veuillez indiquer votre nom complet.', 'Nom');
    }

    if (tajinoos_string_length($name) > 120) {
        return new WP_Error('invalid_name', 'Le nom indiqué est trop long.', 'Nom');
    }

    $phone = tajinoos_normalize_phone_number($raw_phone);

    if ($phone === '') {
        return new WP_Error(
            'invalid_phone',
            'Veuillez saisir un numéro WhatsApp valide, par exemple 06 12 34 56 78.',
            'Telephone'
        );
    }

    if (!tajinoos_delivery_city_is_valid($city)) {
        return new WP_Error('invalid_city', 'Veuillez indiquer une ville de livraison valide.', 'Ville');
    }

    if ($address === '' || tajinoos_string_length($address) < 4) {
        return new WP_Error('invalid_address', 'Veuillez indiquer votre adresse de livraison.', 'Adresse');
    }

    if (tajinoos_string_length($address) > 300) {
        return new WP_Error('invalid_address', 'L’adresse indiquée est trop longue.', 'Adresse');
    }

    if (!in_array($quantity, [1, 2, 3, 4, 5], true)) {
        return new WP_Error('invalid_quantity', 'Veuillez choisir une quantité disponible.', 'Quantite');
    }

    if ($product !== TAJINOOS_ORDER_PRODUCT) {
        return new WP_Error('invalid_product', 'Le modèle sélectionné n’est pas disponible.', 'Produit');
    }

    if (tajinoos_string_length($message) > 1000) {
        return new WP_Error('invalid_message', 'Votre message est trop long (1 000 caractères maximum).', 'Message');
    }

    $unit_price = tajinoos_get_order_unit_price();
    $product_subtotal = tajinoos_calculate_product_subtotal($quantity);
    $delivery_fee = tajinoos_get_delivery_fee($city);
    $final_total = tajinoos_calculate_order_total($quantity, $city);
    $submitted_timestamp = current_time('timestamp');

    return [
        'name' => $name,
        'phone' => $phone,
        'phone_display' => tajinoos_format_phone_number($phone),
        'address' => $address,
        'city' => $city,
        'delivery_city' => $city,
        'is_marrakech_delivery' => tajinoos_is_marrakech_delivery($city),
        'quantity' => $quantity,
        'product' => TAJINOOS_ORDER_PRODUCT,
        'unit_price' => $unit_price,
        'product_subtotal' => $product_subtotal,
        'delivery_fee' => $delivery_fee,
        'final_total' => $final_total,
        'total' => $final_total,
        'message' => $message,
        'submitted_at' => current_time('mysql'),
        'submitted_display' => wp_date('d/m/Y à H:i', $submitted_timestamp, wp_timezone()),
        'status' => 'Nouvelle commande',
    ];
}

/**
 * Normalize Moroccan local numbers and international WhatsApp numbers to digits.
 */
function tajinoos_normalize_phone_number(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    $digits = is_string($digits) ? $digits : '';

    if (strpos($digits, '00') === 0) {
        $digits = substr($digits, 2);
    }

    if (preg_match('/^0([5-7][0-9]{8})$/', $digits, $matches)) {
        $digits = '212' . $matches[1];
    } elseif (preg_match('/^[5-7][0-9]{8}$/', $digits)) {
        $digits = '212' . $digits;
    }

    if (!preg_match('/^[1-9][0-9]{8,14}$/', $digits)) {
        return '';
    }

    return $digits;
}

function tajinoos_format_phone_number(string $phone): string
{
    if (preg_match('/^212([5-7])([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$/', $phone, $matches)) {
        return sprintf('+212 %s %s %s %s %s', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5]);
    }

    return '+' . $phone;
}

/**
 * Build a unique readable reference without a race-prone daily counter.
 */
function tajinoos_generate_order_reference(int $post_id): string
{
    return sprintf('TJ-%s-%03d', current_time('Ymd'), $post_id);
}

/**
 * Store only the requested order fields. Customer IP is intentionally omitted.
 *
 * @param array<string, mixed> $order_data
 */
function tajinoos_store_order_meta(int $post_id, array $order_data): void
{
    $meta = [
        '_tajinoos_reference' => $order_data['reference'],
        '_tajinoos_customer_name' => $order_data['name'],
        '_tajinoos_phone' => $order_data['phone'],
        '_tajinoos_phone_display' => $order_data['phone_display'],
        '_tajinoos_address' => $order_data['address'],
        '_tajinoos_city' => $order_data['city'],
        '_tajinoos_delivery_city' => $order_data['delivery_city'],
        '_tajinoos_quantity' => $order_data['quantity'],
        '_tajinoos_product' => $order_data['product'],
        '_tajinoos_unit_price' => $order_data['unit_price'],
        '_tajinoos_product_subtotal' => $order_data['product_subtotal'],
        '_tajinoos_delivery_fee' => $order_data['delivery_fee'],
        '_tajinoos_final_total' => $order_data['final_total'],
        '_tajinoos_total' => $order_data['total'],
        '_tajinoos_message' => $order_data['message'],
        '_tajinoos_submitted_at' => $order_data['submitted_at'],
        '_tajinoos_status' => $order_data['status'],
    ];

    foreach ($meta as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }
}

/**
 * Send both channels independently; neither result changes order success.
 *
 * @param array<string, mixed> $order_data
 * @return array{email:string,whatsapp:string}
 */
function tajinoos_send_order_notifications(array $order_data): array
{
    $email_body = tajinoos_format_order_email($order_data);
    $subject = sprintf(
        '[Tajinoos] طلب جديد / Nouvelle commande %s — %d MAD',
        $order_data['reference'],
        $order_data['total']
    );
    $recipient = tajinoos_get_order_notification_email();
    $smtp_is_valid = function_exists('tajinoos_smtp_configuration_is_valid')
        && tajinoos_smtp_configuration_is_valid();
    $email_sent = false;

    if ($recipient === '') {
        tajinoos_mail_safe_log('Order email recipient configuration is missing or invalid.');
    } elseif (!$smtp_is_valid) {
        tajinoos_mail_safe_log('Order Gmail SMTP configuration is missing or invalid.');
    } else {
        $email_sent = wp_mail(
            $recipient,
            $subject,
            $email_body,
            ['Content-Type: text/plain; charset=UTF-8']
        );
    }

    if (!$email_sent) {
        tajinoos_mail_safe_log('Order email notification failed.');
    }

    $whatsapp_status = 'disabled';

    if (defined('TAJINOOS_WA_ENABLED') && TAJINOOS_WA_ENABLED) {
        $whatsapp_message = tajinoos_format_order_notification($order_data);
        $whatsapp_result = tajinoos_send_whatsapp_cloud_notification($order_data, $whatsapp_message);
        $whatsapp_status = is_wp_error($whatsapp_result) ? 'failed' : 'sent';

        if (is_wp_error($whatsapp_result)) {
            tajinoos_debug_log(
                'WhatsApp notification failed for ' . $order_data['reference'] . ': ' . $whatsapp_result->get_error_message()
            );
        }
    }

    return [
        'email' => $email_sent ? 'sent' : 'failed',
        'whatsapp' => $whatsapp_status,
    ];
}

/**
 * Resolve the explicitly configured order-notification recipient.
 */
function tajinoos_get_order_notification_email(): string
{
    if (!defined('TAJINOOS_ORDER_EMAIL')) {
        return '';
    }

    $recipient = sanitize_email(trim((string) TAJINOOS_ORDER_EMAIL));

    return $recipient !== '' && is_email($recipient) ? $recipient : '';
}

/**
 * Format the bilingual plain-text order email.
 *
 * @param array<string, mixed> $order_data
 */
function tajinoos_format_order_email(array $order_data): string
{
    $customer_message = trim((string) $order_data['message']);
    $customer_message = $customer_message !== ''
        ? $customer_message
        : 'لا توجد رسالة / Aucun message';
    $delivery_label_fr = $order_data['delivery_fee'] === 0
        ? 'Gratuite — ' . $order_data['delivery_city']
        : $order_data['delivery_fee'] . ' MAD — ' . $order_data['delivery_city'];
    $delivery_label_ar = $order_data['delivery_fee'] === 0
        ? 'مجانية — ' . $order_data['delivery_city']
        : $order_data['delivery_fee'] . ' MAD — ' . $order_data['delivery_city'];

    return implode("\n", [
        'طلب جديد — TAJINOOS',
        '====================',
        '',
        'مرجع الطلب: ' . $order_data['reference'],
        'اسم العميل: ' . $order_data['name'],
        'رقم الهاتف: ' . $order_data['phone_display'],
        'العنوان: ' . $order_data['address'],
        'مدينة التوصيل: ' . $order_data['delivery_city'],
        'المنتج: ' . $order_data['product'],
        'الكمية: ' . $order_data['quantity'],
        'السعر للوحدة: ' . $order_data['unit_price'] . ' MAD',
        'المجموع الفرعي للمنتج: ' . $order_data['product_subtotal'] . ' MAD',
        'التوصيل: ' . $delivery_label_ar,
        'المبلغ الإجمالي: ' . $order_data['final_total'] . ' MAD',
        'رسالة العميل: ' . $customer_message,
        'تاريخ الطلب: ' . $order_data['submitted_display'],
        'طريقة الدفع: الدفع عند الاستلام',
        '',
        'Nouvelle commande — TAJINOOS',
        '============================',
        '',
        'Référence: ' . $order_data['reference'],
        'Client: ' . $order_data['name'],
        'Téléphone: ' . $order_data['phone_display'],
        'Adresse: ' . $order_data['address'],
        'Ville de livraison: ' . $order_data['delivery_city'],
        'Produit: ' . $order_data['product'],
        'Quantité: ' . $order_data['quantity'],
        'Prix unitaire: ' . $order_data['unit_price'] . ' MAD',
        'Sous-total produit: ' . $order_data['product_subtotal'] . ' MAD',
        'Livraison: ' . $delivery_label_fr,
        'Total: ' . $order_data['final_total'] . ' MAD',
        'Message du client: ' . $customer_message,
        'Date: ' . $order_data['submitted_display'],
        'Paiement: paiement à la livraison',
    ]);
}

/**
 * Format the existing commercial WhatsApp notification.
 *
 * @param array<string, mixed> $order_data
 */
function tajinoos_format_order_notification(array $order_data): string
{
    $customer_message = trim((string) $order_data['message']);
    $customer_message = $customer_message !== '' ? $customer_message : 'Aucun message';
    $delivery_label = $order_data['delivery_fee'] === 0
        ? 'Gratuite — ' . $order_data['delivery_city']
        : $order_data['delivery_fee'] . ' MAD — ' . $order_data['delivery_city'];

    return implode("\n", [
        '🛒 *NOUVELLE COMMANDE TAJINOOS*',
        '',
        '━━━━━━━━━━━━━━━━━━',
        '🔖 *Référence :* ' . $order_data['reference'],
        '👤 *Client :* ' . $order_data['name'],
        '📞 *Téléphone :* ' . $order_data['phone_display'],
        '📍 *Adresse :* ' . $order_data['address'],
        '🏙️ *Ville de livraison :* ' . $order_data['delivery_city'],
        '━━━━━━━━━━━━━━━━━━',
        '🏺 *Produit :* ' . $order_data['product'],
        '📦 *Quantité :* ' . $order_data['quantity'] . ($order_data['quantity'] > 1 ? ' pièces' : ' pièce'),
        '💵 *Prix unitaire :* ' . $order_data['unit_price'] . ' MAD',
        '🧾 *Sous-total produit :* ' . $order_data['product_subtotal'] . ' MAD',
        '🚚 *Livraison :* ' . $delivery_label,
        '💰 *Total :* ' . $order_data['final_total'] . ' MAD',
        '━━━━━━━━━━━━━━━━━━',
        '📝 *Message client :*',
        $customer_message,
        '━━━━━━━━━━━━━━━━━━',
        '🕐 *Date :* ' . $order_data['submitted_display'],
        '💳 *Paiement :* Paiement à la livraison',
        '',
        '✅ Merci de confirmer la commande avec le client.',
    ]);
}

/**
 * Send an official Meta WhatsApp Cloud API message through wp_remote_post().
 *
 * Template mode is enabled automatically when TAJINOOS_WA_TEMPLATE_NAME is set.
 *
 * @param array<string, mixed> $order_data
 * @return true|WP_Error
 */
function tajinoos_send_whatsapp_cloud_notification(array $order_data, string $formatted_message)
{
    $phone_number_id = defined('TAJINOOS_WA_PHONE_NUMBER_ID')
        ? preg_replace('/\D+/', '', (string) TAJINOOS_WA_PHONE_NUMBER_ID)
        : '';
    $access_token = defined('TAJINOOS_WA_ACCESS_TOKEN') ? trim((string) TAJINOOS_WA_ACCESS_TOKEN) : '';
    $owner_number = defined('TAJINOOS_WA_OWNER_NUMBER')
        ? tajinoos_normalize_phone_number((string) TAJINOOS_WA_OWNER_NUMBER)
        : '';
    $api_version = defined('TAJINOOS_WA_API_VERSION') ? trim((string) TAJINOOS_WA_API_VERSION) : '';

    if (
        $phone_number_id === ''
        || $access_token === ''
        || $owner_number === ''
        || !preg_match('/^v[0-9]+\.[0-9]+$/', $api_version)
    ) {
        return new WP_Error('tajinoos_wa_config', 'WhatsApp Cloud API configuration is incomplete.');
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $owner_number,
    ];

    $template_name = defined('TAJINOOS_WA_TEMPLATE_NAME')
        ? sanitize_key((string) TAJINOOS_WA_TEMPLATE_NAME)
        : '';

    if ($template_name !== '') {
        $template_language = defined('TAJINOOS_WA_TEMPLATE_LANGUAGE')
            ? sanitize_text_field((string) TAJINOOS_WA_TEMPLATE_LANGUAGE)
            : 'fr';

        $payload['type'] = 'template';
        $payload['template'] = [
            'name' => $template_name,
            'language' => ['code' => $template_language],
            'components' => [[
                'type' => 'body',
                'parameters' => array_map(
                    static function ($value): array {
                        return ['type' => 'text', 'text' => (string) $value];
                    },
                    [
                        $order_data['reference'],
                        $order_data['name'],
                        $order_data['phone_display'],
                        $order_data['address'],
                        $order_data['product'],
                        $order_data['quantity'],
                        $order_data['unit_price'] . ' MAD',
                        $order_data['total'] . ' MAD',
                        $order_data['message'] !== '' ? $order_data['message'] : 'Aucun message',
                        $order_data['submitted_display'],
                    ]
                ),
            ]],
        ];
    } else {
        $payload['type'] = 'text';
        $payload['text'] = [
            'preview_url' => false,
            'body' => $formatted_message,
        ];
    }

    $endpoint = sprintf(
        'https://graph.facebook.com/%s/%s/messages',
        rawurlencode($api_version),
        rawurlencode($phone_number_id)
    );

    $response = wp_remote_post($endpoint, [
        'timeout' => 15,
        'redirection' => 0,
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'data_format' => 'body',
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);

    if ($status_code < 200 || $status_code >= 300) {
        return new WP_Error('tajinoos_wa_http', 'Meta API returned HTTP ' . $status_code . '.');
    }

    return true;
}

/**
 * Place an opaque receipt token in an HttpOnly cookie and map it to the reference.
 */
function tajinoos_set_order_receipt(string $reference): void
{
    $token = wp_generate_password(48, false, false);
    $transient_key = 'tajinoos_receipt_' . hash('sha256', $token);

    set_transient($transient_key, $reference, TAJINOOS_ORDER_RECEIPT_TTL);

    if (!headers_sent()) {
        setcookie('tajinoos_order_receipt', $token, [
            'expires' => time() + TAJINOOS_ORDER_RECEIPT_TTL,
            'path' => '/',
            'domain' => defined('COOKIE_DOMAIN') ? (string) COOKIE_DOMAIN : '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Safely recover the recent order reference for the thank-you page.
 */
function tajinoos_get_recent_order_reference(): string
{
    if (empty($_COOKIE['tajinoos_order_receipt'])) {
        return '';
    }

    $token = sanitize_text_field(wp_unslash((string) $_COOKIE['tajinoos_order_receipt']));

    if (!preg_match('/^[A-Za-z0-9]{48}$/', $token)) {
        return '';
    }

    $reference = get_transient('tajinoos_receipt_' . hash('sha256', $token));

    if (!is_string($reference) || !preg_match('/^TJ-[0-9]{8}-[0-9]+$/', $reference)) {
        return '';
    }

    return $reference;
}

/**
 * Read current pricing metadata while preserving historical-order fallbacks.
 *
 * @return array<string, int|string|null>
 */
function tajinoos_get_order_pricing_summary(int $post_id): array
{
    $unit_price_raw = get_post_meta($post_id, '_tajinoos_unit_price', true);
    $quantity_raw = get_post_meta($post_id, '_tajinoos_quantity', true);
    $subtotal_raw = get_post_meta($post_id, '_tajinoos_product_subtotal', true);
    $delivery_fee_raw = get_post_meta($post_id, '_tajinoos_delivery_fee', true);
    $final_total_raw = get_post_meta($post_id, '_tajinoos_final_total', true);
    $legacy_total_raw = get_post_meta($post_id, '_tajinoos_total', true);
    $delivery_city = (string) get_post_meta($post_id, '_tajinoos_delivery_city', true);

    if ($delivery_city === '') {
        $delivery_city = (string) get_post_meta($post_id, '_tajinoos_city', true);
    }

    $unit_price = $unit_price_raw !== '' ? (int) $unit_price_raw : null;
    $quantity = $quantity_raw !== '' ? (int) $quantity_raw : null;
    $product_subtotal = $subtotal_raw !== '' ? (int) $subtotal_raw : null;
    $delivery_fee = $delivery_fee_raw !== '' ? (int) $delivery_fee_raw : null;
    $final_total = $final_total_raw !== ''
        ? (int) $final_total_raw
        : ($legacy_total_raw !== '' ? (int) $legacy_total_raw : null);

    if ($product_subtotal === null && $unit_price !== null && $quantity !== null) {
        $product_subtotal = $unit_price * $quantity;
    }

    return [
        'unit_price' => $unit_price,
        'quantity' => $quantity,
        'product_subtotal' => $product_subtotal,
        'delivery_fee' => $delivery_fee,
        'delivery_city' => $delivery_city,
        'final_total' => $final_total,
    ];
}

/**
 * Resolve a private order only after a valid receipt token supplied its reference.
 *
 * @return array<string, int|string|null>
 */
function tajinoos_get_order_summary_by_reference(string $reference): array
{
    if (!preg_match('/^TJ-[0-9]{8}-[0-9]+$/', $reference)) {
        return [];
    }

    $order_ids = get_posts([
        'post_type' => TAJINOOS_ORDER_POST_TYPE,
        'post_status' => 'private',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_tajinoos_reference',
        'meta_value' => $reference,
        'no_found_rows' => true,
        'suppress_filters' => true,
    ]);

    if (!is_array($order_ids) || empty($order_ids[0])) {
        return [];
    }

    return tajinoos_get_order_pricing_summary((int) $order_ids[0]);
}

/**
 * @param array<string, mixed> $order_data
 */
function tajinoos_order_fingerprint(array $order_data): string
{
    $fingerprint_data = [
        $order_data['name'],
        $order_data['phone'],
        $order_data['address'],
        $order_data['quantity'],
        $order_data['product'],
        $order_data['message'],
    ];

    return hash_hmac('sha256', wp_json_encode($fingerprint_data), wp_salt('nonce'));
}

/**
 * Emit a successful AJAX response or the progressive-enhancement redirect.
 */
function tajinoos_order_succeed(string $reference, bool $duplicate): void
{
    $redirect = add_query_arg('commande', 'success', home_url('/merci/'));

    if (wp_doing_ajax()) {
        wp_send_json_success([
            'reference' => $reference,
            'duplicate' => $duplicate,
            'redirect' => wp_make_link_relative($redirect),
        ]);
    }

    wp_safe_redirect($redirect);
    exit;
}

/**
 * Emit a customer-safe validation error.
 */
function tajinoos_order_fail(string $message, string $field = '', int $status = 400): void
{
    if (wp_doing_ajax()) {
        wp_send_json_error([
            'message' => $message,
            'field' => $field,
        ], $status);
    }

    wp_safe_redirect(add_query_arg('tajinoos_order', 'error', home_url('/#commande')));
    exit;
}

/**
 * @param array<string, mixed> $request
 */
function tajinoos_request_text(array $request, string $key): string
{
    return isset($request[$key]) ? sanitize_text_field(wp_unslash((string) $request[$key])) : '';
}

function tajinoos_string_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function tajinoos_extract_city(string $address): string
{
    $parts = preg_split('/[,;\n\-]+/u', $address);
    $city = is_array($parts) && isset($parts[0]) ? trim($parts[0]) : $address;

    if (tajinoos_string_length($city) > 80) {
        $city = function_exists('mb_substr') ? mb_substr($city, 0, 80) : substr($city, 0, 80);
    }

    return $city;
}

/**
 * Log operational failures without leaking credentials or customer data.
 *
 * @param mixed $context
 */
function tajinoos_debug_log(string $message, $context = null): void
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    if (is_wp_error($context)) {
        $message .= ': ' . $context->get_error_message();
    }

    error_log('[Tajinoos] ' . $message);
}

/**
 * Administrator order list columns.
 *
 * @param array<string, string> $columns
 * @return array<string, string>
 */
function tajinoos_order_admin_columns(array $columns): array
{
    return [
        'cb' => $columns['cb'] ?? '<input type="checkbox" />',
        'tajinoos_reference' => 'Référence',
        'title' => 'Client',
        'tajinoos_phone' => 'Téléphone',
        'tajinoos_city' => 'Ville',
        'tajinoos_quantity' => 'Quantité',
        'tajinoos_subtotal' => 'Sous-total',
        'tajinoos_delivery_fee' => 'Livraison',
        'tajinoos_total' => 'Total final',
        'tajinoos_status' => 'Statut',
        'date' => 'Date',
    ];
}

function tajinoos_order_admin_column_value(string $column, int $post_id): void
{
    $pricing = tajinoos_get_order_pricing_summary($post_id);

    switch ($column) {
        case 'tajinoos_reference':
            echo '<strong>' . esc_html((string) get_post_meta($post_id, '_tajinoos_reference', true)) . '</strong>';
            break;
        case 'tajinoos_phone':
            echo esc_html((string) get_post_meta($post_id, '_tajinoos_phone_display', true));
            break;
        case 'tajinoos_city':
            echo esc_html($pricing['delivery_city'] !== '' ? (string) $pricing['delivery_city'] : '—');
            break;
        case 'tajinoos_quantity':
            echo esc_html($pricing['quantity'] !== null ? (string) $pricing['quantity'] : '—');
            break;
        case 'tajinoos_subtotal':
            echo esc_html($pricing['product_subtotal'] !== null ? $pricing['product_subtotal'] . ' MAD' : '—');
            break;
        case 'tajinoos_delivery_fee':
            if ($pricing['delivery_fee'] === null) {
                echo '<span title="Commande historique">—</span>';
            } else {
                echo esc_html($pricing['delivery_fee'] === 0 ? 'Gratuite' : $pricing['delivery_fee'] . ' MAD');
            }
            break;
        case 'tajinoos_total':
            $total = $pricing['final_total'] !== null ? $pricing['final_total'] . ' MAD' : '—';
            echo '<strong>' . esc_html($total) . '</strong>';
            break;
        case 'tajinoos_status':
            echo '<span class="tajinoos-order-status">' .
                esc_html((string) get_post_meta($post_id, '_tajinoos_status', true)) .
                '</span>';
            break;
    }
}

function tajinoos_order_add_details_meta_box(): void
{
    add_meta_box(
        'tajinoos-order-details',
        'Détails de la commande',
        'tajinoos_order_render_details_meta_box',
        TAJINOOS_ORDER_POST_TYPE,
        'normal',
        'high'
    );
}

function tajinoos_order_render_details_meta_box(WP_Post $post): void
{
    $pricing = tajinoos_get_order_pricing_summary($post->ID);
    $delivery_fee = $pricing['delivery_fee'] === null
        ? '— (commande historique)'
        : ($pricing['delivery_fee'] === 0 ? 'Gratuite' : $pricing['delivery_fee'] . ' MAD');

    $rows = [
        'Référence' => get_post_meta($post->ID, '_tajinoos_reference', true),
        'Client' => get_post_meta($post->ID, '_tajinoos_customer_name', true),
        'Téléphone' => get_post_meta($post->ID, '_tajinoos_phone_display', true),
        'Ville' => $pricing['delivery_city'] !== '' ? $pricing['delivery_city'] : '—',
        'Adresse complète' => get_post_meta($post->ID, '_tajinoos_address', true),
        'Produit' => get_post_meta($post->ID, '_tajinoos_product', true),
        'Quantité' => $pricing['quantity'] !== null ? $pricing['quantity'] : '—',
        'Prix unitaire' => $pricing['unit_price'] !== null ? $pricing['unit_price'] . ' MAD' : '—',
        'Sous-total produit' => $pricing['product_subtotal'] !== null ? $pricing['product_subtotal'] . ' MAD' : '—',
        'Frais de livraison' => $delivery_fee,
        'Total final' => $pricing['final_total'] !== null ? $pricing['final_total'] . ' MAD' : '—',
        'Statut' => get_post_meta($post->ID, '_tajinoos_status', true),
        'Date de soumission' => get_post_meta($post->ID, '_tajinoos_submitted_at', true),
        'Notification email' => get_post_meta($post->ID, '_tajinoos_email_status', true),
        'Notification WhatsApp' => get_post_meta($post->ID, '_tajinoos_whatsapp_status', true),
    ];

    echo '<table class="widefat striped" style="border:0">';

    foreach ($rows as $label => $value) {
        echo '<tr><th style="width:190px;padding:10px">' . esc_html($label) . '</th><td style="padding:10px">' .
            esc_html((string) $value) . '</td></tr>';
    }

    $message = (string) get_post_meta($post->ID, '_tajinoos_message', true);
    echo '<tr><th style="padding:10px">Message client</th><td style="padding:10px;white-space:pre-wrap">' .
        esc_html($message !== '' ? $message : 'Aucun message') . '</td></tr>';
    echo '</table>';
}

function tajinoos_order_admin_styles(): void
{
    $screen = get_current_screen();

    if (!$screen || $screen->post_type !== TAJINOOS_ORDER_POST_TYPE) {
        return;
    }

    echo '<style>
      .post-type-tajinoos_order .column-tajinoos_reference{width:155px}
      .post-type-tajinoos_order .column-tajinoos_quantity{width:80px}
      .post-type-tajinoos_order .column-tajinoos_subtotal{width:105px}
      .post-type-tajinoos_order .column-tajinoos_delivery_fee{width:95px}
      .post-type-tajinoos_order .column-tajinoos_total{width:105px}
      .post-type-tajinoos_order .column-tajinoos_status{width:145px}
      .tajinoos-order-status{display:inline-block;padding:5px 9px;border-radius:999px;background:#fff1df;color:#9a351d;font-weight:700}
    </style>';
}
