<?php
/**
 * Tajinoos child theme assets.
 *
 * Keeps landing-page polish outside WordPress core, Astra, and Elementor.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TAJINOOS_CHILD_VERSION', '1.7.0');

require_once get_stylesheet_directory() . '/inc/tajinoos-i18n.php';
require_once get_stylesheet_directory() . '/inc/tajinoos-mail.php';
require_once get_stylesheet_directory() . '/inc/tajinoos-orders.php';
require_once get_stylesheet_directory() . '/inc/tajinoos-thank-you.php';

add_action('wp_enqueue_scripts', 'tajinoos_child_enqueue_assets', 20);
add_action('init', 'tajinoos_child_ensure_thank_you_page');
add_filter('wp_list_pages_excludes', 'tajinoos_child_exclude_thank_you_from_page_list');
add_filter('body_class', 'tajinoos_child_thank_you_body_class');
add_filter('style_loader_src', 'tajinoos_child_page_13_relative_premium_css', 10, 2);
add_filter('script_loader_src', 'tajinoos_child_page_13_relative_premium_js', 10, 2);
add_filter('the_content', 'tajinoos_child_update_landing_navigation_labels', 20);
add_filter('the_content', 'tajinoos_child_render_testimonials', 21);
add_filter('the_content', 'tajinoos_child_render_reference_product_section', 23);
add_filter('the_content', 'tajinoos_child_render_editorial_benefits_section', 24);
add_filter('the_content', 'tajinoos_child_render_final_hero_faq_sections', 99);
add_filter('the_content', 'tajinoos_child_render_reference_match_hero', 100);
add_filter('the_content', 'tajinoos_child_render_command_rebuild_section', 120);
add_filter('the_content', 'tajinoos_child_localize_landing_content', 125);
add_filter('the_content', 'tajinoos_child_add_landing_motion_attributes', 130);
add_action('wp_head', 'tajinoos_child_print_final_hero_faq_css', 100);
add_action('wp_footer', 'tajinoos_child_render_floating_actions', 5);

/**
 * Keep the Page 13 premium assets on the active preview host.
 *
 * The Cloudflare preview forwards HTTPS while WordPress retains its local
 * development URL. A relative URL prevents the public page from requesting
 * premium assets from https://localhost:10034.
 */
function tajinoos_child_page_13_relative_premium_css(string $src, string $handle): string
{
    if ($handle !== 'tajinoos-premium' || !is_page(13)) {
        return $src;
    }

    return wp_make_link_relative($src);
}

function tajinoos_child_page_13_relative_premium_js(string $src, string $handle): string
{
    if ($handle !== 'tajinoos-premium' || !is_page(13)) {
        return $src;
    }

    return wp_make_link_relative($src);
}

function tajinoos_child_enqueue_assets(): void
{
    $premium_css_path = get_stylesheet_directory() . '/assets/css/tajinoos-premium.css';
    $premium_js_path = get_stylesheet_directory() . '/assets/js/tajinoos-premium.js';
    $premium_css_version = is_file($premium_css_path) ? (string) filemtime($premium_css_path) : TAJINOOS_CHILD_VERSION;
    $premium_js_version = is_file($premium_js_path) ? (string) filemtime($premium_js_path) : TAJINOOS_CHILD_VERSION;

    wp_enqueue_style(
        'tajinoos-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'tajinoos-child-style',
        get_stylesheet_uri(),
        ['astra-theme-css'],
        TAJINOOS_CHILD_VERSION
    );

    wp_enqueue_style(
        'tajinoos-premium',
        get_stylesheet_directory_uri() . '/assets/css/tajinoos-premium.css',
        ['tajinoos-child-style', 'tajinoos-fonts'],
        $premium_css_version
    );

    wp_enqueue_script(
        'tajinoos-premium',
        get_stylesheet_directory_uri() . '/assets/js/tajinoos-premium.js',
        [],
        $premium_js_version,
        true
    );

    wp_localize_script('tajinoos-premium', 'tajinoosOrder', [
        'ajaxUrl' => wp_make_link_relative(admin_url('admin-ajax.php')),
        'unitPrice' => tajinoos_get_order_unit_price(),
        'marrakechDeliveryFee' => TAJINOOS_ORDER_MARRAKECH_DELIVERY_FEE,
        'otherCityDeliveryFee' => TAJINOOS_ORDER_OTHER_CITY_DELIVERY_FEE,
        'language' => tajinoos_get_current_language(),
        'processingLabel' => tajinoos_translate('js.processing'),
        'networkError' => tajinoos_translate('js.network_error'),
        'genericError' => tajinoos_translate('js.generic_error'),
        'validationError' => tajinoos_translate('js.validation_error'),
        'deliveryPendingLabel' => tajinoos_translate('js.delivery_pending'),
        'freeDeliveryLabel' => tajinoos_translate('js.delivery_free'),
        'liveTotalLabel' => tajinoos_translate('js.live_total'),
        'liveCityNeededLabel' => tajinoos_translate('js.live_city_needed'),
        'menuOpenLabel' => tajinoos_translate('nav.open'),
        'menuCloseLabel' => tajinoos_translate('nav.close'),
        'homeLabel' => tajinoos_translate('nav.home'),
        'brandHomeLabel' => tajinoos_translate('nav.brand_home'),
        'processLabel' => tajinoos_translate('nav.process'),
        'footerNavigationLabel' => tajinoos_translate('footer.aria'),
        'footerNavigationHeading' => tajinoos_translate('footer.navigation'),
        'footerLinks' => [
            'home' => tajinoos_translate('nav.home'),
            'heritage' => tajinoos_translate('nav.heritage'),
            'benefits' => tajinoos_translate('nav.benefits'),
            'process' => tajinoos_translate('nav.process'),
            'product' => tajinoos_translate('nav.product'),
            'commitments' => tajinoos_translate('nav.commitments'),
            'order' => tajinoos_translate('nav.order'),
        ],
    ]);
}

function tajinoos_child_ensure_thank_you_page(): void
{
    if (wp_installing() || get_page_by_path('merci', OBJECT, 'page')) {
        return;
    }

    wp_insert_post([
        'post_title' => 'Merci pour votre commande',
        'post_name' => 'merci',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_content' => '[tajinoos_thank_you]',
    ]);
}


function tajinoos_child_exclude_thank_you_from_page_list(array $excluded): array
{
    $page = get_page_by_path('merci', OBJECT, 'page');

    if ($page instanceof WP_Post) {
        $excluded[] = (int) $page->ID;
    }

    return array_values(array_unique($excluded));
}

function tajinoos_child_thank_you_body_class(array $classes): array
{
    if (is_page(13)) {
        $classes[] = 'has-tajx-page';
    }

    if (is_page('merci')) {
        $classes[] = 'tajinoos-thank-you-page';
        $classes[] = 'taj-thanks-page-body';
    }

    return $classes;
}

function tajinoos_child_should_filter_landing_content(): bool
{
    if (is_page(13) && in_the_loop() && is_main_query()) {
        return true;
    }

    return defined('REST_REQUEST')
        && REST_REQUEST
        && (int) get_the_ID() === 13;
}

function tajinoos_child_update_landing_navigation_labels(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content()) {
        return $content;
    }

    $content = preg_replace(
        '~(<a\b[^>]*href=(["\'])#avis\2[^>]*>).*?(</a>)~si',
        '$1Nos engagements$3',
        $content
    ) ?: $content;

    return preg_replace(
        '~<div\b[^>]*class=(["\'])[^"\']*\btajx-mobile-sticky\b[^"\']*\1[^>]*>.*?</div>~si',
        '',
        $content,
        1
    ) ?: $content;
}

/**
 * Localize the final hybrid markup after every PHP section renderer has run.
 */
function tajinoos_child_localize_landing_content(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content()) {
        return $content;
    }

    $content = tajinoos_localize_markup($content, 'landing');

    if (strpos($content, 'href="#artisanat"') === false) {
        $process_link = sprintf(
            '<a href="#artisanat">%s</a> ',
            esc_html(tajinoos_translate('nav.process'))
        );
        $content = preg_replace(
            '~(<a\b[^>]*href=("|\')#produit\2[^>]*>)~i',
            $process_link . '$1',
            $content,
            1
        ) ?: $content;
    }

    if (strpos($content, 'href="#faq"') === false) {
        $faq_link = sprintf('<a href="#faq">%s</a> ', esc_html(tajinoos_translate('nav.faq')));
        $content = preg_replace(
            '~(<a\b[^>]*class=("|\')[^"\']*\btajx-navbar-cta\b[^"\']*\2[^>]*>)~i',
            $faq_link . '$1',
            $content,
            1
        ) ?: $content;
    }

    if (strpos($content, 'data-taj-language-switcher') === false) {
        $switcher = tajinoos_language_switcher_html();
        $content = preg_replace(
            '~(<a\b[^>]*class=("|\')[^"\']*\btajx-navbar-cta\b[^"\']*\2[^>]*>)~i',
            $switcher . '$1',
            $content,
            1
        ) ?: $content;
    }

    return $content;
}

