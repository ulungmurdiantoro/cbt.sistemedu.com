"""Cetak panduan HTML (docs/panduan/sumber/*.html) menjadi PDF A4 di docs/panduan/.

    pip install playwright        # memakai Google Chrome yang terpasang (channel="chrome")
    python docs/panduan/build.py

Tangkapan layar di docs/panduan/img/ diambil dari aplikasi lokal dengan data uji (bukan data peserta sungguhan).
"""
import pathlib
from playwright.sync_api import sync_playwright

ROOT = pathlib.Path(__file__).resolve().parent
DOCS = {
    "peserta-pendaftaran.html": ("Panduan Peserta - 1 Pendaftaran Sertifikasi.pdf", "Panduan Peserta — Pendaftaran Sertifikasi"),
    "peserta-ujian.html": ("Panduan Peserta - 2 Mengerjakan Ujian.pdf", "Panduan Peserta — Mengerjakan Ujian"),
    "asesor.html": ("Panduan Asesor - Persiapan dan Penilaian Ujian.pdf", "Panduan Asesor — Persiapan & Penilaian Ujian"),
}

FOOTER = """
<div style="width:100%;font-family:'Segoe UI',Arial,sans-serif;font-size:7.5pt;color:#6B7280;padding:0 15mm;
            display:flex;justify-content:space-between;">
  <span>LSP Edukasi Global Cendekia · {title}</span>
  <span>Halaman <span class="pageNumber"></span> / <span class="totalPages"></span></span>
</div>"""

with sync_playwright() as p:
    browser = p.chromium.launch(channel="chrome")
    page = browser.new_page()
    for src, (out, title) in DOCS.items():
        page.goto((ROOT / "sumber" / src).as_uri())
        page.wait_for_load_state("networkidle")
        page.pdf(
            path=str(ROOT / out),
            format="A4",
            print_background=True,
            prefer_css_page_size=True,
            display_header_footer=True,
            header_template="<span></span>",
            footer_template=FOOTER.format(title=title.replace("&", "&amp;")),
        )
        print("ok", out)
    browser.close()
