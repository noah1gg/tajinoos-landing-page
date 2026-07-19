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


\## Order notifications



Orders submitted from the Tajinoos form are stored privately in WordPress as
`tajinoos_order` posts. The server recalculates the total from the validated
quantity and the 390 MAD unit price.



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

