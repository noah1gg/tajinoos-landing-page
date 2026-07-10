<?php
/**
 * Tajinoos child theme assets.
 *
 * Keeps landing-page polish outside WordPress core, Astra, and Elementor.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TAJINOOS_CHILD_VERSION', '1.1.28');

add_action('wp_enqueue_scripts', 'tajinoos_child_enqueue_assets', 20);
add_action('init', 'tajinoos_child_ensure_thank_you_page');
add_action('admin_post_nopriv_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_action('admin_post_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_shortcode('tajinoos_thank_you', 'tajinoos_child_render_thank_you_shortcode');
add_filter('wp_list_pages_excludes', 'tajinoos_child_exclude_thank_you_from_page_list');
add_filter('body_class', 'tajinoos_child_thank_you_body_class');
add_filter('style_loader_src', 'tajinoos_child_page_13_relative_premium_css', 10, 2);
add_filter('script_loader_src', 'tajinoos_child_page_13_relative_premium_js', 10, 2);
add_filter('the_content', 'tajinoos_child_render_testimonials', 21);
add_filter('the_content', 'tajinoos_child_render_order_section', 22);
add_filter('the_content', 'tajinoos_child_render_reference_product_section', 23);
add_filter('the_content', 'tajinoos_child_render_editorial_benefits_section', 24);
add_filter('the_content', 'tajinoos_child_render_reference_faq_section', 25);
add_filter('the_content', 'tajinoos_child_render_final_hero_faq_sections', 99);
add_filter('the_content', 'tajinoos_child_render_reference_match_hero', 100);
add_filter('the_content', 'tajinoos_child_render_approved_order_section', 110);
add_filter('the_content', 'tajinoos_child_render_command_rebuild_section', 120);
add_action('wp_head', 'tajinoos_child_print_final_hero_faq_css', 100);

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
        TAJINOOS_CHILD_VERSION
    );

    wp_enqueue_script(
        'tajinoos-premium',
        get_stylesheet_directory_uri() . '/assets/js/tajinoos-premium.js',
        [],
        TAJINOOS_CHILD_VERSION,
        true
    );
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

function tajinoos_child_render_thank_you_shortcode(): string
{
    $home_url = esc_url(home_url('/'));

    return <<<HTML
<section class="taj-thanks" aria-labelledby="taj-thanks-title">
  <div class="taj-thanks__inner">
    <article class="taj-thanks__card">
      <span class="taj-thanks__badge">COMMANDE RE&Ccedil;UE</span>
      <h1 id="taj-thanks-title">Merci pour votre commande</h1>
      <p class="taj-thanks__lead">Votre commande Tajinoos a bien &eacute;t&eacute; enregistr&eacute;e.</p>
      <p class="taj-thanks__text">Notre &eacute;quipe vous contactera par WhatsApp dans moins de 24h afin de confirmer les d&eacute;tails de livraison.</p>

      <ul class="taj-thanks__points" aria-label="Garanties de commande">
        <li><span aria-hidden="true">✓</span> Paiement &agrave; la livraison</li>
        <li><span aria-hidden="true">✓</span> Confirmation par WhatsApp</li>
        <li><span aria-hidden="true">✓</span> Livraison partout au Maroc</li>
      </ul>

      <a class="taj-thanks__cta" href="{$home_url}">Retour &agrave; l&rsquo;accueil</a>
      <p class="taj-thanks__note">Veuillez garder votre t&eacute;l&eacute;phone disponible pour la confirmation.</p>
    </article>
  </div>
</section>
HTML;
}

function tajinoos_child_handle_order_submit(): void
{
    $nonce = isset($_POST['_tajinoos_order_nonce']) ? sanitize_text_field(wp_unslash($_POST['_tajinoos_order_nonce'])) : '';

    if (!wp_verify_nonce($nonce, 'tajinoos_order_submit')) {
        tajinoos_child_redirect_order_error();
    }

    $name = tajinoos_child_sanitize_order_text('Nom');
    $phone = tajinoos_child_sanitize_order_text('Telephone');
    $address = tajinoos_child_sanitize_order_text('Adresse');
    $product = tajinoos_child_sanitize_order_text('Produit');
    $message = isset($_POST['Message']) ? sanitize_textarea_field(wp_unslash($_POST['Message'])) : '';
    $source = isset($_POST['Source']) ? esc_url_raw(wp_unslash($_POST['Source'])) : '';
    $quantity = isset($_POST['Quantite']) ? absint(wp_unslash($_POST['Quantite'])) : 0;

    if ($name === '' || $phone === '' || $address === '' || $quantity < 1 || $quantity > 5) {
        tajinoos_child_redirect_order_error();
    }

    $product = $product !== '' ? $product : 'Tajine artisanal Tajinoos Premium';
    $unit_price = 390;
    $total = $quantity * $unit_price;
    $submitted_at = current_time('mysql');
    $referer = wp_get_referer();

    $body = [
        'Nouvelle commande Tajinoos',
        '',
        'Nom: ' . $name,
        'Téléphone: ' . $phone,
        'Adresse: ' . $address,
        'Produit: ' . $product,
        'Quantité: ' . $quantity,
        'Prix unitaire: ' . $unit_price . ' MAD',
        'Total recalculé: ' . $total . ' MAD',
        'Message: ' . ($message !== '' ? $message : '-'),
        '',
        'Source: ' . ($source !== '' ? $source : ($referer ?: '-')),
        'Date: ' . $submitted_at,
    ];

    $sent = wp_mail(
        'elhichemn@gmail.com',
        'Nouvelle commande Tajinoos',
        implode("\n", $body),
        ['Content-Type: text/plain; charset=UTF-8']
    );

    if (!$sent) {
        error_log('Tajinoos order mail failed for phone ' . $phone . ' at ' . $submitted_at);
    }

    wp_safe_redirect('/merci/');
    exit;
}

function tajinoos_child_sanitize_order_text(string $key): string
{
    return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
}

function tajinoos_child_redirect_order_error(): void
{
    wp_safe_redirect('/?tajinoos_order=error#commande');
    exit;
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
    if (is_page('merci')) {
        $classes[] = 'tajinoos-thank-you-page';
    }

    return $classes;
}

/**
 * Replace only the landing page testimonials section while keeping its anchor.
 */