/**
 * Replace only the landing page testimonials section while keeping its anchor.
 */
function tajinoos_child_render_testimonials(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content() || strpos($content, 'id="avis"') === false) {
        return $content;
    }

    $testimonials = <<<'HTML'
<section id="avis" class="tajx-section tajx-testimonials" aria-labelledby="tajx-testimonials-title">
  <div class="tajx-wrap tajx-testimonials-inner" data-motion-group>
    <div class="tajx-testimonials-copy" data-motion="fade-up" data-motion-index="0">
      <div class="tajx-testimonials-labels">
        <span class="tajx-eyebrow">NOS ENGAGEMENTS</span>
        <span class="tajx-testimonials-craft-badge"><span aria-hidden="true">✓</span> TRANSPARENCE TAJINOOS</span>
      </div>
      <h2 id="tajx-testimonials-title">UNE COMMANDE SIMPLE, TRANSPARENTE ET ACCOMPAGNÉE.</h2>
      <p class="tajx-testimonials-lede">De la vérification de chaque pièce à l’assistance après réception, nous vous expliquons clairement comment votre commande est préparée, livrée et suivie.</p>

      <div class="tajx-testimonials-stats" aria-label="Prix et paiement">
        <div class="tajx-testimonials-stat">
          <strong>249 MAD</strong>
          <span>livré à Marrakech</span>
        </div>
        <div class="tajx-testimonials-stat">
          <strong>269 MAD</strong>
          <span>livré dans les autres villes</span>
        </div>
        <div class="tajx-testimonials-stat">
          <strong>Paiement</strong>
          <span>à la livraison</span>
        </div>
      </div>

      <a class="tajx-btn primary tajx-testimonials-cta" href="#commande">COMMANDER MON TAJINOOS — 249 MAD</a>
      <p class="tajx-testimonials-reassurance">Prix de lancement <span>·</span> Paiement à la livraison <span>·</span> Garantie 7 jours encadrée</p>
    </div>

    <div class="tajx-testimonials-showcase" data-motion="slide-left" data-motion-index="1">
      <div class="tajx-reviews-marquee" aria-label="Les engagements Tajinoos">
        <div class="tajx-reviews-track">
        <article class="tajx-review-card tajx-review-featured">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>VÉRIFIÉ AVANT EXPÉDITION</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Contrôle qualité</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Chaque pièce est contrôlée avant son emballage.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>EMBALLAGE RENFORCÉ</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Protection séparée</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>La base et le couvercle sont protégés séparément.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>CONFIRMATION HUMAINE</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Avant expédition</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Nous confirmons votre commande avant l’expédition.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>PAIEMENT À LA LIVRAISON</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Aucun paiement en ligne</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Vous ne payez rien en ligne.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>ASSISTANCE APRÈS RÉCEPTION</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Équipe disponible</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Nous restons disponibles en cas de problème.</p>
        </article>

        <article class="tajx-review-card tajx-review-featured" aria-hidden="true">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>VÉRIFIÉ AVANT EXPÉDITION</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Contrôle qualité</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Chaque pièce est contrôlée avant son emballage.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>EMBALLAGE RENFORCÉ</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Protection séparée</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>La base et le couvercle sont protégés séparément.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>CONFIRMATION HUMAINE</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Avant expédition</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Nous confirmons votre commande avant l’expédition.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>PAIEMENT À LA LIVRAISON</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Aucun paiement en ligne</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Vous ne payez rien en ligne.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <div class="tajx-review-avatar" aria-hidden="true">✓</div>
            <div class="tajx-review-person"><h3>ASSISTANCE APRÈS RÉCEPTION</h3><span>Engagement Tajinoos</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Équipe disponible</span>
          </header>
          <div class="tajx-review-stars" aria-hidden="true">◆</div>
          <p>Nous restons disponibles en cas de problème.</p>
        </article>

        </div>
      </div>
    </div>
  </div>
</section>
HTML;

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])avis\1[^>]*>.*?</section>~s',
        $testimonials,
        $content,
        1
    ) ?: $content;
}


/**
 * Rebuild only the product offer section with the approved premium layout.
 */
function tajinoos_child_render_reference_product_section(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content() || strpos($content, 'id="produit"') === false) {
        return $content;
    }

    $product = <<<'HTML'
