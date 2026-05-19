import os
import re
import pandas as pd
import datetime

import qrcode
from PIL import Image

from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

from reportlab.platypus import Table, TableStyle, Image as RLImage, Paragraph
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib import colors
from reportlab.lib.utils import simpleSplit


# =========================
# PATH CONFIG
# =========================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

EXCEL_FILE = os.path.join(BASE_DIR, "data", "Suryaningsih.xlsx")

ASSETS_DIR = os.path.join(BASE_DIR, "assets")
LOGO_LEFT_PATH  = os.path.join(ASSETS_DIR, "logo2.jpg")         # Edukia
LOGO_RIGHT_PATH = os.path.join(ASSETS_DIR, "logo_iaf_kan.png")  # IAF/KAN
TTD_PATH        = os.path.join(ASSETS_DIR, "ttd.png")           # optional

OUTPUT_ROOT = os.path.join(BASE_DIR, "output", "sk")

# Fonts (Cambria) - optional
FONTS_DIR = os.path.join(ASSETS_DIR, "fonts")
FONT_CAMBRIA_REG    = os.path.join(FONTS_DIR, "Cambria.ttf")
FONT_CAMBRIA_BOLD   = os.path.join(FONTS_DIR, "Cambria Bold.ttf")
FONT_CAMBRIA_ITALIC = os.path.join(FONTS_DIR, "Cambria Italic.ttf")

# =========================
# UNIT CONVERSION
# =========================
CM = 28.346  # 1 cm in points

# =========================
# PAGE
# =========================
PAGE_W, PAGE_H = A4  # portrait

# =========================
# HEADER MARGINS (cm)
# =========================
HEADER_MARGIN_TOP    = 0.4 * CM
HEADER_MARGIN_LEFT   = 1.27 * CM
HEADER_MARGIN_RIGHT  = 1.27 * CM
HEADER_MARGIN_BOTTOM = 0.55 * CM

# =========================
# CONTENT MARGINS (cm)
# =========================
CONTENT_MARGIN_TOP    = 3.54 * CM
CONTENT_MARGIN_LEFT   = 2.54 * CM
CONTENT_MARGIN_RIGHT  = 2.54 * CM
CONTENT_MARGIN_BOTTOM = 2.54 * CM

M_LEFT   = CONTENT_MARGIN_LEFT
M_RIGHT  = CONTENT_MARGIN_RIGHT
M_TOP    = CONTENT_MARGIN_TOP
M_BOTTOM = CONTENT_MARGIN_BOTTOM

CONTENT_W = PAGE_W - M_LEFT - M_RIGHT

FS = 12
LEADING = 16


# =========================
# TEXT TEMPLATE
# =========================
MENIMBANG_ITEMS = [
    "Bahwa kepada peserta Uji Kompetensi Sertifikasi yang dinyatakan <font name='Cambria-Bold'>Lulus</font> akan diberikan Sertifikat Kompetensi sedangkan yang dinyatakan <font name='Cambria-Bold'>Tidak Lulus</font> akan diterbitkan Surat Keputusan Ketidaklulusan;",
    "Bahwa sehubungan dengan poin 1 penetapan peserta Uji Kompetensi {skema} yang dinyatakan <b>Lulus</b> atau <b>Belum Lulus</b> ditetapkan melalui Keputusan Ketua LSP Edukasi Global Cendekia",
]

MENGINGAT_ITEMS = [
    "Standar SNI ISO/IEC 17024:2012 tentang Penilaian Kesesuaian – Persyaratan Umum untuk Lembaga Sertifikasi Person;",
    "Undang-undang Nomor 25 Tahun 2009 tentang Pelayanan Publik;",
    "Undang-undang Republik Indonesia Nomor 5 Tahun 2014 tentang Aparatur Sipil Negara (ASN);",
    "Undang-undang Nomor 20 Tahun 2014 tentang Standardisasi dan Penilaian Kesesuaian;",
    "Undang-undang Nomor 13 Tahun 2003 tentang Ketenagakerjaan;",
    "Peraturan Presiden Republik Indonesia Nomor 8 Tahun 2012 tentang Kerangka Kualifikasi Nasional Indonesia;",
    "Peraturan Menteri Tenaga Kerja dan Transmigrasi Republik Indonesia Nomor 5 Tahun 2014 tentang Sistem Standardisasi Kompetensi Kerja Nasional;",
    "Peraturan Menteri Tenaga Kerja dan Transmigrasi Republik Indonesia Nomor 8 Tahun 2012 tentang Tata Cara Penetapan Standar Kompetensi Kerja Nasional Indonesia;",
    "Undang-undang Republik Indonesia Nomor 11 Tahun 2019 tentang Sistem Nasional Ilmu Pengetahuan dan Teknologi;",
    "Peraturan Pemerintah Republik Indonesia Nomor 11 Tahun 2017 tentang Manajemen Pegawai Negeri Sipil;",
    "Peraturan Pemerintah Republik Indonesia Nomor 34 Tahun 2018 tentang Sistem Standardisasi dan Penilaian Kesesuaian Nasional;",
    "Peraturan Menteri Pendayagunaan Aparatur Negara dan Reformasi Birokrasi Nomor 38 Tahun 2017 tentang Standar Kompetensi Jabatan Aparatur Sipil Negara;",
    "Peraturan Menteri Riset, Teknologi, dan Pendidikan Tinggi Republik Indonesia Nomor 6 Tahun 2022 tentang Ijazah, Sertifikat Kompetensi, Sertifikat Profesi, Gelar, dan Tata Cara Penulisan Gelar di Perguruan Tinggi;",
]


