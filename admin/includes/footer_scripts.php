<?php
/**
 * admin/includes/footer_scripts.php - Petites interactions UI (thème, sidebar, dropdown, profil, lightbox, toast, synchronisation temps réel)
 */
declare(strict_types=1);
$b = defined('BASE_URL') ? BASE_URL : '/immolode-php';
?>
<script>
  (function() {
    var BASE_URL = <?= json_encode($b) ?>;

    // 1. Thème clair / sombre
    function syncThemeIcons() {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      var lightIcon = document.querySelector('.theme-icon-light');
      var darkIcon = document.querySelector('.theme-icon-dark');
      if (lightIcon && darkIcon) {
        lightIcon.style.display = isDark ? 'none' : 'inline-flex';
        darkIcon.style.display = isDark ? 'inline-flex' : 'none';
      }
      var statusEl = document.getElementById('settings-theme-status');
      if (statusEl) statusEl.textContent = 'Mode actuel : ' + (isDark ? 'Sombre' : 'Clair');
      var labelEl = document.getElementById('settings-theme-btn-label');
      if (labelEl) labelEl.textContent = isDark ? 'Basculer en mode clair' : 'Basculer en mode sombre';
    }
    window.toggleAdminTheme = function() {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('immolode_admin_theme', 'light');
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('immolode_admin_theme', 'dark');
      }
      syncThemeIcons();
    };
    syncThemeIcons();
    document.getElementById('btn-theme-toggle')?.addEventListener('click', window.toggleAdminTheme);
    document.getElementById('btn-settings-theme-toggle')?.addEventListener('click', window.toggleAdminTheme);

    // 2. Bouton retour topbar
    document.getElementById('topbar-back-btn')?.addEventListener('click', function() {
      if (window.history.length > 1 && document.referrer && document.referrer.indexOf('/admin') !== -1) {
        window.history.back();
      } else {
        window.location.href = BASE_URL + '/admin/index.php';
      }
    });

    // 3. Sidebar rétractable (état mémorisé et réduction fluide)
    var sidebar = document.getElementById('admin-sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    
    // Restaurer l'état réduit au chargement
    if (localStorage.getItem('sidebarCollapsed') === 'true' && sidebar) {
      sidebar.classList.add('is-collapsed');
      document.body.classList.add('sidebar-collapsed');
    }

    document.getElementById('sidebar-collapse-btn')?.addEventListener('click', function() {
      if (!sidebar) return;
      sidebar.classList.toggle('is-collapsed');
      document.body.classList.toggle('sidebar-collapsed');
      localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('is-collapsed') ? 'true' : 'false');
    });

    document.getElementById('sidebar-toggle-btn')?.addEventListener('click', function() {
      sidebar?.classList.add('is-open');
      backdrop?.classList.add('is-open');
      document.body.classList.add('sidebar-is-open');
    });
    function closeSidebar() {
      sidebar?.classList.remove('is-open');
      backdrop?.classList.remove('is-open');
      document.body.classList.remove('sidebar-is-open');
    }
    document.getElementById('sidebar-close-btn')?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    // 4. Dropdown avatar topbar
    var avatarBtn = document.getElementById('topbar-avatar-btn');
    var dropdown = document.getElementById('topbar-dropdown');
    if (avatarBtn && dropdown) {
      avatarBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
      });
      document.addEventListener('click', function(e) {
        if (!e.target.closest('#topbar-user-menu')) dropdown.style.display = 'none';
      });
    }

    // 5. Modale Mon Profil
    var profModal = document.getElementById('admin-profile-modal');
    function openProf() {
      if (dropdown) dropdown.style.display = 'none';
      profModal?.classList.add('is-active');
    }
    function closeProf() { profModal?.classList.remove('is-active'); }
    document.getElementById('topbar-menu-profile')?.addEventListener('click', openProf);
    document.getElementById('profile-close-btn')?.addEventListener('click', closeProf);
    document.getElementById('profile-cancel-btn')?.addEventListener('click', closeProf);
    profModal?.addEventListener('click', function(e) { if (e.target === profModal) closeProf(); });

    var profFile = document.getElementById('profile-avatar-file');
    var profUrl = document.getElementById('profile-avatar-url');
    var profPrev = document.getElementById('profile-avatar-preview');
    var profRemFlag = document.getElementById('profile-remove-flag');
    document.getElementById('btn-upload-avatar')?.addEventListener('click', function() { profFile?.click(); });
    profFile?.addEventListener('change', function(e) {
      var f = e.target.files && e.target.files[0];
      if (!f) return;
      if (profRemFlag) profRemFlag.value = '';
      var r = new FileReader();
      r.onload = function(ev) {
        if (profPrev) profPrev.innerHTML = '<img src="' + ev.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
      };
      r.readAsDataURL(f);
    });
    document.getElementById('btn-remove-avatar')?.addEventListener('click', function() {
      if (profUrl) profUrl.value = '';
      if (profRemFlag) profRemFlag.value = '1';
      if (profPrev) profPrev.textContent = (document.getElementById('profile-nom')?.value || 'A').charAt(0).toUpperCase();
    });

    // 6. Toasts Notifications dynamiques
    window.showAdminToast = function(message, type) {
      var container = document.querySelector('.admin-toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'admin-toast-container';
        document.body.appendChild(container);
      }
      var toast = document.createElement('div');
      toast.className = 'admin-toast admin-toast--' + (type || 'success');
      toast.innerHTML = '<span class="toast-icon">✓</span><span class="toast-message">' + message + '</span>';
      container.appendChild(toast);
      setTimeout(function() { toast.classList.add('is-visible'); }, 20);
      setTimeout(function() {
        toast.classList.remove('is-visible');
        setTimeout(function() { toast.remove(); }, 320);
      }, 3400);
    };

    // Auto-masquage Toast Flash PHP existant
    var flashToast = document.getElementById('admin-flash-toast');
    if (flashToast) {
      setTimeout(function() {
        var card = flashToast.querySelector('.admin-toast');
        if (card) card.classList.remove('is-visible');
        setTimeout(function() { flashToast.remove(); }, 350);
      }, 3400);
    }

    // 7. Lightbox globale pour pièces d'identité
    var lb = document.getElementById('image-lightbox');
    var lbImg = document.getElementById('lightbox-img');
    var lbTxt = document.getElementById('lightbox-caption-text');
    var lbDl = document.getElementById('lightbox-download-link');
    document.querySelectorAll('.btn-zoom-doc').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var src = btn.getAttribute('data-img');
        var cap = btn.getAttribute('data-caption') || "Pièce d'identité officielle";
        if (!src || !lb || !lbImg) return;
        lbImg.src = src;
        if (lbTxt) lbTxt.textContent = cap;
        if (lbDl) lbDl.href = src;
        lb.hidden = false;
      });
    });
    function closeLb() {
      if (lb) lb.hidden = true;
      if (lbImg) lbImg.src = '';
    }
    document.getElementById('lightbox-close')?.addEventListener('click', closeLb);
    lb?.addEventListener('click', function(e) { if (e.target === lb) closeLb(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && lb && !lb.hidden) closeLb(); });

    // 8. Synchronisation en temps réel des badges de la barre latérale
    window.updateSidebarBadge = function(key, count) {
      var id = (key === 'demandes' ? 'badge-demandes' : 'badge-fiches-client');
      var el = document.getElementById(id);
      if (!el) return;
      var num = Math.max(0, parseInt(count, 10) || 0);
      el.textContent = String(num);
      el.setAttribute('data-count', String(num));
      if (num > 0) {
        el.removeAttribute('hidden');
        el.hidden = false;
        el.classList.remove('is-empty');
      } else {
        el.setAttribute('hidden', '');
        el.hidden = true;
        el.classList.add('is-empty');
      }
    };

    window.syncAdminBadges = function() {
      fetch(BASE_URL + '/api/stats.php', { cache: 'no-store' })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (!data) return;
          var d = (data.unreadDemandes != null) ? data.unreadDemandes : (data.demandes_non_lues || 0);
          var f = (data.unreadFichesClient != null) ? data.unreadFichesClient : (data.fiches_non_lues || 0);
          // "Demandes" dans le menu = total des deux catégories
          window.updateSidebarBadge('demandes', d + f);
          // "Fiches client" garde son propre compteur
          window.updateSidebarBadge('fiches-client', f);
        })
        .catch(function() {});
    };

    // Polling régulier toutes les 10 secondes
    setInterval(window.syncAdminBadges, 10000);

    // 9. Animation de mise en surbrillance d'une ligne ciblée par URL (?id=X)
    (function() {
      var params = new URLSearchParams(window.location.search);
      var targetId = params.get('id');
      if (targetId) {
        var row = document.querySelector('tr[data-id="' + targetId + '"]') || document.getElementById('row-' + targetId);
        if (row) {
          setTimeout(function() {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('row-highlight');
            setTimeout(function() { row.classList.remove('row-highlight'); }, 3000);
          }, 250);
        }
      }
    })();
  })();
</script>
