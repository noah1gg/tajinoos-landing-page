<?php
/**
 * Academic-demo notices, legal pages and public-author privacy controls.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('tajinoos_academic_legal', 'tajinoos_academic_render_legal_page');
add_filter('the_content', 'tajinoos_academic_enhance_landing_content', 135);
add_filter('wp_sitemaps_add_provider', 'tajinoos_academic_disable_user_sitemap', 10, 2);
add_action('template_redirect', 'tajinoos_academic_block_author_archives', 9);
add_action('template_redirect', 'tajinoos_academic_block_user_sitemap_routes', 9);

/**
 * Stable page slugs are language-neutral database identifiers.
 * Customer-facing labels and content come from the existing translation files.
 *
 * @return array<string, string>
 */
function tajinoos_academic_legal_page_slugs(): array
{
    return [
        'privacy' => 'politique-de-confidentialite',
        'terms' => 'conditions-utilisation',
        'legal' => 'mentions-legales',
    ];
}

function tajinoos_academic_legal_page_type(): string
{
    foreach (tajinoos_academic_legal_page_slugs() as $type => $slug) {
        if (is_page($slug)) {
            return $type;
        }
    }

    return '';
}

function tajinoos_academic_is_legal_page(): bool
{
    return tajinoos_academic_legal_page_type() !== '';
}

function tajinoos_academic_legal_url(string $type, ?string $language = null): string
{
    $slugs = tajinoos_academic_legal_page_slugs();

    if (!isset($slugs[$type])) {
        return home_url('/');
    }

    $url = home_url('/' . $slugs[$type] . '/');
    $language = tajinoos_normalize_language($language ?? tajinoos_get_current_language());

    return $language === 'en' ? add_query_arg('lang', 'en', $url) : $url;
}

function tajinoos_academic_legal_links_html(string $class = 'taj-academic-legal-links', ?string $language = null): string
{
    $language = tajinoos_normalize_language($language ?? tajinoos_get_current_language());
    $links = [];

    foreach (array_keys(tajinoos_academic_legal_page_slugs()) as $type) {
        $links[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(wp_make_link_relative(tajinoos_academic_legal_url($type, $language))),
            esc_html(tajinoos_translate('legal.' . $type . '.title', [], $language))
        );
    }

    return sprintf(
        '<nav class="%s" aria-label="%s">%s</nav>',
        esc_attr($class),
        esc_attr(tajinoos_translate('legal.navigation_aria', [], $language)),
        implode('', $links)
    );
}

/**
 * Add the global academic badge and legal footer links to the final localized
 * landing-page markup without changing the stored Elementor document.
 */
function tajinoos_academic_enhance_landing_content(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content()) {
        return $content;
    }

    if (strpos($content, 'taj-academic-site-notice') === false) {
        $notice = sprintf(
            '<aside class="taj-academic-site-notice" role="note"><span aria-hidden="true">&#9671;</span><p>%s</p></aside>',
            esc_html(tajinoos_translate('academic.site_notice'))
        );
        $content = preg_replace(
            '~(<section\b[^>]*\bid=(["\'])accueil\2[^>]*>)~i',
            $notice . '$1',
            $content,
            1
        ) ?: $content;
    }

    if (strpos($content, 'tajx-footer-legal') === false) {
        $links = tajinoos_academic_legal_links_html('tajx-footer-legal');
        $content = preg_replace('~</footer>~i', $links . '</footer>', $content, 1) ?: $content;
    }

    return $content;
}

/**
 * Render the selected bilingual legal page through the current language
 * resolver. The translated HTML is theme-owned and passed through wp_kses.
 *
 * @param array<string, mixed> $attributes
 */
function tajinoos_academic_render_legal_page(array $attributes = []): string
{
    $attributes = shortcode_atts(['type' => 'privacy'], $attributes, 'tajinoos_academic_legal');
    $type = sanitize_key((string) $attributes['type']);
    $slugs = tajinoos_academic_legal_page_slugs();

    if (!isset($slugs[$type])) {
        return '';
    }

    $language = tajinoos_get_current_language();
    $title = tajinoos_translate('legal.' . $type . '.title', [], $language);
    $content = tajinoos_translate('legal.' . $type . '.content', [], $language);

    return sprintf(
        '<article class="taj-academic-legal-page"><span class="taj-academic-legal-page__eyebrow">%s</span><h1>%s</h1><div class="taj-academic-legal-page__content">%s</div>%s</article>',
        esc_html(tajinoos_translate('academic.eyebrow', [], $language)),
        esc_html($title),
        wp_kses_post($content),
        tajinoos_academic_legal_links_html('taj-academic-legal-page__links', $language)
    );
}

function tajinoos_academic_disable_user_sitemap($provider, string $name)
{
    return $name === 'users' ? false : $provider;
}

function tajinoos_academic_block_author_archives(): void
{
    if (!is_author() || is_admin()) {
        return;
    }

    tajinoos_academic_render_404();
}

function tajinoos_academic_block_user_sitemap_routes(): void
{
    $request_path = isset($_SERVER['REQUEST_URI'])
        ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)
        : '';
    $request_path = trim($request_path, '/');

    if (!preg_match('/^wp-sitemap-users(?:-[0-9]+)?\.xml$/i', $request_path)) {
        return;
    }

    tajinoos_academic_render_404();
}

function tajinoos_academic_render_404(): void
{
    if (is_admin()) {
        return;
    }

    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();

    $template = get_404_template();
    if ($template) {
        include $template;
    }
    exit;
}