# =========================
# HELPERS
# =========================
def safe_str(v) -> str:
    if v is None:
        return ""
    if isinstance(v, float) and pd.isna(v):
        return ""
    s = str(v).strip()
    return "" if s.lower() == "nan" else s


def sanitize_filename(s: str) -> str:
    s = str(s).strip()
    s = re.sub(r"[\\/:*?\"<>|]+", "_", s)
    s = re.sub(r"\s+", "_", s)
    return s


def register_cambria_fonts():
    if os.path.exists(FONT_CAMBRIA_REG):
        pdfmetrics.registerFont(TTFont("Cambria", FONT_CAMBRIA_REG))
    if os.path.exists(FONT_CAMBRIA_BOLD):
        pdfmetrics.registerFont(TTFont("Cambria-Bold", FONT_CAMBRIA_BOLD))
    if os.path.exists(FONT_CAMBRIA_ITALIC):
        pdfmetrics.registerFont(TTFont("Cambria-Italic", FONT_CAMBRIA_ITALIC))


def fnt(name: str) -> str:
    reg = set(pdfmetrics.getRegisteredFontNames())
    return name if name in reg else "Helvetica"


def to_id_date(dt: datetime.date) -> str:
    bulan = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ]
    return f"{dt.day:02d} {bulan[dt.month - 1]} {dt.year}"


def parse_excel_date_to_id(v) -> str:
    raw = safe_str(v)
    if not raw:
        return "-"
    try:
        dt = pd.to_datetime(raw).date()
        return to_id_date(dt)
    except Exception:
        return raw
def format_hari_tanggal(hari: str, tanggal: str) -> str:
    if hari and tanggal:
        return f"{hari}, {tanggal}"
    if tanggal:
        return tanggal
    return "-"

def add_hari_indonesia(tanggal_str: str) -> str:
    if not tanggal_str or tanggal_str == "-":
        return "-"

    try:
        dt = pd.to_datetime(tanggal_str, dayfirst=True)
        hari_map = {
            0: "Senin",
            1: "Selasa",
            2: "Rabu",
            3: "Kamis",
            4: "Jumat",
            5: "Sabtu",
            6: "Minggu",
        }
        hari = hari_map[dt.weekday()]
        return f"{hari}, {tanggal_str}"
    except Exception:
        return tanggal_str


def to_id_day_date(dt: datetime.date) -> str:
    hari = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"]
    bulan = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ]
    return f"{hari[dt.weekday()]}, {dt.day:02d} {bulan[dt.month-1]} {dt.year}"


def parse_excel_daydate_to_id(v) -> str:
    raw = safe_str(v)
    if not raw:
        return "-"
    try:
        dt = pd.to_datetime(raw).date()
        return to_id_day_date(dt)
    except Exception:
        return raw


def draw_left(c: canvas.Canvas, x: float, y: float, text: str, font_name: str, font_size: int):
    c.setFont(font_name, font_size)
    c.drawString(x, y, text)


def draw_centered(c: canvas.Canvas, y: float, text: str, font_name: str, font_size: int):
    c.setFont(font_name, font_size)
    c.drawCentredString(PAGE_W / 2, y, text)


def draw_justify_paragraph(c, x, y, text, width, font_name="Cambria", font_size=12, leading=16, indent=0):
    style = ParagraphStyle(
        name="JustifyStyle",
        fontName=font_name,
        fontSize=font_size,
        leading=leading,
        alignment=4,  # justify
        firstLineIndent=indent,
        spaceAfter=0,
        spaceBefore=0,
    )
    p = Paragraph(text, style)
    w, h = p.wrap(width, PAGE_H)
    p.drawOn(c, x, y - h)
    return y - h


def draw_table(c: canvas.Canvas, tbl: Table, x: float, y_top: float, wrap_w: float = None):
    if wrap_w is None:
        wrap_w = PAGE_W - x - M_RIGHT
    tbl.wrapOn(c, wrap_w, PAGE_H)
    w, h = tbl.wrap(wrap_w, PAGE_H)
    y_bottom = y_top - h
    tbl.drawOn(c, x, y_bottom)
    return y_bottom


def br_small(size=6):
    return f"<br/><font size='{size}' color='white'>.</font>"