function tajinoos_child_render_testimonials(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query() || strpos($content, 'id="avis"') === false) {
        return $content;
    }

    $testimonials = <<<'HTML'
<section id="avis" class="tajx-section tajx-testimonials" aria-labelledby="tajx-testimonials-title">
  <div class="tajx-wrap tajx-testimonials-inner">
    <div class="tajx-testimonials-copy">
      <div class="tajx-testimonials-labels">
        <span class="tajx-eyebrow">AVIS CLIENTS</span>
        <span class="tajx-testimonials-craft-badge"><span aria-hidden="true">✓</span> FAIT MAIN AU MAROC</span>
      </div>
      <h2 id="tajx-testimonials-title">ILS ONT CHOISI LE TAJINE TAJINOOS</h2>
      <p class="tajx-testimonials-lede">Des clients partout au Maroc découvrent une cuisson plus authentique, une table plus élégante et un tajine fait main qui dure dans le temps.</p>

      <div class="tajx-testimonials-stats" aria-label="Chiffres clés">
        <div class="tajx-testimonials-stat tajx-testimonials-rating">
          <span class="tajx-review-stars" aria-label="5 étoiles sur 5">★★★★★</span>
          <strong>4.9/5 <small>basé sur 120 avis</small></strong>
        </div>
        <div class="tajx-testimonials-stat">
          <strong>120+</strong>
          <span>Clients satisfaits</span>
        </div>
        <div class="tajx-testimonials-stat">
          <strong>Paiement</strong>
          <span>à la livraison</span>
        </div>
      </div>

      <a class="tajx-btn primary tajx-testimonials-cta" href="#commande">Commander mon Tajine — 390 MAD</a>
      <p class="tajx-testimonials-reassurance">Livraison partout au Maroc <span>·</span> Paiement à la livraison <span>·</span> Garantie 7 jours</p>
    </div>

    <div class="tajx-testimonials-showcase">
      <div class="tajx-reviews-marquee" aria-label="Avis de nos clients">
        <div class="tajx-reviews-track">
        <article class="tajx-review-card tajx-review-featured">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">SB</div>
            <div class="tajx-review-person"><h3>Salma B.</h3><span>Marrakech</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars" aria-label="5 étoiles sur 5">★★★★★</div>
          <p>Le tajine est magnifique, lourd, bien fini et il donne tout de suite une présence premium sur la table.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">YA</div>
            <div class="tajx-review-person"><h3>Yassine A.</h3><span>Casablanca</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars" aria-label="5 étoiles sur 5">★★★★★</div>
          <p>Livraison rapide, confirmation par téléphone, et le produit est encore plus beau en vrai.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">NL</div>
            <div class="tajx-review-person"><h3>Nadia L.</h3><span>Marrakech</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars" aria-label="5 étoiles sur 5">★★★★★</div>
          <p>Je l’ai offert pour une nouvelle maison. La personne l’a gardé exposé dans la cuisine avant même de l’utiliser.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">HA</div>
            <div class="tajx-review-person"><h3>Hicham A.</h3><span>Rabat</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars" aria-label="5 étoiles sur 5">★★★★★</div>
          <p>J’avais peur qu’un tajine décoratif soit fragile. La pièce est stable, lourde et bien finie.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">IR</div>
            <div class="tajx-review-person"><h3>Imane R.</h3><span>Fès</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars" aria-label="5 étoiles sur 5">★★★★★</div>
          <p>La cuisson lente donne vraiment un goût différent. On sent la différence avec un tajine industriel.</p>
        </article>

        <article class="tajx-review-card">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">MT</div>
            <div class="tajx-review-person"><h3>Mehdi T.</h3><span>Agadir</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars" aria-label="5 étoiles sur 5">★★★★★</div>
          <p>Très bon rapport qualité/prix. Le paiement à la livraison m’a rassuré avant de commander.</p>
        </article>

        <article class="tajx-review-card tajx-review-featured" aria-hidden="true">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">SB</div>
            <div class="tajx-review-person"><h3>Salma B.</h3><span>Marrakech</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars">★★★★★</div>
          <p>Le tajine est magnifique, lourd, bien fini et il donne tout de suite une présence premium sur la table.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">YA</div>
            <div class="tajx-review-person"><h3>Yassine A.</h3><span>Casablanca</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars">★★★★★</div>
          <p>Livraison rapide, confirmation par téléphone, et le produit est encore plus beau en vrai.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">NL</div>
            <div class="tajx-review-person"><h3>Nadia L.</h3><span>Marrakech</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars">★★★★★</div>
          <p>Je l’ai offert pour une nouvelle maison. La personne l’a gardé exposé dans la cuisine avant même de l’utiliser.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">HA</div>
            <div class="tajx-review-person"><h3>Hicham A.</h3><span>Rabat</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars">★★★★★</div>
          <p>J’avais peur qu’un tajine décoratif soit fragile. La pièce est stable, lourde et bien finie.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">IR</div>
            <div class="tajx-review-person"><h3>Imane R.</h3><span>Fès</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars">★★★★★</div>
          <p>La cuisson lente donne vraiment un goût différent. On sent la différence avec un tajine industriel.</p>
        </article>

        <article class="tajx-review-card" aria-hidden="true">
          <header class="tajx-review-card-header">
            <!-- Replace with WordPress Media Library image -->
            <div class="tajx-review-avatar" aria-hidden="true">MT</div>
            <div class="tajx-review-person"><h3>Mehdi T.</h3><span>Agadir</span></div>
            <span class="tajx-review-badge"><span aria-hidden="true">✓</span> Acheteur vérifié</span>
          </header>
          <div class="tajx-review-stars">★★★★★</div>
          <p>Très bon rapport qualité/prix. Le paiement à la livraison m’a rassuré avant de commander.</p>
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
 * Render the premium two-card checkout without changing surrounding sections.
 */
