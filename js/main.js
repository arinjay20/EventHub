/* ================================================================
   EventHub – main.js
   Global JS: Navbar, Mobile Menu, Stats Counter, Scroll Animations,
   Toast Notifications, Smooth Scroll
   ================================================================ */

// ===== NAVBAR SCROLL =====
(function () {
  const navbar = document.getElementById('navbar');
  if (!navbar) return;

  // Already solid class means page uses solid navbar always (events, dashboards)
  if (!navbar.classList.contains('solid')) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 50);
    });
  }
})();

// ===== MOBILE HAMBURGER =====
(function () {
  const hamburger = document.getElementById('hamburger');
  const menu      = document.getElementById('navbarMenu');
  if (!hamburger || !menu) return;

  hamburger.addEventListener('click', () => {
    menu.classList.toggle('open');
    hamburger.classList.toggle('open');
    // Animate spans
    const spans = hamburger.querySelectorAll('span');
    if (hamburger.classList.contains('open')) {
      spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
      spans[1].style.opacity   = '0';
      spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
    } else {
      spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
    }
  });

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (!hamburger.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove('open');
      hamburger.classList.remove('open');
      hamburger.querySelectorAll('span').forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
    }
  });
})();

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const href = this.getAttribute('href');
    if (href === '#') return;
    const target = document.querySelector(href);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// ===== ANIMATED STATS COUNTER =====
function animateCounter(el, target, suffix = '') {
  let current = 0;
  const step = Math.ceil(target / 80);
  const interval = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current.toLocaleString() + suffix;
    if (current >= target) clearInterval(interval);
  }, 20);
}

function initStats() {
  fetch('php/live_feed.php?action=all')
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;

      // Update Animated Stats Grid
      const statsMap = {
        statEvents:     { val: data.active_events || 0,   suffix: '+' },
        statStudents:   { val: data.total_users || 0,     suffix: '+' },
        statOrganizers: { val: 32,                        suffix: '+' }, // Typical base for a campus
        statCategories: { val: 12,                       suffix: '' },
      };

      Object.entries(statsMap).forEach(([id, cfg]) => {
        const el = document.getElementById(id);
        if (el) animateCounter(el, cfg.val, cfg.suffix);
      });

      // Update Live Mini-Stats
      if (document.getElementById('lsTotalUsers'))    document.getElementById('lsTotalUsers').textContent = data.total_users;
      if (document.getElementById('lsActiveEvents'))  document.getElementById('lsActiveEvents').textContent = data.active_events;
      if (document.getElementById('lsTotalRegs'))    document.getElementById('lsTotalRegs').textContent = data.total_registrations;

      // Update Community Spotlight
      const stack = document.getElementById('liveAvatarStack');
      const text  = document.getElementById('communityText');
      if (stack && data.feed && data.feed.length > 0) {
        stack.innerHTML = data.feed.slice(0, 4).map((user, i) => `
          <div class="avatar" style="width: 45px; height: 45px; border-radius: 50%; 
               background: ${['#6c63ff', '#f5576c', '#43e97b', '#3b82f6'][i%4]}; 
               border: 2px solid #0a0a0f; margin-left: ${i === 0 ? '0' : '-12px'}; 
               display: flex; align-items: center; justify-content: center; 
               font-weight: bold; font-size: 0.8rem; color: #fff;">
            ${user.avatar}
          </div>
        `).join('');
        
        if (data.total_users > 4) {
          stack.innerHTML += `<div class="avatar" style="width: 45px; height: 45px; border-radius: 50%; background: #374151; border: 2px solid #0a0a0f; margin-left: -12px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.8rem; color: #fff;">+${data.total_users - 4}</div>`;
        }
        
        if (text) text.innerHTML = `Connecting **${data.total_users.toLocaleString()}** students across Graphic Era.`;
      }
    })
    .catch(err => console.error('Stats fetch failed:', err));
}

// Trigger on scroll into view
(function () {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        initStats();
        observer.disconnect();
      }
    });
  }, { threshold: 0.3 });

  const statsSection = document.querySelector('.stats-section');
  if (statsSection) observer.observe(statsSection);
  
  // Also trigger if live section is visible
  const liveSection = document.getElementById('liveFeedSection');
  if (liveSection) observer.observe(liveSection);
})();

// ===== SCROLL REVEAL ANIMATIONS =====
(function () {
  const elements = document.querySelectorAll('.feature-card, .event-card, .stat-card, .registered-card');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity  = '1';
          entry.target.style.transform = 'translateY(0)';
        }, i * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  elements.forEach(el => {
    el.style.opacity   = '0';
    el.style.transform = 'translateY(24px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
  });
})();

// ===== SESSION CHECK & NAVBAR UPDATE =====
(function () {
  const menu = document.getElementById('navbarMenu');
  if (!menu) return;

  fetch('php/get_session.php')
    .then(res => res.json())
    .then(data => {
      if (data.loggedIn) {
        // Find existing Login/Register links
        const loginLink    = menu.querySelector('a[href="login.html"]')?.parentElement;
        const registerLink = menu.querySelector('a[href="register.html"]')?.parentElement;

        if (loginLink) loginLink.remove();
        if (registerLink) registerLink.remove();

        // Create Dashboard link
        const dashboardMap = { student: 'student-dashboard.html', organizer: 'organizer-dashboard.html', admin: 'admin-dashboard.html' };
        const dashUrl = dashboardMap[data.role] || 'student-dashboard.html';

        const dashLi = document.createElement('li');
        dashLi.innerHTML = `<a href="${dashUrl}">My Dashboard</a>`;
        menu.appendChild(dashLi);

        // Create Logout Link
        const logoutLi = document.createElement('li');
        logoutLi.innerHTML = `<a href="#" id="logoutBtn" style="color:#ef4444; font-weight:700;">Logout</a>`;
        menu.appendChild(logoutLi);

        // Mark active if on dashboard
        const path = window.location.pathname.split('/').pop() || 'index.html';
        if (path === dashUrl) {
            const dashLink = menu.querySelector(`a[href="${dashUrl}"]`);
            if (dashLink) dashLink.classList.add('active');
        }
      }
    })
    .catch(err => console.error('Session check failed:', err));
})();

// ===== GLOBAL LOGOUT HANDLER =====
document.addEventListener('click', (e) => {
  const logoutBtn = e.target.closest('#logoutBtn');
  if (logoutBtn) {
    e.preventDefault();
    fetch('php/logout.php')
      .finally(() => {
        if (window.showToast) showToast('Logged out successfully. Redirecting...', 'success');
        setTimeout(() => { window.location.href = 'index.html'; }, 800);
      });
  }
});

// ===== TOAST NOTIFICATIONS =====
function showToast(message, type = 'success', duration = 3500) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <span style="font-size:1.2rem">${icons[type] || '✅'}</span>
    <span style="flex:1; font-size:0.9rem; font-weight:500;">${message}</span>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:1rem;color:#9ca3af;padding:0;">✕</button>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity   = '0';
    toast.style.transform = 'translateX(30px)';
    toast.style.transition = 'all 0.4s ease';
    setTimeout(() => toast.remove(), 400);
  }, duration);
}

// Make globally available
window.showToast = showToast;

// ===== CLOSE MODAL OVERLAY =====
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.classList.remove('active');
  });
});

// ===== ACTIVE NAV LINK =====
(function () {
  const path = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.navbar-menu a, .navbar-menu li a').forEach(link => {
    const href = link.getAttribute('href');
    if (href === path) link.classList.add('active');
  });
})();

// ===== KEYBOARD ESC closes modals =====
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
  }
});
