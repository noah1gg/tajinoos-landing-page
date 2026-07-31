<?php
/**
 * Lightweight, cache-safe French/English localization for Tajinoos.
 */

if (!defined('ABSPATH')) {
    exit;
}

const TAJINOOS_DEFAULT_LANGUAGE = 'fr';
const TAJINOOS_LANGUAGE_COOKIE = 'tajinoos_language';
const TAJINOOS_LANGUAGE_COOKIE_TTL = 31536000;

add_action('init', 'tajinoos_persist_explicit_language', 1);
add_action('template_redirect', 'tajinoos_normalize_cookie_language_url', 1);
add_action('send_headers', 'tajinoos_language_cache_headers');
add_filter('language_attributes', 'tajinoos_filter_language_attributes', 20, 2);
add_filter('document_title_parts', 'tajinoos_filter_document_title_parts', 20);
add_filter('get_canonical_url', 'tajinoos_filter_core_canonical', 20, 2);
add_filter('wpseo_title', 'tajinoos_filter_seo_title');
add_filter('wpseo_metadesc', 'tajinoos_filter_seo_description');
add_filter('wpseo_canonical', 'tajinoos_filter_seo_canonical');
add_filter('rank_math/frontend/title', 'tajinoos_filter_seo_title');
add_filter('rank_math/frontend/description', 'tajinoos_filter_seo_description');
add_filter('rank_math/frontend/canonical', 'tajinoos_filter_seo_canonical');
add_action('wp_head', 'tajinoos_print_language_metadata', 2);

function tajinoos_get_supported_languages(): array
{
    return ['fr', 'en'];
}

function tajinoos_normalize_language($language, string $fallback = TAJINOOS_DEFAULT_LANGUAGE): string
{
    $language = is_string($language) ? strtolower(trim(sanitize_key($language))) : '';

    return in_array($language, tajinoos_get_supported_languages(), true)
        ? $language
        : (in_array($fallback, tajinoos_get_supported_languages(), true) ? $fallback : TAJINOOS_DEFAULT_LANGUAGE);
}

function tajinoos_has_explicit_language(): bool
{
    return array_key_exists('lang', $_GET);
}

function tajinoos_get_current_language(): string
{
    static $language = null;

    if ($language !== null) {
        return $language;
    }

    if (tajinoos_has_explicit_language()) {
        $explicit = is_string($_GET['lang']) ? wp_unslash($_GET['lang']) : '';
        $language = tajinoos_normalize_language($explicit);
        return $language;
    }

    if (isset($_COOKIE[TAJINOOS_LANGUAGE_COOKIE])) {
        $cookie_language = tajinoos_normalize_language(
            wp_unslash((string) $_COOKIE[TAJINOOS_LANGUAGE_COOKIE]),
            ''
        );

        if (in_array($cookie_language, tajinoos_get_supported_languages(), true)) {
            $language = $cookie_language;
            return $language;
        }
    }

    $language = TAJINOOS_DEFAULT_LANGUAGE;
    return $language;
}

function tajinoos_is_english(): bool
{
    return tajinoos_get_current_language() === 'en';
}

/**
 * The secure receipt page is authoritative to its stored order language.
 */
function tajinoos_get_render_language(): string
{
    if (function_exists('is_page') && is_page('merci')
        && function_exists('tajinoos_get_recent_order_reference')
        && function_exists('tajinoos_get_order_summary_by_reference')) {
        $reference = tajinoos_get_recent_order_reference();
        $summary = $reference !== '' ? tajinoos_get_order_summary_by_reference($reference) : [];

        if (!empty($summary['language'])) {
            return tajinoos_normalize_language((string) $summary['language']);
        }
    }

    return tajinoos_get_current_language();
}