function tajinoos_child_render_order_section(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query() || strpos($content, 'id="commande"') === false) {
        return $content;
    }

    $order = <<<'HTML'
<section id="commande" class="tajx-section tajx-order" aria-labelledby="tajx-order-title">
  <div class="tajx-wrap tajx-order-inner">
    <header class="tajx-order-header">
      <span class="tajx-order-eyebrow">COMMANDER</span>
      <h2 id="tajx-order-title">Finaliser votre commande</h2>
      <p>L’excellence de l’artisanat marocain à votre table.</p>
    </header>

    <div class="tajx-order-grid">
      <article class="tajx-offer-card">
        <div class="tajx-offer-badges">
          <span>SÉRIE LIMITÉE</span>
          <span>FAIT MAIN AU MAROC</span>
        </div>

        <figure class="tajx-offer-image">
          <!-- Replace with WordPress Media Library image -->
          <img src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" alt="Tajine artisanal Tajinoos Premium" width="1024" height="1024" loading="lazy" decoding="async">
        </figure>

        <div class="tajx-offer-content">
          <p class="tajx-offer-kicker">Réservez votre Tajinoos</p>
          <h3 class="tajx-offer-title"><span>OFFRE SPÉCIALE:</span> Tajine Artisanal Tajinoos Premium</h3>
          <div class="tajx-offer-price"><strong>390 MAD</strong><del>520 MAD</del></div>

          <div class="tajx-stock-alert"><span aria-hidden="true">🔥</span> Plus que 12 pièces disponibles</div>

          <ul class="tajx-offer-trust" aria-label="Garanties de la commande">
            <li>Paiement à la livraison</li>
            <li>Livraison partout au Maroc</li>
            <li>Confirmation téléphonique</li>
            <li>Garantie 7 jours</li>
          </ul>
        </div>
      </article>

      <form class="tajx-form-card tajx-order-form" action="mailto:orders@tajinoos.com" enctype="text/plain" method="post">
        <div class="tajx-form-strip" aria-label="Garanties de paiement et livraison">
          <span>PAIEMENT À LA LIVRAISON</span>
          <span>LIVRAISON AU MAROC</span>
          <span>GARANTIE 7 JOURS</span>
        </div>

        <div class="tajx-order-form-body">
          <header class="tajx-form-heading">
            <h3>Réservez votre Tajinoos</h3>
            <p>Remplissez vos informations. Notre équipe vous contacte pour confirmer la disponibilité et la livraison.</p>
          </header>

          <div class="tajx-form-row">
            <label class="tajx-field">Nom complet
              <input name="Nom" required type="text" placeholder="Ex: Ahmed Alaoui" autocomplete="name">
            </label>
            <label class="tajx-field">Téléphone (WhatsApp)
              <input name="Telephone" required type="tel" placeholder="06 XX XX XX XX" autocomplete="tel">
            </label>
          </div>

          <label class="tajx-field tajx-field--full">Ville &amp; Adresse de livraison
            <input name="Adresse" required type="text" placeholder="Votre ville et adresse complète..." autocomplete="street-address">
          </label>

          <div class="tajx-form-row">
            <label class="tajx-field">Modèle
              <select name="Produit">
                <option>Tajine artisanal Tajinoos Premium</option>
              </select>
            </label>
            <label class="tajx-field">Quantité
              <select name="Quantite">
                <option>1 pièce</option>
                <option>2 pièces</option>
                <option>3 pièces</option>
                <option>4 pièces</option>
                <option>5 pièces</option>
              </select>
            </label>
          </div>

          <label class="tajx-field tajx-field--full">Message (Optionnel)
            <textarea name="Message" rows="3" placeholder="Précisez un horaire de rappel..."></textarea>
          </label>

          <button class="tajx-submit" type="submit">COMMANDER MON TAJINE — 390 MAD <span aria-hidden="true">→</span></button>
          <p class="tajx-form-reassurance"><span aria-hidden="true">🔒</span> Aucun paiement en ligne. Confirmation humaine avant l’expédition.</p>
        </div>
      </form>
    </div>
  </div>
</section>
HTML;

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])commande\1[^>]*>.*?</section>~s',
        $order,
        $content,
        1
    ) ?: $content;
}

/**
 * Rebuild only the product offer section with the approved premium layout.
 */
function tajinoos_child_render_reference_product_section(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query() || strpos($content, 'id="produit"') === false) {
        return $content;
    }

    $product = <<<'HTML'