# =========================
# QR GENERATOR
# =========================
def build_qr(no_sk: str, tgl_rilis: str, out_path: str):
    qr_data = f"""Dokumen ini telah ditandatangani secara digital oleh:
Dr. Agung Yulianto, M.Si
Sebagai Ketua LSP
LSP Edukasi Global Cendikia

Dengan No Dokumen:
No: {no_sk}
Tanggal: {tgl_rilis}

Link: https://verifikasi-sertifikat.lspedukia.id/sk/{no_sk}
"""

    qr = qrcode.QRCode(
        version=None,
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        box_size=10,
        border=4,
    )
    qr.add_data(qr_data)
    qr.make(fit=False)  # ukuran QR fix
    qr_img = qr.make_image(fill_color="black", back_color="white").convert("RGBA")

    # Embed logo (proporsional)
    if os.path.exists(LOGO_LEFT_PATH):
        logo = Image.open(LOGO_LEFT_PATH).convert("RGBA")

        qr_w, qr_h = qr_img.size
        logo_w = int(qr_w * 0.40)  # ubah 0.30/0.35/0.40
        aspect = logo.size[1] / logo.size[0]
        logo_h = int(logo_w * aspect)

        logo = logo.resize((logo_w, logo_h), Image.Resampling.LANCZOS)
        pos = ((qr_w - logo_w) // 2, (qr_h - logo_h) // 2)
        qr_img.alpha_composite(logo, dest=pos)

    qr_img.convert("RGB").save(out_path)


# =========================
# HEADER (SP STYLE)
# =========================
def draw_header_sk(c: canvas.Canvas):
    y_top = PAGE_H - HEADER_MARGIN_TOP

    logo_left_w_pt, logo_left_h_pt = 3.99 * CM, 1.67 * CM
    logo_right_w_pt, logo_right_h_pt = 5.2 * CM, 1.81 * CM

    p_style = ParagraphStyle(
        name="HeaderMid",
        fontName=fnt("Cambria"),
        fontSize=12,
        leading=14,
        alignment=1,
        spaceBefore=0,
        spaceAfter=0
    )

    mid_cell = Paragraph("", p_style)

    left_cell = RLImage(LOGO_LEFT_PATH, width=logo_left_w_pt, height=logo_left_h_pt) if os.path.exists(LOGO_LEFT_PATH) else Paragraph("", p_style)
    right_cell = RLImage(LOGO_RIGHT_PATH, width=logo_right_w_pt, height=logo_right_h_pt) if os.path.exists(LOGO_RIGHT_PATH) else Paragraph("", p_style)

    header_w = PAGE_W - HEADER_MARGIN_LEFT - HEADER_MARGIN_RIGHT

    gap = 6
    col_left = logo_left_w_pt + gap
    col_right = logo_right_w_pt + gap
    col_mid = header_w - col_left - col_right
    if col_mid < 20:
        col_mid = 20

    row_h = max(logo_left_h_pt, logo_right_h_pt, 70)

    table = Table([[left_cell, mid_cell, right_cell]],
                  colWidths=[col_left, col_mid, col_right],
                  rowHeights=[row_h])

    table.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("ALIGN", (0, 0), (0, 0), "LEFT"),
        ("ALIGN", (1, 0), (1, 0), "CENTER"),
        ("ALIGN", (2, 0), (2, 0), "RIGHT"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
        ("GRID", (0, 0), (-1, -1), 0, colors.white),
    ]))

    table_x = HEADER_MARGIN_LEFT
    table_y = y_top - row_h

    table.wrapOn(c, header_w, PAGE_H)
    table.drawOn(c, table_x, table_y)

    return table_y - 13


# =========================
# PAGE 1
# =========================

def draw_centered_multiline(
    c, y, text, font_name, font_size,
    max_width_pt,
    line_gap_pt=16,
    force_split_paren=True
):
    text = (text or "").strip()
    if not text:
        return y

    # Rapikan spacing yang kadang nempel (contoh: "MANAGEMENTOFFICER")
    text = re.sub(r"([A-Z])OFFICER\b", r"\1 OFFICER", text)

    # Paksa enter sebelum "(" agar jadi 2 blok baris
    if force_split_paren and "(" in text:
        before, after = text.split("(", 1)
        part1 = before.strip()
        part2 = "(" + after.strip()

        lines = []
        if part1:
            lines += simpleSplit(part1, font_name, font_size, max_width_pt)
        if part2:
            lines += simpleSplit(part2, font_name, font_size, max_width_pt)
    else:
        lines = simpleSplit(text, font_name, font_size, max_width_pt)

    # buang line kosong biar tidak "double print"
    lines = [ln.strip() for ln in lines if ln and ln.strip()]

    for line in lines:
        draw_centered(c, y, line, font_name, font_size)  # y = pt
        y -= line_gap_pt  # turun 16 pt per baris (bukan /72)

    return y