function tajinoos_translation_array(?string $language = null): array
{
    static $translations = [];
    $language = tajinoos_normalize_language($language ?? tajinoos_get_current_language());

    if (!isset($translations[$language])) {
        $path = get_stylesheet_directory() . '/inc/languages/' . $language . '.php';
        $translations[$language] = is_file($path) ? (array) require $path : [];
    }

    return $translations[$language];
}

function tajinoos_array_value(array $array, string $key)
{
    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return null;
        }
        $array = $array[$segment];
    }

    return $array;
}

function tajinoos_translate(string $key, array $replacements = [], ?string $language = null): string
{
    $language = tajinoos_normalize_language($language ?? tajinoos_get_current_language());
    $value = tajinoos_array_value(tajinoos_translation_array($language), $key);

    if (!is_string($value) && $language !== TAJINOOS_DEFAULT_LANGUAGE) {
        $value = tajinoos_array_value(tajinoos_translation_array(TAJINOOS_DEFAULT_LANGUAGE), $key);
    }

    if (!is_string($value)) {
        do_action('tajinoos_missing_translation', $key, $language);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[Tajinoos i18n] Missing key "%s" for "%s".', $key, $language));
        }
        return '';
    }

    foreach ($replacements as $name => $replacement) {
        $value = str_replace('{' . sanitize_key((string) $name) . '}', (string) $replacement, $value);
    }

    return $value;
}

function tajinoos_language_name(string $language, ?string $display_language = null): string
{
    $language = tajinoos_normalize_language($language);
    return tajinoos_translate('language.names.' . $language, [], $display_language);
}

function tajinoos_is_supported_hash(string $hash): bool
{
    return in_array($hash, [
        '#accueil', '#heritage', '#benefices', '#artisanat', '#produit', '#avis', '#faq', '#commande',
    ], true);
}

function tajinoos_language_url(string $language, string $hash = '', ?string $base_url = null): string
{
    $language = tajinoos_normalize_language($language);
    $url = $base_url ?: home_url('/');
    $url = remove_query_arg('lang', $url);

    if ($language === 'en') {
        $url = add_query_arg('lang', 'en', $url);
    }

    if ($hash !== '' && tajinoos_is_supported_hash($hash)) {
        $url = strtok($url, '#') . $hash;
    }

    return $url;
}

function tajinoos_language_switcher_html(): string
{
    $current = tajinoos_get_current_language();
    $label = tajinoos_translate('language.switcher_label');
    $links = [];

    foreach (tajinoos_get_supported_languages() as $language) {
        $active = $current === $language;
        $switch_url = $language === TAJINOOS_DEFAULT_LANGUAGE
            ? add_query_arg('lang', TAJINOOS_DEFAULT_LANGUAGE, home_url('/'))
            : tajinoos_language_url($language);
        $links[] = sprintf(
            '<a class="taj-language-switcher__link%s" href="%s" lang="%s" hreflang="%s"%s aria-label="%s"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
            $active ? ' is-active' : '',
            esc_url(wp_make_link_relative($switch_url)),
            esc_attr($language),
            esc_attr($language),
            $active ? ' aria-current="true"' : '',
            esc_attr(tajinoos_translate('language.choose_' . $language)),
            esc_html(strtoupper($language)),
            esc_html(tajinoos_language_name($language))
        );
    }

    return sprintf(
        '<nav class="taj-language-switcher" aria-label="%s" data-taj-language-switcher>%s<span class="taj-language-switcher__separator" aria-hidden="true">|</span>%s</nav>',
        esc_attr($label),
        $links[0],
        $links[1]
    );
}