<section id="produit" class="tajx-section tajx-offer taj-product-final" aria-label="Tajine artisanal Tajinoos Premium">
  <div class="taj-product-final__desktop" data-motion-group>
    <figure class="taj-product-final__visual" data-motion="slide-right" data-motion-index="0">
      <div class="taj-product-final__stage">
        <img class="taj-product-final__image" src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" alt="Tajine artisanal Tajinoos Premium sur son socle" width="1024" height="1024" loading="lazy" decoding="async">
      </div>
      <figcaption class="taj-product-final__trust" aria-label="Garanties principales">
        <span><strong>PAIEMENT</strong><small>&agrave; la livraison</small></span>
        <span><strong>CONFIRMATION</strong><small>t&eacute;l&eacute;phonique</small></span>
        <span><strong>GARANTIE</strong><small>7 jours</small></span>
      </figcaption>
    </figure>

    <div class="taj-product-final__content" data-motion="slide-left" data-motion-index="1">
      <header class="taj-product-final__header">
        <span class="taj-product-final__eyebrow">OFFRE SIGNATURE</span>
        <h2 id="tajx-offer-title" class="taj-product-final__title"><span>TAJINE ARTISANAL</span><em>TAJINOOS PREMIUM</em></h2>
        <p class="taj-product-final__intro">Un tajine en terre cuite fa&ccedil;onn&eacute; &agrave; la main pour une cuisson lente,<br>une table &eacute;l&eacute;gante et des repas qui rassemblent.</p>
      </header>

      <div class="taj-product-final__price" aria-label="249 MAD livr&eacute; &agrave; Marrakech. Prix de lancement 269 MAD livr&eacute; dans les autres villes.">
        <div class="taj-product-final__price-main">
          <strong><span>249</span> <small>MAD</small></strong>
          <p><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>livr&eacute; &agrave; Marrakech</p>
        </div>
        <span class="taj-product-final__price-divider" aria-hidden="true"></span>
        <div class="taj-product-final__price-secondary">
          <span>PRIX DE LANCEMENT</span>
          <strong>269 <small>MAD</small></strong>
          <p><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>livr&eacute; dans les autres villes</p>
        </div>
      </div>

      <dl class="taj-product-final__specs" aria-label="Caract&eacute;ristiques du Tajinoos Premium">
        <div class="taj-product-final__spec"><dt><span class="taj-product-final__spec-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8"/><path d="M5 17 17 5M5 17l3-.5M5 17l.5-3M19 7l-3 .5M19 7l-.5 3"/></svg></span><span>DIAM&Egrave;TRE</span></dt><dd>30 cm</dd></div>
        <div class="taj-product-final__spec"><dt><span class="taj-product-final__spec-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 4h8M8 20h8M10 4v16M14 4v16M7 7l3-3 3 3M11 17l3 3 3-3"/></svg></span><span>HAUTEUR</span></dt><dd>Environ 25 cm</dd></div>
        <div class="taj-product-final__spec"><dt><span class="taj-product-final__spec-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 8h10l2 12H5L7 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/><text x="8" y="17">KG</text></svg></span><span>POIDS</span></dt><dd>Environ 3 kg</dd></div>
        <div class="taj-product-final__spec"><dt><span class="taj-product-final__spec-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 3h4v4l5 8-3 6H8l-3-6 5-8V3ZM7 15h10M9 11h6"/></svg></span><span>CAPACIT&Eacute;</span></dt><dd>Environ 3,5 litres</dd></div>
        <div class="taj-product-final__spec"><dt><span class="taj-product-final__spec-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="9" cy="8" r="3"/><circle cx="16.5" cy="9" r="2.5"/><path d="M3 20v-2a5 5 0 0 1 10 0v2M13 16a4 4 0 0 1 8 2v2"/></svg></span><span>PORTIONS</span></dt><dd>4 &agrave; 6 personnes</dd></div>
        <div class="taj-product-final__spec"><dt><span class="taj-product-final__spec-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 4C12 4 7 8 7 14a5 5 0 0 0 5 5c5 0 8-6 8-15Z"/><path d="M4 21c3-6 7-9 12-12"/></svg></span><span>MATI&Egrave;RE</span></dt><dd>Terre cuite artisanale</dd></div>
        <div class="taj-product-final__spec"><dt><span class="taj-product-final__spec-icon"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 10h14l-1 9H6l-1-9ZM8 10V8h8v2M9 5c0-1 1-1 1-2M12 5c0-1 1-1 1-2M15 5c0-1 1-1 1-2"/></svg></span><span>USAGE</span></dt><dd>Cuisson lente et service &agrave; table</dd></div>
      </dl>

      <div class="taj-product-final__care" aria-label="Compatibilit&eacute; et entretien">
        <div class="taj-product-final__care-column">
          <div><span class="taj-product-final__star" aria-hidden="true">&#10022;</span><p><strong>GAZ ET FOUR</strong><small>Mont&eacute;e progressive en temp&eacute;rature.</small></p></div>
          <div><span class="taj-product-final__star" aria-hidden="true">&#10022;</span><p><strong>INDUCTION</strong><small>Adaptateur n&eacute;cessaire.</small></p></div>
        </div>
        <div class="taj-product-final__care-column">
          <div><span class="taj-product-final__star" aria-hidden="true">&#10022;</span><p><strong>PLAQUE &Eacute;LECTRIQUE</strong><small>Diffuseur recommand&eacute;.</small></p></div>
          <div><span class="taj-product-final__star" aria-hidden="true">&#10022;</span><p><strong>ENTRETIEN</strong><small>Lavage &agrave; la main recommand&eacute;.</small></p></div>
        </div>
      </div>

      <a class="taj-product-final__cta" href="#commande">
        <svg class="taj-product-final__bag" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        <span>COMMANDER MON TAJINOOS &mdash; 249 MAD</span>
        <span class="taj-product-final__arrow" aria-hidden="true">&rarr;</span>
      </a>
      <p class="taj-product-final__reassurance">PRODUIT CONTR&Ocirc;L&Eacute; AVANT EXP&Eacute;DITION <span aria-hidden="true">&bull;</span> ASSISTANCE APR&Egrave;S R&Eacute;CEPTION</p>
    </div>
  </div>

  <div class="tajp-mobile" data-motion-group>
    <header class="tajp-mobile__intro" data-motion="fade-up" data-motion-index="0">
      <span class="tajp-mobile__eyebrow">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 2.8c.8 4.4 3.4 7 7.8 7.8-4.4.8-7 3.4-7.8 7.8-.8-4.4-3.4-7-7.8-7.8 4.4-.8 7-3.4 7.8-7.8Z"/></svg>
        LE TAJINE SIGNATURE
      </span>
      <h2 class="tajp-mobile__title"><span>TAJINE ARTISANAL</span><span>TAJINOOS PREMIUM</span></h2>
      <p class="tajp-mobile__lead">Une cuisson lente, homog&egrave;ne et pleine de saveurs.</p>
    </header>

    <figure class="tajp-mobile__media" aria-label="Tajine artisanal Tajinoos Premium fait main au Maroc" data-motion="scale-soft" data-motion-index="1">
      <span class="tajp-mobile__handmade">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m12 3 1.1 3.2L16 7.5l-2.9 1.3L12 12l-1.1-3.2L8 7.5l2.9-1.3L12 3Z"/><path d="M5 12.5v4.2c0 2.4 1.9 4.3 4.3 4.3H12M19 12.5v4.2c0 2.4-1.9 4.3-4.3 4.3H12"/><path d="M5 13.5 3.8 12a1.4 1.4 0 0 1 2.1-1.8L9 13M19 13.5l1.2-1.5a1.4 1.4 0 0 0-2.1-1.8L15 13"/></svg>
        <span>FAIT MAIN<br>AU MAROC</span>
      </span>

      <div class="tajp-mobile__product-stage">
        <img class="tajp-mobile__product" src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" alt="Tajine artisanal Tajinoos Premium" width="1024" height="1024" loading="lazy" decoding="async">
      </div>

      <div class="tajp-mobile__specs" aria-label="Caract&eacute;ristiques principales">
        <div class="tajp-mobile__spec">
          <span class="tajp-mobile__spec-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="7.5"/><path d="M5.5 18.5 18.5 5.5M4 20l2.5-.5L4.5 17.5 4 20ZM20 4l-2.5.5 2 2L20 4Z"/></svg>
          </span>
          <span class="tajp-mobile__spec-copy"><small>DIAM&Egrave;TRE</small><strong>&Oslash; 30 CM</strong></span>
        </div>
        <div class="tajp-mobile__spec">
          <span class="tajp-mobile__spec-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="7" r="2.5"/><circle cx="5.5" cy="9" r="2"/><circle cx="18.5" cy="9" r="2"/><path d="M7.5 19v-4.2a4.5 4.5 0 0 1 9 0V19M2.5 18v-3.1a3 3 0 0 1 4.5-2.6M21.5 18v-3.1a3 3 0 0 0-4.5-2.6"/></svg>
          </span>
          <span class="tajp-mobile__spec-copy"><small>PORTIONS</small><strong>4&ndash;6 <em>PERSONNES</em></strong></span>
        </div>
        <div class="tajp-mobile__spec">
          <span class="tajp-mobile__spec-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><path d="M20 4C12 4 6.5 8.7 6.5 15.3c0 2.3 1.7 4.2 4 4.2C17 19.5 20 12 20 4Z"/><path d="M4 21c3.2-5.2 7.1-8.7 12.2-11.2"/></svg>
          </span>
          <span class="tajp-mobile__spec-copy"><small>MATI&Egrave;RE</small><strong>TERRE CUITE <em>ARTISANALE</em></strong></span>
        </div>
        <div class="tajp-mobile__spec">
          <span class="tajp-mobile__spec-icon" aria-hidden="true">↕</span>
          <span class="tajp-mobile__spec-copy"><small>HAUTEUR</small><strong>ENVIRON 25 CM</strong></span>
        </div>
        <div class="tajp-mobile__spec">
          <span class="tajp-mobile__spec-icon" aria-hidden="true">≈</span>
          <span class="tajp-mobile__spec-copy"><small>POIDS</small><strong>ENVIRON 3 KG</strong></span>
        </div>
        <div class="tajp-mobile__spec">
          <span class="tajp-mobile__spec-icon" aria-hidden="true">◇</span>
          <span class="tajp-mobile__spec-copy"><small>CAPACIT&Eacute;</small><strong>ENVIRON 3,5 L</strong></span>
        </div>
      </div>
    </figure>

    <div class="tajp-mobile__benefits" aria-label="Avantages du Tajinoos Premium" data-motion="fade-up" data-motion-index="2">
      <article class="tajp-mobile__benefit">
        <span class="tajp-mobile__benefit-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false"><path d="M8 15.5c-2.2-3.7.1-6.2 2.2-8.9.3 2.1 1.2 3.1 2.1 4.1.7-3.6 2.6-5.4 3.9-7.2.6 3.5 3.3 5.8 3.3 9.5a7.5 7.5 0 0 1-15 0c0-1.6.4-3 1.2-4.3.2 2.7 1 4.8 2.3 6.8Z"/><path d="M7 21h10M8.5 18.5h7"/></svg>
        </span>
        <h3>CUISSON HOMOG&Egrave;NE</h3>
        <p>La chaleur se diffuse lentement pour une cuisson r&eacute;guli&egrave;re et ma&icirc;tris&eacute;e.</p>
      </article>

      <article class="tajp-mobile__benefit">
        <span class="tajp-mobile__benefit-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false"><path d="M20 4c-6.9 0-11.5 3.7-11.5 8.4 0 2.2 1.7 4 4 4C17 16.4 20 11.3 20 4Z"/><path d="M4 20c3.4-5.3 7.2-8.5 12.4-10.8M9.2 16.1C7 13.9 4.8 13 2.5 13c0 4 2.1 6.5 5.1 6.5"/></svg>
        </span>
        <h3>SAVEURS PR&Eacute;SERV&Eacute;ES</h3>
        <p>Les aliments restent tendres, parfum&eacute;s et riches en saveurs naturelles.</p>
      </article>

      <article class="tajp-mobile__benefit">
        <span class="tajp-mobile__benefit-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false"><path d="M8.5 11V5.5a1.5 1.5 0 0 1 3 0V10M11.5 10V4a1.5 1.5 0 0 1 3 0v6M14.5 10V5.5a1.5 1.5 0 0 1 3 0V12M8.5 9.5a1.5 1.5 0 0 0-3 0V14c0 4.4 2.7 7 7 7h.5c4.1 0 6.5-2.6 6.5-6.5V10a1.5 1.5 0 0 0-3 0v2"/><path d="m12.5 13 .6 1.7 1.7.6-1.7.6-.6 1.7-.6-1.7-1.7-.6 1.7-.6.6-1.7Z"/></svg>
        </span>
        <h3>FABRICATION ARTISANALE</h3>
        <p>Chaque tajine est fa&ccedil;onn&eacute; et d&eacute;cor&eacute; &agrave; la main par des artisans marocains.</p>
      </article>

      <article class="tajp-mobile__benefit">
        <span class="tajp-mobile__benefit-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false"><path d="M12 3 8.5 8h7L12 3ZM5 11h14l-1 9H6l-1-9Z"/><path d="M3.5 8h17M3.5 11h17M8 15h8"/></svg>
        </span>
        <h3>ID&Eacute;AL AU QUOTIDIEN</h3>
        <p>Parfait pour les tajines, l&eacute;gumes, viandes et plats familiaux.</p>
      </article>
    </div>

    <div class="tajp-mobile__reassurance" aria-label="Garanties de commande" data-motion="fade" data-motion-index="3">
      <div class="tajp-mobile__reassurance-item">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg>
        <span>PAIEMENT &Agrave;<br>LA LIVRAISON</span>
      </div>
      <div class="tajp-mobile__reassurance-item">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M7.2 3.5 10 8 7.8 9.8a15.2 15.2 0 0 0 6.4 6.4L16 14l4.5 2.8-1.3 3.1c-.4.9-1.3 1.5-2.3 1.3C9.6 20 4 14.4 2.8 7.1c-.2-1 .4-1.9 1.3-2.3l3.1-1.3Z"/></svg>
        <span>CONFIRMATION<br>T&Eacute;L&Eacute;PHONIQUE</span>
      </div>
      <div class="tajp-mobile__reassurance-item">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 3 19 6v5.2c0 4.5-2.8 7.5-7 9.8-4.2-2.3-7-5.3-7-9.8V6l7-3Z"/><path d="m8.8 12 2.1 2.1 4.5-4.5"/></svg>
        <span>GARANTIE<br>7 JOURS</span>
      </div>
    </div>

    <div class="tajp-mobile__offer" aria-label="Offre Tajinoos Premium" data-motion="fade-up" data-motion-index="4">
      <div class="tajp-mobile__prices">
        <div class="tajp-mobile__old-price">
          <span>MARRAKECH</span>
          <strong>249 <small>MAD</small></strong>
        </div>
        <div class="tajp-mobile__current-price">
          <span>AUTRES VILLES</span>
          <strong>269 <small>MAD</small></strong>
        </div>
        <span class="tajp-mobile__urgency">PRIX DE LANCEMENT</span>
      </div>

      <a class="tajp-mobile__cta" href="#commande">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L21 7H6"/><circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
        <span>COMMANDER MON TAJINOOS — 249 MAD</span>
      </a>
      <p class="tajp-mobile__payment-note">Paiement &agrave; la r&eacute;ception</p>
    </div>

    <p class="tajp-mobile__quality" data-motion="fade" data-motion-index="5">
      <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 3 19 6v5.2c0 4.5-2.8 7.5-7 9.8-4.2-2.3-7-5.3-7-9.8V6l7-3Z"/><path d="m8.8 12 2.1 2.1 4.5-4.5"/></svg>
      <span>QUALIT&Eacute; AUTHENTIQUE, CON&Ccedil;UE POUR DURER.</span>
    </p>
  </div>