def draw_sk_page1(c: canvas.Canvas, d: dict):
    y = draw_header_sk(c)

    draw_centered(c, y, "SURAT KEPUTUSAN", fnt("Cambria-Bold"), 12)
    y -= 16
    draw_centered(c, y, "LEMBAGA SERTIFIKASI PERSON EDUKASI GLOBAL CENDEKIA", fnt("Cambria-Bold"), 12)
    y -= 16
    draw_centered(c, y, f"Nomor: {d.get('no_sk','-')}", fnt("Cambria-Bold"), 12)
    y -= 28

    # ...
    draw_centered(c, y, "TENTANG", fnt("Cambria-Bold"), 12)
    y -= 16

    judul = (d.get("judul_skema") or f"SERTIFIKASI {d.get('skema', '-')}".strip()).upper()

    y = draw_centered_multiline(
        c, y, judul,
        fnt("Cambria-Bold"), 12,
        max_width_pt=CONTENT_W,
        line_gap_pt=16
    )

    y -= 14

    # Menimbang
    draw_left(c, M_LEFT, y, "Menimbang :", fnt("Cambria-Bold"), 12)
    y -= 7

    menimbang_table_data = []
    for i, text in enumerate(MENIMBANG_ITEMS, start=1):
        text_fmt = text.format(skema=(d.get("skema", "-") or "-"))
        menimbang_table_data.append([
            Paragraph(f"{i}.", ParagraphStyle(
                name=f"NumCambria_MEN_{i}", fontName=fnt("Cambria"), fontSize=12, leading=LEADING, alignment=0
            )),
            Paragraph(text_fmt, ParagraphStyle(
                name=f"MenimbangJustify_{i}", fontName=fnt("Cambria"), fontSize=12, leading=LEADING, alignment=4
            )),
        ])

    TABLE_W = CONTENT_W
    NUM_COL_W = 0.6 * CM
    t_menimbang = Table(menimbang_table_data, colWidths=[NUM_COL_W, TABLE_W - NUM_COL_W], repeatRows=0)
    t_menimbang.setStyle(TableStyle([
        ("GRID", (0, 0), (-1, -1), 0, colors.white),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
    ]))
    y = draw_table(c, t_menimbang, M_LEFT, y, wrap_w=TABLE_W)
    y -= 21

    # Mengingat
    draw_left(c, M_LEFT, y, "Mengingat :", fnt("Cambria-Bold"), 12)
    y -= 7

    mengingat_table_data = []
    for i, text in enumerate(MENGINGAT_ITEMS, start=1):
        mengingat_table_data.append([
            Paragraph(f"{i}.", ParagraphStyle(
                name=f"NumCambria_ING_{i}", fontName=fnt("Cambria"), fontSize=12, leading=LEADING, alignment=0
            )),
            Paragraph(text, ParagraphStyle(
                name=f"MengingatJustify_{i}", fontName=fnt("Cambria"), fontSize=12, leading=LEADING, alignment=4
            )),
        ])

    TABLE_W_ING = CONTENT_W
    NUM_COL_W_ING = 0.6 * CM
    t_mengingat = Table(mengingat_table_data, colWidths=[NUM_COL_W_ING, TABLE_W_ING - NUM_COL_W_ING], repeatRows=0)
    t_mengingat.setStyle(TableStyle([
        ("GRID", (0, 0), (-1, -1), 0, colors.white),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
    ]))
    y = draw_table(c, t_mengingat, M_LEFT, y, wrap_w=TABLE_W_ING)
    y -= 6


