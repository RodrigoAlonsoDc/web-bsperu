// ===============================
// FORMULARIO DE CONTACTO - INICIO
// Envía los datos del formulario al WhatsApp de BS PERÚ
// ===============================
const contactForm = document.getElementById('contactForm');
const formStatus = document.getElementById('form-status');

if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nombre = document.querySelector('input[name="nombre"]').value.trim();
        const email = document.querySelector('input[name="email"]').value.trim();
        const razonSocial = document.querySelector('input[name="razonSocial"]').value.trim();
        const ruc = document.querySelector('input[name="ruc"]').value.trim();
        const telefono = document.querySelector('input[name="telefono"]').value.trim();
        const mensaje = document.querySelector('textarea[name="mensaje"]').value.trim();
        
        const whatsappMensaje = `*CONTACTO DESDE BS PERÚ*\n\n` +
            `Nombre: ${nombre}\n` +
            `Correo: ${email}\n` +
            `Razón Social: ${razonSocial}\n` +
            `RUC: ${ruc}\n` +
            `Teléfono: ${telefono}\n` +
            `Mensaje: ${mensaje}`;
        
        const mensajeCodificado = encodeURIComponent(whatsappMensaje);
        window.open(`https://wa.me/51914776669?text=${mensajeCodificado}`, '_blank');
        
        formStatus.textContent = '¡Se abrió WhatsApp! Envía el mensaje para contactarnos.';
        formStatus.className = 'alert alert-success mt-2';
        
        setTimeout(() => {
            contactForm.reset();
            formStatus.textContent = '';
            formStatus.className = '';
        }, 1000);
    });
}
// ===============================
// FORMULARIO DE CONTACTO - FIN
// ===============================