</section>
HTML;

    $content = str_replace(
        'Inspiration des tables familiales marocaines',
        'Inspiré des tables familiales marocaines',
        $content
    );

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])produit\1[^>]*>.*?</section>~s',
        $product,
        $content,
        1
    ) ?: $content;
}

function tajinoos_child_render_editorial_benefits_section(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content() || strpos($content, 'id="benefices"') === false) {
        return $content;
    }

    $benefits = <<<'HTML'
<section id="benefices" class="tajx-section taj-benefices-final" aria-labelledby="taj-benefices-final-title">
  <span class="taj-benefices-final__arch" aria-hidden="true"></span>

  <div class="taj-benefices-final__inner">
    <header class="taj-benefices-final__head" data-motion="fade-up">
      <div class="taj-benefices-final__heading">
        <p class="taj-benefices-final__eyebrow"><span aria-hidden="true"></span>POURQUOI TAJINOOS</p>
        <h2 id="taj-benefices-final-title">CE QUI REND<br><span class="taj-benefices-final__brand">TAJINOOS</span><span class="taj-benefices-final__difference"> DIFF&Eacute;RENT</span></h2>
      </div>
      <p class="taj-benefices-final__intro">Un tajine artisanal pens&eacute; pour mieux cuisiner,<br>embellir la table et offrir une commande<br>simple<span class="taj-benefices-final__intro-desktop">, rassurante et humaine.</span><span class="taj-benefices-final__intro-mobile">, rassurante et humaine.</span></p>
    </header>

    <div class="taj-benefices-final__grid" aria-label="Les avantages Tajinoos" data-motion-group>
      <article class="taj-benefices-final__item" data-motion="fade-up" data-motion-index="0">
        <span class="taj-benefices-final__item-icon" aria-hidden="true"><svg viewBox="0 0 64 64" focusable="false"><path d="M22 44V22a4 4 0 0 1 8 0v13M30 34V16a4 4 0 0 1 8 0v19M38 34V19a4 4 0 0 1 8 0v19M22 31l-4-4a4 4 0 0 0-6 5l10 16c3 5 8 8 14 8h2c9 0 16-7 16-16V27a4 4 0 0 0-8 0v9"/></svg></span>
        <span class="taj-benefices-final__number" aria-hidden="true"><span>01</span></span>
        <div class="taj-benefices-final__item-content">
          <p class="taj-benefices-final__label">AUTHENTICIT&Eacute;</p>
          <h3>Fa&ccedil;onn&eacute; &agrave; la<br> main au Maroc</h3>
          <p class="taj-benefices-final__copy">Chaque pi&egrave;ce poss&egrave;de ses propres<br> nuances et son caract&egrave;re.</p>
          <p class="taj-benefices-final__proof">Petites s&eacute;ries artisanales</p>
        </div>
      </article>

      <article class="taj-benefices-final__item" data-motion="fade-up" data-motion-index="1">
        <span class="taj-benefices-final__item-icon" aria-hidden="true"><svg viewBox="0 0 64 64" focusable="false"><path d="M33 58c-11 0-19-8-19-19 0-8 5-15 13-24 0 8 3 12 7 14 2-8 6-14 10-19 1 9 7 14 7 24 0 5-2 11-7 16 1-9-3-14-8-18 0 8-7 11-7 18 0 4 2 6 4 8Z"/></svg></span>
        <span class="taj-benefices-final__number" aria-hidden="true"><span>02</span></span>
        <div class="taj-benefices-final__item-content">
          <p class="taj-benefices-final__label">CUISSON MA&Icirc;TRIS&Eacute;E</p>
          <h3>Une chaleur<br> douce et r&eacute;guli&egrave;re</h3>
          <p class="taj-benefices-final__copy">La terre cuite concentre les ar&ocirc;mes<br> et pr&eacute;serve la tendret&eacute;.</p>
          <p class="taj-benefices-final__proof">Terre cuite naturelle</p>
        </div>
      </article>

      <article class="taj-benefices-final__item" data-motion="fade-up" data-motion-index="2">
        <span class="taj-benefices-final__item-icon" aria-hidden="true"><svg viewBox="0 0 64 64" focusable="false"><path d="M26 12h12l-2 8 15 25H13l15-25-2-8Zm-10 33h32l5 5-3 5H14l-3-5 5-5Zm7-8h18"/></svg></span>
        <span class="taj-benefices-final__number" aria-hidden="true"><span>03</span></span>
        <div class="taj-benefices-final__item-content">
          <p class="taj-benefices-final__label">UTILE &amp; D&Eacute;CORATIF</p>
          <h3>Pens&eacute; pour<br> cuisiner et servir</h3>
          <p class="taj-benefices-final__copy">Une pi&egrave;ce qui passe naturellement<br> de la cuisine &agrave; la table.</p>
          <p class="taj-benefices-final__proof">Beau au quotidien</p>
        </div>
      </article>

      <article class="taj-benefices-final__item" data-motion="fade-up" data-motion-index="3">
        <span class="taj-benefices-final__item-icon" aria-hidden="true"><svg viewBox="0 0 64 64" focusable="false"><path d="M54 31c0 11-10 20-23 20-4 0-8-1-11-3L9 53l4-11c-3-3-5-7-5-11 0-11 10-20 23-20s23 9 23 20Z"/></svg></span>
        <span class="taj-benefices-final__number" aria-hidden="true"><span>04</span></span>
        <div class="taj-benefices-final__item-content">
          <p class="taj-benefices-final__label">ACHAT RASSURANT</p>
          <h3>Commande simple<br> et humaine</h3>
          <p class="taj-benefices-final__copy">Confirmation avant exp&eacute;dition et<br> paiement &agrave; la livraison.</p>
          <p class="taj-benefices-final__proof">Accompagn&eacute; jusqu&rsquo;&agrave; r&eacute;ception</p>
        </div>
      </article>
    </div>
  </div>

  <div class="taj-benefices-final__trust-wrap" data-motion="fade" data-motion-index="4">
    <ul class="taj-benefices-final__trust" aria-label="Garanties de commande">
      <li><span class="taj-benefices-final__trust-check" aria-hidden="true">&#10003;</span><span class="taj-benefices-final__trust-icon" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><rect x="7" y="11" width="30" height="22" rx="3"/><path d="M7 18h30M29 37l4 4 8-10"/></svg></span><span>Paiement &agrave; la livraison</span></li>
      <li><span class="taj-benefices-final__trust-check" aria-hidden="true">&#10003;</span><span class="taj-benefices-final__trust-icon" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><path d="M39 23a15 15 0 0 1-22 13l-9 3 3-9A15 15 0 1 1 39 23Z"/><path d="M18 16c1 8 6 13 14 14l3-4-5-3-2 3c-3-1-5-3-6-6l3-2-3-5-4 3Z"/></svg></span><span>Confirmation WhatsApp</span></li>
      <li><span class="taj-benefices-final__trust-check" aria-hidden="true">&#10003;</span><span class="taj-benefices-final__trust-icon" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><path d="M4 13h24v22H4zM28 21h8l7 8v6H28zM13 40a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm21 0a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM7 20h14M2 25h14"/></svg></span><span>Marrakech gratuite &middot; autres villes 20 MAD</span></li>
      <li><span class="taj-benefices-final__trust-check" aria-hidden="true">&#10003;</span><span class="taj-benefices-final__trust-icon" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><path d="M24 5c6 5 12 6 17 7v12c0 10-7 16-17 20C14 40 7 34 7 24V12c5-1 11-2 17-7Z"/><path d="m16 24 5 5 11-12"/></svg></span><span>Garantie 7 jours</span></li>
    </ul>
  </div>

  <div class="taj-benefices-final__inner">
    <aside class="taj-benefices-final__cta" aria-label="Commander le Tajinoos artisanal" data-motion="fade-up" data-motion-index="5">
      <div class="taj-benefices-final__experience">
        <span class="taj-benefices-final__cta-shield" aria-hidden="true"><svg viewBox="0 0 64 64" focusable="false"><path d="M32 6c8 6 16 7 23 9v16c0 13-9 22-23 28C18 53 9 44 9 31V15c7-2 15-3 23-9Z"/><path d="m22 32 7 7 14-16"/></svg></span>
        <p class="taj-benefices-final__cta-label">L&rsquo;EXP&Eacute;RIENCE TAJINOOS</p>
        <p>Achat en toute confiance, de la commande &agrave; la r&eacute;ception.<br>Chaque tajine est v&eacute;rifi&eacute; avec soin et emball&eacute; avec attention.<br>Garantie 7 jours selon les conditions indiqu&eacute;es dans la FAQ.</p>
      </div>
      <span class="taj-benefices-final__cta-divider" aria-hidden="true"></span>
      <a href="#commande" aria-label="Commander mon Tajinoos &agrave; partir de 249 MAD"><span>COMMANDER MON TAJINOOS <strong>&mdash; 249 MAD</strong></span><span class="taj-benefices-final__cta-arrow" aria-hidden="true">&rarr;</span></a>
      <p class="taj-benefices-final__cta-support"><span aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>Paiement &agrave; la livraison &middot; 100% s&eacute;curis&eacute;</p>
    </aside>
  </div>
