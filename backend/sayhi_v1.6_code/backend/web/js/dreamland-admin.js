/**
 * Dreamland Admin — light interactivity (no heavy 3D tilt)
 */
(function () {
  'use strict';

  function initSidebarIconPress() {
    if (!document.body.classList.contains('dreamland-admin')) return;

    document.querySelectorAll('.sidebar-menu a').forEach(function (link) {
      var wrap = link.querySelector('.dl-menu-icon-wrap');
      if (!wrap) return;

      link.addEventListener('mousedown', function () {
        wrap.style.transform = 'translateY(1px) scale(0.96)';
      });

      var reset = function () {
        wrap.style.transform = '';
      };

      link.addEventListener('mouseup', reset);
      link.addEventListener('mouseleave', reset);
    });
  }

  function initStatCounters() {
    if (!document.body.classList.contains('dreamland-dashboard')) return;

    document.querySelectorAll('.site-index .info-box-number').forEach(function (el) {
      var text = el.textContent || '';
      var prefix = text.match(/^[^\d.-]*/)[0] || '';
      var target = parseFloat(text.replace(/[^\d.-]/g, ''));
      if (isNaN(target) || target <= 0 || target > 999999) return;

      var start = performance.now();
      var duration = 700;

      function tick(now) {
        var p = Math.min(1, (now - start) / duration);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = prefix + Math.round(target * eased).toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
      }

      el.textContent = prefix + '0';
      requestAnimationFrame(tick);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSidebarIconPress();
    initStatCounters();
  });
})();