<section id="produit" class="tajx-section tajx-offer" aria-labelledby="tajx-offer-title">
  <div class="tajx-wrap tajx-offer__grid">
    <figure class="tajx-offer__visual" aria-label="Tajine artisanal Tajinoos Premium">
      <img class="tajx-offer__product" src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" alt="Tajine artisanal Tajinoos Premium" width="1024" height="1024" loading="lazy" decoding="async">

      <div class="tajx-offer__trust" aria-label="Garanties principales">
        <div class="tajx-offer__trust-item">
          <strong>Paiement</strong>
          <span>à la livraison</span>
        </div>
        <div class="tajx-offer__trust-item">
          <strong>Confirmation</strong>
          <span>téléphonique</span>
        </div>
        <div class="tajx-offer__trust-item">
          <strong>Garantie</strong>
          <span>7 jours</span>
        </div>
      </div>
    </figure>

    <div class="tajx-offer__content">
      <span class="tajx-offer__eyebrow">OFFRE SIGNATURE</span>
      <h2 id="tajx-offer-title" class="tajx-offer__title"><span>TAJINE ARTISANAL</span><em>TAJINOOS PREMIUM</em></h2>
      <p class="tajx-offer__subtitle">Un tajine en terre cuite façonné à la main pour une cuisson lente, une table élégante et des repas qui rassemblent.</p>

      <div class="tajx-offer__price" aria-label="Prix de l'offre">
        <div class="tajx-offer__price-main">
          <strong>390 MAD</strong>
          <del>520 MAD</del>
        </div>
        <div class="tajx-offer__price-meta">
          <span>Offre limitée</span>
          <em>Stock disponible cette semaine</em>
        </div>
      </div>

      <div class="tajx-offer__features" aria-label="Avantages du produit">
        <article class="tajx-offer__feature">
          <span class="tajx-offer__feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 3a7 7 0 0 0-7 7v4.5l-2 2V19h18v-2.5l-2-2V10a7 7 0 0 0-7-7Z"/><path d="M9 18V12a3 3 0 0 1 6 0v6"/></svg>
          </span>
          <div class="tajx-offer__feature-copy">
            <h3>Fait main</h3>
            <p>Chaque pièce est unique, réalisée avec soin par nos artisans.</p>
          </div>
          <span class="tajx-offer__feature-chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
          </span>
        </article>

        <article class="tajx-offer__feature">
          <span class="tajx-offer__feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M7 12c0-3 2.5-5 5-5s5 2 5 5-2.5 5-5 5-5-2-5-5Z"/><path d="M12 2v4M12 18v4M4.2 4.2 7 7M17 17l2.8 2.8M2 12h4M18 12h4"/></svg>
          </span>
          <div class="tajx-offer__feature-copy">
            <h3>Cuisson lente</h3>
            <p>Le couvercle conique aide à préserver les saveurs.</p>
          </div>
          <span class="tajx-offer__feature-chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
          </span>
        </article>

        <article class="tajx-offer__feature">
          <span class="tajx-offer__feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4 7h16M6 7v10h12V7"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>
          </span>
          <div class="tajx-offer__feature-copy">
            <h3>Format familial</h3>
            <p>Idéal pour les repas du quotidien et du week-end.</p>
          </div>
          <span class="tajx-offer__feature-chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
          </span>
        </article>

        <article class="tajx-offer__feature">
          <span class="tajx-offer__feature-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M4 7h16v10H4z"/><path d="m4 9 8 5 8-5"/></svg>
          </span>
          <div class="tajx-offer__feature-copy">
            <h3>Livraison au Maroc</h3>
            <p>Expédition suivie sous 2 à 4 jours.</p>
          </div>
          <span class="tajx-offer__feature-chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
          </span>
        </article>
      </div>

      <a class="tajx-btn primary tajx-offer__cta" href="#commande">
        <span class="tajx-offer__cta-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M6 8h12l-1 10H7L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg>
        </span>
        <span>RÉSERVER MON TAJINOOS</span>
        <span class="tajx-offer__cta-arrow" aria-hidden="true">→</span>
      </a>

      <p class="tajx-offer__reassurance">Satisfait ou remboursé • Assistance rapide</p>
    </div>
  </div>
</section>
HTML;

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])produit\1[^>]*>.*?</section>~s',
        $product,
        $content,
        1
    ) ?: $content;
}

function tajinoos_child_render_editorial_benefits_section(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query() || strpos($content, 'id="benefices"') === false) {
        return $content;
    }

    $benefits = <<<'HTML'