function tajinoos_persist_explicit_language(): void
{
    if (!tajinoos_has_explicit_language() || headers_sent()) {
        return;
    }

    $language = tajinoos_get_current_language();
    setcookie(TAJINOOS_LANGUAGE_COOKIE, $language, [
        'expires' => time() + TAJINOOS_LANGUAGE_COOKIE_TTL,
        'path' => '/',
        'domain' => defined('COOKIE_DOMAIN') ? (string) COOKIE_DOMAIN : '',
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE[TAJINOOS_LANGUAGE_COOKIE] = $language;
}

function tajinoos_normalize_cookie_language_url(): void
{
    if (is_admin() || wp_doing_ajax() || !is_page(13)) {
        return;
    }

    if (tajinoos_has_explicit_language()) {
        $explicit = is_string($_GET['lang']) ? strtolower(sanitize_key(wp_unslash($_GET['lang']))) : '';

        if ($explicit === TAJINOOS_DEFAULT_LANGUAGE) {
            wp_safe_redirect(wp_make_link_relative(tajinoos_language_url(TAJINOOS_DEFAULT_LANGUAGE)));
            exit;
        }

        return;
    }

    if (tajinoos_get_current_language() === 'en') {
        wp_safe_redirect(wp_make_link_relative(tajinoos_language_url('en')));
        exit;
    }
}

function tajinoos_language_cache_headers(): void
{
    if (is_page(13) && !headers_sent()) {
        header('Vary: Cookie', false);
    }
}

function tajinoos_filter_language_attributes(string $output, string $doctype): string
{
    if (!is_page(13) && !is_page('merci')) {
        return $output;
    }

    $language = tajinoos_get_render_language();

    if (preg_match('/\blang=("|\')[^"\']*\1/i', $output)) {
        return preg_replace('/\blang=("|\')[^"\']*\1/i', 'lang="' . esc_attr($language) . '"', $output, 1) ?: $output;
    }

    return trim($output . ' lang="' . esc_attr($language) . '"');
}

function tajinoos_filter_document_title_parts(array $parts): array
{
    if (is_page(13)) {
        $parts['title'] = tajinoos_translate('seo.title');
        unset($parts['site'], $parts['tagline']);
    } elseif (is_page('merci')) {
        $parts['title'] = tajinoos_translate('thank.seo_title', [], tajinoos_get_render_language());
    }

    return $parts;
}

function tajinoos_filter_seo_title(string $title): string
{
    return is_page(13) ? tajinoos_translate('seo.title') : $title;
}

function tajinoos_filter_seo_description(string $description): string
{
    return is_page(13) ? tajinoos_translate('seo.description') : $description;
}

function tajinoos_filter_seo_canonical(string $canonical): string
{
    return is_page(13) ? tajinoos_language_url(tajinoos_get_current_language()) : $canonical;
}

function tajinoos_filter_core_canonical(string $canonical, WP_Post $post): string
{
    return (int) $post->ID === 13
        ? tajinoos_language_url(tajinoos_get_current_language())
        : $canonical;
}

function tajinoos_print_language_metadata(): void
{
    if (!is_page(13)) {
        return;
    }

    $fr_url = tajinoos_language_url('fr');
    $en_url = tajinoos_language_url('en');
    $seo_plugin_owns_meta = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION');

    if (!$seo_plugin_owns_meta) {
        printf("\n<meta name=\"description\" content=\"%s\">", esc_attr(tajinoos_translate('seo.description')));
    }

    printf("\n<link rel=\"alternate\" hreflang=\"fr\" href=\"%s\">", esc_url($fr_url));
    printf("\n<link rel=\"alternate\" hreflang=\"en\" href=\"%s\">", esc_url($en_url));
    printf("\n<link rel=\"alternate\" hreflang=\"x-default\" href=\"%s\">\n", esc_url($fr_url));
}

/**
 * Apply a curated server-side markup dictionary to the hybrid Elementor/PHP page.
 * This never runs in the browser and never changes business values or selectors.
 */
function tajinoos_localize_markup(string $markup, string $scope, ?string $language = null): string
{
    $language = tajinoos_normalize_language($language ?? tajinoos_get_current_language());
    $replacements = tajinoos_array_value(tajinoos_translation_array($language), 'markup.' . $scope);

    return is_array($replacements) && $replacements ? strtr($markup, $replacements) : $markup;
}