</section>
HTML;

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])benefices\1[^>]*>.*?</section>~s',
        $benefits,
        $content,
        1
    ) ?: $content;
}


function tajinoos_child_render_final_hero_faq_sections(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content()) {
        return $content;
    }


    $faq = <<<'HTML'
<section id="faq" class="taj-final-faq" aria-labelledby="taj-final-faq-title">
  <div class="taj-final-faq__inner">
    <header class="taj-final-faq__head" data-motion="fade-up">
      <span class="taj-final-eyebrow">Questions fréquentes</span>
      <h2 id="taj-final-faq-title">Les réponses avant de commander.</h2>
    </header>

    <div class="taj-final-faq__layout" data-motion-group>
      <div class="taj-final-faq__list" data-motion="fade-up" data-motion-index="0">
        <details><summary>Le tajine peut-il &ecirc;tre utilis&eacute; pour cuisiner ?</summary><p>Oui. Il est con&ccedil;u pour une cuisson lente &agrave; basse temp&eacute;rature, en respectant les conseils de premi&egrave;re utilisation et de compatibilit&eacute; ci-dessous.</p></details>
        <details><summary>Comment préparer le tajine avant la première utilisation ?</summary><p>1. Faites tremper la base et le couvercle dans l’eau pendant une nuit.<br>2. Laissez sécher naturellement.<br>3. Appliquez une fine couche d’huile alimentaire à l’intérieur.<br>4. Chauffez progressivement à basse température.<br>5. Laissez refroidir complètement avant le nettoyage.</p></details>
        <details><summary>Quelles sources de chaleur sont compatibles ?</summary><p>Compatible avec le gaz à feu doux ; un diffuseur de chaleur est recommandé.<br><br>Compatible avec le four avec une montée progressive en température : placez le tajine dans un four froid avant de commencer la cuisson.<br><br>Compatible avec une plaque électrique classique uniquement à faible puissance et avec un diffuseur.<br><br>Non compatible directement avec l’induction : un disque adaptateur compatible est nécessaire.</p></details>
        <details><summary>Quels sont les prix et délais de livraison ?</summary><p><strong>Marrakech :</strong> 249 MAD livré, livraison gratuite sous 24 heures maximum.<br><br><strong>Autres villes du Maroc :</strong> 269 MAD livré, dont 20 MAD de livraison. Délai estimé de 3 à 6 jours ouvrables.</p></details>
        <details><summary>Que faire si le produit arrive cassé ou endommagé ?</summary><p>Contactez-nous dans les 24 heures suivant la réception. Envoyez des photos claires du produit, du carton, des protections intérieures et de l’étiquette de livraison. Après vérification, Tajinoos prend en charge gratuitement le remplacement du produit ou son remboursement.</p></details>
        <details><summary>Que couvre la garantie 7 jours ?</summary><p><strong>La garantie couvre :</strong> un défaut de fabrication, un produit non conforme à la commande ou un dommage constaté à la réception.<br><br><strong>Elle ne couvre pas :</strong> une chute ou un choc après réception, une mauvaise utilisation, un choc thermique, une source de chaleur incompatible, ni les variations naturelles de couleur ou de finition liées au travail artisanal.</p></details>
        <details><summary>Qui prend en charge les frais de retour ?</summary><p><strong>Produit cassé, défectueux ou incorrect :</strong> les frais sont pris en charge par Tajinoos.<br><br><strong>Changement d’avis :</strong> les frais de retour sont à la charge du client, à condition que le produit soit inutilisé, intact et conservé dans son emballage d’origine.</p></details>
        <details><summary>Comment le Tajinoos est-il emballé ?</summary><p>Emballage renforcé avec carton rigide, protection séparée de la base et du couvercle, calage intérieur pour limiter les mouvements et indication « Fragile ».</p></details>
        <details><summary>Comment nettoyer le Tajinoos ?</summary><p>Lavage à la main recommandé afin de préserver la terre cuite et la finition artisanale. Évitez les chocs thermiques et laissez le tajine refroidir complètement avant le nettoyage.</p></details>
      </div>

      <aside class="taj-final-support" aria-label="Support Tajinoos" data-motion="fade-up" data-motion-index="1">
        <span class="taj-final-support__label">ASSISTANCE TAJINOOS</span>
        <span class="taj-final-support__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11a9 9 0 0 1 18 0"/><path d="M21 12v4a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M3 12v4a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg></span>
        <h3>Besoin d'aide ?</h3>
        <strong>Notre équipe est là pour vous.</strong>
        <p>Service client réactif 7j/7.</p>
        <a class="taj-final-support__wa" href="https://wa.me/?text=Bonjour%2C%20je%20souhaite%20commander%20un%20Tajinoos." target="_blank" rel="noopener noreferrer"><span class="taj-final-support__button-icon taj-final-support__button-icon--whatsapp" aria-hidden="true"><svg viewBox="0 0 16 16" focusable="false"><path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93a7.898 7.898 0 0 0-2.327-5.607zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.012-.304.088-.403.087-.087.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.33 1.129.422.475.152.904.129 1.246.08.38-.057 1.17-.479 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/></svg></span><span>WhatsApp</span></a>
        <a class="taj-final-support__email" href="mailto:orders@tajinoos.com"><span class="taj-final-support__button-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span><span>Envoyer un email</span></a>
        <div class="taj-final-support__trust" aria-label="Les engagements du service client">
          <span>Réponse rapide</span>
          <span>Service client 7j/7</span>
          <span>Confirmation humaine</span>
        </div>
      </aside>
    </div>
  </div>