<section id="benefices" class="tajx-section tajx-benefits taj-benefits-orange" aria-labelledby="taj-benefits-orange-title">
  <div class="tajx-wrap taj-benefits-orange__inner">
    <div class="taj-benefits-orange__left">
      <div class="taj-benefits-orange__eyebrow" aria-label="POURQUOI TAJINOOS">
        POURQUOI TAJINOOS
      </div>
      <h2 id="taj-benefits-orange-title" class="taj-benefits-orange__title">PLUS QU&rsquo;UN TAJINE :<br>UNE EXP&Eacute;RIENCE<br>DE TABLE.</h2>
      <p class="taj-benefits-orange__intro">Chez Tajinoos, chaque pi&egrave;ce raconte un savoir-faire ancestral et transforme la cuisine du quotidien en un rituel de partage et d&rsquo;authenticit&eacute;.</p>
      <blockquote class="taj-benefits-orange__quote">
        <span class="taj-benefits-orange__quote-mark" aria-hidden="true">&ldquo;</span>
        <p>Chaque repas devient un moment de partage.</p>
      </blockquote>
      <div class="taj-benefits-orange__divider" aria-hidden="true">
        <span class="taj-benefits-orange__divider-line"></span>
        <span class="taj-benefits-orange__divider-diamond">&#9670;</span>
        <span class="taj-benefits-orange__divider-line"></span>
      </div>
      <div class="taj-benefits-orange__trust" aria-label="Les preuves Tajinoos">
        <div class="taj-benefits-orange__trust-item">
          <span class="taj-benefits-orange__trust-dot" aria-hidden="true"></span>
          <strong>Fait main</strong>
          <span>Par des artisans passionn&eacute;s</span>
        </div>
        <div class="taj-benefits-orange__trust-sep" aria-hidden="true"></div>
        <div class="taj-benefits-orange__trust-item">
          <span class="taj-benefits-orange__trust-dot" aria-hidden="true"></span>
          <strong>Terre cuite naturelle</strong>
          <span>100% naturelle et saine</span>
        </div>
        <div class="taj-benefits-orange__trust-sep" aria-hidden="true"></div>
        <div class="taj-benefits-orange__trust-item">
          <span class="taj-benefits-orange__trust-dot" aria-hidden="true"></span>
          <strong>Livraison partout au Maroc</strong>
          <span>Emballage soign&eacute;, livraison s&eacute;curis&eacute;e</span>
        </div>
      </div>
    </div>
    <div class="taj-benefits-orange__right">
      <div class="taj-benefits-orange__panel">
        <article class="taj-benefits-orange__card">
          <span class="taj-benefits-orange__num" aria-hidden="true">01</span>
          <span class="taj-benefits-orange__card-divider" aria-hidden="true"></span>
          <div class="taj-benefits-orange__card-content">
            <span class="taj-benefits-orange__badge">NOTRE SIGNATURE</span>
            <h3 class="taj-benefits-orange__card-title">Cuisson lente ma&icirc;tris&eacute;e</h3>
            <p class="taj-benefits-orange__card-text">La terre cuite diffuse une chaleur douce qui attendrit les aliments et concentre naturellement les ar&ocirc;mes.</p>
          </div>
        </article>
        <article class="taj-benefits-orange__card">
          <span class="taj-benefits-orange__num" aria-hidden="true">02</span>
          <span class="taj-benefits-orange__card-divider" aria-hidden="true"></span>
          <div class="taj-benefits-orange__card-content">
            <span class="taj-benefits-orange__badge">NOTRE SIGNATURE</span>
            <h3 class="taj-benefits-orange__card-title">Design artisanal unique</h3>
            <p class="taj-benefits-orange__card-text">Une pr&eacute;sence &eacute;l&eacute;gante qui transforme le tajine en pi&egrave;ce de service et en objet d&eacute;coratif.</p>
          </div>
        </article>
        <article class="taj-benefits-orange__card">
          <span class="taj-benefits-orange__num" aria-hidden="true">03</span>
          <span class="taj-benefits-orange__card-divider" aria-hidden="true"></span>
          <div class="taj-benefits-orange__card-content">
            <span class="taj-benefits-orange__badge">NOTRE SIGNATURE</span>
            <h3 class="taj-benefits-orange__card-title">Valeur de cadeau</h3>
            <p class="taj-benefits-orange__card-text">Un cadeau chaleureux, culturel et premium pour une maison, un mariage ou un amateur de cuisine.</p>
          </div>
        </article>
        <article class="taj-benefits-orange__card">
          <span class="taj-benefits-orange__num" aria-hidden="true">04</span>
          <span class="taj-benefits-orange__card-divider" aria-hidden="true"></span>
          <div class="taj-benefits-orange__card-content">
            <span class="taj-benefits-orange__badge">NOTRE SIGNATURE</span>
            <h3 class="taj-benefits-orange__card-title">Achat rassurant</h3>
            <p class="taj-benefits-orange__card-text">Commande confirm&eacute;e, livraison suivie et paiement &agrave; la r&eacute;ception pour acheter avec tranquillit&eacute;.</p>
          </div>
        </article>
      </div>
    </div>
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

/**
 * Redesign only the landing FAQ section and keep the existing section anchor.
 */
function tajinoos_child_render_reference_faq_section(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query() || strpos($content, 'id="faq"') === false) {
        return $content;
    }

    $faq = <<<'HTML'
<section id="faq" class="tajx-section tajx-faq tajx-faq-reference" aria-labelledby="tajx-faq-title">
  <div class="tajx-wrap tajx-faq-shell">
    <header class="tajx-section-head tajx-faq-head">
      <span class="tajx-eyebrow">Questions fréquentes</span>
      <h2 id="tajx-faq-title">Les réponses avant de commander.</h2>
    </header>

    <div class="tajx-faq-list">
      <details>
        <summary>Comment entretenir mon tajine avant la première utilisation ?</summary>
        <p>Rincez-le doucement, laissez-le sécher complètement, puis huilez légèrement l'intérieur avant la première cuisson.</p>
      </details>

      <details>
        <summary>Combien de temps prend la livraison ?</summary>
        <p>La livraison est généralement effectuée sous 48H après confirmation de votre commande par téléphone.</p>
      </details>

      <details>
        <summary>Le tajine peut-il aller au lave-vaisselle ?</summary>
        <p>Nous recommandons un lavage à la main avec une éponge douce afin de préserver la terre cuite et la finition artisanale.</p>
      </details>

      <details>
        <summary>Puis-je retourner le produit s'il ne me convient pas ?</summary>
        <p>Oui, vous bénéficiez d'une garantie de 7 jours. Notre équipe vous accompagne si le produit ne correspond pas à vos attentes.</p>
      </details>

      <details>
        <summary>Quels types de feux sont compatibles ?</summary>
        <p>Le tajine peut être utilisé sur gaz, au four et au charbon avec une chauffe progressive et adaptée à la terre cuite.</p>
      </details>

      <details>
        <summary>Le produit est-il garanti ?</summary>
        <p>Oui, chaque pièce est vérifiée avant expédition et couverte par notre garantie satisfaction de 7 jours.</p>
      </details>
    </div>

    <aside class="tajx-faq-support" aria-label="Support Tajinoos">
      <span class="tajx-faq-support__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M3 11a9 9 0 0 1 18 0"/><path d="M21 12v4a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M3 12v4a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg></span>
      <div class="tajx-faq-support__copy">
        <h3>Besoin d'aide ? Notre équipe est là pour vous.</h3>
        <p>Service client réactif 7j/7.</p>
      </div>
      <div class="tajx-faq-support__actions">
        <a class="tajx-faq-support__button tajx-faq-support__button--primary" href="https://wa.me/?text=Bonjour%2C%20je%20souhaite%20commander%20un%20Tajinoos." target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a class="tajx-faq-support__button" href="mailto:orders@tajinoos.com">Email</a>
      </div>
    </aside>
  </div>
