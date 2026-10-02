const fs = require('fs');

const htmlStack = `
    <!-- ============ STACK FLOTANTE ============ -->
    <aside class="floating-contact-stack" aria-label="Canales de contacto y redes sociales">
        <div class="floating-social-group">
            <a href="https://www.facebook.com/people/Bsperu/100090456091171/" target="_blank" rel="noopener noreferrer" class="social-float-btn fb" aria-label="Facebook BS Perú" title="Facebook">
                <i class="ph-fill ph-facebook-logo" aria-hidden="true"></i>
                <span class="tooltip-label">Facebook</span>
            </a>
            <a href="https://www.instagram.com/bsp.peru/" target="_blank" rel="noopener noreferrer" class="social-float-btn ig" aria-label="Instagram BS Perú" title="Instagram">
                <i class="ph-fill ph-instagram-logo" aria-hidden="true"></i>
                <span class="tooltip-label">Instagram</span>
            </a>
            <a href="https://www.tiktok.com/@bs_peru?_t=ZS-90uDwonltem&_r=1" target="_blank" rel="noopener noreferrer" class="social-float-btn tk" aria-label="TikTok BS Perú" title="TikTok">
                <i class="ph-fill ph-tiktok-logo" aria-hidden="true"></i>
                <span class="tooltip-label">TikTok</span>
            </a>
            <a href="https://www.youtube.com/@bsperu-BSP" target="_blank" rel="noopener noreferrer" class="social-float-btn yt" aria-label="YouTube BS Perú" title="YouTube">
                <i class="ph-fill ph-youtube-logo" aria-hidden="true"></i>
                <span class="tooltip-label">YouTube</span>
            </a>
            <a href="https://www.linkedin.com/company/bs-per%C3%BA/?viewAsMember=true" target="_blank" rel="noopener noreferrer" class="social-float-btn li" aria-label="LinkedIn BS Perú" title="LinkedIn">
                <i class="ph-fill ph-linkedin-logo" aria-hidden="true"></i>
                <span class="tooltip-label">LinkedIn</span>
            </a>
        </div>
        <a class="wa-float" id="waFloat" href="https://wa.me/51914776669" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp a BS Perú" title="WhatsApp Oficial">
            <i class="ph-fill ph-whatsapp-logo" aria-hidden="true"></i>
            <span class="tooltip-label" style="right: calc(100% + 16px);">WhatsApp</span>
        </a>
    </aside>
`;

function processFile(filename) {
    let content = fs.readFileSync(filename, 'utf8');
    content = content.replace(/<!-- ============ WHATSAPP FLOTANTE ============ -->\r?\n\s*<a class="wa-float"[^>]*>[\s\S]*?<\/a>/, htmlStack);
    fs.writeFileSync(filename, content, 'utf8');
    console.log('Processed ' + filename);
}

processFile('index.html');
processFile('productos.html');
