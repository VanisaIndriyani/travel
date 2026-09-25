<?php
// =============================================
// ADMIN FOOTER - menutup div main-content, main, dan tag html
// =============================================
?>
        </div> <!-- end main-content -->
    </main>
</div>

<!-- CDN untuk Export PDF Langsung (TANPA DOMPDF! Ringan via Browser) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
document.addEventListener('alpine:init', function(){});

window.formatRupiah = function(angka){
    return 'Rp ' + parseInt(angka||0).toLocaleString('id-ID');
};

document.querySelectorAll('[data-confirm]').forEach(function(el){
    el.addEventListener('click', function(e){
        if(!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
});

document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-hapus-id]');
    if (!btn) return;
    e.preventDefault();
    if (window.modalHapus && typeof window.modalHapus.open === 'function') {
        window.modalHapus.open(btn.getAttribute('data-hapus-id'), btn.getAttribute('data-hapus-nama') || '');
    }
});

document.addEventListener('click', function(e){
    const btn = e.target.closest('.js-pdf');
    if (!btn || typeof window.generatePDF !== 'function') return;
    window.generatePDF(
        btn.getAttribute('data-pdf-target'),
        btn.getAttribute('data-pdf-file'),
        btn.getAttribute('data-pdf-orient') || 'p',
        btn.getAttribute('data-pdf-judul') || 'LAPORAN',
        btn.getAttribute('data-pdf-periode') || '',
        btn
    );
});

document.querySelectorAll('.table-wrap.is-cards table.admin-table').forEach(function(table){
    const headers = Array.prototype.slice.call(table.querySelectorAll('thead th')).map(function(th){ return th.textContent.trim(); });
    table.querySelectorAll('tbody tr').forEach(function(tr){
        Array.prototype.slice.call(tr.children).forEach(function(td, i){
            if (!td.hasAttribute('colspan') && headers[i] && !td.getAttribute('data-label')) {
                td.setAttribute('data-label', headers[i]);
            }
        });
    });
});

window.generatePDF = async function(selectorTarget, namaFileOutput, orientasi, judulLaporan, periodeLaporan, btnEl){
    const elTarget = document.querySelector('#pdf-print') || document.querySelector(selectorTarget);
    if(!elTarget){
        alert('Elemen laporan tidak ditemukan!');
        return false;
    }

    const NAMA_TRAVEL = <?= json_encode(SITE_NAME, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    const btnOriginal = btnEl || null;
    let originalHTML = '';
    if(btnOriginal){
        originalHTML = btnOriginal.innerHTML;
        btnOriginal.disabled = true;
        btnOriginal.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyusun PDF...';
    }

    try{
        const canvas = await html2canvas(elTarget, {
            scale: 2.4,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            width: elTarget.scrollWidth,
            windowWidth: elTarget.scrollWidth,
            allowTaint: true
        });

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'p', unit: 'mm', format: [210, 330], compress: true });
        const pageWidth  = 210;
        const pageHeight = 330;
        const marginKiri = 10;
        const marginAtas = 10;
        const marginBawah = 14;
        const maxWidth   = pageWidth - (marginKiri * 2);
        const imgWidth  = maxWidth;
        const imgHeight = imgWidth * (canvas.height / canvas.width);
        const sisaHeightHalaman = pageHeight - marginAtas - marginBawah;

        if(imgHeight <= sisaHeightHalaman){
            pdf.addImage(canvas.toDataURL('image/jpeg', 0.94), 'JPEG', marginKiri, marginAtas, imgWidth, imgHeight);
        }else{
            let sisaMm = imgHeight;
            let canvasY = 0;
            const pxPerMm = canvas.height / imgHeight;
            while(sisaMm > 0.4){
                const potongMm = Math.min(sisaMm, sisaHeightHalaman);
                const potongPx = Math.max(1, Math.round(potongMm * pxPerMm));
                const tmp = document.createElement('canvas');
                tmp.width = canvas.width;
                tmp.height = potongPx;
                const ctx = tmp.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, tmp.width, tmp.height);
                ctx.drawImage(canvas, 0, canvasY, canvas.width, potongPx, 0, 0, tmp.width, tmp.height);
                pdf.addImage(tmp.toDataURL('image/jpeg', 0.94), 'JPEG', marginKiri, marginAtas, imgWidth, potongMm);
                sisaMm -= potongMm;
                canvasY += potongPx;
                if(sisaMm > 0.4) pdf.addPage([210, 330], 'p');
            }
        }

        const totalPages = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setDrawColor(201, 162, 39);
            pdf.setLineWidth(0.35);
            pdf.line(marginKiri, pageHeight - 9, pageWidth - marginKiri, pageHeight - 9);
            pdf.setFontSize(8);
            pdf.setTextColor(10, 22, 40);
            pdf.text(NAMA_TRAVEL + '  ·  Laporan resmi  ·  F4', marginKiri, pageHeight - 5.5);
            pdf.setTextColor(154, 123, 31);
            pdf.text('Hal. ' + String(i) + ' / ' + String(totalPages), pageWidth - marginKiri, pageHeight - 5.5, { align: 'right' });
        }

        const namaFix = (namaFileOutput || 'Laporan_Mustika_Travel').endsWith('.pdf') ? namaFileOutput : (namaFileOutput || 'Laporan_Mustika_Travel') + '.pdf';
        pdf.save(namaFix);
        return true;
    }catch(err){
        console.error('Generate PDF error:', err);
        alert('Gagal generate PDF: ' + err.message);
        return false;
    }finally{
        if(btnOriginal){
            btnOriginal.disabled = false;
            btnOriginal.innerHTML = originalHTML;
        }
    }
};
</script>
</body>
</html>