</section>
HTML;

    $content = preg_replace('~<section\b[^>]*class=(["\'])[^"\']*\btajx-trustbar-section\b[^"\']*\1[^>]*>.*?</section>~s', '', $content, 1) ?: $content;
    $content = preg_replace('~<section\b[^>]*\bid=(["\'])faq\1[^>]*>.*?</section>~s', $faq, $content, 1) ?: $content;

    return $content;
}

function tajinoos_child_render_reference_match_hero(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content()) {
        return $content;
    }

    $hero = <<<'HTML'
<section id="accueil" class="taj-clean-hero" aria-labelledby="taj-clean-hero-title">
  <div class="taj-clean-hero__inner">
    <div class="taj-clean-hero__copy">
      <p class="taj-clean-hero__eyebrow">MADE IN MOROCCO</p>
      <h1 id="taj-clean-hero-title">LE <span class="taj-clean-hero__accent">TAJINE</span><br>QUE VOTRE<br>TABLE M&Eacute;RITE.</h1>
      <p class="taj-clean-hero__lead">Un tajine artisanal en terre cuite fa&ccedil;onn&eacute; au Maroc pour sublimer vos repas avec &eacute;l&eacute;gance et authenticit&eacute;.</p>

      <div class="taj-clean-hero__price" aria-label="Prix de l'offre Tajinoos">
        <div class="taj-clean-hero__price-main">
          <strong>249 <span class="taj-clean-hero__currency">MAD</span></strong>
          <span>livré à Marrakech</span>
        </div>
        <div class="taj-clean-hero__price-offer">
          <em>PRIX DE LANCEMENT</em>
          <strong>269 <span class="taj-clean-hero__currency">MAD</span></strong>
          <span>LIVRÉ DANS LES AUTRES VILLES</span>
        </div>
      </div>

      <div class="taj-clean-hero__actions">
        <a class="taj-clean-hero__primary" href="#commande">COMMANDER MON TAJINOOS &mdash; 249 MAD</a>
        <a class="taj-clean-hero__secondary" href="#produit">EN SAVOIR PLUS</a>
      </div>

      <div class="taj-clean-hero__trust" aria-label="Les garanties Tajinoos">
        <span class="taj-clean-hero__trust-delivery">
          <svg class="taj-clean-hero__trust-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 6h10v9h1.2a3 3 0 0 1 5.6 0H21v-4.1L18.4 8H15V6h4.3L23 10.1V17h-3.2a3 3 0 0 1-5.6 0H9.8a3 3 0 0 1-5.6 0H1V6h2Zm3 10.5a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0Zm10 0a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0ZM3 8v7h1.2a3 3 0 0 1 5.6 0H11V8H3Z"/></svg>
          Marrakech gratuite · autres villes 20 MAD
        </span>
        <span class="taj-clean-hero__trust-payment">
          <svg class="taj-clean-hero__trust-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 4h14V7H5v2Zm0 2v6h14v-6H5Zm2 3h5v2H7v-2Z"/></svg>
          Paiement &agrave; la livraison
        </span>
        <span class="taj-clean-hero__trust-confirmation">
          <svg class="taj-clean-hero__trust-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="m7.6 3.6 2.5 2.5c.6.6.6 1.5.1 2.1l-1.1 1.1a10.8 10.8 0 0 0 5.6 5.6l1.1-1.1c.6-.5 1.5-.5 2.1.1l2.5 2.5c.6.6.7 1.5.2 2.2l-1.2 1.7c-.5.7-1.4 1.1-2.3.9C10.6 20 4 13.4 2.8 6.9c-.2-.9.2-1.8.9-2.3l1.7-1.2c.7-.5 1.6-.4 2.2.2Z"/></svg>
          Confirmation t&eacute;l&eacute;phonique
        </span>
        <span class="taj-clean-hero__trust-guarantee">
          <svg class="taj-clean-hero__trust-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.5 21 6v6.3c0 5.4-3.6 8.7-9 9.7c-5.4-1-9-4.3-9-9.7V6l9-3.5Zm0 2.2L5 7.4v4.9c0 4.1 2.5 6.5 7 7.5c4.5-1 7-3.4 7-7.5V7.4l-7-2.7Zm3.8 4.5 1.4 1.4-5.9 5.9-3.5-3.5 1.4-1.4 2.1 2.1 4.5-4.5Z"/></svg>
          Garantie 7 jours
        </span>
        <span class="taj-clean-hero__trust-mobile">
          <svg class="taj-clean-hero__trust-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.5c1.2 2.4 1.9 4.4 1.9 6.1c0 2.1-1.1 3.6-2.9 4.5c-.4-1.2-.5-2.4-.2-3.6c-1.9 1.7-2.8 3.6-2.8 5.6c0 3 2 5.4 4.8 5.4c3.2 0 5.7-2.5 5.7-5.9c0-3.7-2.2-7.7-6.5-12.1ZM6.4 8.7C4.5 10.9 3.5 13.2 3.5 15.5c0 3.4 2.1 5.8 5.2 6.3c-1.8-1.5-2.7-3.6-2.7-6.3c0-2.2.7-4.5 2.2-6.9c-.7.5-1.3.5-1.8.1Z"/></svg>
          Fait main au Maroc
        </span>
      </div>
    </div>

    <figure class="taj-clean-hero__visual" aria-label="Tajine artisanal Tajinoos">
      <div class="taj-clean-hero__orange-panel" aria-hidden="true"></div>
      <div class="taj-clean-hero__glow" aria-hidden="true"></div>
      <div class="taj-clean-hero__ground-shadow" aria-hidden="true"></div>
      <div class="taj-clean-hero__product-wrap">
        <div class="taj-clean-hero__product-motion">
          <img class="taj-clean-hero__product" src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" alt="Tajine artisanal marocain Tajinoos" width="1024" height="1024" fetchpriority="high" decoding="sync">
        </div>
      </div>
    </figure>
  </div>
</section>
HTML;

    $content = preg_replace(
        '~<section\b[^>]*\bid=(["\'])accueil\1[^>]*>.*?</section>~s',
        $hero,
        $content,
        1
    ) ?: $content;

    $content = preg_replace('~<section\b[^>]*class=(["\'])[^"\']*\btajx-strip\b[^"\']*\1[^>]*>.*?</section>~s', '', $content, 1) ?: $content;
    $content = preg_replace('~<section\b[^>]*class=(["\'])[^"\']*\btajx-trustbar-section\b[^"\']*\1[^>]*>.*?</section>~s', '', $content, 1) ?: $content;

    return $content;
}


/**
 * Final rebuilt checkout section, rendered after earlier legacy order filters.
 */
function tajinoos_child_render_command_rebuild_section(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content() || strpos($content, 'id="commande"') === false) {
        return $content;
    }

    $form_action = esc_url(wp_make_link_relative(admin_url('admin-post.php')));
    $nonce_field = wp_nonce_field('tajinoos_order_submit', '_tajinoos_order_nonce', true, false);
    $source_url = esc_url(tajinoos_language_url(tajinoos_get_current_language(), '#commande'));
    $unit_price = (string) tajinoos_get_order_unit_price();
    $language = esc_attr(tajinoos_get_current_language());

    $order = <<<'HTML'
