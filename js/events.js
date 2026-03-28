/* ================================================================
   EventHub – events.js
   Events Page: Search, Filter, Sort, Grid/List Toggle, Registration
   ================================================================ */

(function () {
  const searchInput = document.getElementById('searchInput');
  const pills = document.querySelectorAll('.pill[data-filter]');
  const sortSelect = document.getElementById('sortSelect');
  const eventsGrid = document.getElementById('eventsGrid');
  const eventCount = document.getElementById('eventCount');
  const noEventsMsg = document.getElementById('noEventsMsg');
  const gridViewBtn = document.getElementById('gridView');
  const listViewBtn = document.getElementById('listView');
  const registerModal = document.getElementById('registerModal');
  const closeModal = document.getElementById('closeModal');
  const modalName = document.getElementById('modalEventName');

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
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const sortBy = sortSelect ? sortSelect.value : 'date';
    const cards = Array.from(eventsGrid ? eventsGrid.querySelectorAll('.event-card') : []);

    let visible = cards.filter(card => {
      const cat = card.dataset.category || '';
      const name = (card.dataset.name || '').toLowerCase();
      const title = (card.querySelector('.event-title')?.textContent || '').toLowerCase();
      const desc = (card.textContent || '').toLowerCase();

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

      const eventId = btn.dataset.id;
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

  // ===== RENDER EVENTS =====
  function renderEvents(events) {
    if (!eventsGrid) return;
    eventsGrid.innerHTML = '';

    if (events.length === 0) {
      if (noEventsMsg) noEventsMsg.style.display = 'block';
      if (eventCount) eventCount.textContent = '0';
      return;
    }

    if (noEventsMsg) noEventsMsg.style.display = 'none';
    if (eventCount) eventCount.textContent = events.length;

    events.forEach(ev => {
      const isPast = new Date(ev.event_date) < new Date();
      const pct = Math.min(100, Math.round((ev.registered_count / ev.max_capacity) * 100));
      const catClass = `cat-bg-${ev.category.toLowerCase()}`;

      const card = document.createElement('div');
      card.className = 'event-card';

      card.innerHTML = `
        <div class="event-card-image ${catClass}">
          <span class="event-category-badge">${ev.category}</span>
          ${getCategoryEmoji(ev.category)}
        </div>
        <div class="event-card-body">
          <h3 class="event-title">${ev.name}</h3>
          
          <div class="event-meta-list">
            <div class="meta-item">
              <span class="meta-icon">📅</span>
              <span>${new Date(ev.event_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
            </div>
            <div class="meta-item">
              <span class="meta-icon">📍</span>
              <span>${ev.venue}</span>
            </div>
          </div>
          
          <p class="event-description">${ev.description}</p>
          
          <div class="capacity-container">
            <div class="capacity-header">
              <span>Spots Filled</span>
              <span>${pct}%</span>
            </div>
            <div class="capacity-progress">
              <div class="progress-fill ${pct >= 100 ? 'full' : ''}" style="width: ${pct}%"></div>
            </div>
            <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 4px;">
              ${ev.registered_count} / ${ev.max_capacity} students registered
            </div>
          </div>

          <div class="organizer-info">
            <span>👤 ${ev.organizer_name || 'Tech Organizer'}</span>
          </div>
          
          <div class="register-btn-wrap">
            <button class="btn btn-primary register-btn" 
                    style="width: 100%;"
                    data-id="${ev.id}" 
                    data-name="${ev.name}"
                    ${isPast || ev.registered_count >= ev.max_capacity ? 'disabled' : ''}>
              ${isPast ? 'Completed' : (ev.registered_count >= ev.max_capacity ? 'Event Full' : 'Register Now')}
            </button>
          </div>
        </div>
      `;
      eventsGrid.appendChild(card);
    });
  }

  function getCategoryEmoji(cat) {
    const map = { technology: '🤖', cultural: '🎭', sports: '⚽', academic: '📚', workshop: '🛠️' };
    return map[cat] || '✨';
  }

  // ===== FETCH EVENTS (AJAX) =====
  function fetchEvents() {
    const query = searchInput ? searchInput.value.trim() : '';
    const sort = sortSelect ? sortSelect.value : 'date';
    const url = `php/get_events.php?category=${activeFilter}&search=${encodeURIComponent(query)}&sort=${sort}`;

    if (eventsGrid) eventsGrid.style.opacity = '0.5';

    fetch(url)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          renderEvents(data.events);
        } else {
          console.error('Failed to fetch events:', data.message);
        }
      })
      .catch(err => console.error('Error fetching events:', err))
      .finally(() => {
        if (eventsGrid) eventsGrid.style.opacity = '1';
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

  // ===== INIT =====
  fetchEvents();

  // Re-link filters to fetch instead of just DOM filtering
  pills.forEach(pill => {
    pill.addEventListener('click', () => {
      // activeFilter set in existing pill listener
      setTimeout(fetchEvents, 0);
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', debounce(fetchEvents, 300));
  }
  if (sortSelect) {
    sortSelect.addEventListener('change', fetchEvents);
  }

})();
