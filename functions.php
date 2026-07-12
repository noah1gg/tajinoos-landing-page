<?php
/**
 * Tajinoos child theme.
 *
 * Page 13 is rendered from one source so its content, checkout, and visual
 * system cannot drift across competing Elementor content filters.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TAJINOOS_CHILD_VERSION', '2.1.0');
define('TAJINOOS_UNIT_PRICE', 390);

add_action('wp_enqueue_scripts', 'tajinoos_child_enqueue_assets', 20);
add_action('wp_enqueue_scripts', 'tajinoos_child_dequeue_landing_scripts', 100);
add_action('wp_print_footer_scripts', 'tajinoos_child_dequeue_landing_scripts', 1);
add_action('init', 'tajinoos_child_ensure_thank_you_page');
add_action('admin_post_nopriv_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_action('admin_post_tajinoos_submit_order', 'tajinoos_child_handle_order_submit');
add_shortcode('tajinoos_thank_you', 'tajinoos_child_render_thank_you_shortcode');
add_filter('wp_list_pages_excludes', 'tajinoos_child_exclude_thank_you_from_page_list');
add_filter('body_class', 'tajinoos_child_body_class');
add_filter('style_loader_src', 'tajinoos_child_page_13_relative_asset', 10, 2);
add_filter('script_loader_src', 'tajinoos_child_page_13_relative_asset', 10, 2);
add_filter('script_loader_tag', 'tajinoos_child_filter_landing_script_tag', 10, 3);
add_filter('the_content', 'tajinoos_child_render_landing_page', 20);

function tajinoos_child_page_13_relative_asset(string $src, string $handle): string
{
    if ($handle !== 'tajinoos-premium' || (!is_page(13) && !is_page('merci'))) {
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

function tajinoos_child_dequeue_landing_scripts(): void
{
    if (!is_page(13) && !is_page('merci')) {
        return;
    }

    $unused_scripts = [
        'astra-theme-js',
        'elementor-webpack-runtime',
        'elementor-frontend-modules',
        'elementor-frontend',
        'elementor-pro-webpack-runtime',
        'elementor-pro-frontend',
        'jquery-ui-core',
        'wp-hooks',
        'wp-i18n',
    ];

    foreach ($unused_scripts as $handle) {
        wp_dequeue_script($handle);
    }
}

function tajinoos_child_filter_landing_script_tag(string $tag, string $handle, string $src): string
{
    if (!is_page(13) && !is_page('merci')) {
        return $tag;
    }

    $unused_handles = [
        'astra-theme-js',
        'elementor-webpack-runtime',
        'elementor-frontend-modules',
        'elementor-frontend',
        'elementor-pro-webpack-runtime',
        'elementor-pro-frontend',
        'jquery-ui-core',
        'wp-hooks',
        'wp-i18n',
    ];

    return in_array($handle, $unused_handles, true) ? '' : $tag;
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
    $whatsapp_url = esc_url('https://wa.me/212627424509?text=' . rawurlencode('Bonjour, je viens de passer une commande Tajinoos et j\'ai une question.'));

    return <<<HTML
<section class="tjn-thanks">
  <div class="tjn-thanks__pattern" aria-hidden="true"></div>
  <a class="tjn-thanks__brand" href="{$home_url}" aria-label="Tajinoos, retour &agrave; l&rsquo;accueil">TAJINOOS</a>

  <div class="tjn-thanks__shell">
    <div class="tjn-thanks__message">
      <span class="tjn-thanks__mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false"><path d="M5 12.5 9.2 17 19 7"/></svg>
      </span>
      <p class="tjn-kicker">Commande bien re&ccedil;ue</p>
      <h1>Votre Tajinoos est entre de bonnes mains.</h1>
      <p class="tjn-thanks__lede">Merci pour votre confiance. Votre demande nous est bien parvenue et aucune somme n&rsquo;a &eacute;t&eacute; pr&eacute;lev&eacute;e.</p>

      <div class="tjn-thanks__actions">
        <a class="tjn-button tjn-button--primary" href="{$home_url}">Retour &agrave; l&rsquo;accueil <span aria-hidden="true">&rarr;</span></a>
        <a class="tjn-thanks__help" href="{$whatsapp_url}" target="_blank" rel="noopener noreferrer">Une question ? &Eacute;crivez-nous</a>
      </div>
    </div>

    <aside class="tjn-thanks__next" aria-labelledby="tjn-thanks-next-title">
      <p class="tjn-thanks__next-label">La suite, simplement</p>
      <h2 id="tjn-thanks-next-title">Que se passe-t-il maintenant&nbsp;?</h2>
      <ol class="tjn-thanks__steps">
        <li>
          <span class="tjn-thanks__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 4h3l1.2 4-2 1.7a15 15 0 0 0 5.1 5.1l1.7-2 4 1.2v3a3 3 0 0 1-3 3C9.8 20 4 14.2 4 7a3 3 0 0 1 3-3Z"/></svg></span>
          <div><small>&Eacute;tape 01</small><strong>Nous vous appelons</strong><p>Un membre de notre &eacute;quipe confirme avec vous le produit, l&rsquo;adresse et la livraison.</p></div>
        </li>
        <li>
          <span class="tjn-thanks__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 9h16v11H4zM7 9V6h10v3M8 13h8M8 16h5"/></svg></span>
          <div><small>&Eacute;tape 02</small><strong>Nous pr&eacute;parons votre pi&egrave;ce</strong><p>Votre Tajinoos est v&eacute;rifi&eacute; puis emball&eacute; avec soin avant son d&eacute;part.</p></div>
        </li>
        <li>
          <span class="tjn-thanks__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 7h11v10H3zM14 10h4l3 4v3h-7zM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM17.5 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg></span>
          <div><small>&Eacute;tape 03</small><strong>Vous payez &agrave; la r&eacute;ception</strong><p>Votre commande arrive &agrave; l&rsquo;adresse confirm&eacute;e. Le paiement se fait &agrave; la livraison.</p></div>
        </li>
      </ol>
      <p class="tjn-thanks__phone-note"><span aria-hidden="true">!</span> Gardez votre t&eacute;l&eacute;phone disponible pour notre appel de confirmation.</p>
    </aside>
  </div>
</section>
HTML;
}

function tajinoos_child_handle_order_submit(): void
{
    $nonce = isset($_POST['_tajinoos_order_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['_tajinoos_order_nonce']))
        : '';

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
    $phone_digits = preg_replace('/\D+/', '', $phone) ?: '';

    if (
        $name === ''
        || $address === ''
        || strlen($phone_digits) < 9
        || strlen($phone_digits) > 13
        || $quantity < 1
        || $quantity > 5
    ) {
        tajinoos_child_redirect_order_error();
    }

    $product = $product !== '' ? $product : 'Tajine artisanal Tajinoos Premium';
    $total = $quantity * TAJINOOS_UNIT_PRICE;
    $submitted_at = current_time('mysql');
    $referer = wp_get_referer();

    $body = [
        'Nouvelle commande Tajinoos',
        '',
        'Nom: ' . $name,
        'Telephone: ' . $phone,
        'Adresse: ' . $address,
        'Produit: ' . $product,
        'Quantite: ' . $quantity,
        'Prix unitaire: ' . TAJINOOS_UNIT_PRICE . ' MAD',
        'Total recalcule: ' . $total . ' MAD',
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

    wp_safe_redirect(home_url('/merci/'));
    exit;
}

function tajinoos_child_sanitize_order_text(string $key): string
{
    return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
}

function tajinoos_child_redirect_order_error(): void
{
    wp_safe_redirect(home_url('/?tajinoos_order=error#commande'));
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

function tajinoos_child_body_class(array $classes): array
{
    if (is_page(13)) {
        $classes[] = 'tjn-landing-page';
    }

    if (is_page('merci')) {
        $classes[] = 'tjn-thank-you-page';
    }

    return $classes;
}

function tajinoos_child_render_landing_page(string $content): string
{
    if (!is_page(13) || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $form_action = esc_url(wp_make_link_relative(admin_url('admin-post.php')));
    $nonce_field = wp_nonce_field('tajinoos_order_submit', '_tajinoos_order_nonce', true, false);
    $source_url = esc_url(get_permalink() ?: home_url('/#commande'));
    $price = TAJINOOS_UNIT_PRICE;
    $order_error = isset($_GET['tajinoos_order']) && sanitize_key(wp_unslash($_GET['tajinoos_order'])) === 'error';
    $order_notice = $order_error
        ? '<p class="tjn-form__error" role="alert">V&eacute;rifiez vos informations puis r&eacute;essayez.</p>'
        : '';

    return <<<HTML
<div class="tjn-site" data-tjn-landing>
  <a class="tjn-skip" href="#contenu">Aller au contenu</a>

  <header class="tjn-header" data-tjn-header>
    <div class="tjn-container tjn-header__inner">
      <button class="tjn-nav-toggle" type="button" aria-expanded="false" aria-controls="tjn-navigation">
        <span></span><span></span><span></span><span class="screen-reader-text">Ouvrir le menu</span>
      </button>
      <nav id="tjn-navigation" class="tjn-nav" aria-label="Navigation principale">
        <a href="#accueil">Accueil</a>
        <a href="#heritage">H&eacute;ritage</a>
        <a href="#savoir-faire">Processus</a>
        <a href="#produit">Produit</a>
        <a href="#avis">Avis</a>
        <a class="tjn-nav__cta" href="#commande">Commander</a>
      </nav>
    </div>
  </header>

  <main id="contenu">
    <section id="accueil" class="tjn-hero" aria-labelledby="tjn-hero-title">
      <div class="tjn-hero__grain" aria-hidden="true"></div>
      <div class="tjn-container tjn-hero__grid">
        <div class="tjn-hero__copy">
          <p class="tjn-kicker tjn-reveal">L&rsquo;art de la convivialit&eacute;</p>
          <h1 id="tjn-hero-title">
            <span class="tjn-reveal">Le <em>tajine</em></span>
            <span class="tjn-reveal">que votre table m&eacute;rite.</span>
          </h1>
          <p class="tjn-hero__lede tjn-reveal">Fa&ccedil;onn&eacute; &agrave; la main au Maroc pour une cuisson lente, des plats qui ont une &acirc;me et des repas qui rassemblent.</p>

          <div class="tjn-hero__offer tjn-reveal" aria-label="Prix promotionnel du Tajinoos Premium">
            <div class="tjn-hero__price"><small>Offre actuelle</small><strong>{$price} <span>MAD</span></strong><del>520 MAD</del></div>
            <div class="tjn-hero__saving"><small>Vous &eacute;conomisez</small><strong>130 MAD</strong></div>
            <a class="tjn-button tjn-button--primary" href="#commande">Commander maintenant <span aria-hidden="true">&rarr;</span></a>
            <p>Paiement &agrave; la livraison <span>&bull;</span> Garantie 7 jours</p>
          </div>
        </div>

        <div class="tjn-hero__visual tjn-reveal" data-tjn-parallax>
          <span class="tjn-hero__seal"><small>100%</small><strong>fait main</strong></span>
          <div class="tjn-hero__halo" aria-hidden="true"></div>
          <img src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" srcset="/wp-content/uploads/2026/06/tajinoos-hero-product-300x300.webp 300w, /wp-content/uploads/2026/06/tajinoos-hero-product-768x768.webp 768w, /wp-content/uploads/2026/06/tajinoos-hero-product.webp 1024w" sizes="(max-width: 760px) 88vw, 48vw" width="1024" height="1024" alt="Tajine artisanal marocain Tajinoos Premium" fetchpriority="high" decoding="async">
        </div>
      </div>

      <div class="tjn-container tjn-trust" aria-label="Services Tajinoos">
        <span><b aria-hidden="true">&#10022;</b> Fait main au Maroc</span>
        <span><b aria-hidden="true">&#9671;</b> Argile naturelle</span>
        <span><b aria-hidden="true">&#10003;</b> Cuisson lente et saine</span>
        <span><b aria-hidden="true">&#8640;</b> Livraison partout au Maroc</span>
      </div>
    </section>

    <section id="heritage" class="tjn-section tjn-story" aria-labelledby="tjn-story-title">
      <div class="tjn-container tjn-story__grid">
        <figure class="tjn-story__visual tjn-reveal">
          <img src="/wp-content/uploads/2026/06/tajinoos-artisan-master-768x1052.webp" width="768" height="1052" alt="Artisan marocain travaillant la terre cuite &agrave; la main" loading="lazy" decoding="async">
          <figcaption>L&rsquo;h&eacute;ritage et le savoir-faire transmis de g&eacute;n&eacute;ration en g&eacute;n&eacute;ration.</figcaption>
        </figure>

        <div class="tjn-story__copy">
          <p class="tjn-kicker tjn-reveal">Notre h&eacute;ritage</p>
          <h2 id="tjn-story-title" class="tjn-reveal">L&rsquo;h&eacute;ritage dans chaque pi&egrave;ce.</h2>
          <p class="tjn-story__intro tjn-reveal">Tajinoos r&eacute;unit le savoir-faire de l&rsquo;artisan et les exigences d&rsquo;une cuisine familiale d&rsquo;aujourd&rsquo;hui. Chaque pi&egrave;ce est fa&ccedil;onn&eacute;e &agrave; la main pour rester utile, belle et durable.</p>
          <p class="tjn-story__intro tjn-story__intro--second tjn-reveal">La terre cuite diffuse la chaleur doucement; le couvercle conique aide &agrave; conserver l&rsquo;humidit&eacute;, les ar&ocirc;mes et la tendret&eacute; des aliments.</p>

          <div class="tjn-story__proofs">
            <article class="tjn-proof tjn-reveal"><span aria-hidden="true">&#10022;</span><div><h3>Savoir-faire artisanal marocain</h3></div></article>
            <article class="tjn-proof tjn-reveal"><span aria-hidden="true">&#9671;</span><div><h3>Argile naturelle s&eacute;lectionn&eacute;e</h3></div></article>
            <article class="tjn-proof tjn-reveal"><span aria-hidden="true">&#10003;</span><div><h3>Chaque pi&egrave;ce contr&ocirc;l&eacute;e</h3></div></article>
            <article class="tjn-proof tjn-reveal"><span aria-hidden="true">&#8734;</span><div><h3>Un objet fait pour durer</h3></div></article>
          </div>
        </div>
      </div>
    </section>

    <section class="tjn-manifesto" aria-labelledby="tjn-manifesto-title">
      <div class="tjn-container tjn-manifesto__grid">
        <div class="tjn-manifesto__copy">
          <p class="tjn-kicker tjn-kicker--light tjn-reveal">L&rsquo;exp&eacute;rience Tajinoos</p>
          <h2 id="tjn-manifesto-title" class="tjn-reveal">Plus qu&rsquo;un tajine&nbsp;:<br>une exp&eacute;rience de table.</h2>
          <p class="tjn-reveal">Con&ccedil;u pour sublimer vos repas et rassembler vos proches autour de moments chaleureux, simples et authentiques.</p>
          <div class="tjn-manifesto__benefits tjn-reveal">
            <span><b aria-hidden="true">&#10003;</b> Cuisson saine et savoureuse</span>
            <span><b aria-hidden="true">&#9671;</b> Conception artisanale</span>
            <span><b aria-hidden="true">&#8640;</b> Livraison partout au Maroc</span>
          </div>
        </div>
        <ol class="tjn-manifesto__values tjn-reveal">
          <li><span>01</span><div><h3>Cuisson lente ma&icirc;tris&eacute;e</h3><p>Le couvercle conique favorise une circulation naturelle de la vapeur et pr&eacute;serve les saveurs.</p></div></li>
          <li><span>02</span><div><h3>Design artisanal unique</h3><p>Chaque pi&egrave;ce est fa&ccedil;onn&eacute;e &agrave; la main avec des motifs traditionnels &eacute;l&eacute;gants.</p></div></li>
          <li><span>03</span><div><h3>Une valeur qui se transmet</h3><p>Un objet utile, raffin&eacute; et durable qui trouve naturellement sa place au centre de la table.</p></div></li>
          <li><span>04</span><div><h3>Achat rassurant</h3><p>Paiement &agrave; la livraison, confirmation t&eacute;l&eacute;phonique et garantie 7 jours.</p></div></li>
        </ol>
      </div>
    </section>

    <section id="savoir-faire" class="tjn-section tjn-process" aria-labelledby="tjn-process-title">
      <div class="tjn-container">
        <header class="tjn-heading tjn-heading--center">
          <p class="tjn-kicker tjn-reveal">Le savoir-faire Tajinoos</p>
          <h2 id="tjn-process-title" class="tjn-reveal">Un parcours en cinq gestes essentiels.</h2>
          <p class="tjn-reveal">De la terre brute &agrave; votre table, chaque &eacute;tape a une raison.</p>
        </header>

        <ol class="tjn-process__list">
          <li class="tjn-reveal"><span class="tjn-process__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 17c4-1 7-4 8-9 3 2 4 5 3 8-1 4-5 6-9 4-3-2-4-6-2-9 1-3 4-5 7-6"/></svg></span><small>01</small><h3>S&eacute;lection de l&rsquo;argile</h3><p>Une terre choisie pour sa tenue, sa chaleur et son caract&egrave;re.</p></li>
          <li class="tjn-reveal"><span class="tjn-process__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 19c-2-4-1-9 2-12M17 19c2-4 1-9-2-12M9 8c1-3 5-3 6 0v8c0 3-6 3-6 0Z"/></svg></span><small>02</small><h3>Fa&ccedil;onnage &agrave; la main</h3><p>La forme prend vie lentement sous les gestes de l&rsquo;artisan.</p></li>
          <li class="tjn-reveal"><span class="tjn-process__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 20c-3-4 0-7 2-9 1-2 1-4 1-6 5 3 7 8 4 12-1 2-3 3-5 3M12 20c-1-2 0-4 2-6"/></svg></span><small>03</small><h3>Cuisson traditionnelle</h3><p>La pi&egrave;ce gagne sa solidit&eacute; sans perdre son &acirc;me min&eacute;rale.</p></li>
          <li class="tjn-reveal"><span class="tjn-process__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 12h4l2 5 4-10 2 5h4M6 5l1 2M18 5l-1 2"/></svg></span><small>04</small><h3>Inspection qualit&eacute;</h3><p>Chaque tajine est v&eacute;rifi&eacute; pour garantir une finition soign&eacute;e.</p></li>
          <li class="tjn-reveal"><span class="tjn-process__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 18c4-4 5-9 6-14 3 5 4 10 8 14M7 15h10M9 11h6"/></svg></span><small>05</small><h3>Finition finale</h3><p>Les motifs sont pos&eacute;s avec mesure, jamais &agrave; la cha&icirc;ne.</p></li>
        </ol>
      </div>
    </section>

    <section id="produit" class="tjn-section tjn-offer" aria-labelledby="tjn-offer-title">
      <div class="tjn-container tjn-offer__card">
        <div class="tjn-offer__visual tjn-reveal">
          <img src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" width="1024" height="1024" alt="Tajinoos Premium, tajine en terre cuite artisanale" loading="lazy" decoding="async">
          <div class="tjn-offer__badges"><span>Fait main au Maroc</span><span>Argile naturelle</span><span>Pi&egrave;ce contr&ocirc;l&eacute;e</span></div>
        </div>
        <div class="tjn-offer__content">
          <p class="tjn-kicker tjn-kicker--gold tjn-reveal">Notre gamme premium</p>
          <h2 id="tjn-offer-title" class="tjn-reveal">Tajinoos Premium</h2>
          <p class="tjn-reveal">Un tajine artisanal d&rsquo;exception, alliant beaut&eacute;, performance et authenticit&eacute; marocaine.</p>
          <div class="tjn-offer__price tjn-reveal"><div><small>Offre actuelle</small><strong>{$price} MAD</strong></div><del>520 MAD</del></div>
          <ul class="tjn-offer__features tjn-reveal">
            <li>Terre cuite fa&ccedil;onn&eacute;e &agrave; la main</li>
            <li>Cuisson lente, saine et savoureuse</li>
            <li>Design authentique et motifs uniques</li>
            <li>Paiement &agrave; la r&eacute;ception</li>
            <li>Livraison partout au Maroc</li>
          </ul>
          <a class="tjn-button tjn-button--primary tjn-offer__cta tjn-reveal" href="#commande">Commander maintenant <span aria-hidden="true">&rarr;</span></a>
          <p class="tjn-offer__note"><span aria-hidden="true">&#10003;</span> Paiement &agrave; la livraison &nbsp;&bull;&nbsp; Garantie 7 jours</p>
        </div>
      </div>
    </section>

    <section id="avis" class="tjn-section tjn-reviews" aria-labelledby="tjn-reviews-title">
      <div class="tjn-container tjn-reviews__layout">
        <header class="tjn-heading tjn-reviews__heading">
          <p class="tjn-kicker tjn-reveal">Autour de la table</p>
          <h2 id="tjn-reviews-title" class="tjn-reveal">Ils ont choisi le tajine Tajinoos.</h2>
          <p class="tjn-reveal">Leurs mots parlent de la qualit&eacute; de la pi&egrave;ce, de la cuisson et d&rsquo;une commande simple et rassurante.</p>
          <div class="tjn-reviews__trust tjn-reveal"><span>Paiement &agrave; la livraison</span><span>Confirmation humaine</span></div>
          <a class="tjn-button tjn-button--primary tjn-reveal" href="#commande">Commander le mien</a>
        </header>
        <div class="tjn-reviews__grid">
          <blockquote class="tjn-review tjn-reveal"><span class="tjn-review__quote" aria-hidden="true">&ldquo;</span><p>Il est encore plus beau en vrai. La cuisson est douce et le tajine garde bien la chaleur quand on le pose &agrave; table.</p><footer><span>SB</span><cite>Salma B., Marrakech</cite></footer></blockquote>
          <blockquote class="tjn-review tjn-reveal"><span class="tjn-review__quote" aria-hidden="true">&ldquo;</span><p>J&rsquo;ai &eacute;t&eacute; appel&eacute; avant l&rsquo;envoi, puis j&rsquo;ai pay&eacute; &agrave; la livraison. Simple, rassurant, et tr&egrave;s bien fini.</p><footer><span>YA</span><cite>Yassine A., Casablanca</cite></footer></blockquote>
          <blockquote class="tjn-review tjn-reveal"><span class="tjn-review__quote" aria-hidden="true">&ldquo;</span><p>Je l&rsquo;ai offert pour une nouvelle maison. C&rsquo;est &agrave; la fois un bel objet et quelque chose que l&rsquo;on utilise vraiment.</p><footer><span>NL</span><cite>Nadia L., Marrakech</cite></footer></blockquote>
        </div>
      </div>
    </section>

    <section id="faq" class="tjn-section tjn-faq" aria-labelledby="tjn-faq-title">
      <div class="tjn-container tjn-faq__layout">
        <div>
          <header class="tjn-heading">
            <p class="tjn-kicker tjn-reveal">Avant de commander</p>
            <h2 id="tjn-faq-title" class="tjn-reveal">Les r&eacute;ponses utiles, sans d&eacute;tour.</h2>
          </header>
          <div class="tjn-faq__list">
            <details class="tjn-reveal"><summary>Sur quels feux puis-je utiliser le tajine ?</summary><div><p>Il convient au gaz, au four et au charbon avec une chauffe progressive. Pour l&rsquo;induction, utilisez un adaptateur compatible.</p></div></details>
            <details class="tjn-reveal"><summary>Comment le pr&eacute;parer avant la premi&egrave;re cuisson ?</summary><div><p>Rincez-le doucement, laissez-le s&eacute;cher, puis huilez l&eacute;g&egrave;rement l&rsquo;int&eacute;rieur. Un guide simple accompagne votre commande.</p></div></details>
            <details class="tjn-reveal"><summary>Comment se passe la livraison ?</summary><div><p>Apr&egrave;s votre commande, nous vous appelons pour confirmer l&rsquo;adresse et les modalit&eacute;s avant l&rsquo;exp&eacute;dition.</p></div></details>
            <details class="tjn-reveal"><summary>Quand dois-je payer ?</summary><div><p>Le paiement se fait &agrave; la livraison. Aucun paiement en ligne n&rsquo;est demand&eacute; sur cette page.</p></div></details>
            <details class="tjn-reveal"><summary>Que faire si la pi&egrave;ce arrive endommag&eacute;e ?</summary><div><p>Contactez-nous d&egrave;s la r&eacute;ception avec une photo. Notre &eacute;quipe vous indiquera rapidement la solution adapt&eacute;e.</p></div></details>
            <details class="tjn-reveal"><summary>Comment entretenir la terre cuite ?</summary><div><p>Lavez-la &agrave; la main avec une &eacute;ponge douce, &eacute;vitez les chocs thermiques et laissez-la s&eacute;cher compl&egrave;tement avant rangement.</p></div></details>
          </div>
        </div>

        <aside class="tjn-support tjn-reveal">
          <span class="tjn-support__icon" aria-hidden="true">?</span>
          <p class="tjn-kicker">Une question pr&eacute;cise ?</p>
          <h3>Parlez &agrave; une vraie personne.</h3>
          <p>Notre &eacute;quipe vous aide pour l&rsquo;usage, la livraison ou votre commande.</p>
          <a class="tjn-button tjn-button--primary" href="https://wa.me/212627424509?text=Bonjour%2C%20j%27ai%20une%20question%20sur%20le%20Tajinoos%20Premium." target="_blank" rel="noopener noreferrer">Poser ma question sur WhatsApp</a>
          <a class="tjn-support__mail" href="mailto:orders@tajinoos.com">orders@tajinoos.com</a>
        </aside>
      </div>
    </section>

    <section id="commande" class="tjn-section tjn-checkout" aria-labelledby="tjn-checkout-title">
      <div class="tjn-container">
        <header class="tjn-heading tjn-heading--center">
          <p class="tjn-kicker tjn-reveal">Commande s&eacute;curis&eacute;e</p>
          <h2 id="tjn-checkout-title" class="tjn-reveal">Finaliser votre commande.</h2>
          <p class="tjn-reveal">Remplissez le formulaire. Nous vous appelons pour confirmer chaque exp&eacute;dition.</p>
        </header>

        <div class="tjn-checkout__grid">
          <article class="tjn-checkout__product tjn-reveal">
            <div class="tjn-checkout__image">
              <img src="/wp-content/uploads/2026/06/tajinoos-hero-product.webp" width="1024" height="1024" alt="Tajine artisanal Tajinoos Premium" loading="lazy" decoding="async">
            </div>
            <div class="tjn-checkout__product-copy">
              <p class="tjn-kicker">Votre commande</p>
              <h3>Tajinoos Premium</h3>
              <p>Terre cuite artisanale, cuisson lente, format familial.</p>
              <ul><li>Fait main au Maroc</li><li>Argile naturelle</li><li>Paiement &agrave; la livraison</li><li>Garantie 7 jours</li></ul>
            </div>
          </article>

          <form class="tjn-form tjn-reveal" action="{$form_action}" method="post" data-tjn-order-form novalidate>
            {$nonce_field}
            <input type="hidden" name="action" value="tajinoos_submit_order">
            <input type="hidden" name="Source" value="{$source_url}">
            <input type="hidden" name="Prix_unitaire" value="{$price}">
            <input type="hidden" name="Total" value="{$price}" data-tjn-total-input>

            <div class="tjn-form__heading">
              <span>01</span><div><h3>Vos informations</h3><p>Tous les champs marqu&eacute;s * sont n&eacute;cessaires.</p></div>
            </div>
            {$order_notice}

            <label>Nom complet *<input name="Nom" required type="text" autocomplete="name" placeholder="Ex. Ahmed Alaoui"></label>
            <label>T&eacute;l&eacute;phone / WhatsApp *<input name="Telephone" required type="tel" inputmode="tel" autocomplete="tel" placeholder="06 XX XX XX XX" aria-describedby="tjn-phone-help"><small id="tjn-phone-help">Num&eacute;ro marocain local ou avec l&rsquo;indicatif +212.</small></label>
            <label>Ville et adresse de livraison *<input name="Adresse" required type="text" autocomplete="street-address" placeholder="Ville, quartier et adresse"></label>

            <div class="tjn-form__row">
              <label>Quantit&eacute;<select name="Quantite" data-tjn-quantity><option value="1">1 pi&egrave;ce</option><option value="2">2 pi&egrave;ces</option><option value="3">3 pi&egrave;ces</option><option value="4">4 pi&egrave;ces</option><option value="5">5 pi&egrave;ces</option></select></label>
              <label>Mod&egrave;le<select name="Produit"><option value="Tajine artisanal Tajinoos Premium">Tajinoos Premium</option></select></label>
            </div>

            <label>Message <small>(optionnel)</small><textarea name="Message" rows="3" placeholder="Une pr&eacute;cision pour la livraison ?"></textarea></label>

            <div class="tjn-form__total"><span><strong>Total &agrave; payer</strong><small>Paiement &agrave; la livraison</small></span><strong><span data-tjn-total>{$price}</span> MAD</strong></div>
            <p class="tjn-form__next">Apr&egrave;s l&rsquo;envoi, nous vous appelons pour confirmer votre commande et votre adresse.</p>
            <button class="tjn-button tjn-button--primary tjn-form__submit" type="submit">Confirmer ma commande &mdash; <span data-tjn-submit-total>{$price}</span> MAD <span aria-hidden="true">&rarr;</span></button>
            <p class="tjn-form__reassurance"><span>Paiement &agrave; la livraison</span><span>Confirmation humaine</span><span>Garantie 7 jours</span></p>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer class="tjn-footer">
    <div class="tjn-container tjn-footer__grid">
      <div><a class="tjn-brand tjn-brand--light" href="#accueil">TAJINOOS</a><p>L&rsquo;artisanat marocain pens&eacute; pour les tables d&rsquo;aujourd&rsquo;hui.</p></div>
      <nav aria-label="Navigation de pied de page"><strong>Navigation</strong><a href="#accueil">Accueil</a><a href="#heritage">H&eacute;ritage</a><a href="#savoir-faire">Processus</a><a href="#produit">Produit</a></nav>
      <div><strong>Ressources</strong><a href="#faq">Questions fr&eacute;quentes</a><a href="#avis">Avis clients</a><a href="#commande">Commander</a></div>
      <div><strong>Contact</strong><a href="https://wa.me/212627424509" target="_blank" rel="noopener noreferrer">WhatsApp</a><a href="mailto:orders@tajinoos.com">orders@tajinoos.com</a><span>Livraison partout au Maroc</span></div>
    </div>
    <div class="tjn-container tjn-footer__bottom"><span>&copy; Tajinoos. Artisanat marocain.</span><span>Paiement &agrave; la livraison</span></div>
  </footer>

  <a class="tjn-whatsapp" href="https://wa.me/212627424509?text=Bonjour%2C%20je%20suis%20int%C3%A9ress%C3%A9%20par%20le%20Tajinoos%20Premium." target="_blank" rel="noopener noreferrer" aria-label="Contacter Tajinoos sur WhatsApp">WA</a>
  <a class="tjn-mobile-cta" href="#commande"><span>Commander</span><strong>{$price} MAD</strong></a>
</div>
HTML;
}
