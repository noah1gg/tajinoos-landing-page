(function () {
  'use strict';

  var hero = document.querySelector('#accueil.taj-clean-hero, #accueil.taj-final-hero');

  if (!hero || document.body.classList.contains('elementor-editor-active') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  var frame = null;
  var targetX = 0;
  var targetY = 0;

  function update() {
    hero.style.setProperty('--taj-hero-parallax-x', (targetX * 4).toFixed(2) + 'px');
    hero.style.setProperty('--taj-hero-parallax-y', (targetY * 3).toFixed(2) + 'px');
    hero.style.setProperty('--taj-product-parallax-x', (targetX * 7).toFixed(2) + 'px');
    hero.style.setProperty('--taj-product-parallax-y', (targetY * 5).toFixed(2) + 'px');
    frame = null;
  }

  hero.addEventListener('pointermove', function (event) {
    if (event.pointerType === 'touch') {
      return;
    }

    var rect = hero.getBoundingClientRect();
    targetX = (event.clientX - rect.left) / rect.width - 0.5;
    targetY = (event.clientY - rect.top) / rect.height - 0.5;

    if (!frame) {
      frame = window.requestAnimationFrame(update);
    }
  }, { passive: true });

  hero.addEventListener('pointerleave', function () {
    targetX = 0;
    targetY = 0;

    if (!frame) {
      frame = window.requestAnimationFrame(update);
    }
  }, { passive: true });
})();

(function () {
  'use strict';

  var order = document.querySelector('#commande.tajcmd');

  if (!order || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var configuredUnitPrice = window.tajinoosOrder ? parseInt(window.tajinoosOrder.unitPrice, 10) : 390;
  var unitPrice = Number.isNaN(configuredUnitPrice) ? 390 : configuredUnitPrice;
  var minQuantity = 1;
  var maxQuantity = 5;
  var quantity = minQuantity;
  var quantityText = order.querySelector('[data-tajcmd-quantity]');
  var quantitySelect = order.querySelector('[data-tajcmd-quantity-select]');
  var totalText = order.querySelector('[data-tajcmd-total]');
  var formTotalText = order.querySelector('[data-tajcmd-form-total]');
  var ctaTotalText = order.querySelector('[data-tajcmd-cta-total]');
  var totalInput = order.querySelector('[data-tajcmd-total-input]');
  var buttons = order.querySelectorAll('[data-tajcmd-qty]');

  function clampQuantity(value) {
    var parsed = parseInt(value, 10);

    if (Number.isNaN(parsed)) {
      parsed = minQuantity;
    }

    return Math.max(minQuantity, Math.min(maxQuantity, parsed));
  }

  function formatPrice(value) {
    return String(value);
  }

  function syncOrder(nextQuantity) {
    quantity = clampQuantity(nextQuantity);

    var total = quantity * unitPrice;
    var totalLabel = formatPrice(total);

    if (quantityText) {
      quantityText.textContent = String(quantity);
    }

    if (quantitySelect && quantitySelect.value !== String(quantity)) {
      quantitySelect.value = String(quantity);
    }

    if (totalText) {
      totalText.textContent = totalLabel;
    }

    if (formTotalText) {
      formTotalText.textContent = totalLabel;
    }

    if (ctaTotalText) {
      ctaTotalText.textContent = totalLabel;
    }

    if (totalInput) {
      totalInput.value = totalLabel;
    }

    buttons.forEach(function (button) {
      var action = button.getAttribute('data-tajcmd-qty');

      button.disabled = (action === 'minus' && quantity <= minQuantity) || (action === 'plus' && quantity >= maxQuantity);
      button.setAttribute('aria-disabled', button.disabled ? 'true' : 'false');
    });
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      var action = button.getAttribute('data-tajcmd-qty');
      var nextQuantity = action === 'plus' ? quantity + 1 : quantity - 1;

      button.classList.add('is-active');
      window.setTimeout(function () {
        button.classList.remove('is-active');
      }, 160);

      syncOrder(nextQuantity);
    });
  });

  if (quantitySelect) {
    quantitySelect.addEventListener('change', function () {
      syncOrder(quantitySelect.value);
    });
  }

  syncOrder(quantitySelect ? quantitySelect.value : quantity);
})();