</section>
HTML;

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])faq\1[^>]*>.*?</section>~s',
        $faq,
        $content,
        1
    ) ?: $content;
}

function tajinoos_child_render_final_hero_faq_sections(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query()) {
        return $content;
    }


    $faq = <<<'HTML'
<section id="faq" class="taj-final-faq" aria-labelledby="taj-final-faq-title">
  <div class="taj-final-faq__inner">
    <header class="taj-final-faq__head">
      <span class="taj-final-eyebrow">Questions fréquentes</span>
      <h2 id="taj-final-faq-title">Les réponses avant de commander.</h2>
    </header>

    <div class="taj-final-faq__layout">
      <div class="taj-final-faq__list">
        <details><summary>Comment entretenir mon tajine avant la première utilisation ?</summary><p>Rincez-le doucement, laissez-le sécher complètement, puis huilez légèrement l'intérieur avant la première cuisson.</p></details>
        <details><summary>Combien de temps prend la livraison ?</summary><p>La livraison est généralement effectuée sous 48H après confirmation de votre commande par téléphone.</p></details>
        <details><summary>Le tajine peut-il aller au lave-vaisselle ?</summary><p>Nous recommandons un lavage à la main avec une éponge douce afin de préserver la terre cuite et la finition artisanale.</p></details>
        <details><summary>Puis-je retourner le produit s'il ne me convient pas ?</summary><p>Oui, vous bénéficiez d'une garantie de 7 jours. Notre équipe vous accompagne si le produit ne correspond pas à vos attentes.</p></details>
        <details><summary>Quels types de feux sont compatibles ?</summary><p>Le tajine peut être utilisé sur gaz, au four et au charbon avec une chauffe progressive et adaptée à la terre cuite.</p></details>
        <details><summary>Le produit est-il garanti ?</summary><p>Oui, chaque pièce est vérifiée avant expédition et couverte par notre garantie satisfaction de 7 jours.</p></details>
      </div>

      <aside class="taj-final-support" aria-label="Support Tajinoos">
        <span class="taj-final-support__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11a9 9 0 0 1 18 0"/><path d="M21 12v4a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M3 12v4a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/></svg></span>
        <h3>Besoin d'aide ?</h3>
        <strong>Notre équipe est là pour vous.</strong>
        <p>Service client réactif 7j/7.</p>
        <a class="taj-final-support__wa" href="https://wa.me/?text=Bonjour%2C%20je%20souhaite%20commander%20un%20Tajinoos." target="_blank" rel="noopener noreferrer">WhatsApp</a>
        <a class="taj-final-support__email" href="mailto:orders@tajinoos.com">Envoyer un email</a>
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
    if (!is_page(13) || !in_the_loop() || !is_main_query()) {
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
          <strong>390 <span class="taj-clean-hero__currency">MAD</span></strong>
          <span>au lieu de <del>520 MAD</del></span>
        </div>
        <div class="taj-clean-hero__price-offer">
          <em>OFFRE LIMIT&Eacute;E</em>
          <strong>130 MAD</strong>
          <span>DE R&Eacute;DUCTION</span>
        </div>
      </div>

      <div class="taj-clean-hero__actions">
        <a class="taj-clean-hero__primary" href="#commande">COMMANDER MAINTENANT &mdash; 390 MAD</a>
        <a class="taj-clean-hero__secondary" href="#produit">EN SAVOIR PLUS</a>
      </div>

      <div class="taj-clean-hero__trust" aria-label="Les garanties Tajinoos">
        <span>Livraison partout au Maroc</span>
        <span>Paiement &agrave; la livraison</span>
        <span>Confirmation t&eacute;l&eacute;phonique</span>
        <span>Garantie 7 jours</span>
      </div>
    </div>

    <div class="taj-clean-hero__visual" aria-label="Tajine artisanal Tajinoos">
      <img class="taj-clean-hero__product" src="/wp-content/uploads/2026/06/tajinoos-hero-product.png" alt="Tajine artisanal marocain Tajinoos" width="1024" height="1024" fetchpriority="high" decoding="async">
      <div class="taj-clean-hero__shadow" aria-hidden="true"></div>
    </div>
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
 * Final approved two-card checkout section.
 */
function tajinoos_child_render_approved_order_section(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query() || strpos($content, 'id="commande"') === false) {
        return $content;
    }

    $order = <<<'HTML'
<section id="commande" class="tajx-section tajx-order tajx-order-premium" aria-labelledby="tajx-order-title">
  <div class="tajx-wrap tajx-order-inner tajx-order-premium__inner">
    <header class="tajx-order-header tajx-order-premium__header">
      <span class="tajx-order-eyebrow tajx-order-premium__eyebrow"><span aria-hidden="true">◆</span> COMMANDE S&Eacute;CURIS&Eacute;E</span>
      <h2 id="tajx-order-title" class="tajx-order-premium__title">FINALISER VOTRE COMMANDE</h2>
      <p class="tajx-order-premium__subtitle">L&rsquo;excellence de l&rsquo;artisanat marocain, pr&ecirc;te &agrave; rejoindre votre table.</p>
    </header>

    <div class="tajx-order-grid tajx-order-premium__grid">
      <article class="tajx-offer-card tajx-order-premium__offer">
        <div class="tajx-offer-badges tajx-order-premium__badges">
          <span>OFFRE LIMIT&Eacute;E</span>
          <span>FAIT MAIN AU MAROC</span>
        </div>

        <div class="tajx-offer-content tajx-order-premium__offer-body">
          <p class="tajx-offer-kicker tajx-order-premium__kicker">R&Eacute;SERVEZ VOTRE TAJINOOS</p>
          <h3 class="tajx-offer-title tajx-order-premium__offer-title"><span>OFFRE SP&Eacute;CIALE &mdash;</span> Tajine Artisanal Tajinoos Premium</h3>
          <div class="tajx-offer-price tajx-order-premium__price"><strong>390 MAD</strong><del>520 MAD</del></div>
          <p class="tajx-order-premium__price-note"><span aria-hidden="true">◆</span> Le prix total s&rsquo;ajuste selon la quantit&eacute; choisie</p>

          <ul class="tajx-offer-trust tajx-order-premium__benefits" aria-label="Garanties de la commande">
            <li><span aria-hidden="true">◆</span> Paiement &agrave; la livraison</li>
            <li><span aria-hidden="true">◆</span> Livraison partout au Maroc</li>
            <li><span aria-hidden="true">◆</span> Confirmation t&eacute;l&eacute;phonique</li>
            <li><span aria-hidden="true">◆</span> Garantie 7 jours</li>
          </ul>
        </div>
      </article>

      <form class="tajx-form-card tajx-order-form tajx-order-premium__form-card" action="mailto:orders@tajinoos.com" enctype="text/plain" method="post">
        <div class="tajx-form-strip tajx-order-premium__reassurance" aria-label="Garanties de paiement et livraison">
          <span><span aria-hidden="true">◆</span> Paiement &agrave; la livraison</span>
          <span><span aria-hidden="true">◆</span> Livraison au Maroc</span>
          <span><span aria-hidden="true">◆</span> Garantie 7 jours</span>
        </div>

        <div class="tajx-order-form-body tajx-order-premium__form">
          <header class="tajx-form-heading tajx-order-premium__form-heading">
            <h3>R&Eacute;SERVEZ VOTRE TAJINOOS</h3>
            <p>Remplissez vos informations. Notre &eacute;quipe vous contacte pour confirmer la disponibilit&eacute; et la livraison.</p>
          </header>

          <div class="tajx-form-row tajx-order-premium__form-row">
            <label class="tajx-field tajx-order-premium__field">NOM COMPLET
              <input name="Nom" required type="text" placeholder="Ex: Ahmed Alaoui" autocomplete="name">
            </label>
            <label class="tajx-field tajx-order-premium__field">T&Eacute;L&Eacute;PHONE (WHATSAPP)
              <input name="Telephone" required type="tel" placeholder="06 XX XX XX XX" autocomplete="tel">
            </label>
          </div>

          <label class="tajx-field tajx-field--full tajx-order-premium__field tajx-order-premium__field--full">VILLE &amp; ADRESSE DE LIVRAISON
            <input name="Adresse" required type="text" placeholder="Votre ville et adresse compl&egrave;te..." autocomplete="street-address">
          </label>

          <div class="tajx-form-row tajx-order-premium__form-row">
            <label class="tajx-field tajx-order-premium__field">MOD&Egrave;LE
              <select name="Produit">
                <option>Tajine artisanal Tajinoos Premium</option>
              </select>
            </label>
            <label class="tajx-field tajx-order-premium__field">QUANTIT&Eacute;
              <select name="Quantite">
                <option>1 pi&egrave;ce</option>
                <option>2 pi&egrave;ces</option>
                <option>3 pi&egrave;ces</option>
                <option>4 pi&egrave;ces</option>
                <option>5 pi&egrave;ces</option>
              </select>
            </label>
          </div>

          <label class="tajx-field tajx-field--full tajx-order-premium__field tajx-order-premium__field--full">MESSAGE (OPTIONNEL)
            <textarea name="Message" rows="3" placeholder="Pr&eacute;cisez un horaire de rappel..."></textarea>
          </label>

          <button class="tajx-submit" type="submit">COMMANDER MON TAJINE &mdash; 390 MAD <span aria-hidden="true">&rarr;</span></button>
          <p class="tajx-form-reassurance tajx-order-premium__note"><span aria-hidden="true">◆</span> Cash on delivery</p>
        </div>
      </form>
    </div>
  </div>
</section>
HTML;

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])commande\1[^>]*>.*?</section>~s',
        $order,
        $content,
        1
    ) ?: $content;
}

