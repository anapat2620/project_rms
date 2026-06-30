// status_summary.js — optimized init with cached colors and idempotent charts
(function () {
  'use strict';

  // Cache theme colors once per init to minimise layout thrash.
  // Uses try/finally to guarantee probe element cleanup even on error.
  function readThemeColors() {
    var classes = ['text-primary', 'text-success', 'text-warning', 'text-error', 'text-base-content', 'text-info', 'text-secondary'];
    var cache = {};
    var probe = document.createElement('span');
    probe.style.cssText = 'position:absolute;left:-9999px;visibility:hidden;';
    document.body.appendChild(probe);
    try {
      classes.forEach(function (cls) {
        probe.className = cls;
        cache[cls] = getComputedStyle(probe).color;
      });
    } finally {
      document.body.removeChild(probe);
    }
    return {
      primary:   cache['text-primary'],
      success:   cache['text-success'],
      warning:   cache['text-warning'],
      error:     cache['text-error'],
      base:      cache['text-base-content'],
      info:      cache['text-info'],
      secondary: cache['text-secondary'],
    };
  }

  // Parse embedded JSON data once.
  var DATA = {};
  var jsonEl = document.getElementById('status-summary-data');
  if (jsonEl) {
    try { DATA = JSON.parse(jsonEl.textContent || '{}'); } catch (e) { DATA = {}; }
  }

  // Hold Chart instances to prevent duplicates on re-render.
  // Use a plain property instead of logical-assignment (||=) for broader compatibility.
  if (!window.STATUS_SUMMARY_CHARTS) {
    window.STATUS_SUMMARY_CHARTS = { pie: null, line: null };
  }
  var CHARTS = window.STATUS_SUMMARY_CHARTS;

  function buildCommonOptions(colors) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: colors.base, usePointStyle: true, boxWidth: 10 },
        },
        tooltip: { enabled: true },
      },
      layout: { padding: 8 },
    };
  }

  function initStatusPie(colors) {
    var canvas = document.getElementById('statusPie');
    if (!canvas || !window.Chart) return;
    if (CHARTS.pie) { CHARTS.pie.destroy(); }
    CHARTS.pie = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: Object.keys(DATA.statusCounts || {}),
        datasets: [{
          data: Object.values(DATA.statusCounts || {}),
          backgroundColor: [colors.warning, colors.success, colors.error, colors.secondary],
          borderWidth: 1,
          borderColor: 'rgba(255,255,255,0.6)',
        }],
      },
      options: buildCommonOptions(colors),
    });
  }

  function initTrendLine(colors) {
    var canvas = document.getElementById('trendLine');
    if (!canvas || !window.Chart) return;
    if (CHARTS.line) { CHARTS.line.destroy(); }
    var common = buildCommonOptions(colors);
    CHARTS.line = new Chart(canvas, {
      type: 'bar',
      data: {
        labels: DATA.applicantTypeLabels || [],
        datasets: [{
          label: 'จำนวนการยื่น',
          data: DATA.applicantTypeCounts || [],
          backgroundColor: (DATA.applicantTypeLabels || []).map(function(_, index) {
            return [colors.primary, colors.success, colors.warning, colors.error, colors.info][index % 5];
          }),
          borderColor: colors.base,
          borderWidth: 1,
        }],
      },
      options: Object.assign({}, common, {
        scales: {
          x: {
            ticks: { color: colors.base, maxRotation: 0, autoSkip: false },
            grid: { display: false },
          },
          y: {
            ticks: { color: colors.base, precision: 0 },
            grid: { color: 'rgba(156,163,175,0.15)' },
            beginAtZero: true,
          },
        },
      }),
    });
  }

  function initStatusSummary() {
    if (!window.Chart) {
      setTimeout(initStatusSummary, 100);
      return;
    }
    var colors = readThemeColors();
    requestAnimationFrame(function () {
      initStatusPie(colors);
      initTrendLine(colors);
    });
  }

  // Expose for manual re-initialisation (e.g. after theme switch).
  window.initStatusSummary = initStatusSummary;

  // Bootstrap: defer until DOM is ready.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStatusSummary);
  } else {
    initStatusSummary();
  }
}());