(function () {
  'use strict';

  var form = document.querySelector('#commande.tajcmd .tajcmd-form');

  if (!form || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var submitButton = form.querySelector('.tajcmd-submit');
  var messageBox = form.querySelector('[data-tajcmd-messages]');
  var originalButtonHtml = submitButton ? submitButton.innerHTML : '';
  var isSubmitting = false;

  function showError(message, fieldName) {
    if (messageBox) {
      messageBox.textContent = message;
      messageBox.hidden = false;
      messageBox.classList.add('is-visible');
      messageBox.focus({ preventScroll: true });
    }

    if (fieldName) {
      var field = form.elements.namedItem(fieldName);

      if (field && typeof field.focus === 'function') {
        field.setAttribute('aria-invalid', 'true');
        field.focus();
      }
    }
  }

  function clearError() {
    form.querySelectorAll('[aria-invalid="true"]').forEach(function (field) {
      field.removeAttribute('aria-invalid');
    });

    if (messageBox) {
      messageBox.textContent = '';
      messageBox.hidden = true;
      messageBox.classList.remove('is-visible');
    }
  }

  function setSubmitting(nextState) {
    isSubmitting = nextState;
    form.setAttribute('aria-busy', nextState ? 'true' : 'false');

    if (!submitButton) {
      return;
    }

    submitButton.disabled = nextState;
    submitButton.classList.toggle('is-submitting', nextState);
    submitButton.innerHTML = nextState
      ? '<span class="tajcmd-submit__spinner" aria-hidden="true"></span>' +
        (window.tajinoosOrder ? window.tajinoosOrder.processingLabel : 'Traitement de votre commande…')
      : originalButtonHtml;
  }

  form.addEventListener('submit', function (event) {
    clearError();

    if (isSubmitting) {
      event.preventDefault();
      return;
    }

    if (!form.checkValidity()) {
      event.preventDefault();
      var firstInvalidField = form.querySelector(':invalid');
      showError(
        'Veuillez vérifier les champs indiqués avant de continuer.',
        firstInvalidField ? firstInvalidField.name : ''
      );
      return;
    }

    if (!window.fetch || !window.tajinoosOrder || !window.tajinoosOrder.ajaxUrl) {
      setSubmitting(true);
      return;
    }

    event.preventDefault();
    setSubmitting(true);

    var payload = new FormData(form);

    window.fetch(window.tajinoosOrder.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: payload
    })
      .then(function (response) {
        return response.json().catch(function () {
          throw new Error('invalid_json');
        }).then(function (json) {
          return { ok: response.ok, json: json };
        });
      })
      .then(function (result) {
        if (result.ok && result.json.success && result.json.data && result.json.data.redirect) {
          window.location.assign(result.json.data.redirect);
          return;
        }

        var data = result.json && result.json.data ? result.json.data : {};
        showError(
          data.message || window.tajinoosOrder.genericError,
          data.field || ''
        );
        setSubmitting(false);
      })
      .catch(function () {
        showError(window.tajinoosOrder.networkError || window.tajinoosOrder.genericError, '');
        setSubmitting(false);
      });
  });
})();

