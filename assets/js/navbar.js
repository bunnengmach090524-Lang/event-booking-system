(function () {
  // ---------- THEME ----------
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const isDark = document.documentElement.classList.toggle('dark');
      const next = isDark ? 'dark' : 'light';
      localStorage.setItem('theme', next);

      fetch('/event-booking/includes/theme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ theme: next }),
      }).catch(() => {});
    });
  }

  // ---------- LANGUAGE ----------
  const langToggle = document.getElementById('langToggle');
  if (langToggle) {
    const current = langToggle.dataset.current;
    langToggle.querySelectorAll('.lang-option').forEach((el) => {
      if (el.dataset.lang === current) {
        el.classList.add('bg-white', 'dark:bg-gray-800', 'text-blue-600', 'dark:text-blue-400', 'shadow-sm');
      }
    });

    langToggle.addEventListener('click', (e) => {
      const target = e.target.closest('[data-lang]');
      if (!target) return;
      const lang = target.dataset.lang;

      fetch(`/event-booking/includes/lang.php?set=${lang}&ajax=1`)
        .then((r) => r.json())
        .then((data) => {
          if (data.success) window.location.reload();
        });
    });
  }

  // ---------- FAVORITES ----------
  // Call from a heart button on any event card: toggleFavorite(eventId, buttonEl)
  // Pass removeCardOnUnfavorite=true on the favorites page so unfavoriting
  // removes the card from view immediately instead of just changing color.
  window.toggleFavorite = function (eventId, buttonEl, removeCardOnUnfavorite) {
    fetch('/event-booking/api/favorites.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ event_id: eventId }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data.success) return;

        if (removeCardOnUnfavorite && !data.favorited) {
          const card = buttonEl.closest('[data-fav-card]');
          if (card) {
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => {
              card.remove();
              const grid = document.getElementById('favoritesGrid');
              if (grid && !grid.querySelector('[data-fav-card]')) {
                grid.innerHTML = `
                  <div class="col-span-3 text-center py-20">
                    <p class="text-gray-400">No favorites left</p>
                  </div>`;
              }
            }, 200);
          }
        } else {
          buttonEl.classList.toggle('text-red-500', data.favorited);
          buttonEl.classList.toggle('text-gray-400', !data.favorited);
        }

        refreshFavoritesCount();
      });
  };

  function refreshFavoritesCount() {
    const badge = document.getElementById('favoritesCount');
    if (!badge) return;

    fetch('/event-booking/api/favorites.php?action=list')
      .then((r) => r.json())
      .then((data) => {
        if (!data.success) return;
        const count = data.favorites.length;
        badge.textContent = count;
        badge.hidden = count === 0;
      });
  }

  // ---------- NOTIFICATIONS ----------
  const notificationsBtn = document.getElementById('notificationsBtn');
  const notificationsDropdown = document.getElementById('notificationsDropdown');

  function renderNotifications(list) {
    if (!notificationsDropdown) return;
    if (list.length === 0) {
      notificationsDropdown.innerHTML = '<p class="px-4 py-2 text-gray-400 dark:text-gray-500 text-xs">No notifications yet</p>';
      return;
    }
    notificationsDropdown.innerHTML = list.map((n) => `
      <a href="${n.link || '#'}" class="block px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-50 dark:border-gray-700 last:border-0">
        <p class="font-medium text-gray-700 dark:text-gray-200">${n.title}</p>
        <p class="text-gray-500 dark:text-gray-400 text-xs mt-0.5">${n.message}</p>
      </a>
    `).join('');
  }

  function refreshNotifications() {
    const badge = document.getElementById('notificationsCount');
    if (!badge) return;

    fetch('/event-booking/api/notifications.php?action=list')
      .then((r) => r.json())
      .then((data) => {
        if (!data.success) return;
        badge.textContent = data.unread;
        badge.hidden = data.unread === 0;
        renderNotifications(data.notifications);
      });
  }

  if (notificationsBtn) {
    notificationsBtn.addEventListener('click', () => {
      const isHidden = notificationsDropdown.hidden;
      notificationsDropdown.hidden = !isHidden;

      if (isHidden) {
        fetch('/event-booking/api/notifications.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ mark_all: true }),
        }).then(refreshNotifications);
      }
    });

    document.addEventListener('click', (e) => {
      if (!notificationsBtn.contains(e.target) && !notificationsDropdown.contains(e.target)) {
        notificationsDropdown.hidden = true;
      }
    });
  }

  refreshFavoritesCount();
  refreshNotifications();

  // ---------- SEARCH SUGGESTIONS ----------
  const searchInput = document.getElementById('navSearchInput');
  const searchDropdown = document.getElementById('searchSuggestions');
  let searchDebounce = null;

  function renderSuggestions(results) {
    if (!searchDropdown) return;

    if (results.length === 0) {
      searchDropdown.innerHTML = '<p class="px-4 py-3 text-gray-400 dark:text-gray-500 text-xs">No events found</p>';
      searchDropdown.hidden = false;
      return;
    }

    searchDropdown.innerHTML = results.map((ev) => `
      <a href="/event-booking/customer/event-detail.php?id=${ev.id}"
         class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-50 dark:border-gray-700 last:border-0">
        <div class="w-10 h-10 rounded-md bg-gradient-to-br from-blue-500 to-purple-600 flex-shrink-0 overflow-hidden flex items-center justify-center text-white text-xs">
          ${ev.image ? `<img src="/event-booking/uploads/events/${ev.image}" class="w-full h-full object-cover">` : '🎫'}
        </div>
        <div class="min-w-0">
          <p class="font-medium text-gray-700 dark:text-gray-200 truncate">${ev.title}</p>
          <p class="text-gray-400 dark:text-gray-500 text-xs truncate">${ev.location} · $${Number(ev.price).toFixed(2)}</p>
        </div>
      </a>
    `).join('');
    searchDropdown.hidden = false;
  }

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim();
      clearTimeout(searchDebounce);

      if (q.length < 2) {
        searchDropdown.hidden = true;
        return;
      }

      searchDebounce = setTimeout(() => {
        fetch(`/event-booking/api/search-suggestions.php?q=${encodeURIComponent(q)}`)
          .then((r) => r.json())
          .then((data) => {
            if (!data.success) return;
            renderSuggestions(data.results);
          });
      }, 250); // debounce so we don't hit the server on every keystroke
    });

    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
        searchDropdown.hidden = true;
      }
    });
  }

  if (window.lucide) lucide.createIcons();
})();