<section id="commande" class="tajx-section tajx-order tajcmd" aria-labelledby="tajcmd-title">
  <div class="tajcmd__inner">
    <header class="tajcmd__header" data-motion="fade-up">
      <span class="tajcmd__badge"><span aria-hidden="true">&#9671;</span> COMMANDE S&Eacute;CURIS&Eacute;E</span>
      <h2 id="tajcmd-title">FINALISER VOTRE COMMANDE</h2>
      <p>L&rsquo;excellence de l&rsquo;artisanat marocain, pr&ecirc;te &agrave; rejoindre votre table.</p>
    </header>

    <div class="tajx-order-grid tajcmd__grid" data-motion-group>
      <article class="tajcmd-product" aria-labelledby="tajcmd-product-title" data-motion="slide-right" data-motion-index="0">
        <div class="tajcmd-product__visual" aria-label="Tajine artisanal Tajinoos Premium">
          <img class="tajcmd-product__pattern" src="/wp-content/uploads/2026/06/tajinoos-pattern-bg.webp" alt="" loading="lazy" decoding="async" aria-hidden="true">
          <img class="tajcmd-product__image" src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" alt="Tajine artisanal Tajinoos Premium" width="1024" height="1024" loading="lazy" decoding="async">
        </div>

        <div class="tajcmd-product__content">
          <span class="tajcmd-product__badge">PRIX DE LANCEMENT</span>
          <h3 id="tajcmd-product-title">TAJINOOS PREMIUM</h3>
          <p class="tajcmd-product__mobile-kicker">Tajine artisanal</p>
          <p class="tajcmd-product__price"><strong>%%TAJINOOS_UNIT_PRICE%%</strong><span>MAD</span><small>Prix unitaire</small></p>
          <p class="tajcmd-product__description">Un tajine en terre cuite fa&ccedil;onn&eacute; &agrave; la main pour une cuisson lente, une table &eacute;l&eacute;gante et des repas qui rassemblent.</p>

          <ul class="tajcmd-product__microbenefits" aria-label="Avantages essentiels">
            <li>Fait main</li>
            <li>Paiement &agrave; la livraison</li>
            <li>Garantie 7 jours</li>
          </ul>

          <ul class="tajcmd-trust" aria-label="Garanties Tajinoos">
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 11.5V8a2 2 0 0 1 4 0v3"/><path d="M11 10V6.5a2 2 0 0 1 4 0V12"/><path d="M15 11V8.5a2 2 0 0 1 4 0v5.2c0 4.3-2.8 7.3-7.1 7.3H10a6 6 0 0 1-4.8-2.4L3 15.5a1.9 1.9 0 0 1 3-2.3l1.6 1.9"/></svg></span><strong>Fait main</strong><small>Chaque pi&egrave;ce est unique, r&eacute;alis&eacute;e avec soin.</small></li>
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 9h10l-1 9H8L7 9Z"/><path d="M5 9h14"/><path d="M9 9V7a3 3 0 0 1 6 0v2"/><path d="M9 4c-.8-.8-.8-1.6 0-2.4"/><path d="M13 4c-.8-.8-.8-1.6 0-2.4"/></svg></span><strong>Cuisson lente</strong><small>Le couvercle conique aide &agrave; pr&eacute;server les saveurs.</small></li>
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7.5h14.5A2.5 2.5 0 0 1 21 10v7a2.5 2.5 0 0 1-2.5 2.5h-12A2.5 2.5 0 0 1 4 17V7.5Z"/><path d="M4 8l10.5-3.5A2 2 0 0 1 17 6.4V8"/><path d="M16 13.5h5"/><path d="M17.5 13.5h.1"/></svg></span><strong>Paiement &agrave; la livraison</strong><small>R&eacute;glez en toute s&eacute;r&eacute;nit&eacute; &agrave; la r&eacute;ception.</small></li>
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 6h11v10H3z"/><path d="M14 9h4l3 4v3h-7z"/><path d="M6.5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M17.5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg></span><strong>Livraison claire</strong><small>Gratuite à Marrakech ; 20 MAD dans les autres villes.</small></li>
          </ul>
        </div>
      </article>

      <form class="tajx-form-card tajx-order-form tajcmd-form" action="%%TAJINOOS_ORDER_ACTION%%" method="post" data-motion="slide-left" data-motion-index="1">
        %%TAJINOOS_ORDER_NONCE%%
        <input type="hidden" name="action" value="tajinoos_submit_order">
        <input type="hidden" name="tajinoos_language" value="%%TAJINOOS_LANGUAGE%%">
        <input type="hidden" name="Source" value="%%TAJINOOS_ORDER_SOURCE%%">
        <input type="hidden" name="Prix_unitaire" value="%%TAJINOOS_UNIT_PRICE%%">
        <input type="hidden" name="Sous_total" value="%%TAJINOOS_UNIT_PRICE%%" data-tajcmd-subtotal-input>
        <input type="hidden" name="Frais_livraison" value="0" data-tajcmd-delivery-input>
        <input type="hidden" name="Total" value="%%TAJINOOS_UNIT_PRICE%%" data-tajcmd-total-input>

        <header class="tajcmd-form__head">
          <span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.5 19 7v5c0 4.5-2.8 7.4-7 8.5-4.2-1.1-7-4-7-8.5V7l7-3.5Z"/><path d="m9.5 12 1.7 1.7 3.6-4"/></svg></span>
          <div class="tajcmd-form__heading-copy"><h3>VOS INFORMATIONS</h3><small>Commande s&eacute;curis&eacute;e</small></div>
        </header>

        <div class="tajcmd-form__body">
          <div class="tajcmd-form__messages" data-tajcmd-messages role="alert" tabindex="-1" hidden></div>

          <div class="tajcmd-form__row">
            <label class="tajcmd-field">NOM COMPLET
              <input name="Nom" required type="text" maxlength="120" placeholder="Ex: Ahmed Alaoui" autocomplete="name">
            </label>
            <label class="tajcmd-field">T&Eacute;L&Eacute;PHONE (WHATSAPP)
              <input name="Telephone" required type="tel" maxlength="24" inputmode="tel" placeholder="06 XX XX XX XX" autocomplete="tel">
            </label>
          </div>

          <div class="tajcmd-form__row">
            <label class="tajcmd-field">VILLE DE LIVRAISON
              <input name="Ville" required type="text" maxlength="80" placeholder="Ex: Marrakech, Rabat..." autocomplete="address-level2" data-tajcmd-city>
              <small>Marrakech : livraison gratuite · autres villes : 20 MAD</small>
            </label>
            <label class="tajcmd-field">ADRESSE DE LIVRAISON
              <input name="Adresse" required type="text" maxlength="300" placeholder="Quartier, rue, résidence..." autocomplete="street-address">
            </label>
          </div>

          <div class="tajcmd-form__row tajcmd-form__row--compact">
            <label class="tajcmd-field">QUANTIT&Eacute;
              <select name="Quantite" data-tajcmd-quantity-select>
                <option value="1">1 pi&egrave;ce</option>
                <option value="2">2 pi&egrave;ces</option>
                <option value="3">3 pi&egrave;ces</option>
                <option value="4">4 pi&egrave;ces</option>
                <option value="5">5 pi&egrave;ces</option>
              </select>
              <small>Le total se met &agrave; jour automatiquement</small>
            </label>
            <label class="tajcmd-field">MOD&Egrave;LE
              <select name="Produit">
                <option value="Tajine artisanal Tajinoos Premium">Tajine artisanal Tajinoos Premium</option>
              </select>
            </label>
          </div>

          <label class="tajcmd-field tajcmd-field--full">MESSAGE (OPTIONNEL)
            <textarea name="Message" rows="3" maxlength="1000" placeholder="Pr&eacute;cisez un horaire de rappel, un d&eacute;tail de livraison..."></textarea>
          </label>

          <div class="tajcmd-pricing-guide" aria-label="Détail des prix">
            <span>Prix du Tajinoos <strong>%%TAJINOOS_UNIT_PRICE%% MAD</strong></span>
            <span>Livraison à Marrakech <strong>Gratuite</strong></span>
            <span>Livraison autres villes <strong>20 MAD</strong></span>
          </div>

          <div class="tajcmd-action">
            <div class="tajcmd-total" aria-label="Total &agrave; payer" aria-live="polite" aria-atomic="true">
              <span class="tajcmd-total__meta">
                <strong>TOTAL &Agrave; PAYER</strong>
                <small>Sous-total : <span data-tajcmd-product-subtotal>%%TAJINOOS_UNIT_PRICE%%</span> MAD · Livraison : <span data-tajcmd-delivery-fee>Gratuite</span></small>
              </span>
              <strong class="tajcmd-total__price"><span data-tajcmd-form-total>%%TAJINOOS_UNIT_PRICE%%</span><span class="tajcmd-total__currency"> MAD</span></strong>
            </div>

            <button class="tajx-submit tajcmd-submit" type="submit"><span class="tajcmd-submit__label">COMMANDER MON TAJINOOS</span> <span class="tajcmd-submit__separator" aria-hidden="true">&mdash;</span> <span class="tajcmd-submit__price"><span data-tajcmd-cta-total>%%TAJINOOS_UNIT_PRICE%%</span><span class="tajcmd-submit__currency"> MAD</span></span> <span class="tajcmd-submit__arrow" aria-hidden="true">&rarr;</span></button>
            <p class="screen-reader-text" data-tajcmd-price-live aria-live="polite" aria-atomic="true"></p>
          </div>

          <div class="tajcmd-reassurance" aria-label="Garanties de commande">
            <span class="tajcmd-reassurance__item"><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.5 19 7v5c0 4.5-2.8 7.4-7 8.5-4.2-1.1-7-4-7-8.5V7l7-3.5Z"/><path d="m9.5 12 1.7 1.7 3.6-4"/></svg></span><span class="tajcmd-reassurance__label">Paiement &agrave; la livraison</span></span>
            <span class="tajcmd-reassurance__item"><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 5h10a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3h-3l-4 3v-3H7a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3Z"/><path d="M8 10h8"/><path d="M8 13h5"/></svg></span><span class="tajcmd-reassurance__label">Confirmation t&eacute;l&eacute;phonique</span></span>
            <span class="tajcmd-reassurance__item"><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 6h11v10H3z"/><path d="M14 9h4l3 4v3h-7z"/><path d="M6.5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M17.5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg></span><span class="tajcmd-reassurance__label">Marrakech 0 MAD · autres villes 20 MAD</span></span>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
