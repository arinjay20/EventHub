/* ================================================================
   EventHub – events.js
   Events Page: Search, Filter, Sort, Grid/List Toggle, Registration
   ================================================================ */

(function () {
  const searchInput  = document.getElementById('searchInput');
  const pills        = document.querySelectorAll('.pill[data-filter]');
  const sortSelect   = document.getElementById('sortSelect');
  const eventsGrid   = document.getElementById('eventsGrid');
  const eventCount   = document.getElementById('eventCount');
  const noEventsMsg  = document.getElementById('noEventsMsg');
  const gridViewBtn  = document.getElementById('gridView');
  const listViewBtn  = document.getElementById('listView');
  const registerModal = document.getElementById('registerModal');
  const closeModal   = document.getElementById('closeModal');
  const modalName    = document.getElementById('modalEventName');

  let activeFilter = 'all';

  // ===== FILTER PILLS =====
  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      pills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeFilter = pill.dataset.filter;
      applyFilters();
    });
  });

  // ===== SEARCH =====
  if (searchInput) {
    searchInput.addEventListener('input', debounce(applyFilters, 250));
  }

  // ===== SORT =====
  if (sortSelect) {
    sortSelect.addEventListener('change', applyFilters);
  }

  // ===== APPLY FILTERS =====
  function applyFilters() {
    const query   = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const sortBy  = sortSelect ? sortSelect.value : 'date';
    const cards   = Array.from(eventsGrid ? eventsGrid.querySelectorAll('.event-card') : []);

    let visible = cards.filter(card => {
      const cat   = card.dataset.category || '';
      const name  = (card.dataset.name || '').toLowerCase();
      const title = (card.querySelector('.event-title')?.textContent || '').toLowerCase();
      const desc  = (card.textContent || '').toLowerCase();

      const matchFilter = activeFilter === 'all' || cat === activeFilter;
      const matchSearch = !query || name.includes(query) || title.includes(query) || desc.includes(query);

      return matchFilter && matchSearch;
    });

    // Sort
    if (sortBy === 'name') {
      visible.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || ''));
    } else if (sortBy === 'date') {
      visible.sort((a, b) => new Date(a.dataset.date) - new Date(b.dataset.date));
    }

    // Show/hide
    cards.forEach(card => card.style.display = 'none');
    visible.forEach(card => card.style.display = '');

    // Update count
    if (eventCount) eventCount.textContent = visible.length;

    // No results message
    if (noEventsMsg) {
      noEventsMsg.style.display = visible.length === 0 ? 'block' : 'none';
    }

    // Re-append sorted cards
    if (eventsGrid) {
      visible.forEach(card => eventsGrid.appendChild(card));
    }
  }

  // ===== VIEW TOGGLE =====
  if (gridViewBtn && listViewBtn && eventsGrid) {
    gridViewBtn.addEventListener('click', () => {
      eventsGrid.classList.add('grid-3');
      eventsGrid.classList.remove('grid-1');
      gridViewBtn.classList.add('active');
      listViewBtn.classList.remove('active');
    });

    listViewBtn.addEventListener('click', () => {
      eventsGrid.classList.remove('grid-3');
      eventsGrid.classList.add('grid-1');
      listViewBtn.classList.add('active');
      gridViewBtn.classList.remove('active');
    });
  }

  // ===== REGISTER BUTTONS (using event delegation) =====
  if (eventsGrid) {
    eventsGrid.addEventListener('click', function (e) {
      const btn = e.target.closest('.register-btn');
      if (!btn || btn.disabled) return;

      const eventId   = btn.dataset.id;
      const eventName = btn.dataset.name || 'this event';

      // 1. Check session via get_session.php
      fetch('php/get_session.php')
        .then(res => res.json())
        .then(data => {
          if (data.loggedIn && data.role === 'student') {
            
            // TWO-CLICK REGISTRATION FLOW (Replaces native confirm)
            if (btn.dataset.state !== 'confirming') {
              // Click 1: Ask for confirmation
              btn.dataset.state = 'confirming';
              btn.dataset.originalText = btn.textContent;
              btn.textContent = 'Confirm?';
              btn.style.background = '#10b981'; // Success green
              
              // Revert after 3 seconds if not clicked
              setTimeout(() => {
                if (btn.dataset.state === 'confirming') {
                  btn.dataset.state = '';
                  btn.textContent = btn.dataset.originalText;
                  btn.style.background = '';
                }
              }, 3000);
              return;
            }

            // Click 2: Proceed with registration
            btn.disabled = true;
            btn.textContent = '⏳ ...';
            btn.dataset.state = '';

            const fd = new FormData();
            fd.append('event_id', eventId);

            fetch('php/event_register.php', { method: 'POST', body: fd })
              .then(res => res.json())
              .then(result => {
                if (result.success) {
                  showToast(result.message, 'success');
                  btn.textContent = 'Registered';
                  btn.classList.add('btn-ghost');
                  btn.classList.remove('btn-primary');
                  btn.style.background = '';
                } else {
                  showToast(result.message, 'error');
                  btn.disabled = false;
                  btn.textContent = 'Register';
                  btn.style.background = '';
                }
              })
              .catch(() => {
                showToast('Registration failed.', 'error');
                btn.disabled = false;
                btn.textContent = 'Register';
                btn.style.background = '';
              });
          } else {
            // Not logged in -> show modal
            if (modalName) modalName.textContent = eventName;
            if (registerModal) registerModal.classList.add('active');
          }
        })
        .catch(err => {
           console.error('Session check failed:', err);
           // Fallback to modal
           if (modalName) modalName.textContent = eventName;
           if (registerModal) registerModal.classList.add('active');
        });
    });
  }

  if (closeModal) {
    closeModal.addEventListener('click', () => {
      registerModal.classList.remove('active');
    });
  }

  // ===== DEBOUNCE =====
  function debounce(fn, delay) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  // ===== LIVE FETCH (AJAX) — Simulated =====
  // In production, replace this with actual AJAX fetch to PHP backend:
  //
  //   fetch('php/get_events.php?category=' + activeFilter + '&search=' + query)
  //     .then(res => res.json())
  //     .then(data => renderEvents(data))
  //     .catch(err => console.error('Error fetching events:', err));
  //
  // function renderEvents(events) {
  //   eventsGrid.innerHTML = '';
  //   events.forEach(ev => {
  //     eventsGrid.innerHTML += `<div class="event-card">...</div>`;
  //   });
  // }

  // ===== INIT =====
  applyFilters();

})();