(function () {
  'use strict';

  var faq = document.querySelector('#faq.taj-final-faq');

  if (!faq || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  faq.querySelectorAll('details').forEach(function (detail) {
    var summary = detail.querySelector('summary');

    if (!summary) {
      return;
    }

    function syncExpandedState() {
      summary.setAttribute('aria-expanded', detail.open ? 'true' : 'false');
    }

    syncExpandedState();
    detail.addEventListener('toggle', syncExpandedState);
  });
})();

(function () {
  'use strict';

  var section = document.querySelector('#avis.tajx-testimonials');
  var track = section ? section.querySelector('.tajx-reviews-track') : null;

  if (!track || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var mobileQuery = window.matchMedia('(max-width: 1023px)');
  var initialized = false;

  function initializeMobileReviews() {
    if (!mobileQuery.matches || initialized) {
      return;
    }

    var cards = Array.prototype.slice.call(track.querySelectorAll('.tajx-review-card:not([aria-hidden="true"])'));

    if (cards.length < 2) {
      return;
    }

    initialized = true;
    var dots = document.createElement('div');
    dots.className = 'tajx-review-dots';
    dots.setAttribute('aria-label', 'Choisir un avis client');

    cards.forEach(function (card, index) {
      var dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'tajx-review-dot';
      dot.setAttribute('aria-label', 'Afficher l\u2019avis ' + (index + 1));
      dot.setAttribute('aria-current', index === 0 ? 'true' : 'false');
      dot.addEventListener('click', function () {
        card.scrollIntoView({
          behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
          block: 'nearest',
          inline: 'center'
        });
      });
      dots.appendChild(dot);
    });

    section.querySelector('.tajx-testimonials-showcase').appendChild(dots);

    var ticking = false;

    function updateDots() {
      ticking = false;
      var trackRect = track.getBoundingClientRect();
      var trackCenter = trackRect.left + trackRect.width / 2;
      var activeIndex = 0;
      var smallestDistance = Infinity;

      cards.forEach(function (card, index) {
        var rect = card.getBoundingClientRect();
        var distance = Math.abs(rect.left + rect.width / 2 - trackCenter);

        if (distance < smallestDistance) {
          smallestDistance = distance;
          activeIndex = index;
        }
      });

      dots.querySelectorAll('.tajx-review-dot').forEach(function (dot, index) {
        dot.setAttribute('aria-current', index === activeIndex ? 'true' : 'false');
      });
    }

    track.addEventListener('scroll', function () {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(updateDots);
      }
    }, { passive: true });

    updateDots();
  }

  initializeMobileReviews();

  if (mobileQuery.addEventListener) {
    mobileQuery.addEventListener('change', initializeMobileReviews);
  }
})();

(function () {
  'use strict';

  var landing = document.querySelector('.tajx, .tajv2-page');
  var order = document.querySelector('#commande');
  var footer = document.querySelector('.tajx-footer');

  if (!landing || !order || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var mobileQuery = window.matchMedia('(max-width: 767px)');
  var orderButton = null;
  var contextObserver = null;
  var contextVisibility = {
    order: false,
    footer: false
  };

  function updateOrderButtonVisibility() {
    if (!orderButton) {
      return;
    }

    orderButton.classList.toggle('is-context-hidden', contextVisibility.order || contextVisibility.footer);
  }

  function removeOrderButton() {
    if (contextObserver) {
      contextObserver.disconnect();
      contextObserver = null;
    }

    if (orderButton && orderButton.parentNode) {
      orderButton.parentNode.removeChild(orderButton);
    }

    orderButton = null;
    contextVisibility.order = false;
    contextVisibility.footer = false;
  }

  function initializeOrderButton() {
    document.querySelectorAll('.taj-mobile-actions').forEach(function (actions) {
      actions.remove();
    });

    if (mobileQuery.matches) {
      document.querySelectorAll('.tajx-mobile-sticky').forEach(function (stickyBar) {
        stickyBar.remove();
      });
    }

    if (!mobileQuery.matches) {
      removeOrderButton();
      return;
    }

    if (orderButton || document.querySelector('.taj-order-float')) {
      return;
    }

    orderButton = document.createElement('a');
    orderButton.className = 'taj-order-float';
    orderButton.href = '#commande';
    orderButton.title = 'Commander';
    orderButton.setAttribute('aria-label', 'Commander mon Tajinoos');
    orderButton.innerHTML = [
      '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">',
      '<path d="M6 8h12l-1 12H7L6 8Z"/>',
      '<path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
      '</svg>'
    ].join('');

    orderButton.addEventListener('click', function (event) {
      event.preventDefault();
      order.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'start'
      });
    });

    document.body.appendChild(orderButton);

    if ('IntersectionObserver' in window) {
      contextObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.target === order) {
            contextVisibility.order = entry.isIntersecting;
          } else if (entry.target === footer) {
            contextVisibility.footer = entry.isIntersecting;
          }
        });

        updateOrderButtonVisibility();
      }, {
        root: null,
        rootMargin: '0px 0px -6% 0px',
        threshold: 0.08
      });

      contextObserver.observe(order);

      if (footer) {
        contextObserver.observe(footer);
      }
    }
  }

  initializeOrderButton();

  if (mobileQuery.addEventListener) {
    mobileQuery.addEventListener('change', initializeOrderButton);
  }

  if (footer && !footer.querySelector('.tajx-footer-mobile-contact')) {
    var existingWhatsApp = document.querySelector('.taj-whatsapp-float, #faq .taj-final-support__wa');
    var emailLink = document.querySelector('#faq .taj-final-support__email');
    var whatsappHref = existingWhatsApp ? existingWhatsApp.href : '';

    if (whatsappHref) {
      var mobileContact = document.createElement('div');
      mobileContact.className = 'tajx-footer-mobile-contact';
      mobileContact.innerHTML = [
        '<strong>Contact</strong>',
        '<a href="' + whatsappHref.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener noreferrer">WhatsApp</a>',
        emailLink ? '<a href="' + emailLink.href.replace(/"/g, '&quot;') + '">Email</a>' : '',
        '<a href="#faq">FAQ</a>'
      ].join('');
      footer.appendChild(mobileContact);
    }
  }
})();