# =========================
# PAGE 2
# =========================
def draw_sk_page2(c: canvas.Canvas, d: dict, qr_path: str):
    y = draw_header_sk(c)
    y -= 28

    draw_centered(c, y, "MEMUTUSKAN", fnt("Cambria-Bold"), 12)
    y -= 16

    skema = d.get("skema", "-")
    nama = d.get("nama_lengkap", "-")
    kategori = safe_str(d.get("kategori", ""))  # mis: "Bagus Sekali (Excellence)" atau lainnya
    gelar = safe_str(d.get("gelar", ""))        # gelar non akademik
    status_raw = safe_str(d.get("status", "")).upper()

    if status_raw == "KOMPETEN":
        status_lulus = "LULUS"
        hak_kata = "berhak"
    elif status_raw == "TIDAK KOMPETEN":
        status_lulus = "TIDAK LULUS"
        hak_kata = "tidak berhak"
    else:
        status_lulus = "-"
        hak_kata = "berhak"

    status_lulus_fmt = status_lulus.capitalize()
    kategori_fmt = kategori.capitalize() if kategori else "-"
    status_fmt = status_raw.capitalize() if status_raw else "-"

    menetapkan_rows = [
        ("Menetapkan", ":", ""),
        ("PERTAMA", ":",
         f"Atas nama : {nama}{br_small(6)}"
         f"Telah mengikuti Uji Kompetensi {skema} melalui penilaian langsung "
         f"dan sertifikasi langsung dinyatakan : "
         f"<font name='Cambria-Bold'>{status_fmt}/{status_lulus_fmt}</font>{br_small(6)}"
         f"dengan Kategori: <font name='Cambria-Bold'>{kategori_fmt}</font>"
         ),
    ]

    # logika untuk KEDUA
    if gelar.strip():
        menetapkan_rows.append((
            "KEDUA", ":",
            f"Kepada peserta Uji Kompetensi yang dinyatakan "
            f"{status_fmt}/{status_lulus_fmt} "
            f"{hak_kata} mencantumkan gelar non akademik "
            f"<font name='Cambria-Bold'>{gelar}</font> "
            f"di belakang nama selama masa berlakunya sertifikat"
        ))
    else:
        menetapkan_rows.append((
            "KEDUA", ":",
            "Kepada peserta Uji Kompetensi yang dinyatakan Lulus/ Kompeten "
            "berhak menggunakan sertifikat sertifikasi sebagai alat bukti keahlian "
            "sesuai jenis skema sertifikasinya selama masa berlakunya sertifikat"
        ))

    # lanjutkan baris KETIGA
    menetapkan_rows.append((
        "KETIGA", ":",
        f"Sehubungan dengan hal tersebut pada poin PERTAMA ditetapkan sebagai peserta "
        f"Uji Kompetensi {skema} Perguruan Tinggi melalui Keputusan Ketua LSP Edukasi Global Cendekia"
    ))

    ps_label = ParagraphStyle(
        name="MenetapkanLabel", fontName=fnt("Cambria"), fontSize=12, leading=LEADING, alignment=0
    )
    ps_colon = ParagraphStyle(
        name="MenetapkanColon", fontName=fnt("Cambria"), fontSize=12, leading=LEADING, alignment=1
    )
    ps_text = ParagraphStyle(
        name="MenetapkanText", fontName=fnt("Cambria"), fontSize=12, leading=18, alignment=4
    )

    menetapkan_table_data = [[Paragraph(lbl, ps_label), Paragraph(col, ps_colon), Paragraph(txt, ps_text)]
                             for (lbl, col, txt) in menetapkan_rows]

    COL_LABEL = 3.2 * CM
    COL_COLON = 0.35 * CM
    COL_TEXT = CONTENT_W - COL_LABEL - COL_COLON

    t_menetapkan = Table(menetapkan_table_data, colWidths=[COL_LABEL, COL_COLON, COL_TEXT], repeatRows=0)
    t_menetapkan.setStyle(TableStyle([
        ("GRID", (0, 0), (-1, -1), 0, colors.white),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
    ]))
    y = draw_table(c, t_menetapkan, M_LEFT, y, wrap_w=CONTENT_W)
    y -= 6

    y -= 8
    y = draw_justify_paragraph(
        c, M_LEFT, y,
        "Demikian surat keputusan ini ditetapkan, apabila terdapat kekeliruan dalam penerbitan "
        "surat keputusan ini maka akan diperbaiki sebagaimana mestinya.",
        width=CONTENT_W, font_name=fnt("Cambria"), font_size=12, leading=LEADING, indent=0
    )

    RIGHT_BLOCK_W = 10 * CM
    RIGHT_X = PAGE_W - M_RIGHT - RIGHT_BLOCK_W

    kota = d.get("kota", "Semarang")
    tgl_ditetapkan = d.get("tgl_ditetapkan_id", d.get("tgl_rilis_id", "-"))

    ps_right = ParagraphStyle(
        name="RightBlockText", fontName=fnt("Cambria"), fontSize=12, leading=LEADING, alignment=0
    )

    ditetapkan_table = Table(
        [
            [Paragraph("Ditetapkan di", ps_right), Paragraph(":", ps_right), Paragraph(kota, ps_right)],
            [Paragraph("Pada tanggal", ps_right), Paragraph(":", ps_right), Paragraph(tgl_ditetapkan, ps_right)],
        ],
        colWidths=[3.6 * CM, 0.3 * CM, RIGHT_BLOCK_W - 3.9 * CM]
    )
    ditetapkan_table.setStyle(TableStyle([
        ("GRID", (0, 0), (-1, -1), 0, colors.white),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 2),
    ]))

    y -= 10
    y = draw_table(c, ditetapkan_table, RIGHT_X, y, wrap_w=RIGHT_BLOCK_W)

    QR_SIZE = 3.2 * CM
    QR_X = RIGHT_X
    QR_Y = y - QR_SIZE - 6

    if os.path.exists(qr_path):
        c.drawImage(qr_path, QR_X, QR_Y, width=QR_SIZE, height=QR_SIZE, mask="auto")

    ps_qr_note = ParagraphStyle(
        name="QRNote", fontName=fnt("Cambria-Italic"), fontSize=12, leading=18, alignment=0
    )

    qr_note_text = (
        "Dokumen ini telah ditandatangani<br/>"
        "secara elektronik menggunakan<br/>"
        "system digital yang terintegrasi"
    )

    NOTE_X = QR_X + QR_SIZE + 8
    NOTE_W = RIGHT_BLOCK_W - QR_SIZE - 8
    NOTE_OFFSET_Y = 10

    p_note = Paragraph(qr_note_text, ps_qr_note)
    w_n, h_n = p_note.wrap(NOTE_W, PAGE_H)

    NOTE_Y = QR_Y + QR_SIZE - h_n - NOTE_OFFSET_Y
    p_note.drawOn(c, NOTE_X, NOTE_Y)

    block_bottom_y = min(QR_Y, NOTE_Y)

    ps_qr_sign = ParagraphStyle(
        name="QRSigner", fontName=fnt("Cambria-Bold"), fontSize=12, leading=16, alignment=0
    )

    qr_sign_text = (
        "Dr. Agung Yulianto, M.Si.<br/>"
        "Ketua LSP Edukasi Global Cendekia"
    )

    SIGN_GAP = 8
    SIGN_X = QR_X
    SIGN_W = RIGHT_BLOCK_W

    p_sign = Paragraph(qr_sign_text, ps_qr_sign)
    w_s, h_s = p_sign.wrap(SIGN_W, PAGE_H)

    SIGN_Y = block_bottom_y - SIGN_GAP - h_s
    p_sign.drawOn(c, SIGN_X, SIGN_Y)

