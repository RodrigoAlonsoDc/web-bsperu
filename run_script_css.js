const fs = require('fs');
const cssStack = `
        /* ============ STACK FLOTANTE: REDES SOCIALES + WHATSAPP ============ */
        .floating-contact-stack {
            position: fixed;
            bottom: 26px;
            right: 26px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .floating-social-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .social-float-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 22px;
            text-decoration: none;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s;
        }
        .social-float-btn:hover {
            transform: scale(1.15) translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
            z-index: 2;
        }

        .social-float-btn.fb { background: #1877F2; }
        .social-float-btn.ig { background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%); }
        .social-float-btn.tk { background: #000000; }
        .social-float-btn.yt { background: #FF0000; }
        .social-float-btn.li { background: #0A66C2; }

        .tooltip-label {
            position: absolute;
            right: calc(100% + 12px);
            background: var(--bg-card);
            color: var(--title-color);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateX(10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
            border: 1px solid var(--border-color);
        }
        .social-float-btn:hover .tooltip-label,
        .wa-float:hover .tooltip-label {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        /* ============ WHATSAPP FLOTANTE ============ */
        .wa-float {
            position: relative;
            bottom: auto;
            right: auto;
            left: auto;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: var(--wa-color);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.45);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s;
        }
        .wa-float:hover {
            transform: scale(1.12);
            box-shadow: 0 8px 26px rgba(37, 211, 102, 0.55);
        }
        .wa-float::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px solid #25d366;
            opacity: 0.8;
            animation: waRadarPulse 2.4s cubic-bezier(0.2, 0.8, 0.4, 1) infinite;
            pointer-events: none;
        }
        @keyframes waRadarPulse {
            0% { transform: scale(0.95); opacity: 0.85; }
            70% { transform: scale(1.3); opacity: 0; }
            100% { transform: scale(1.3); opacity: 0; }
        }`;

function processFile(filename) {
    let content = fs.readFileSync(filename, 'utf8');
    
    if (content.includes('waRadarPulse')) {
        console.log('Already processed ' + filename);
        return;
    }

    // Replace old CSS
    content = content.replace(/\/\* ============ WHATSAPP FLOTANTE ============ \*\/[\s\S]*?@keyframes waRadar {[\s\S]*?}/, cssStack);
    
    // Remove wa-float mobile override
    content = content.replace(/\.wa-float\s*\{\s*bottom:\s*22px;\s*left:\s*22px;\s*width:\s*50px;\s*height:\s*50px;\s*font-size:\s*26px;\s*\}/g, '');
    
    fs.writeFileSync(filename, content, 'utf8');
    console.log('Processed ' + filename);
}

processFile('index.html');
processFile('productos.html');
