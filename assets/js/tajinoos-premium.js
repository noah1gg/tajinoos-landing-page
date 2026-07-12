(function () {
  'use strict';

  var page = document.querySelector('[data-tjn-landing]');

  if (!page || document.body.classList.contains('elementor-editor-active')) {
    return;
  }

  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var header = page.querySelector('[data-tjn-header]');
  var navToggle = page.querySelector('.tjn-nav-toggle');
  var mobileCta = page.querySelector('.tjn-mobile-cta');
  var whatsapp = page.querySelector('.tjn-whatsapp');
  var checkout = page.querySelector('#commande');

  function updateHeader() {
    var scrolled = window.scrollY > 18;
    header.classList.toggle('is-scrolled', scrolled);

    if (mobileCta) {
      mobileCta.classList.toggle('is-visible', window.scrollY > 520 && !isCheckoutVisible());
    }
  }

  function isCheckoutVisible() {
    if (!checkout) {
      return false;
    }

    var rect = checkout.getBoundingClientRect();
    return rect.top < window.innerHeight * 0.88 && rect.bottom > 100;
  }

  updateHeader();
  window.addEventListener('scroll', updateHeader, { passive: true });

  if (navToggle) {
    navToggle.addEventListener('click', function () {
      var isOpen = header.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  }

  page.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      var selector = link.getAttribute('href');
      var target = selector && selector.length > 1 ? document.querySelector(selector) : null;

      if (!target) {
        return;
      }

      event.preventDefault();
      header.classList.remove('is-open');

      if (navToggle) {
        navToggle.setAttribute('aria-expanded', 'false');
      }

      target.scrollIntoView({
        behavior: reducedMotion.matches ? 'auto' : 'smooth',
        block: 'start'
      });

      if (history.pushState) {
        history.pushState(null, '', selector);
      }
    });
  });

  var revealItems = page.querySelectorAll('.tjn-reveal');

  if (reducedMotion.matches || !('IntersectionObserver' in window)) {
    revealItems.forEach(function (item) {
      item.classList.add('is-visible');
    });
  } else {
    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      });
    }, {
      rootMargin: '0px 0px -7% 0px',
      threshold: 0.1
    });

    revealItems.forEach(function (item, index) {
      item.style.setProperty('--tjn-delay', Math.min(index % 4 * 65, 195) + 'ms');
      revealObserver.observe(item);
    });
  }

  var parallax = page.querySelector('[data-tjn-parallax]');

  if (parallax && !reducedMotion.matches && window.matchMedia('(pointer: fine)').matches) {
    var parallaxFrame = null;
    var parallaxX = 0;
    var parallaxY = 0;

    function paintParallax() {
      parallax.style.setProperty('--tjn-parallax-x', (parallaxX * 8).toFixed(2) + 'px');
      parallax.style.setProperty('--tjn-parallax-y', (parallaxY * 6).toFixed(2) + 'px');
      parallaxFrame = null;
    }

    parallax.addEventListener('pointermove', function (event) {
      var rect = parallax.getBoundingClientRect();
      parallaxX = (event.clientX - rect.left) / rect.width - 0.5;
      parallaxY = (event.clientY - rect.top) / rect.height - 0.5;

      if (!parallaxFrame) {
        parallaxFrame = window.requestAnimationFrame(paintParallax);
      }
    }, { passive: true });

    parallax.addEventListener('pointerleave', function () {
      parallaxX = 0;
      parallaxY = 0;

      if (!parallaxFrame) {
        parallaxFrame = window.requestAnimationFrame(paintParallax);
      }
    }, { passive: true });
  }

  page.querySelectorAll('.tjn-faq details').forEach(function (detail) {
    var summary = detail.querySelector('summary');
    var content = detail.querySelector('div');

    if (!summary || !content || reducedMotion.matches || !detail.animate) {
      return;
    }

    summary.addEventListener('click', function (event) {
      if (detail.dataset.animating === 'true') {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      detail.dataset.animating = 'true';
      var startHeight = detail.offsetHeight;
      var willOpen = !detail.open;

      if (willOpen) {
        detail.open = true;
      }

      var endHeight = willOpen ? summary.offsetHeight + content.offsetHeight : summary.offsetHeight;
      var animation = detail.animate({ height: [startHeight + 'px', endHeight + 'px'] }, {
        duration: 240,
        easing: 'cubic-bezier(.22, .61, .36, 1)'
      });

      animation.onfinish = function () {
        detail.open = willOpen;
        detail.style.height = '';
        detail.dataset.animating = 'false';
      };

      animation.oncancel = function () {
        detail.style.height = '';
        detail.dataset.animating = 'false';
      };
    });
  });

  var form = page.querySelector('[data-tjn-order-form]');

  if (form) {
    var unitPrice = 390;
    var quantity = form.querySelector('[data-tjn-quantity]');
    var total = form.querySelector('[data-tjn-total]');
    var submitTotal = form.querySelector('[data-tjn-submit-total]');
    var totalInput = form.querySelector('[data-tjn-total-input]');
    var phone = form.querySelector('input[name="Telephone"]');
    var submit = form.querySelector('.tjn-form__submit');

    function updateTotal() {
      var amount = Math.max(1, Math.min(5, parseInt(quantity.value, 10) || 1)) * unitPrice;
      total.textContent = String(amount);
      submitTotal.textContent = String(amount);
      totalInput.value = String(amount);
    }

    function validatePhone() {
      var digits = phone.value.replace(/\D/g, '');
      var valid = digits.length >= 9 && digits.length <= 13;
      phone.setAttribute('aria-invalid', valid || !phone.value ? 'false' : 'true');
      phone.setCustomValidity(valid ? '' : 'Saisissez un num\u00e9ro marocain valide.');
      return valid;
    }

    quantity.addEventListener('change', updateTotal);
    phone.addEventListener('input', validatePhone);
    updateTotal();

    form.addEventListener('submit', function (event) {
      validatePhone();

      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
      }

      submit.disabled = true;
      submit.textContent = 'Envoi en cours...';
    });
  }

  if (checkout && whatsapp && 'IntersectionObserver' in window) {
    var checkoutObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        whatsapp.classList.toggle('is-hidden', entry.isIntersecting);
      });
    }, { threshold: 0.15 });

    checkoutObserver.observe(checkout);
  }
})();