def draw_sk_page3(c: canvas.Canvas, d: dict):
    y = draw_header_sk(c)
    y -= 10

    draw_left(c, M_LEFT, y, "Lampiran Hasil Penilaian:", fnt("Cambria-Bold"), 14)
    y -= 22

    ps_lbl = ParagraphStyle(
        name="LampLbl",
        fontName=fnt("Cambria"),
        fontSize=12,
        leading=14,
        alignment=0,
        spaceBefore=0,
        spaceAfter=0,
    )
    ps_val = ParagraphStyle(
        name="LampVal",
        fontName=fnt("Cambria"),
        fontSize=12,
        leading=14,
        alignment=0,
        spaceBefore=0,
        spaceAfter=0,
    )

    # Pelaksanaan + Batch (kalau ada)
    pel = d.get("lamp_pelaksanaan", d.get("skema", "-"))
    bt = safe_str(d.get("batch", "")).strip()
    pel_full = f"{pel} <font name='Cambria-Italic'>Batch</font> {bt}".strip() if bt else pel

    # --- NIK: jangan dipotong karakter pertama secara paksa
    nik_raw = safe_str(d.get("nik", "-"))
    nik_clean = nik_raw.lstrip("'").strip() if nik_raw else "-"

    ident_rows = [
        ("Nama Peserta", d.get("nama_lengkap", "-")),
        ("NIK", nik_clean or "-"),
        ("Pelaksanaan", pel_full or "-"),
        ("Hari/Tanggal Ujian", format_hari_tanggal(d.get("hari", ""), d.get("held_on", "-"))),
        ("Tempat Uji Kompetensi", "Daring/<font name='Cambria-Italic'>Online</font>"),
    ]

    ident_table_data = []
    for lbl, val in ident_rows:
        ident_table_data.append([
            Paragraph(lbl, ps_lbl),
            Paragraph(":", ps_lbl),
            Paragraph(safe_str(val) or "-", ps_val),
        ])

    LAMP_X = M_LEFT
    LAMP_W = CONTENT_W

    COL_A = 4.5 * CM
    COL_B = 0.35 * CM
    COL_C = LAMP_W - COL_A - COL_B

    t_ident = Table(ident_table_data, colWidths=[COL_A, COL_B, COL_C])
    t_ident.setStyle(TableStyle([
        ("GRID", (0, 0), (-1, -1), 0, colors.white),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("ALIGN", (0, 0), (0, -1), "LEFT"),
        ("ALIGN", (1, 0), (1, -1), "CENTER"),
        ("ALIGN", (2, 0), (2, -1), "LEFT"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 2),
    ]))

    y = draw_table(c, t_ident, LAMP_X, y, wrap_w=LAMP_W)
    y -= 18

    # =========================
    # TABEL NILAI (FIX: data_nilai didefinisikan)
    # =========================
    ps_head = ParagraphStyle(
        name="NilaiHead",
        fontName=fnt("Cambria-Bold"),
        fontSize=11,
        leading=12,
        alignment=1,  # center
        spaceBefore=0,
        spaceAfter=0,
    )
    ps_cell = ParagraphStyle(
        name="NilaiCell",
        fontName=fnt("Cambria"),
        fontSize=11,
        leading=12,
        alignment=1,  # center
        spaceBefore=0,
        spaceAfter=0,
    )
    ps_cell_bold = ParagraphStyle(
        name="NilaiCellBold",
        fontName=fnt("Cambria-Bold"),
        fontSize=11,
        leading=12,
        alignment=1,  # center
        spaceBefore=0,
        spaceAfter=0,
    )

    # Header 2 baris + 1 baris nilai
    data_nilai = [
        [
            Paragraph("NILAI WAWANCARA", ps_head),
            Paragraph("NILAI PILIHAN<br/>GANDA", ps_head),
            Paragraph("NILAI ESAI", ps_head),
            Paragraph("REKAPITULASI HASIL ASESMEN ", ps_head),     # akan di-span dengan kolom sebelahnya
            "",                              # placeholder untuk span
            Paragraph("KATEGORI", ps_head),
        ],
        [
            "", "", "",
            Paragraph("HASIL NILAI", ps_head),
            Paragraph("STATUS", ps_head),
            "",
        ],
        [
            Paragraph(safe_str(d.get("nil_wawancara", "-")) or "-", ps_cell),
            Paragraph(safe_str(d.get("nil_pilgan", "-")) or "-", ps_cell),
            Paragraph(safe_str(d.get("nil_esai", "-")) or "-", ps_cell),
            Paragraph(safe_str(d.get("hasil_nilai", "-")) or "-", ps_cell_bold),
            Paragraph(safe_str(d.get("status", "-")) or "-", ps_cell_bold),
            Paragraph(safe_str(d.get("kategori", "-")) or "-", ps_cell),
        ],
    ]

    # ===== Lebar kolom: AUTO SCALE supaya tidak pernah minus =====
    base = [3.3 * CM, 3.6 * CM, 2.8 * CM, 3.0 * CM, 3.0 * CM]  # w1..w5
    min_w6 = 2.4 * CM  # minimal kolom KATEGORI

    sum_base = sum(base)
    available_for_1to5 = LAMP_W - min_w6

    if available_for_1to5 < 3.0 * CM:
        min_w6 = max(1.6 * CM, CONTENT_W * 0.22)
        available_for_1to5 = max(1.0 * CM, CONTENT_W - min_w6)

    if sum_base > available_for_1to5:
        scale = available_for_1to5 / sum_base
        widths_1to5 = [w * scale for w in base]
    else:
        widths_1to5 = base

    w1, w2, w3, w4, w5 = widths_1to5

    w6 = max(min_w6, LAMP_W - (w1 + w2 + w3 + w4 + w5))
    col_widths = [w1, w2, w3, w4, w5, w6]

    t_nilai = Table(
        data_nilai,
        colWidths=col_widths,
        rowHeights=[0.95 * CM, 0.85 * CM, 1.2 * CM]
    )

    header_bg = colors.HexColor("#BFCDE9")
    status_bg = colors.HexColor("#D9EAD3")

    t_nilai.setStyle(TableStyle([
        ("GRID", (0, 0), (-1, -1), 0.8, colors.black),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("ALIGN", (0, 0), (-1, -1), "CENTER"),

        ("BACKGROUND", (0, 0), (-1, 0), header_bg),
        ("BACKGROUND", (0, 1), (-1, 1), header_bg),

        ("SPAN", (3, 0), (4, 0)),  # HASIL men-span NILAI+STATUS
        ("SPAN", (0, 0), (0, 1)),
        ("SPAN", (1, 0), (1, 1)),
        ("SPAN", (2, 0), (2, 1)),
        ("SPAN", (5, 0), (5, 1)),

        ("LEFTPADDING", (0, 0), (-1, -1), 4),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 6),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 6),

        # highlight STATUS (kolom ke-5 index=4) pada baris nilai (baris index=2)
        ("BACKGROUND", (4, 2), (4, 2), status_bg),
    ]))

    y = draw_table(c, t_nilai, LAMP_X, y, wrap_w=LAMP_W)
    y -= 28
    draw_left(c, LAMP_X, y, "Kategori:", fnt("Cambria"), 12)
    y -= 6

    kategori_rows = [
        ("1.", "&lt; Batas Minimal (60)", ": Remidial <font name='Cambria-Italic'>(Re-Assessment)</font>"),
        ("2.", "&gt; 60.00 - &lt; 70.00", ": Cukup <font name='Cambria-Italic'>(Average)</font>"),
        ("3.", "&gt; 70.01 - &lt; 80.00", ": Bagus <font name='Cambria-Italic'>(Good)</font>"),
        ("4.", "&gt; 80.01 - &lt; 100.00", ": Bagus Sekali <font name='Cambria-Italic'>(Excellence)</font>"),
    ]

    ps_no = ParagraphStyle(
        name="KatNo",
        fontName=fnt("Cambria"),
        fontSize=12,
        leading=14,
        alignment=0,
    )
    ps_mid = ParagraphStyle(
        name="KatMid",
        fontName=fnt("Cambria"),
        fontSize=12,
        leading=14,
        alignment=0,
    )
    ps_desc = ParagraphStyle(
        name="KatDesc",
        fontName=fnt("Cambria"),
        fontSize=12,
        leading=14,
        alignment=0,
    )

    kategori_table_data = [
        [Paragraph(no, ps_no), Paragraph(rng, ps_mid), Paragraph(desc, ps_desc)]
        for no, rng, desc in kategori_rows
    ]

    COL_NO = 0.5 * CM
    COL_RNG = 4.6 * CM  # lebar kolom range (silakan adjust 4.8–6.0 cm)
    COL_DESC = LAMP_W - COL_NO - COL_RNG

    t_kategori = Table(
        kategori_table_data,
        colWidths=[COL_NO, COL_RNG, COL_DESC],
    )

    t_kategori.setStyle(TableStyle([
        ("GRID", (0, 0), (-1, -1), 0, colors.white),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),

        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 1),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 1),

        # optional biar kolom range & desc tidak “nempel” banget
        ("RIGHTPADDING", (1, 0), (1, -1), 6),
    ]))

    y = draw_table(c, t_kategori, LAMP_X, y, wrap_w=LAMP_W)