HTML;

    $order = str_replace(
        ['%%TAJINOOS_ORDER_ACTION%%', '%%TAJINOOS_ORDER_NONCE%%', '%%TAJINOOS_ORDER_SOURCE%%', '%%TAJINOOS_UNIT_PRICE%%', '%%TAJINOOS_LANGUAGE%%'],
        [$form_action, $nonce_field, $source_url, $unit_price, $language],
        $order
    );

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])commande\1[^>]*>.*?</section>~s',
        $order,
        $content,
        1
    ) ?: $content;
}

/**
 * Add section-level motion hooks to landing sections retained from Elementor.
 *
 * Rebuilt theme sections declare their logical groups directly in their
 * semantic markup. Heritage and process remain stored in the page content, so
 * the final content pass gives each section one no-JS-safe reveal hook.
 */
function tajinoos_child_add_landing_motion_attributes(string $content): string
{
    if (!tajinoos_child_should_filter_landing_content()) {
        return $content;
    }

    foreach (['heritage', 'artisanat'] as $section_id) {
        $pattern = sprintf(
            '~<([a-z][a-z0-9]*)\b(?=[^>]*\bid=(["\'])%s\2)[^>]*>~i',
            preg_quote($section_id, '~')
        );

        $content = preg_replace_callback(
            $pattern,
            static function (array $match): string {
                if (strpos($match[0], 'data-motion=') !== false) {
                    return $match[0];
                }

                return substr($match[0], 0, -1) . ' data-motion="fade-up">';
            },
            $content,
            1
        ) ?: $content;
    }

    return $content;
}

/**
 * Render permanent floating actions with the document instead of injecting
 * them after paint in JavaScript.
 */
function tajinoos_child_render_floating_actions(): void
{
    if (!is_page(13) || isset($_GET['elementor-preview'])) {
        return;
    }
    ?>
    <a class="taj-order-float" href="#commande" title="<?php echo esc_attr(tajinoos_translate('floating.order_title')); ?>" aria-label="<?php echo esc_attr(tajinoos_translate('floating.order_aria')); ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
    </a>
    <a class="taj-whatsapp-float" href="<?php echo esc_url('https://wa.me/212627424509?text=' . rawurlencode(tajinoos_translate('floating.whatsapp_message'))); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(tajinoos_translate('floating.whatsapp_aria')); ?>">
      <span class="taj-whatsapp-float__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.49 0 .15 5.34.15 11.91c0 2.1.55 4.15 1.6 5.96L0 24l6.3-1.65a11.85 11.85 0 0 0 5.76 1.47h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.46-8.43Zm-8.45 18.33h-.01a9.9 9.9 0 0 1-5.05-1.39l-.36-.21-3.74.98 1-3.65-.24-.37a9.88 9.88 0 0 1-1.51-5.26c0-5.45 4.44-9.89 9.91-9.89 2.64 0 5.12 1.03 6.98 2.9a9.8 9.8 0 0 1 2.9 6.98c0 5.46-4.44 9.91-9.88 9.91Zm5.43-7.42c-.3-.15-1.76-.87-2.04-.97-.27-.1-.47-.15-.66.15-.2.3-.76.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.27-.47-2.42-1.49a9.13 9.13 0 0 1-1.68-2.08c-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.66-.5h-.56c-.2 0-.51.07-.78.37-.27.3-1.03 1.01-1.03 2.46s1.05 2.85 1.2 3.05c.15.2 2.05 3.13 4.97 4.39.7.3 1.24.47 1.66.6.7.22 1.34.19 1.84.12.56-.08 1.76-.72 2-1.41.25-.7.25-1.3.18-1.42-.08-.12-.28-.2-.58-.35Z"/></svg>
      </span>
      <span class="taj-whatsapp-float__label">WhatsApp</span>
    </a>
    <?php
}

function tajinoos_child_print_final_hero_faq_css(): void
{
    if (!is_page(13)) {
        return;
    }
    ?>
    <style id="tajinoos-final-hero-faq-css">
      body.page-id-13 .taj-final-faq, body.page-id-13 .taj-final-faq * { box-sizing: border-box; }
      body.page-id-13 .taj-final-faq {
        padding: clamp(64px, 6vw, 90px) 0 clamp(70px, 7vw, 108px);
        background: linear-gradient(180deg, #fff8ef, #f7e8d6);
      }
      body.page-id-13 .taj-final-faq__inner { width: min(1180px, calc(100% - 48px)); margin: 0 auto; }
      body.page-id-13 .taj-final-faq__head { text-align: center; margin-bottom: 34px; }
      body.page-id-13 .taj-final-faq__head .taj-final-eyebrow { justify-content: center; margin-bottom: 14px; font-size: 11px; color: #9e4226; }
      body.page-id-13 .taj-final-faq__head h2 {
        margin: 0; color: #3a2117; font-family: "Cormorant Garamond", Georgia, serif;
        font-size: clamp(32px, 3.5vw, 46px); line-height: 1.08;
      }
      body.page-id-13 .taj-final-faq__layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 34px; align-items: start; }
      body.page-id-13 .taj-final-faq__list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 28px; }
      body.page-id-13 .taj-final-faq details {
        overflow: hidden; border: 1px solid rgba(139,82,47,.11); border-radius: 14px;
        background: rgba(255,248,238,.84); box-shadow: 0 12px 28px rgba(70,39,23,.07);
      }
      body.page-id-13 .taj-final-faq summary {
        min-height: 58px; display: flex; align-items: center; justify-content: space-between; gap: 16px;
        padding: 18px 20px; cursor: pointer; list-style: none; color: #2b1a12; font: 820 13px/1.35 Manrope, sans-serif;
      }
      body.page-id-13 .taj-final-faq summary::-webkit-details-marker { display: none; }
      body.page-id-13 .taj-final-faq summary::after {
        content: "+"; color: #a74728; font-size: 24px; font-weight: 400; transition: transform .25s ease;
      }
      body.page-id-13 .taj-final-faq details[open] summary::after { transform: rotate(45deg); }
      body.page-id-13 .taj-final-faq details p { margin: 0; padding: 0 20px 18px; color: #634a3d; font: 500 13px/1.65 Manrope, sans-serif; }
      body.page-id-13 .taj-final-support {
        padding: 28px 22px; border: 1px solid rgba(139,82,47,.13); border-radius: 18px;
        background: rgba(255,249,241,.82); box-shadow: 0 18px 42px rgba(70,39,23,.09), inset 0 1px rgba(255,255,255,.76);
        text-align: center;
      }
      body.page-id-13 .taj-final-support__icon { width: 58px; height: 58px; margin: 0 auto 15px; display: grid; place-items: center; color: #2b1a12; }
      body.page-id-13 .taj-final-support__icon svg { width: 44px; height: 44px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
      body.page-id-13 .taj-final-support h3 { margin: 0 0 7px; color: #2d1a12; font: 850 19px/1.25 "Cormorant Garamond", Georgia, serif; }
      body.page-id-13 .taj-final-support strong { display: block; color: #2d1a12; font: 850 15px/1.4 Manrope, sans-serif; }
      body.page-id-13 .taj-final-support p { margin: 12px 0 18px; color: #674f42; font: 500 12px/1.5 Manrope, sans-serif; }
      body.page-id-13 .taj-final-support a {
        min-height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 999px;
        font: 850 12px/1 Manrope, sans-serif; text-decoration: none !important;
      }
      body.page-id-13 .taj-final-support__wa { background: linear-gradient(135deg, #c75b34, #963720); color: #fff8ef !important; box-shadow: 0 14px 28px rgba(126,47,25,.20); }
      body.page-id-13 .taj-final-support__email { margin-top: 10px; border: 1px solid rgba(151,69,36,.34); color: #7f321f !important; background: rgba(255,252,247,.76); }
      @media (max-width: 1100px) {
        body.page-id-13 .taj-final-faq__layout { grid-template-columns: 1fr; }
      }
      @media (max-width: 760px) {
        body.page-id-13 .taj-final-faq__inner { width: min(100% - 28px, 560px); }
        body.page-id-13 .taj-final-faq__list { grid-template-columns: 1fr; }
      }
    </style>
    <?php
}