/**
 * Final rebuilt checkout section, rendered after earlier legacy order filters.
 */
function tajinoos_child_render_command_rebuild_section(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query() || strpos($content, 'id="commande"') === false) {
        return $content;
    }

    $form_action = esc_url(wp_make_link_relative(admin_url('admin-post.php')));
    $nonce_field = wp_nonce_field('tajinoos_order_submit', '_tajinoos_order_nonce', true, false);
    $source_url = esc_url(get_permalink() ?: home_url('/#commande'));

    $order = <<<'HTML'
<section id="commande" class="tajx-section tajx-order tajcmd" aria-labelledby="tajcmd-title">
  <div class="tajcmd__inner">
    <header class="tajcmd__header">
      <span class="tajcmd__badge"><span aria-hidden="true">&#9671;</span> COMMANDE S&Eacute;CURIS&Eacute;E</span>
      <h2 id="tajcmd-title">FINALISER VOTRE COMMANDE</h2>
      <p>L&rsquo;excellence de l&rsquo;artisanat marocain, pr&ecirc;te &agrave; rejoindre votre table.</p>
    </header>

    <div class="tajx-order-grid tajcmd__grid">
      <article class="tajcmd-product" aria-labelledby="tajcmd-product-title">
        <div class="tajcmd-product__visual" aria-label="Tajine artisanal Tajinoos Premium">
          <img class="tajcmd-product__pattern" src="/wp-content/uploads/2026/06/tajinoos-pattern-bg.webp" alt="" loading="lazy" decoding="async" aria-hidden="true">
          <img class="tajcmd-product__image" src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" alt="Tajine artisanal Tajinoos Premium" width="1024" height="1024" loading="lazy" decoding="async">
        </div>

        <div class="tajcmd-product__content">
          <span class="tajcmd-product__badge">OFFRE LIMIT&Eacute;E</span>
          <h3 id="tajcmd-product-title">TAJINOOS PREMIUM</h3>
          <p class="tajcmd-product__description">Un tajine en terre cuite fa&ccedil;onn&eacute; &agrave; la main pour une cuisson lente, une table &eacute;l&eacute;gante et des repas qui rassemblent.</p>

          <ul class="tajcmd-trust" aria-label="Garanties Tajinoos">
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 11.5V8a2 2 0 0 1 4 0v3"/><path d="M11 10V6.5a2 2 0 0 1 4 0V12"/><path d="M15 11V8.5a2 2 0 0 1 4 0v5.2c0 4.3-2.8 7.3-7.1 7.3H10a6 6 0 0 1-4.8-2.4L3 15.5a1.9 1.9 0 0 1 3-2.3l1.6 1.9"/></svg></span><strong>Fait main</strong><small>Chaque pi&egrave;ce est unique, r&eacute;alis&eacute;e avec soin.</small></li>
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 9h10l-1 9H8L7 9Z"/><path d="M5 9h14"/><path d="M9 9V7a3 3 0 0 1 6 0v2"/><path d="M9 4c-.8-.8-.8-1.6 0-2.4"/><path d="M13 4c-.8-.8-.8-1.6 0-2.4"/></svg></span><strong>Cuisson lente</strong><small>Le couvercle conique aide &agrave; pr&eacute;server les saveurs.</small></li>
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7.5h14.5A2.5 2.5 0 0 1 21 10v7a2.5 2.5 0 0 1-2.5 2.5h-12A2.5 2.5 0 0 1 4 17V7.5Z"/><path d="M4 8l10.5-3.5A2 2 0 0 1 17 6.4V8"/><path d="M16 13.5h5"/><path d="M17.5 13.5h.1"/></svg></span><strong>Paiement &agrave; la livraison</strong><small>R&eacute;glez en toute s&eacute;r&eacute;nit&eacute; &agrave; la r&eacute;ception.</small></li>
            <li><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 6h11v10H3z"/><path d="M14 9h4l3 4v3h-7z"/><path d="M6.5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/><path d="M17.5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg></span><strong>Livraison au Maroc</strong><small>Rapide, s&eacute;curis&eacute;e et suivie partout au Maroc.</small></li>
          </ul>
        </div>
      </article>

      <form class="tajx-form-card tajx-order-form tajcmd-form" action="%%TAJINOOS_ORDER_ACTION%%" method="post">
        %%TAJINOOS_ORDER_NONCE%%
        <input type="hidden" name="action" value="tajinoos_submit_order">
        <input type="hidden" name="Source" value="%%TAJINOOS_ORDER_SOURCE%%">
        <input type="hidden" name="Prix_unitaire" value="390">
        <input type="hidden" name="Total" value="390" data-tajcmd-total-input>

        <header class="tajcmd-form__head">
          <span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.5 19 7v5c0 4.5-2.8 7.4-7 8.5-4.2-1.1-7-4-7-8.5V7l7-3.5Z"/><path d="m9.5 12 1.7 1.7 3.6-4"/></svg></span>
          <h3>VOS INFORMATIONS</h3>
        </header>

        <div class="tajcmd-form__body">
          <div class="tajcmd-form__row">
            <label class="tajcmd-field">NOM COMPLET
              <input name="Nom" required type="text" placeholder="Ex: Ahmed Alaoui" autocomplete="name">
            </label>
            <label class="tajcmd-field">T&Eacute;L&Eacute;PHONE (WHATSAPP)
              <input name="Telephone" required type="tel" placeholder="06 XX XX XX XX" autocomplete="tel">
            </label>
          </div>

          <label class="tajcmd-field tajcmd-field--full">VILLE &amp; ADRESSE DE LIVRAISON
            <input name="Adresse" required type="text" placeholder="Votre ville et adresse compl&egrave;te..." autocomplete="street-address">
          </label>

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
            <textarea name="Message" rows="3" placeholder="Pr&eacute;cisez un horaire de rappel, un d&eacute;tail de livraison..."></textarea>
          </label>

          <div class="tajcmd-total" aria-label="Total &agrave; payer">
            <span class="tajcmd-total__meta"><strong>TOTAL &Agrave; PAYER</strong><small>Frais de livraison inclus</small></span>
            <strong class="tajcmd-total__price"><span data-tajcmd-form-total>390</span><span class="tajcmd-total__currency"> MAD</span></strong>
          </div>

          <button class="tajx-submit tajcmd-submit" type="submit"><span class="tajcmd-submit__label">COMMANDER MON TAJINE &mdash;</span> <span class="tajcmd-submit__price"><span data-tajcmd-cta-total>390</span><span class="tajcmd-submit__currency"> MAD</span></span> <span class="tajcmd-submit__arrow" aria-hidden="true">&rarr;</span></button>

          <div class="tajcmd-reassurance" aria-label="Garanties de commande">
            <span><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.5 19 7v5c0 4.5-2.8 7.4-7 8.5-4.2-1.1-7-4-7-8.5V7l7-3.5Z"/><path d="m9.5 12 1.7 1.7 3.6-4"/></svg></span> Cash on delivery</span>
            <span><span class="tajcmd-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 5h10a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3h-3l-4 3v-3H7a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3Z"/><path d="M8 10h8"/><path d="M8 13h5"/></svg></span> Confirmation t&eacute;l&eacute;phonique</span>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
HTML;

    $order = str_replace(
        ['%%TAJINOOS_ORDER_ACTION%%', '%%TAJINOOS_ORDER_NONCE%%', '%%TAJINOOS_ORDER_SOURCE%%'],
        [$form_action, $nonce_field, $source_url],
        $order
    );

    return preg_replace(
        '~<section\b[^>]*\bid=(["\'])commande\1[^>]*>.*?</section>~s',
        $order,
        $content,
        1
    ) ?: $content;
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