(function () {
  'use strict';

  var page = document.querySelector('.taj-thanks-page');

  if (!page) {
    return;
  }

  var nav = page.querySelector('.taj-thanks-page__nav');
  var toggle = page.querySelector('.taj-thanks-page__menu-toggle');
  var menu = page.querySelector('.taj-thanks-page__menu');

  if (!nav || !toggle || !menu) {
    return;
  }

  function closeMenu() {
    nav.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
  }

  toggle.addEventListener('click', function () {
    var isOpen = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  menu.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeMenu();
      toggle.focus();
    }
  });
})();

(function () {
  'use strict';

  var hero = document.querySelector('#accueil.taj-clean-hero, #accueil.tajx-hero');

  if (!hero || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  hero.querySelectorAll('a[href="#commande"]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      var target = document.querySelector('#commande');

      if (!target) {
        return;
      }

      event.preventDefault();
      target.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'start'
      });

      if (history.pushState) {
        history.pushState(null, '', '#commande');
      }
    });
  });
})();

(function () {
  'use strict';

  var page = document.querySelector('.tajv2-page');

  if (!page || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  document.body.classList.add('has-tajv2-page');

  var revealTargets = page.querySelectorAll([
    '.tajv2-hero-grid > *',
    '.tajv2-strip-grid > *',
    '.tajv2-split > *',
    '.tajv2-value-grid > *',
    '.tajv2-benefit-grid > *',
    '.tajv2-process-grid > *',
    '.tajv2-product-grid > *',
    '.tajv2-testimonial-grid > *',
    '.tajv2-faq .elementor-accordion-item',
    '.tajv2-order-grid > *',
    '.tajv2-footer-grid > *'
  ].join(','));

  if (!revealTargets.length || !('IntersectionObserver' in window)) {
    revealTargets.forEach(function (element) {
      element.classList.add('is-visible');
    });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }

      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, {
    root: null,
    rootMargin: '0px 0px -8% 0px',
    threshold: 0.12
  });

  revealTargets.forEach(function (element, index) {
    element.classList.add('tajv2-reveal');
    element.style.transitionDelay = Math.min(index % 5 * 55, 220) + 'ms';
    observer.observe(element);
  });
})();

(function () {
  'use strict';

  var landing = document.querySelector('.tajx, .tajv2-page');

  if (!landing || document.body.classList.contains('elementor-editor-active') || document.querySelector('.taj-whatsapp-float')) {
    return;
  }

  var href = 'https://wa.me/212627424509?text=' + encodeURIComponent('Bonjour, je suis intéressé par le Tajine Tajinoos Premium.');

  var button = document.createElement('a');
  button.className = 'taj-whatsapp-float';
  button.href = href;
  button.target = '_blank';
  button.rel = 'noopener noreferrer';
  button.setAttribute('aria-label', 'Ouvrir une conversation WhatsApp');
  button.innerHTML = [
    '<span class="taj-whatsapp-float__icon" aria-hidden="true">',
    '<svg viewBox="0 0 24 24" role="img" focusable="false">',
    '<path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.49 0 .15 5.34.15 11.91c0 2.1.55 4.15 1.6 5.96L0 24l6.3-1.65a11.85 11.85 0 0 0 5.76 1.47h.01c6.57 0 11.91-5.34 11.91-11.91 0-3.18-1.24-6.17-3.46-8.43Zm-8.45 18.33h-.01a9.9 9.9 0 0 1-5.05-1.39l-.36-.21-3.74.98 1-3.65-.24-.37a9.88 9.88 0 0 1-1.51-5.26c0-5.45 4.44-9.89 9.91-9.89 2.64 0 5.12 1.03 6.98 2.9a9.8 9.8 0 0 1 2.9 6.98c0 5.46-4.44 9.91-9.88 9.91Zm5.43-7.42c-.3-.15-1.76-.87-2.04-.97-.27-.1-.47-.15-.66.15-.2.3-.76.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.27-.47-2.42-1.49a9.13 9.13 0 0 1-1.68-2.08c-.18-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.66-.5h-.56c-.2 0-.51.07-.78.37-.27.3-1.03 1.01-1.03 2.46s1.05 2.85 1.2 3.05c.15.2 2.05 3.13 4.97 4.39.7.3 1.24.47 1.66.6.7.22 1.34.19 1.84.12.56-.08 1.76-.72 2-1.41.25-.7.25-1.3.18-1.42-.08-.12-.28-.2-.58-.35Z"/>',
    '</svg>',
    '</span>',
    '<span class="taj-whatsapp-float__label">WhatsApp</span>'
  ].join('');

  document.body.appendChild(button);
})();

