(function () {
  'use strict';

  /* Runtime and shared configuration */

  var FAST = 160;
  var BASE = 240;
  var REVEAL = 520;
  var STAGGER = 60;
  var MAX_STAGGER = 240;
  var EASE = 'cubic-bezier(.22, .61, .36, 1)';
  var EASE_OUT = 'cubic-bezier(.16, 1, .3, 1)';
  var reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  var tabletQuery = window.matchMedia('(max-width: 1023px)');
  var mobileQuery = window.matchMedia('(max-width: 767px)');
  var finePointerQuery = window.matchMedia('(min-width: 1024px) and (pointer: fine)');
  var root = document.documentElement;
  var isEditor = document.body.classList.contains('elementor-editor-active');
  var isLandingPage = document.body.classList.contains('has-tajx-page') ||
    Boolean(document.querySelector('.tajx, .tajv2-page'));
  var revealObserver = null;
  var floatingObserver = null;
  var observedMotionTargets = new WeakSet();
  var faqControllers = [];

  function addMediaListener(query, listener) {
    if (query.addEventListener) {
      query.addEventListener('change', listener);
    } else if (query.addListener) {
      query.addListener(listener);
    }
  }

  function prefersReducedMotion() {
    return reducedMotionQuery.matches;
  }

  function isVisible(element) {
    if (!element || element.closest('[aria-hidden="true"]')) {
      return false;
    }

    var style = window.getComputedStyle(element);

    return style.display !== 'none' &&
      style.visibility !== 'hidden' &&
      element.getClientRects().length > 0;
  }

  window.TajinoosMotion = {
    FAST: FAST,
    BASE: BASE,
    REVEAL: REVEAL,
    STAGGER: STAGGER,
    MAX_STAGGER: MAX_STAGGER,
    EASE: EASE,
    EASE_OUT: EASE_OUT,
    reducedMotionQuery: reducedMotionQuery,
    prefersReducedMotion: prefersReducedMotion,
    closeMobileMenu: function () {},
    refresh: function () {}
  };

  /* Shared motion and reveal */

  function showMotionTarget(element) {
    var delay = parseInt(element.style.getPropertyValue('--taj-motion-delay'), 10) || 0;

    element.classList.add('is-visible');

    if (revealObserver) {
      revealObserver.unobserve(element);
    }

    if (prefersReducedMotion()) {
      element.style.removeProperty('--taj-motion-delay');
      return;
    }

    window.setTimeout(function () {
      element.style.removeProperty('--taj-motion-delay');
    }, REVEAL + delay);
  }

  function showAllMotionTargets() {
    document.querySelectorAll('[data-motion]').forEach(showMotionTarget);
  }

  function getMotionDelay(element) {
    if (!element.closest('[data-motion-group]')) {
      return 0;
    }

    var index = parseInt(element.getAttribute('data-motion-index'), 10);

    if (Number.isNaN(index) || index < 1) {
      return 0;
    }

    return Math.min(index * STAGGER, MAX_STAGGER);
  }

  function createRevealObserver() {
    if (revealObserver || !('IntersectionObserver' in window)) {
      return;
    }

    revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          showMotionTarget(entry.target);
        }
      });
    }, {
      root: null,
      rootMargin: '0px 0px 8% 0px',
      threshold: 0.08
    });
  }

  function refreshMotionTargets() {
    var targets = document.querySelectorAll('[data-motion]');

    if (!targets.length || isEditor || prefersReducedMotion() || !('IntersectionObserver' in window)) {
      root.classList.remove('motion-ready');
      showAllMotionTargets();
      return;
    }

    createRevealObserver();

    targets.forEach(function (element) {
      if (element.classList.contains('is-visible') || !isVisible(element)) {
        return;
      }

      var delay = getMotionDelay(element);
      var rect = element.getBoundingClientRect();

      element.style.setProperty('--taj-motion-delay', delay + 'ms');

      if (rect.top < window.innerHeight * 0.92 && rect.bottom > 0) {
        showMotionTarget(element);
        return;
      }

      if (!observedMotionTargets.has(element)) {
        observedMotionTargets.add(element);
        revealObserver.observe(element);
      }
    });

    root.classList.add('motion-ready');
  }

  window.TajinoosMotion.refresh = refreshMotionTargets;

  addMediaListener(reducedMotionQuery, function () {
    root.classList.toggle('motion-reduced', prefersReducedMotion());

    if (prefersReducedMotion()) {
      root.classList.remove('motion-ready');
      showAllMotionTargets();
      faqControllers.forEach(function (controller) {
        controller.finish();
      });
    } else {
      refreshMotionTargets();
    }
  });

  addMediaListener(tabletQuery, refreshMotionTargets);
  addMediaListener(mobileQuery, refreshMotionTargets);

  /* Shared anchor navigation */

  var landingAnchors = new Set([
    '#accueil',
    '#heritage',
    '#benefices',
    '#artisanat',
    '#produit',
    '#avis',
    '#faq',
    '#commande'
  ]);

  function getStickyOffset() {
    var navbar = document.querySelector('.tajx-navbar');

    if (!navbar || window.getComputedStyle(navbar).position !== 'sticky') {
      return 0;
    }

    return Math.ceil(navbar.getBoundingClientRect().height) + 10;
  }

  function scrollToTarget(target, updateHistory, allowSmooth) {
    if (!target) {
      return;
    }

    var top = Math.max(
      0,
      target.getBoundingClientRect().top + window.pageYOffset - getStickyOffset()
    );

    window.scrollTo({
      top: top,
      behavior: allowSmooth && !prefersReducedMotion() ? 'smooth' : 'auto'
    });

    if (updateHistory && history.pushState && window.location.hash !== '#' + target.id) {
      history.pushState(null, '', '#' + target.id);
    }
  }

  function initializeAnchorNavigation() {
    if (!isLandingPage || isEditor) {
      return;
    }

    document.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      var link = event.target.closest('a[href]');

      if (!link) {
        return;
      }

      var url;

      try {
        url = new URL(link.href, window.location.href);
      } catch (error) {
        return;
      }

      if (
        url.origin !== window.location.origin ||
        url.pathname !== window.location.pathname ||
        !landingAnchors.has(url.hash)
      ) {
        return;
      }

      var target = document.querySelector(url.hash);

      if (!target) {
        return;
      }

      event.preventDefault();
      window.TajinoosMotion.closeMobileMenu(false);
      scrollToTarget(target, true, true);
    });

    if (landingAnchors.has(window.location.hash)) {
      window.requestAnimationFrame(function () {
        scrollToTarget(document.querySelector(window.location.hash), false, false);
      });
    }
  }

  /* Navbar */

  function initializeNavbar() {
    var navbar = document.querySelector('.tajx-navbar');

    if (!navbar || isEditor) {
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
      drawerBrand.setAttribute('aria-label', 'Tajinoos \u2014 Accueil');

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
      if (tabletQuery.matches) {
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

    function setMenu(open, restoreFocus) {
      var shouldOpen = Boolean(open && tabletQuery.matches);

      navbar.classList.toggle('is-open', shouldOpen);
      backdrop.classList.toggle('is-open', shouldOpen);
      document.body.classList.toggle('tajx-mobile-menu-open', shouldOpen);
      toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
      toggle.setAttribute('aria-label', shouldOpen ? 'Fermer le menu' : 'Ouvrir le menu');
      backdrop.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');

      if (tabletQuery.matches) {
        menu.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
      } else {
        menu.removeAttribute('aria-hidden');
      }

      if (shouldOpen) {
        closeButton.focus();
      } else if (restoreFocus) {
        toggle.focus();
      }
    }

    window.TajinoosMotion.closeMobileMenu = function (restoreFocus) {
      setMenu(false, Boolean(restoreFocus));
    };

    syncMenuLocation();

    toggle.addEventListener('click', function () {
      setMenu(!navbar.classList.contains('is-open'), false);
    });

    closeButton.addEventListener('click', function () {
      setMenu(false, true);
    });

    backdrop.addEventListener('click', function () {
      setMenu(false, true);
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

      var focusable = Array.prototype.slice.call(
        menu.querySelectorAll('button:not([disabled]), a[href]')
      );

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

    addMediaListener(tabletQuery, function () {
      setMenu(false, false);
      syncMenuLocation();
    });
  }

  /* Hero */

  function initializeHeroParallax() {
    var hero = document.querySelector('#accueil.taj-clean-hero');

    if (!hero || isEditor) {
      return;
    }

    var frame = null;
    var targetX = 0;
    var targetY = 0;

    function render() {
      hero.style.setProperty('--taj-hero-parallax-x', (targetX * 4).toFixed(2) + 'px');
      hero.style.setProperty('--taj-hero-parallax-y', (targetY * 3).toFixed(2) + 'px');
      hero.style.setProperty('--taj-product-parallax-x', (targetX * 6).toFixed(2) + 'px');
      hero.style.setProperty('--taj-product-parallax-y', (targetY * 4).toFixed(2) + 'px');
      frame = null;
    }

    function requestRender() {
      if (!frame) {
        frame = window.requestAnimationFrame(render);
      }
    }

    function reset() {
      targetX = 0;
      targetY = 0;
      requestRender();
    }

    hero.addEventListener('pointermove', function (event) {
      if (prefersReducedMotion() || !finePointerQuery.matches || event.pointerType === 'touch') {
        return;
      }

      var rect = hero.getBoundingClientRect();

      targetX = (event.clientX - rect.left) / rect.width - 0.5;
      targetY = (event.clientY - rect.top) / rect.height - 0.5;
      requestRender();
    }, { passive: true });

    hero.addEventListener('pointerleave', reset, { passive: true });
    addMediaListener(reducedMotionQuery, reset);
    addMediaListener(finePointerQuery, reset);
  }

  /* Legacy review carousel support (inactive for the commitments section) */

  function initializeReviews() {
    var section = document.querySelector('#avis.tajx-testimonials[data-tajinoos-reviews]');
    var track = section ? section.querySelector('.tajx-reviews-track') : null;
    var scroller = section ? section.querySelector('.tajx-reviews-marquee') : null;
    var showcase = section ? section.querySelector('.tajx-testimonials-showcase') : null;

    if (!track || !scroller || !showcase || isEditor) {
      return;
    }

    var cards = Array.prototype.slice.call(
      track.querySelectorAll('.tajx-review-card:not([aria-hidden="true"])')
    );
    var dots = null;
    var ticking = false;

    function updateDots() {
      ticking = false;

      if (!dots || !tabletQuery.matches) {
        return;
      }

      var scrollerRect = scroller.getBoundingClientRect();
      var center = scrollerRect.left + scrollerRect.width / 2;
      var activeIndex = 0;
      var smallestDistance = Infinity;

      cards.forEach(function (card, index) {
        var rect = card.getBoundingClientRect();
        var distance = Math.abs(rect.left + rect.width / 2 - center);

        if (distance < smallestDistance) {
          smallestDistance = distance;
          activeIndex = index;
        }
      });

      dots.querySelectorAll('.tajx-review-dot').forEach(function (dot, index) {
        dot.setAttribute('aria-current', index === activeIndex ? 'true' : 'false');
      });
    }

    function createDots() {
      if (dots || cards.length < 2) {
        return;
      }

      dots = document.createElement('div');
      dots.className = 'tajx-review-dots';
      dots.setAttribute('aria-label', 'Choisir un engagement');

      cards.forEach(function (card, index) {
        var dot = document.createElement('button');

        dot.type = 'button';
        dot.className = 'tajx-review-dot';
        dot.setAttribute('aria-label', 'Afficher l\u2019engagement ' + (index + 1));
        dot.setAttribute('aria-current', index === 0 ? 'true' : 'false');
        dot.addEventListener('click', function () {
          card.scrollIntoView({
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
            block: 'nearest',
            inline: 'center'
          });
        });
        dots.appendChild(dot);
      });

      showcase.appendChild(dots);
      updateDots();
    }

    function syncReviewMode() {
      if (tabletQuery.matches) {
        createDots();
      } else if (dots) {
        dots.remove();
        dots = null;
      }
    }

    scroller.addEventListener('scroll', function () {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(updateDots);
      }
    }, { passive: true });

    syncReviewMode();
    addMediaListener(tabletQuery, syncReviewMode);
  }

  /* FAQ */

  function initializeFAQ() {
    var faq = document.querySelector('#faq.taj-final-faq');

    if (!faq || isEditor) {
      return;
    }

    faq.querySelectorAll('details').forEach(function (detail) {
      var summary = detail.querySelector('summary');

      if (!summary) {
        return;
      }

      var animation = null;
      var targetOpen = detail.open;

      function syncExpandedState(open) {
        summary.setAttribute(
          'aria-expanded',
          typeof open === 'boolean' ? (open ? 'true' : 'false') : (detail.open ? 'true' : 'false')
        );
      }

      function finish() {
        if (animation) {
          animation.finish();
        }

        detail.style.height = '';
        detail.removeAttribute('data-motion-state');
        syncExpandedState();
      }

      function animateTo(nextOpen) {
        var startHeight = detail.getBoundingClientRect().height;

        if (animation) {
          animation.onfinish = null;
          animation.oncancel = null;
          animation.cancel();
          animation = null;
        }

        if (nextOpen && !detail.open) {
          detail.open = true;
        }

        detail.style.height = '';

        var endHeight = nextOpen
          ? detail.getBoundingClientRect().height
          : summary.getBoundingClientRect().height;

        targetOpen = nextOpen;
        detail.setAttribute('data-motion-state', nextOpen ? 'opening' : 'closing');
        detail.style.height = startHeight + 'px';
        syncExpandedState(nextOpen);

        animation = detail.animate([
          { height: startHeight + 'px' },
          { height: endHeight + 'px' }
        ], {
          duration: BASE + 20,
          easing: EASE
        });

        animation.onfinish = function () {
          detail.open = targetOpen;
          detail.style.height = '';
          detail.removeAttribute('data-motion-state');
          animation = null;
          syncExpandedState();
        };

        animation.oncancel = function () {
          detail.style.height = '';
          detail.removeAttribute('data-motion-state');
          animation = null;
          syncExpandedState();
        };
      }

      syncExpandedState();
      detail.addEventListener('toggle', function () {
        if (!animation) {
          syncExpandedState();
        }
      });

      summary.addEventListener('click', function (event) {
        if (prefersReducedMotion() || !detail.animate) {
          window.setTimeout(syncExpandedState, 0);
          return;
        }

        event.preventDefault();
        animateTo(animation ? !targetOpen : !detail.open);
      });

      faqControllers.push({ finish: finish });
    });
  }

  /* Order quantity and form */

  function initializeOrderQuantity() {
    var order = document.querySelector('#commande.tajcmd');

    if (!order || isEditor) {
      return;
    }

    var configuredUnitPrice = window.tajinoosOrder
      ? parseInt(window.tajinoosOrder.unitPrice, 10)
      : 249;
    var configuredMarrakechFee = window.tajinoosOrder
      ? parseInt(window.tajinoosOrder.marrakechDeliveryFee, 10)
      : 0;
    var configuredOtherCityFee = window.tajinoosOrder
      ? parseInt(window.tajinoosOrder.otherCityDeliveryFee, 10)
      : 20;
    var unitPrice = Number.isNaN(configuredUnitPrice) ? 249 : configuredUnitPrice;
    var marrakechDeliveryFee = Number.isNaN(configuredMarrakechFee) ? 0 : configuredMarrakechFee;
    var otherCityDeliveryFee = Number.isNaN(configuredOtherCityFee) ? 20 : configuredOtherCityFee;
    var minQuantity = 1;
    var maxQuantity = 5;
    var quantity = minQuantity;
    var cityInput = order.querySelector('[data-tajcmd-city]');
    var quantityText = order.querySelector('[data-tajcmd-quantity]');
    var quantitySelect = order.querySelector('[data-tajcmd-quantity-select]');
    var totalText = order.querySelector('[data-tajcmd-total]');
    var productSubtotalText = order.querySelector('[data-tajcmd-product-subtotal]');
    var deliveryFeeText = order.querySelector('[data-tajcmd-delivery-fee]');
    var formTotalText = order.querySelector('[data-tajcmd-form-total]');
    var ctaTotalText = order.querySelector('[data-tajcmd-cta-total]');
    var subtotalInput = order.querySelector('[data-tajcmd-subtotal-input]');
    var deliveryInput = order.querySelector('[data-tajcmd-delivery-input]');
    var totalInput = order.querySelector('[data-tajcmd-total-input]');
    var priceLiveRegion = order.querySelector('[data-tajcmd-price-live]');
    var buttons = order.querySelectorAll('[data-tajcmd-qty]');
    var marrakechVariants = new Set([
      'marrakech',
      'marrakesh',
      'marrakech city',
      'marrakesh city',
      'ville de marrakech',
      'ville de marrakesh',
      'marrakech ville',
      'marrakesh ville',
      '\u0645\u0631\u0627\u0643\u0634'
    ]);

    function clampQuantity(value) {
      var parsed = parseInt(value, 10);

      if (Number.isNaN(parsed)) {
        parsed = minQuantity;
      }

      return Math.max(minQuantity, Math.min(maxQuantity, parsed));
    }

    function normalizeDeliveryCity(value) {
      var normalized = String(value || '').trim().toLowerCase();

      if (normalized.normalize) {
        normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      }

      return normalized
        .replace(/[^\p{L}\p{N}\s]+/gu, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    function syncOrder(nextQuantity, announce) {
      quantity = clampQuantity(nextQuantity);

      var normalizedCity = normalizeDeliveryCity(cityInput ? cityInput.value : '');
      var hasCity = normalizedCity !== '';
      var isMarrakech = marrakechVariants.has(normalizedCity);
      var deliveryFee = hasCity
        ? (isMarrakech ? marrakechDeliveryFee : otherCityDeliveryFee)
        : 0;
      var productSubtotal = quantity * unitPrice;
      var finalTotal = productSubtotal + deliveryFee;
      var subtotalLabel = String(productSubtotal);
      var totalLabel = String(finalTotal);
      var deliveryLabel = !hasCity
        ? '\u00c0 s\u00e9lectionner'
        : (deliveryFee === 0 ? 'Gratuite' : String(deliveryFee) + ' MAD');

      if (quantityText) {
        quantityText.textContent = String(quantity);
      }

      if (quantitySelect && quantitySelect.value !== String(quantity)) {
        quantitySelect.value = String(quantity);
      }

      if (totalText) {
        totalText.textContent = totalLabel;
      }

      if (productSubtotalText) {
        productSubtotalText.textContent = subtotalLabel;
      }

      if (deliveryFeeText) {
        deliveryFeeText.textContent = deliveryLabel;
      }

      if (formTotalText) {
        formTotalText.textContent = totalLabel;
      }

      if (ctaTotalText) {
        ctaTotalText.textContent = totalLabel;
      }

      if (subtotalInput) {
        subtotalInput.value = subtotalLabel;
      }

      if (deliveryInput) {
        deliveryInput.value = String(deliveryFee);
      }

      if (totalInput) {
        totalInput.value = totalLabel;
      }

      if (priceLiveRegion && announce) {
        priceLiveRegion.textContent = hasCity
          ? 'Sous-total produit ' + subtotalLabel + ' MAD. Livraison ' + deliveryLabel +
            '. Total \u00e0 payer ' + totalLabel + ' MAD.'
          : 'Sous-total produit ' + subtotalLabel +
            ' MAD. Indiquez votre ville pour calculer la livraison.';
      }

      buttons.forEach(function (button) {
        var action = button.getAttribute('data-tajcmd-qty');

        button.disabled =
          (action === 'minus' && quantity <= minQuantity) ||
          (action === 'plus' && quantity >= maxQuantity);
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
        }, FAST);

        syncOrder(nextQuantity, true);
      });
    });

    if (quantitySelect) {
      quantitySelect.addEventListener('change', function () {
        syncOrder(quantitySelect.value, true);
      });
    }

    if (cityInput) {
      cityInput.addEventListener('input', function () {
        syncOrder(quantitySelect ? quantitySelect.value : quantity, true);
      });
      cityInput.addEventListener('change', function () {
        syncOrder(quantitySelect ? quantitySelect.value : quantity, true);
      });
    }

    syncOrder(quantitySelect ? quantitySelect.value : quantity, false);
  }

  function initializeOrderForm() {
    var form = document.querySelector('#commande.tajcmd .tajcmd-form');

    if (!form || isEditor) {
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
          (window.tajinoosOrder ? window.tajinoosOrder.processingLabel : 'Traitement de votre commande\u2026')
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
          'Veuillez v\u00e9rifier les champs indiqu\u00e9s avant de continuer.',
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
          showError(
            window.tajinoosOrder.networkError || window.tajinoosOrder.genericError,
            ''
          );
          setSubmitting(false);
        });
    });
  }

  /* Footer and floating actions */

  function initializeFooter() {
    var page = document.querySelector('.tajx');
    var footer = document.querySelector('.tajx-footer');

    if (!page || !footer || isEditor) {
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

    if (offerCard) {
      offerCard.classList.add('tajx-offer--premium');
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
        '<a href="#heritage">H\u00e9ritage</a>',
        '<a href="#benefices">Pourquoi Tajinoos</a>',
        '<a href="#produit">Produit</a>',
        '<a href="#avis">Nos engagements</a>',
        '<a href="#commande">Commander</a>'
      ].join('');

      footerGrid.insertBefore(navigation, footerColumns[1] || null);
    }

    if (!footer.querySelector('.tajx-footer-mobile-contact')) {
      var emailLink = document.querySelector('#faq .taj-final-support__email');
      var whatsappLink = document.querySelector(
        '.taj-whatsapp-float, #faq .taj-final-support__wa'
      );

      if (whatsappLink) {
        var mobileContact = document.createElement('div');

        mobileContact.className = 'tajx-footer-mobile-contact';
        mobileContact.innerHTML = [
          '<strong>Contact</strong>',
          '<a href="' + whatsappLink.href.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener noreferrer">WhatsApp</a>',
          emailLink ? '<a href="' + emailLink.href.replace(/"/g, '&quot;') + '">Email</a>' : '',
          '<a href="#faq">FAQ</a>'
        ].join('');
        footer.appendChild(mobileContact);
      }
    }
  }

  function initializeFloatingActions() {
    var order = document.querySelector('#commande');
    var footer = document.querySelector('.tajx-footer');
    var orderButton = document.querySelector('.taj-order-float');
    var whatsappButton = document.querySelector('.taj-whatsapp-float');
    var context = {
      order: false,
      footer: false
    };

    if (!isLandingPage || isEditor || (!orderButton && !whatsappButton)) {
      return;
    }

    function update() {
      if (orderButton) {
        orderButton.classList.toggle(
          'is-context-hidden',
          !mobileQuery.matches || context.order || context.footer
        );
      }

      if (whatsappButton) {
        whatsappButton.classList.toggle('is-footer-visible', context.footer);
      }
    }

    if ('IntersectionObserver' in window && (order || footer)) {
      floatingObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.target === order) {
            context.order = entry.isIntersecting;
          } else if (entry.target === footer) {
            context.footer = entry.isIntersecting;
          }
        });

        update();
      }, {
        root: null,
        rootMargin: '0px 0px -6% 0px',
        threshold: 0.08
      });

      if (order) {
        floatingObserver.observe(order);
      }

      if (footer) {
        floatingObserver.observe(footer);
      }
    }

    update();
    addMediaListener(mobileQuery, update);
  }

  /* Thank-you page */

  function initializeThankYouPage() {
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
  }

  /* Initialization */

  root.classList.toggle('motion-reduced', prefersReducedMotion());
  initializeNavbar();
  initializeAnchorNavigation();
  initializeHeroParallax();
  initializeReviews();
  initializeFAQ();
  initializeOrderQuantity();
  initializeOrderForm();
  initializeFooter();
  initializeFloatingActions();
  initializeThankYouPage();
  refreshMotionTargets();
})();
