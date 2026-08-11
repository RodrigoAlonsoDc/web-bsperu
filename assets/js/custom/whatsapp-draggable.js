// ===== HACER EL BOTÓN DE WHATSAPP ARRASTRABL =====

document.addEventListener('DOMContentLoaded', function() {
  const whatsappBtn = document.querySelector('.whatsapp-btn');
  
  if (!whatsappBtn) return; // Si no existe el botón, salir
  
  let isDragging = false;
  let offsetX = 0;
  let offsetY = 0;
  
  const STORAGE_KEY = 'whatsapp_position';
  const whatsappURL = 'https://wa.me/51914776669';
  
  /**
   * Cargar posición guardada del localStorage
   */
  function loadPosition() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      const { right, bottom } = JSON.parse(saved);
      whatsappBtn.style.right = right + 'px';
      whatsappBtn.style.bottom = bottom + 'px';
    }
  }
  
  /**
   * Guardar posición en localStorage
   */
  function savePosition() {
    const rect = whatsappBtn.getBoundingClientRect();
    const right = window.innerWidth - rect.right;
    const bottom = window.innerHeight - rect.bottom;
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ right, bottom }));
  }
  
  /**
   * Doble clic para abrir WhatsApp
   */
  whatsappBtn.addEventListener('dblclick', function() {
    window.open(whatsappURL, '_blank');
  });
  
  /**
   * Iniciar arrastre
   */
  whatsappBtn.addEventListener('mousedown', function(e) {
    isDragging = true;
    whatsappBtn.style.cursor = 'grabbing';
    
    const rect = whatsappBtn.getBoundingClientRect();
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;
    
    // Prevenir que se seleccione texto mientras se arrastra
    e.preventDefault();
  });
  
  /**
   * Mientras se arrastra
   */
  document.addEventListener('mousemove', function(e) {
    if (!isDragging) return;
    
    const newX = e.clientX - offsetX;
    const newY = e.clientY - offsetY;
    
    // Limitar los bordes (evitar que salga de la pantalla)
    const maxX = window.innerWidth - whatsappBtn.offsetWidth;
    const maxY = window.innerHeight - whatsappBtn.offsetHeight;
    
    const constrainedX = Math.max(0, Math.min(newX, maxX));
    const constrainedY = Math.max(0, Math.min(newY, maxY));
    
    whatsappBtn.style.left = constrainedX + 'px';
    whatsappBtn.style.top = constrainedY + 'px';
    whatsappBtn.style.right = 'auto';
    whatsappBtn.style.bottom = 'auto';
  });
  
  /**
   * Finalizar arrastre
   */
  document.addEventListener('mouseup', function() {
    if (isDragging) {
      isDragging = false;
      whatsappBtn.style.cursor = 'pointer';
      savePosition();
    }
  });
  
  // También soportar touch en móviles
  whatsappBtn.addEventListener('touchstart', function(e) {
    isDragging = true;
    const rect = whatsappBtn.getBoundingClientRect();
    const touch = e.touches[0];
    offsetX = touch.clientX - rect.left;
    offsetY = touch.clientY - rect.top;
  });
  
  document.addEventListener('touchmove', function(e) {
    if (!isDragging) return;
    
    const touch = e.touches[0];
    const newX = touch.clientX - offsetX;
    const newY = touch.clientY - offsetY;
    
    const maxX = window.innerWidth - whatsappBtn.offsetWidth;
    const maxY = window.innerHeight - whatsappBtn.offsetHeight;
    
    const constrainedX = Math.max(0, Math.min(newX, maxX));
    const constrainedY = Math.max(0, Math.min(newY, maxY));
    
    whatsappBtn.style.left = constrainedX + 'px';
    whatsappBtn.style.top = constrainedY + 'px';
    whatsappBtn.style.right = 'auto';
    whatsappBtn.style.bottom = 'auto';
  });
  
  document.addEventListener('touchend', function() {
    if (isDragging) {
      isDragging = false;
      savePosition();
    }
  });
  
  // Cargar posición al iniciar
  loadPosition();
});
