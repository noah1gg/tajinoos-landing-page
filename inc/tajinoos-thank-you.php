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
    $summary_markup = '';

    if ($reference !== '') {
        $reference_markup = sprintf(
            '<div class="taj-thanks-v2__reference">
                <span class="taj-thanks-v2__reference-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24"><path d="M7 3.5h10a2 2 0 0 1 2 2v15l-3-1.8-2 1.8-2-1.8-2 1.8-2-1.8-3 1.8v-15a2 2 0 0 1 2-2Z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>
                </span>
                <span class="taj-thanks-v2__reference-copy">
                  <span>Référence de commande</span>
                  <strong>%s</strong>
                </span>
            </div>',
            esc_html($reference)
        );

        $order_summary = tajinoos_get_order_summary_by_reference($reference);

        if (!empty($order_summary) && $order_summary['final_total'] !== null) {
            $delivery_fee = $order_summary['delivery_fee'];
            $delivery_city = (string) $order_summary['delivery_city'];
            $delivery_label = $delivery_fee === null
                ? 'À confirmer'
                : ($delivery_fee === 0 ? 'Gratuite' : $delivery_fee . ' MAD');
            $delivery_delay = $delivery_fee === null
                ? 'À confirmer'
                : ($delivery_fee === 0 ? 'Sous 24 heures maximum' : '3 à 6 jours ouvrables');

            $summary_markup = sprintf(
                '<div class="taj-thanks-v2__order-summary" aria-label="Récapitulatif de livraison">
                  <div><span>Total à payer</span><strong>%s MAD</strong></div>
                  <div><span>Livraison%s</span><strong>%s</strong></div>
                  <div><span>Délai estimé</span><strong>%s</strong></div>
                </div>',
                esc_html((string) $order_summary['final_total']),
                $delivery_city !== '' ? ' — ' . esc_html($delivery_city) : '',
                esc_html($delivery_label),
                esc_html($delivery_delay)
            );
        }
    }

    $whatsapp_url = esc_url(
        'https://wa.me/212627424509?text=' .
        rawurlencode('Bonjour, je souhaite contacter l’équipe Tajinoos au sujet de ma commande.')
    );

    $advice_url = esc_url(home_url('/#faq'));
    $product_image = esc_url(home_url('/wp-content/uploads/2026/06/tajinoos-hero-product.webp'));
    $pattern_image = esc_url(home_url('/wp-content/uploads/2026/06/tajinoos-pattern-bg.webp'));
    $logo_image = esc_url(home_url('/wp-content/uploads/2026/06/tajinoos-logo-icon.webp'));

    return <<<HTML