# =========================
# MAIN
# =========================
def main():
    register_cambria_fonts()
    df = pd.read_excel(EXCEL_FILE)

    # =========================
    # OUTPUT DIR BERDASARKAN NAMA FILE EXCEL
    # =========================
    excel_base = os.path.splitext(os.path.basename(EXCEL_FILE))[0]
    excel_folder = sanitize_filename(excel_base)

    OUTPUT_ROOT = os.path.join(BASE_DIR, "output", "sk")
    OUT_DIR = os.path.join(OUTPUT_ROOT, excel_folder)
    OUT_QR = os.path.join(OUT_DIR, "qr")
    os.makedirs(OUT_QR, exist_ok=True)

    for idx, row in df.iterrows():
        try:
            no_sk = safe_str(row.get("no_sk", ""))
            if not no_sk:
                print(f"⏭️ Skip row {idx}: no_sk kosong.")
                continue

            nama_lengkap = safe_str(row.get("nama_lengkap", "")) or "Tanpa_Nama"
            safe_nama = sanitize_filename(nama_lengkap)

            tgl_rilis_raw = (
                safe_str(row.get("tgl_rilis", "")) or
                safe_str(row.get("tgl_rilis_id", "")) or
                safe_str(row.get("tgl_rilis_eng", "")) or "-"
            )
            tgl_rilis_id = parse_excel_date_to_id(tgl_rilis_raw)

            d = {
                "no_sk": no_sk,
                "tgl_rilis": tgl_rilis_raw,
                "tgl_rilis_id": tgl_rilis_id,
                "tgl_ditetapkan_id": parse_excel_date_to_id(
                    row.get("tgl_ditetapkan", tgl_rilis_raw)
                ),
                "skema": safe_str(row.get("skema", ""))
                          or safe_str(row.get("skema_eng", ""))
                          or "-",
                "judul_skema": safe_str(row.get("judul_skema", "")),
                "nama_lengkap": nama_lengkap,
                "hasil": safe_str(row.get("hasil", "")) or "Lulus/Kompeten",
                "kota": safe_str(row.get("kota", "")) or "Semarang",
                "status": safe_str(row.get("status", ""))
                          or safe_str(row.get("hasil", ""))
                          or "",
                "kategori": safe_str(row.get("kategori", "")) or "",
                "gelar": safe_str(row.get("nama_gelar", "")) or "",
                "batch": safe_str(row.get("batch", "")) or "",
                "held_on": safe_str(row.get("held_on", "-")) or "-",
                "nil_wawancara": safe_str(row.get("nil_wawancara", "-")) or "-",
                "nil_pilgan": safe_str(row.get("nil_pilgan", "-")) or "-",
                "nil_esai": safe_str(row.get("nil_esai", "-")) or "-",
                "hasil_nilai": safe_str(row.get("hasil_nilai", "-")) or "-",
                "nik": safe_str(row.get("nik", "")) or "-",
                "hari": safe_str(row.get("hari", "")) or "",
            }

            # =========================
            # QR CODE (pakai no_sk, tetap aman)
            # =========================
            qr_path = os.path.join(OUT_QR, f"QR_SK_{sanitize_filename(no_sk)}.png")
            if not os.path.exists(qr_path):
                build_qr(
                    no_sk=no_sk,
                    tgl_rilis=tgl_rilis_id,
                    out_path=qr_path
                )

            # =========================
            # PDF → SK_Nama_Lengkap.pdf
            # =========================
            out_pdf = os.path.join(OUT_DIR, f"SK_{safe_nama}.pdf")
            c = canvas.Canvas(out_pdf, pagesize=A4)

            draw_sk_page1(c, d)
            c.showPage()

            draw_sk_page2(c, d, qr_path)
            c.showPage()

            draw_sk_page3(c, d)
            c.showPage()

            c.save()
            print(f"✅ SK dibuat: {out_pdf}")

        except Exception as e:
            print(f"❌ Error row {idx}: {e}")

if __name__ == "__main__":
    main()
