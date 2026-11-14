/**
 * MaPOS Thermal Receipt Printer Helper
 * Mencetak struk thermal ke iframe terisolasi tanpa merusak tampilan UI aplikasi atau terkena scrollbar modal.
 */
window.printThermalReceipt = function(elementId, paperWidth) {
    if (!paperWidth) {
        paperWidth = '80mm';
    }

    const printArea = document.getElementById(elementId);
    if (!printArea) {
        console.error('Area cetak struk tidak ditemukan:', elementId);
        return;
    }

    const is58 = paperWidth === '58mm';
    const contentWidth = is58 ? '48mm' : '72mm';
    const baseFontSize = is58 ? '10px' : '11px';
    const titleFontSize = is58 ? '13px' : '15px';

    let iframe = document.getElementById('mapos-receipt-iframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'mapos-receipt-iframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.style.visibility = 'hidden';
        document.body.appendChild(iframe);
    }

    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
    iframeDoc.open();

    const receiptHtml = [
        '<!DOCTYPE html>',
        '<html>',
        '<head>',
        '<meta charset="utf-8">',
        '<title>Struk Belanja - MaPOS</title>',
        '<style>',
        '@page { size: ' + paperWidth + ' auto; margin: 0; }',
        '* { box-sizing: border-box; margin: 0; padding: 0; }',
        'body {',
        '  font-family: "Courier New", Courier, monospace;',
        '  font-size: ' + baseFontSize + ';',
        '  line-height: 1.35;',
        '  color: #000;',
        '  background: #fff;',
        '  width: ' + contentWidth + ';',
        '  max-width: ' + contentWidth + ';',
        '  margin: 0 auto;',
        '  padding: 4mm 1.5mm;',
        '}',
        '.text-center { text-align: center; }',
        '.text-right { text-align: right; }',
        '.text-left { text-align: left; }',
        '.font-bold { font-weight: bold; }',
        '.font-semibold { font-weight: 600; }',
        '.text-base { font-size: ' + titleFontSize + '; }',
        '.text-sm { font-size: ' + (is58 ? '11px' : '12px') + '; }',
        '.text-xs { font-size: ' + baseFontSize + '; }',
        '.tracking-wider { letter-spacing: 0.05em; }',
        '.tracking-widest { letter-spacing: 0.1em; }',
        '.uppercase { text-transform: uppercase; }',
        '.italic { font-style: italic; }',
        '.flex { display: flex; }',
        '.justify-between { justify-content: space-between; }',
        '.items-center { align-items: center; }',
        '.border-b { border-bottom: 1px dashed #333; }',
        '.border-t { border-top: 1px dashed #333; }',
        '.border-dashed { border-style: dashed; }',
        '.space-y-0\\.5 > * + * { margin-top: 2px; }',
        '.space-y-1 > * + * { margin-top: 4px; }',
        '.space-y-1\\.5 > * + * { margin-top: 6px; }',
        '.space-y-2 > * + * { margin-top: 8px; }',
        '.pb-1 { padding-bottom: 4px; }',
        '.pb-2 { padding-bottom: 6px; }',
        '.pt-1 { padding-top: 4px; }',
        '.pt-2 { padding-top: 6px; }',
        '.py-1 { padding-top: 4px; padding-bottom: 4px; }',
        '.p-4 { padding: 0; }',
        '.shadow-inner, .rounded-lg { box-shadow: none !important; border-radius: 0 !important; }',
        '</style>',
        '</head>',
        '<body>',
        printArea.innerHTML,
        '</body>',
        '</html>'
    ].join('\n');

    iframeDoc.write(receiptHtml);
    iframeDoc.close();

    setTimeout(function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 250);
};
