<?php
/**
 * Branded Tajinoos thank-you page.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('tajinoos_thank_you', 'tajinoos_child_render_thank_you_shortcode');
add_filter('wp_robots', 'tajinoos_child_thank_you_robots');
add_action('send_headers', 'tajinoos_child_thank_you_noindex_header');

function tajinoos_child_render_thank_you_shortcode(): string
{
    $home_url = esc_url(home_url('/'));
    $reference = tajinoos_get_recent_order_reference();
    $reference_markup = '';

    if ($reference !== '') {
        $reference_markup = sprintf(
            '<div class="taj-thanks-page__reference"><span>RÉFÉRENCE DE COMMANDE</span><strong>%s</strong></div>',
            esc_html($reference)
        );
    }

    $whatsapp_url = esc_url(
        'https://wa.me/212627424509?text=' .
        rawurlencode('Bonjour, je souhaite contacter l’équipe Tajinoos au sujet de ma commande.')
    );

    $advice_url = esc_url(home_url('/#faq'));
    $product_image = esc_url(home_url('/wp-content/uploads/2026/06/tajinoos-hero-product.webp'));
    $pattern_image = esc_url(home_url('/wp-content/uploads/2026/06/tajinoos-pattern-bg.webp'));

    return <<<HTML
<div class="taj-thanks-page">
  <main class="taj-thanks-page__main">
    <section class="taj-thanks-page__hero" aria-labelledby="taj-thanks-title">
      <div class="taj-thanks-page__hero-copy">
        <span class="taj-thanks-page__eyebrow">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 12 3.2 3.2L17.5 8"/><circle cx="12" cy="12" r="9"/></svg>
          COMMANDE BIEN REÇUE
        </span>
        <h1 id="taj-thanks-title">MERCI POUR VOTRE <em>CONFIANCE.</em></h1>
        <h2>Votre Tajinoos est presque en route.</h2>
        <p>Votre commande a bien été enregistrée. Notre équipe vous contactera sur WhatsApp dans moins de 24 heures afin de confirmer vos informations et préparer la livraison.</p>

        {$reference_markup}

        <div class="taj-thanks-page__actions">
          <a class="taj-thanks-page__button taj-thanks-page__button--primary" href="{$home_url}">RETOUR À L’ACCUEIL</a>
          <a class="taj-thanks-page__button taj-thanks-page__button--secondary" href="{$whatsapp_url}" target="_blank" rel="noopener noreferrer">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.9 11.9 0 0 0 1.8 17.9L.2 23.8l6-1.6a11.9 11.9 0 0 0 5.7 1.5h.1A11.9 11.9 0 0 0 20.5 3.5Z"/><path d="M8.2 7.1c.2-.4.4-.4.7-.4h.5c.2 0 .4 0 .6.5l.8 2c.1.3.1.5-.1.7l-.6.8c-.2.2-.3.4-.1.7.8 1.5 1.9 2.7 3.4 3.5.3.2.5.1.7-.1l.9-1.1c.2-.3.5-.3.8-.2l2.1 1c.3.2.5.2.5.4.1.2.1 1.1-.3 2.1-.4.9-1.8 1.7-2.7 1.8-.7.1-1.7.2-4.9-1.2-4.1-1.8-6.7-6-6.9-6.3-.2-.3-1.7-2.3-1.6-4.4.1-2 .9-3 1.3-3.4"/></svg>
            NOUS CONTACTER SUR WHATSAPP
          </a>
        </div>
        <p class="taj-thanks-page__microcopy">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 5h4M11 18.5h2"/></svg>
          Gardez votre téléphone disponible pour la confirmation.
        </p>
      </div>

      <figure class="taj-thanks-page__visual">
        <img class="taj-thanks-page__pattern" src="{$pattern_image}" alt="" aria-hidden="true" decoding="async">
        <span class="taj-thanks-page__success-seal" aria-label="Commande enregistrée">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6.8 12.2 3.2 3.2 7.5-7.5"/><circle cx="12" cy="12" r="9.5"/></svg>
          <span><strong>COMMANDE</strong> ENREGISTRÉE</span>
        </span>
        <img class="taj-thanks-page__product" src="{$product_image}" alt="Tajine artisanal marocain Tajinoos" width="1024" height="1024" fetchpriority="high" decoding="async">
        <figcaption>Fait main au Maroc <span aria-hidden="true">•</span> Préparé avec soin</figcaption>
      </figure>
    </section>

    <section class="taj-thanks-page__journey" aria-labelledby="taj-thanks-journey-title">
      <header>
        <span>ET MAINTENANT ?</span>
        <h2 id="taj-thanks-journey-title">Votre commande, étape par étape.</h2>
      </header>
      <ol>
        <li class="is-complete">
          <span class="taj-thanks-page__step-number">1</span>
          <span class="taj-thanks-page__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12.5 9.2 17 19 7"/></svg></span>
          <div><strong>Commande enregistrée</strong><p>Nous avons bien reçu vos informations.</p></div>
        </li>
        <li>
          <span class="taj-thanks-page__step-number">2</span>
          <span class="taj-thanks-page__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 5h12a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3h-4l-5 4v-4H6a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3Z"/><path d="M8 9.5h8M8 13h5"/></svg></span>
          <div><strong>Confirmation WhatsApp</strong><p>Notre équipe vous contacte sous 24h.</p></div>
        </li>
        <li>
          <span class="taj-thanks-page__step-number">3</span>
          <span class="taj-thanks-page__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 9h4l3 4v3h-7z"/><circle cx="6.5" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/></svg></span>
          <div><strong>Livraison à domicile</strong><p>Vous payez uniquement à la réception.</p></div>
        </li>
      </ol>
    </section>

    <section class="taj-thanks-page__reassurance" aria-label="Les garanties Tajinoos">
      <div><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7.5h14.5A2.5 2.5 0 0 1 21 10v7a2.5 2.5 0 0 1-2.5 2.5h-12A2.5 2.5 0 0 1 4 17V7.5Z"/><path d="M4 8l10.5-3.5A2 2 0 0 1 17 6.4V8M16 13.5h5"/></svg></span><strong>Paiement à la livraison</strong></div>
      <div><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 9h4l3 4v3h-7z"/><circle cx="6.5" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/></svg></span><strong>Livraison partout au Maroc</strong></div>
      <div><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 5h12a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3h-4l-5 4v-4H6a3 3 0 0 1-3-3V8a3 3 0 0 1 3-3Z"/><path d="M8 10h8M8 13h5"/></svg></span><strong>Confirmation humaine</strong></div>
      <div><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.5 19 7v5c0 4.5-2.8 7.4-7 8.5-4.2-1.1-7-4-7-8.5V7l7-3.5Z"/><path d="m8.8 12 2.1 2.2 4.5-4.7"/></svg></span><strong>Garantie 7 jours</strong></div>
    </section>

    <section class="taj-thanks-page__advice" aria-labelledby="taj-thanks-advice-title">
      <span class="taj-thanks-page__advice-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 10h12l-1 9H7l-1-9ZM4 10h16M9 10V8a3 3 0 0 1 6 0v2"/><path d="M9 5.5c-.8-1-.7-2 .2-3M13 5.5c-.8-1-.7-2 .2-3"/></svg></span>
      <div>
        <h2 id="taj-thanks-advice-title">En attendant votre confirmation…</h2>
        <p>Découvrez comment entretenir votre tajine artisanal pour préserver sa beauté et sa cuisson naturelle.</p>
      </div>
      <a href="{$advice_url}">VOIR LES CONSEILS D’UTILISATION <span aria-hidden="true">→</span></a>
    </section>
  </main>

</div>
HTML;
}

/**
 * Keep the confirmation page out of search results.
 *
 * @param array<string, bool> $robots
 * @return array<string, bool>
 */
function tajinoos_child_thank_you_robots(array $robots): array
{
    if (is_page('merci')) {
        $robots['noindex'] = true;
        $robots['nofollow'] = true;
    }

    return $robots;
}

function tajinoos_child_thank_you_noindex_header(): void
{
    if (is_page('merci') && !headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow', true);
    }
}
