const fs = require('fs');
const oldContent = fs.readFileSync('old_producto.php', 'utf8');
let match = oldContent.match(/\/\* ============ BREADCRUMB ============ \*\/[\s\S]*?<\/style>/);
if (match) {
    let fullCss = match[0];
    fullCss = fullCss.replace(/\/\* ============ FOOTER ============ \*\/[\s\S]*?(?=(\/\* ============ STACK FLOTANTE|<\/style>))/, '');
    fullCss = fullCss.replace('</style>', '');
    
    let phpContent = fs.readFileSync('producto.php', 'utf8');
    phpContent = phpContent.replace('</style>', fullCss + '\n</style>');
    fs.writeFileSync('producto.php', phpContent, 'utf8');
    console.log('Success');
} else {
    console.log('Match not found');
}