(function () {
  'use strict';

  var navbar = document.querySelector('.tajx-navbar');

  if (!navbar || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var menu = navbar.querySelector('.tajx-navbar-menu');

  if (!menu) {
    return;
  }

  var toggle = navbar.querySelector('.tajx-navbar-toggle');

  if (!toggle) {
    toggle = document.createElement('button');
    toggle.className = 'tajx-navbar-toggle';
    toggle.type = 'button';
    toggle.setAttribute('aria-label', 'Ouvrir le menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<span aria-hidden="true"></span>';
    navbar.insertBefore(toggle, menu);
  }

  if (!menu.querySelector('a[href="#faq"]')) {
    var faqLink = document.createElement('a');
    var commanderLink = menu.querySelector('.tajx-navbar-cta, a[href="#commande"]');

    faqLink.href = '#faq';
    faqLink.textContent = 'FAQ';
    menu.insertBefore(faqLink, commanderLink || null);
  }

  if (document.querySelector('#artisanat') && !menu.querySelector('a[href="#artisanat"]')) {
    var processLink = document.createElement('a');
    var productLink = menu.querySelector('a[href="#produit"]');

    processLink.href = '#artisanat';
    processLink.textContent = 'Processus';
    processLink.className = 'tajx-navbar-mobile-only';
    menu.insertBefore(processLink, productLink || menu.querySelector('.tajx-navbar-cta') || null);
  }

  menu.id = menu.id || 'tajx-mobile-menu';
  toggle.setAttribute('aria-controls', menu.id);
  var mobileMenuQuery = window.matchMedia('(max-width: 1023px)');
  var menuHome = document.createComment('tajx-navbar-menu-home');
  menu.parentNode.insertBefore(menuHome, menu);

  var closeButton = menu.querySelector('.tajx-navbar-close');

  if (!closeButton) {
    closeButton = document.createElement('button');
    closeButton.className = 'tajx-navbar-close';
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Fermer le menu');
    closeButton.innerHTML = '<span aria-hidden="true"></span>';
    menu.insertBefore(closeButton, menu.firstChild);
  }

  var drawerHeader = menu.querySelector('.tajx-navbar-drawer-header');

  if (!drawerHeader) {
    drawerHeader = document.createElement('div');
    drawerHeader.className = 'tajx-navbar-drawer-header';

    var drawerBrand = document.createElement('a');
    drawerBrand.className = 'tajx-navbar-drawer-brand';
    drawerBrand.href = '#accueil';
    drawerBrand.textContent = 'TAJINOOS';
    drawerBrand.setAttribute('aria-label', 'Tajinoos — Accueil');

    drawerHeader.appendChild(drawerBrand);
    drawerHeader.appendChild(closeButton);
    menu.insertBefore(drawerHeader, menu.firstChild);
  }

  var backdrop = document.querySelector('.tajx-navbar-backdrop');

  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'tajx-navbar-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    document.body.appendChild(backdrop);
  }

  function syncMenuLocation() {
    if (mobileMenuQuery.matches) {
      if (menu.parentNode !== document.body) {
        document.body.appendChild(menu);
      }
      menu.setAttribute('aria-hidden', navbar.classList.contains('is-open') ? 'false' : 'true');
      return;
    }

    if (menuHome.parentNode && menu.parentNode !== menuHome.parentNode) {
      menuHome.parentNode.insertBefore(menu, menuHome.nextSibling);
    }
    menu.removeAttribute('aria-hidden');
  }

  syncMenuLocation();

  function setMenu(open, restoreFocus) {
    var mobile = mobileMenuQuery.matches;
    var shouldOpen = Boolean(open && mobile);

    navbar.classList.toggle('is-open', shouldOpen);
    backdrop.classList.toggle('is-open', shouldOpen);
    document.body.classList.toggle('tajx-mobile-menu-open', shouldOpen);
    toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
    toggle.setAttribute('aria-label', shouldOpen ? 'Fermer le menu' : 'Ouvrir le menu');
    backdrop.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
    menu.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');

    if (shouldOpen) {
      closeButton.focus();
    } else if (restoreFocus) {
      toggle.focus();
    }
  }

  toggle.addEventListener('click', function () {
    setMenu(!navbar.classList.contains('is-open'), false);
  });

  closeButton.addEventListener('click', function () {
    setMenu(false, true);
  });

  backdrop.addEventListener('click', function () {
    setMenu(false, true);
  });

  menu.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', function () {
      setMenu(false, false);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (!navbar.classList.contains('is-open')) {
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      setMenu(false, true);
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    var focusable = Array.prototype.slice.call(menu.querySelectorAll('button:not([disabled]), a[href]'));

    if (!focusable.length) {
      return;
    }

    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  mobileMenuQuery.addEventListener('change', function () {
    setMenu(false, false);
    syncMenuLocation();
  });
})();

(function () {
  'use strict';

  var page = document.querySelector('.tajx');

  if (!page || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var benefitIconTypes = ['heat', 'craft', 'gift', 'trust'];

  page.querySelectorAll('#benefices .tajx-icon').forEach(function (icon, index) {
    icon.textContent = '';
    icon.setAttribute('aria-hidden', 'true');

    if (benefitIconTypes[index]) {
      icon.classList.add('tajx-icon--' + benefitIconTypes[index]);
    }
  });

  page.querySelectorAll('.tajx-quote cite').forEach(function (cite) {
    if (cite.querySelector('.tajx-verified-badge')) {
      return;
    }

    var badge = document.createElement('span');
    badge.className = 'tajx-verified-badge';
    badge.textContent = 'Acheteur v\u00e9rifi\u00e9';
    cite.appendChild(badge);
  });

  var formNote = page.querySelector('.tajx-form .tajx-note');

  if (formNote) {
    formNote.textContent = 'Paiement \u00e0 la livraison \u00b7 Confirmation humaine avant exp\u00e9dition';
  }
})();

(function () {
  'use strict';

  var faq = document.querySelector('#faq.tajx-faq-reference');

  if (!faq || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  faq.querySelectorAll('details').forEach(function (detail) {
    var summary = detail.querySelector('summary');

    if (!summary) {
      return;
    }

    summary.addEventListener('click', function (event) {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !detail.animate) {
        return;
      }

      event.preventDefault();

      if (detail.dataset.animating === 'true') {
        return;
      }

      detail.dataset.animating = 'true';

      var startHeight = detail.offsetHeight;
      var endHeight;

      if (detail.open) {
        endHeight = summary.offsetHeight;
      } else {
        detail.open = true;
        endHeight = detail.offsetHeight;
      }

      var animation = detail.animate({
        height: [startHeight + 'px', endHeight + 'px']
      }, {
        duration: 260,
        easing: 'cubic-bezier(.22, .61, .36, 1)'
      });

      animation.onfinish = function () {
        if (startHeight > endHeight) {
          detail.open = false;
        }

        detail.style.height = '';
        detail.dataset.animating = 'false';
      };

      animation.oncancel = function () {
        detail.style.height = '';
        detail.dataset.animating = 'false';
      };
    });
  });
})();

(function () {
  'use strict';

  var page = document.querySelector('.tajx');

  if (!page || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var targets = page.querySelectorAll([
    '.tajx-strip-grid > *',
    '.tajx-title',
    '.tajx-split > *',
    '.tajx-grid4 > *',
    '.tajx-timeline > *',
    '.tajx-product-grid > *',
    '.tajp-mobile__intro',
    '.tajp-mobile__media',
    '.tajp-mobile__benefit',
    '.tajp-mobile__reassurance',
    '.tajp-mobile__offer',
    '.tajp-mobile__quality',
    '.tajx-quotes > *',
    '.tajx-faq-list > details',
    '.tajx-order-grid > *'
  ].join(','));

  if (!targets.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
    targets.forEach(function (element) {
      element.classList.add('is-visible');
    });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }

      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, {
    rootMargin: '0px 0px -7% 0px',
    threshold: 0.10
  });

  targets.forEach(function (element, index) {
    element.classList.add('tajx-reveal');
    element.style.transitionDelay = Math.min(index % 4 * 55, 165) + 'ms';
    observer.observe(element);
  });
})();

(function () {
  'use strict';

  var page = document.querySelector('.tajx');

  if (!page || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var footer = page.querySelector('.tajx-footer');

  if (!footer) {
    return;
  }

  document.body.classList.add('has-tajx-page');
  page.classList.add('tajx-bottom-premium');
  footer.classList.add('tajx-footer--premium');

  var orderSection = page.querySelector('.tajx-order');

  if (orderSection) {
    orderSection.classList.add('tajx-order--premium');
  }

  var offerCard = page.querySelector('.tajx-offer');
  var offerList = page.querySelector('.tajx-offer-list');

  if (offerCard) {
    offerCard.classList.add('tajx-offer--premium');
  }

  if (offerList && !offerList.querySelector('.tajx-offer-trust-list')) {
    offerList.innerHTML = [
      '<ul class="tajx-offer-trust-list">',
      '<li>Confirmation téléphonique avant expédition</li>',
      '<li>Paiement à la réception</li>',
      '<li>Livraison suivie partout au Maroc</li>',
      '<li>Garantie satisfaction 7 jours</li>',
      '</ul>'
    ].join('');
  }

  if (false && orderSection && !page.querySelector('.tajx-final-trust')) {
    var trustSection = document.createElement('section');
    trustSection.className = 'tajx-final-trust';
    trustSection.setAttribute('aria-label', 'Nos garanties');
    trustSection.innerHTML = [
      '<div class="tajx-final-trust__inner">',
      '<div class="tajx-final-trust__item"><span aria-hidden="true">01</span><strong>Paiement à la livraison</strong></div>',
      '<div class="tajx-final-trust__item"><span aria-hidden="true">02</span><strong>Livraison suivie partout au Maroc</strong></div>',
      '<div class="tajx-final-trust__item"><span aria-hidden="true">03</span><strong>Confirmation téléphonique avant expédition</strong></div>',
      '<div class="tajx-final-trust__item"><span aria-hidden="true">04</span><strong>Garantie satisfaction 7 jours</strong></div>',
      '</div>'
    ].join('');
    footer.parentNode.insertBefore(trustSection, footer);
  }

  var footerGrid = footer.querySelector('.tajx-footer-grid');

  if (footerGrid && !footerGrid.querySelector('.tajx-footer-nav')) {
    var footerColumns = footerGrid.children;

    if (footerColumns[0]) {
      footerColumns[0].classList.add('tajx-footer-brand');
    }

    if (footerColumns[1]) {
      footerColumns[1].classList.add('tajx-footer-reassurance');
    }

    if (footerColumns[2]) {
      footerColumns[2].classList.add('tajx-footer-contact');
    }

    var navigation = document.createElement('nav');
    navigation.className = 'tajx-footer-nav';
    navigation.setAttribute('aria-label', 'Navigation du pied de page');
    navigation.innerHTML = [
      '<strong>Navigation</strong>',
      '<a href="#accueil">Accueil</a>',
      '<a href="#heritage">Héritage</a>',
      '<a href="#benefices">Pourquoi Tajinoos</a>',
      '<a href="#produit">Produit</a>',
      '<a href="#avis">Avis clients</a>',
      '<a href="#commande">Commander</a>'
    ].join('');

    footerGrid.insertBefore(navigation, footerColumns[1] || null);
  }

  var whatsappButton = document.querySelector('.taj-whatsapp-float');

  if (whatsappButton && 'IntersectionObserver' in window) {
    var footerObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        whatsappButton.classList.toggle('is-footer-visible', entry.isIntersecting);
      });
    }, {
      threshold: 0.08
    });

    footerObserver.observe(footer);
  }
})();