<div class="taj-thanks-v2">
  <header class="taj-thanks-v2__brand-header">
    <a class="taj-thanks-v2__brand" href="{$home_url}" aria-label="Tajinoos — retour à l’accueil">
      <span class="taj-thanks-v2__brand-symbol" aria-hidden="true">
        <img src="{$logo_image}" alt="" width="768" height="1152" decoding="async">
      </span>
      <span class="taj-thanks-v2__brand-copy">
        <span class="taj-thanks-v2__wordmark">Tajinoos</span>
        <span>L’art de vivre marocain</span>
      </span>
    </a>
  </header>

  <main class="taj-thanks-v2__main">
    <section class="taj-thanks-v2__hero" aria-labelledby="taj-thanks-title">
      <div class="taj-thanks-v2__hero-copy">
        <span class="taj-thanks-v2__eyebrow">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16.5 8.5"/></svg>
          Commande bien reçue
        </span>
        <h1 id="taj-thanks-title">Merci pour<br>votre <em>confiance.</em></h1>
        <p class="taj-thanks-v2__lede">Votre commande a bien été enregistrée.</p>
        <p class="taj-thanks-v2__intro">Notre équipe vous contactera sur WhatsApp dans moins de 24 heures afin de confirmer vos informations et préparer la livraison.</p>

        {$reference_markup}
        {$summary_markup}

        <div class="taj-thanks-v2__actions">
          <a class="taj-thanks-v2__button taj-thanks-v2__button--primary" href="{$home_url}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m10 6-6 6 6 6M4 12h16"/></svg>
            Retour à l’accueil
          </a>
          <a class="taj-thanks-v2__button taj-thanks-v2__button--secondary" href="{$whatsapp_url}" target="_blank" rel="noopener noreferrer">
            <svg class="taj-icon--whatsapp" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.198-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.208-.242-.58-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479s1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.693.625.712.226 1.36.194 1.871.118.57-.085 1.758-.719 2.006-1.413.248-.694.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.981.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.81 9.81 0 0 1 7.021 2.91 9.82 9.82 0 0 1 2.897 7.027c-.003 5.45-4.436 9.88-9.922 9.88m8.413-18.297A11.815 11.815 0 0 0 12.055 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.14 1.588 5.945L.057 24l6.305-1.654a11.88 11.88 0 0 0 5.683 1.448h.005c6.557 0 11.892-5.335 11.895-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
            Nous contacter sur WhatsApp
          </a>
        </div>
        <p class="taj-thanks-v2__microcopy">
          <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 5h4M11 18.5h2"/></svg>
          Gardez votre téléphone disponible pour la confirmation.
        </p>
      </div>

      <figure class="taj-thanks-v2__showcase">
        <img class="taj-thanks-v2__pattern" src="{$pattern_image}" alt="" aria-hidden="true" decoding="async">
        <span class="taj-thanks-v2__showcase-arch" aria-hidden="true"></span>
        <span class="taj-thanks-v2__success-seal">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m7.5 12.2 3 3L17 8.7"/></svg>
          <span>Commande<br><strong>enregistrée</strong></span>
        </span>
        <img class="taj-thanks-v2__product" src="{$product_image}" alt="Tajine artisanal marocain Tajinoos" width="1024" height="1024" fetchpriority="high" decoding="async">
        <figcaption>
          <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.1 5.2 5.6.4-4.3 3.6 1.4 5.5-4.8-3-4.8 3 1.4-5.5-4.3-3.6 5.6-.4L12 3Z"/></svg>Fait main au Maroc</span>
          <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 4 1.7 4.3L18 10l-4.3 1.7L12 16l-1.7-4.3L6 10l4.3-1.7L12 4Z"/></svg>Préparé avec soin</span>
        </figcaption>
      </figure>
    </section>

    <section class="taj-thanks-v2__journey" aria-labelledby="taj-thanks-journey-title">
      <h2 id="taj-thanks-journey-title">Votre commande, étape par étape</h2>
      <ol class="taj-thanks-v2__steps">
        <li class="is-complete">
          <span class="taj-thanks-v2__step-number">1</span>
          <span class="taj-thanks-v2__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5.5" y="5.5" width="13" height="15" rx="2"/><path d="M9 5.5V4h6v1.5M8.5 12l2.2 2.2 4.8-5"/></svg></span>
          <div><h3>Commande enregistrée</h3><p>Nous avons bien reçu vos informations.</p><span class="screen-reader-text">Étape terminée</span></div>
        </li>
        <li>
          <span class="taj-thanks-v2__step-number">2</span>
          <span class="taj-thanks-v2__step-icon taj-thanks-v2__step-icon--whatsapp" aria-hidden="true"><svg class="taj-icon--whatsapp" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.198-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.208-.242-.58-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479s1.065 2.875 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.693.625.712.226 1.36.194 1.871.118.57-.085 1.758-.719 2.006-1.413.248-.694.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.981.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.81 9.81 0 0 1 7.021 2.91 9.82 9.82 0 0 1 2.897 7.027c-.003 5.45-4.436 9.88-9.922 9.88m8.413-18.297A11.815 11.815 0 0 0 12.055 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.14 1.588 5.945L.057 24l6.305-1.654a11.88 11.88 0 0 0 5.683 1.448h.005c6.557 0 11.892-5.335 11.895-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg></span>
          <div><h3>Confirmation WhatsApp</h3><p>Notre équipe vous contacte sous 24h.</p></div>
        </li>
        <li>
          <span class="taj-thanks-v2__step-number">3</span>
          <span class="taj-thanks-v2__step-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 9h4l3 4v3h-7z"/><circle cx="6.5" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/></svg></span>
          <div><h3>Livraison à domicile</h3><p>Vous payez uniquement à la réception.</p></div>
        </li>
      </ol>
    </section>

    <section class="taj-thanks-v2__reassurance" aria-label="Les garanties Tajinoos">
      <article><span class="taj-thanks-v2__trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7.5h14.5A2.5 2.5 0 0 1 21 10v7a2.5 2.5 0 0 1-2.5 2.5h-12A2.5 2.5 0 0 1 4 17V7.5Z"/><path d="M4 8l10.5-3.5A2 2 0 0 1 17 6.4V8M16 13.5h5"/></svg></span><div><h3>Paiement à la livraison</h3><p>Payez uniquement à la réception.</p></div></article>
      <article><span class="taj-thanks-v2__trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 6h11v10H3zM14 9h4l3 4v3h-7z"/><circle cx="6.5" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/></svg></span><div><h3>Livraison partout au Maroc</h3><p>Gratuite à Marrakech, 20 MAD dans les autres villes.</p></div></article>
      <article><span class="taj-thanks-v2__trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 10V8a5 5 0 0 1 10 0v2M5 11.5h2.5v6H5a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2ZM19 11.5h-2.5v6H19a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2ZM16.5 17.5c0 2-1.3 3-3.5 3"/></svg></span><div><h3>Confirmation humaine</h3><p>Notre équipe vous accompagne.</p></div></article>
      <article><span class="taj-thanks-v2__trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3.5 19 7v5c0 4.5-2.8 7.4-7 8.5-4.2-1.1-7-4-7-8.5V7l7-3.5Z"/><path d="m8.8 12 2.1 2.2 4.5-4.7"/></svg></span><div><h3>Garantie 7 jours</h3><p>Selon les conditions détaillées dans la FAQ.</p></div></article>
    </section>

    <section class="taj-thanks-v2__advice" aria-labelledby="taj-thanks-advice-title">
      <span class="taj-thanks-v2__advice-icon" aria-hidden="true">
        <img src="{$product_image}" alt="" width="1024" height="1024" loading="lazy" decoding="async">
      </span>
      <div>
        <h2 id="taj-thanks-advice-title">En attendant votre confirmation…</h2>
        <p>Découvrez comment entretenir votre tajine artisanal pour préserver sa beauté et sa cuisson naturelle.</p>
      </div>
      <a href="{$advice_url}">Voir les conseils d’utilisation <span aria-hidden="true">→</span></a>
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
