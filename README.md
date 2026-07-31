\# Tajinoos Landing Page



Premium WordPress landing page for Tajinoos, a Moroccan handmade tajine cookware brand.



\## Project Type



WordPress child theme customization.



\## Features



\- Premium Moroccan landing page design

\- Responsive layout

\- Product showcase

\- Order form section

\- Dynamic quantity and price update

\- WhatsApp floating button

\- Thank You page flow

\- Admin email notification setup



\## Main Files



\- `functions.php`

\- `assets/css/tajinoos-premium.css`

\- `assets/js/tajinoos-premium.js`



\## Notes



This repository contains the custom child theme only, not the full WordPress installation.


\## French/English localization


French is the canonical default at `/`. English uses the explicit cache-safe
URL `/?lang=en`. The server accepts only `fr` and `en`, with resolution in this
order: an explicit `lang` query value, the `tajinoos_language` preference
cookie, then French. Invalid explicit values always fall back to French.


Translations live in `inc/languages/fr.php` and `inc/languages/en.php`; the
resolver and helpers live in `inc/tajinoos-i18n.php`. The page is translated
while WordPress renders it. JavaScript does not scan or replace page copy. New
orders store `_tajinoos_order_language`, and the secure receipt page uses that
stored value so its language cannot drift from the order.


The language preference cookie contains only `fr` or `en`, is scoped to `/`,
uses `SameSite=Lax`, is Secure on HTTPS, and expires after one year. English
cookie visits to the clean landing URL are redirected to `/?lang=en` before
rendering.


\### Cache/CDN requirement


Page caching must vary the landing page by the full query string and must keep
`/?lang=en` separate from `/`. Do not configure a CDN to ignore the `lang`
query parameter. The theme emits `Vary: Cookie` and prefers an explicit English
URL; nevertheless, a cache that serves one query variant for every URL will mix
languages and is unsupported. Purge both variants after changing translations.


\## Order notifications



Orders submitted from the Tajinoos form are stored privately in WordPress as
`tajinoos_order` posts. The server recalculates the total from the validated
quantity, the 249 MAD unit price, and a delivery fee applied once per order:
0 MAD for Marrakech or 20 MAD for other Moroccan cities.



Email is sent to the WordPress Administration Email configured under
**Settings > General**. Email or WhatsApp delivery failure never deletes an
order and never blocks the customer confirmation page.



To enable the official Meta WhatsApp Cloud API, add these constants to
`wp-config.php` above the "stop editing" line:



```php
define('TAJINOOS_WA_ENABLED', false);
define('TAJINOOS_WA_PHONE_NUMBER_ID', '');
define('TAJINOOS_WA_ACCESS_TOKEN', '');
define('TAJINOOS_WA_OWNER_NUMBER', '212XXXXXXXXX');
define('TAJINOOS_WA_API_VERSION', 'vXX.X');

// Optional approved-template mode:
define('TAJINOOS_WA_TEMPLATE_NAME', '');
define('TAJINOOS_WA_TEMPLATE_LANGUAGE', 'fr');
```



Set `TAJINOOS_WA_ENABLED` to `true` only after the phone-number ID, permanent
access token, owner number, and current Graph API version are configured.
Secrets must remain in `wp-config.php`; never place them in the theme.



Free-form text mode is used when `TAJINOOS_WA_TEMPLATE_NAME` is empty. To use
an approved Meta template, set that constant to the exact approved template
name. The template body must define 10 variables in this order: reference,
client, phone, address, product, quantity, unit price, total, customer message,
and submission date. `TAJINOOS_WA_TEMPLATE_LANGUAGE` must match the approved
template language code.

