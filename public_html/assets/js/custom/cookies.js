// ===== GESTIÓN DE COOKIES BANNER Y MODAL =====

document.addEventListener('DOMContentLoaded', function() {
  // Elementos del DOM
  const cookiesBanner = document.getElementById('cookiesBanner');
  const cookiesModal = document.getElementById('cookiesModal');
  const btnCookiesAccept = document.getElementById('btnCookiesAccept');
  const btnCookiesInfo = document.getElementById('btnCookiesInfo');
  const closeCookiesModal = document.getElementById('closeCookiesModal');
  const closeModalBtn = document.getElementById('closeModalBtn');

  // Constante para almacenar en localStorage
  const COOKIES_ACCEPTED_KEY = 'bsperu_cookies_accepted';

  /**
   * Verificar si el usuario ya aceptó las cookies
   */
  function checkCookiesAcceptance() {
    const accepted = localStorage.getItem(COOKIES_ACCEPTED_KEY);
    
    if (!accepted) {
      // Si no ha aceptado, mostrar banner después de 1 segundo
      setTimeout(() => {
        showCookiesBanner();
      }, 1000);
    }
  }

  /**
   * Mostrar el banner de cookies
   */
  function showCookiesBanner() {
    if (cookiesBanner) {
      cookiesBanner.style.display = 'block';
    }
  }

  /**
   * Ocultar el banner de cookies
   */
  function hideCookiesBanner() {
    if (cookiesBanner) {
      cookiesBanner.style.animation = 'slideDown 0.3s ease reverse';
      setTimeout(() => {
        cookiesBanner.style.display = 'none';
      }, 300);
    }
  }

  /**
   * Aceptar cookies
   */
  function acceptCookies() {
    // Guardar en localStorage que aceptó
    localStorage.setItem(COOKIES_ACCEPTED_KEY, 'true');
    localStorage.setItem('cookies_accepted_date', new Date().toISOString());
    
    hideCookiesBanner();
    
    // Log para verificación
    console.log('Cookies aceptadas por el usuario');
  }

  /**
   * Mostrar modal de información
   */
  function showCookiesModal() {
    if (cookiesModal) {
      cookiesModal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }

  /**
   * Cerrar modal de información
   */
  function closeCookiesModalFunction() {
    if (cookiesModal) {
      cookiesModal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  }

  // Event Listeners
  if (btnCookiesAccept) {
    btnCookiesAccept.addEventListener('click', acceptCookies);
  }

  if (btnCookiesInfo) {
    btnCookiesInfo.addEventListener('click', () => {
      showCookiesModal();
    });
  }

  if (closeCookiesModal) {
    closeCookiesModal.addEventListener('click', closeCookiesModalFunction);
  }

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeCookiesModalFunction);
  }

  // Cerrar modal al hacer clic fuera del contenido
  if (cookiesModal) {
    cookiesModal.addEventListener('click', function(event) {
      if (event.target === cookiesModal) {
        closeCookiesModalFunction();
      }
    });
  }

  // Cerrar modal con tecla ESC
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && cookiesModal && cookiesModal.style.display === 'flex') {
      closeCookiesModalFunction();
    }
  });

  // Inicializar: verificar si ya aceptó cookies
  checkCookiesAcceptance();

  // Exponer funciones globalmente si se necesitan desde otro lugar
  window.CookiesBanner = {
    show: showCookiesBanner,
    hide: hideCookiesBanner,
    accept: acceptCookies,
    showModal: showCookiesModal,
    closeModal: closeCookiesModalFunction,
    isAccepted: function() {
      return localStorage.getItem(COOKIES_ACCEPTED_KEY) === 'true';
    },
    revoke: function() {
      localStorage.removeItem(COOKIES_ACCEPTED_KEY);
      localStorage.removeItem('cookies_accepted_date');
      showCookiesBanner();
    }
  };
});
