import os
import re
import pandas as pd
import datetime

from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

from reportlab.platypus import Table, TableStyle, Image as RLImage, Paragraph
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib import colors


# =========================
# PATH CONFIG
# =========================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

EXCEL_FILE = os.path.join(BASE_DIR, "data", "ToTUPDTidar.xlsx")
EXCEL_BASENAME = os.path.splitext(os.path.basename(EXCEL_FILE))[0]

ASSETS_DIR = os.path.join(BASE_DIR, "assets")
LOGO_PATH = os.path.join(ASSETS_DIR, "logo2.jpg")   # boleh png/jpg
TTD_PATH  = os.path.join(ASSETS_DIR, "ttd.png")

# Fonts (Cambria)
FONTS_DIR = os.path.join(ASSETS_DIR, "fonts")
FONT_CAMBRIA_REG = os.path.join(FONTS_DIR, "Cambria.ttf")
FONT_CAMBRIA_BOLD = os.path.join(FONTS_DIR, "Cambria Bold.ttf")
FONT_CAMBRIA_ITALIC = os.path.join(FONTS_DIR, "Cambria Italic.ttf")
FONT_CAMBRIA_BOLD_ITALIC = os.path.join(FONTS_DIR, "Cambria Bold Italic.ttf")

OUT_DIR_SP = os.path.join(BASE_DIR, "output", "sp")
os.makedirs(OUT_DIR_SP, exist_ok=True)

# =========================
# UNIT CONVERSION
# =========================
CM = 28.346  # 1 cm in points

# =========================
# PAGE
# =========================
PAGE_W, PAGE_H = A4  # portrait

# =========================
# HEADER MARGINS (cm) - UBAH DI SINI
# =========================
HEADER_MARGIN_TOP    = 1 * CM
HEADER_MARGIN_LEFT   = 1.72 * CM
HEADER_MARGIN_RIGHT  = 1.72 * CM
HEADER_MARGIN_BOTTOM = 0.5 * CM  # jarak dari tabel header ke garis bawah

# =========================
# CONTENT MARGINS (cm) - KONTEN SURAT
# =========================
CONTENT_MARGIN_TOP    = 3.54 * CM
CONTENT_MARGIN_LEFT   = 2.5 * CM
CONTENT_MARGIN_RIGHT  = 2.5 * CM
CONTENT_MARGIN_BOTTOM = 3.54 * CM

M_LEFT = CONTENT_MARGIN_LEFT
M_RIGHT = CONTENT_MARGIN_RIGHT
M_TOP = CONTENT_MARGIN_TOP
M_BOTTOM = CONTENT_MARGIN_BOTTOM

CONTENT_W = PAGE_W - M_LEFT - M_RIGHT

FS = 12
LEADING = 16


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
    if os.path.exists(FONT_CAMBRIA_BOLD_ITALIC):
        pdfmetrics.registerFont(TTFont("Cambria-BoldItalic", FONT_CAMBRIA_BOLD_ITALIC))


def fnt(name: str) -> str:
    reg = set(pdfmetrics.getRegisteredFontNames())
    return name if name in reg else "Helvetica"


def wrap_text(text: str, font_name: str, font_size: int, max_w: float):
    words = (text or "").split()
    lines = []
    cur = ""
    for w in words:
        test = (cur + " " + w).strip()
        if pdfmetrics.stringWidth(test, font_name, font_size) <= max_w:
            cur = test
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)
    return lines


def draw_left(c: canvas.Canvas, x: float, y: float, text: str, font_name: str, font_size: int):
    c.setFont(font_name, font_size)
    c.drawString(x, y, text)


def draw_right(c: canvas.Canvas, x_right: float, y: float, text: str, font_name: str, font_size: int):
    c.setFont(font_name, font_size)
    w = pdfmetrics.stringWidth(text, font_name, font_size)
    c.drawString(x_right - w, y, text)


def draw_centered(c: canvas.Canvas, y: float, text: str, font_name: str, font_size: int):
    c.setFont(font_name, font_size)
    c.drawCentredString(PAGE_W / 2, y, text)


def draw_paragraph(c: canvas.Canvas, x: float, y: float, text: str, font_name: str, font_size: int, max_w: float, leading: int = LEADING):
    lines = wrap_text(text, font_name, font_size, max_w)
    c.setFont(font_name, font_size)
    for line in lines:
        c.drawString(x, y, line)
        y -= leading
    return y

def draw_justify_paragraph(c, x, y, text, width, font_name="Cambria", font_size=12, leading=16, indent=28):
    style = ParagraphStyle(
        name="JustifyStyle",
        fontName=font_name,
        fontSize=font_size,
        leading=leading,
        alignment=4,  # 4 = justify
        firstLineIndent=indent,
        spaceAfter=0,
        spaceBefore=0,
    )

    p = Paragraph(text, style)
    w, h = p.wrap(width, PAGE_H)
    p.drawOn(c, x, y - h)

    return y - h - 6

def draw_paragraph_with_indent(c: canvas.Canvas, x: float, y: float, text: str, font_name: str, font_size: int, max_w: float, indent: float = 28, leading: int = LEADING):
    words = (text or "").split()
    c.setFont(font_name, font_size)

    lines = []
    cur = ""
    first_max = max_w - indent

    for w in words:
        test = (cur + " " + w).strip()
        limit = first_max if len(lines) == 0 else max_w
        if pdfmetrics.stringWidth(test, font_name, font_size) <= limit:
            cur = test
        else:
            if cur:
                lines.append(cur)
            cur = w
    if cur:
        lines.append(cur)

    for i, line in enumerate(lines):
        c.drawString(x + (indent if i == 0 else 0), y, line)
        y -= leading
    return y


def to_id_date(dt: datetime.date) -> str:
    bulan = [
        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
    ]
    return f"{dt.day:02d} {bulan[dt.month - 1]} {dt.year}"


def parse_excel_date_to_id(v) -> str:
    raw = safe_str(v)
    if not raw:
        return to_id_date(datetime.date.today())
    try:
        dt = pd.to_datetime(raw).date()
        return to_id_date(dt)
    except Exception:
        return raw


# =========================
# HEADER (TABLE, NO BORDER) - MARGIN KIRI/KANAN/ATAS/BAWAH
# =========================
def draw_header(c: canvas.Canvas):
    """
    Header = tabel tanpa border
    kiri = logo
    kanan = teks (CENTER)
    margin header:
      - atas: HEADER_MARGIN_TOP
      - kiri: HEADER_MARGIN_LEFT
      - kanan: HEADER_MARGIN_RIGHT
      - bawah: HEADER_MARGIN_BOTTOM (jarak tabel ke garis)
    """

    y_top = PAGE_H - HEADER_MARGIN_TOP

    # Logo size in cm
    logo_w_cm, logo_h_cm = 3.55, 1.48
    logo_w_pt, logo_h_pt = logo_w_cm * CM, logo_h_cm * CM

    # Header text
    title = "LSP EDUKASI GLOBAL CENDEKIA"
    addr = ("Kompleks Ruko Teras Bali No. 12, Desa/Kelurahan, Bubakan, Kec. Mijen, "
            "Kota Semarang, Jawa Tengah Telp. +62 851-7547-9385")
    web = "www.lspedukia.id"

    # Paragraph style CENTER
    p_style = ParagraphStyle(
        name="HeaderRight",
        fontName=fnt("Cambria"),
        fontSize=12,
        leading=14,
        alignment=1,  # center
        spaceBefore=0,
        spaceAfter=0
    )

    right_html = (
        f"<font name='{fnt('Cambria')}' size='22'>{title}</font><br/>"
        f"<font name='{fnt('Cambria')}' size='12'>{addr}</font><br/>"
        f"<font name='{fnt('Cambria')}' size='12'>{web}</font>"
    )
    right_cell = Paragraph(right_html, p_style)

    # Logo cell must be RLImage
    if os.path.exists(LOGO_PATH):
        left_cell = RLImage(LOGO_PATH, width=logo_w_pt, height=logo_h_pt)
    else:
        left_cell = Paragraph("", p_style)

    # Header area width (pakai margin kiri & kanan header)
    header_w = PAGE_W - HEADER_MARGIN_LEFT - HEADER_MARGIN_RIGHT

    gap = 2.4
    col_left = logo_w_pt + gap
    col_right = header_w - col_left

    row_h = max(logo_h_pt, 70)

    table = Table(
        [[left_cell, right_cell]],
        colWidths=[col_left, col_right],
        rowHeights=[row_h]
    )

    table.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("ALIGN", (0, 0), (0, 0), "LEFT"),
        ("ALIGN", (1, 0), (1, 0), "CENTER"),
        ("LEFTPADDING", (0, 0), (-1, -1), 0),
        ("RIGHTPADDING", (0, 0), (-1, -1), 0),
        ("TOPPADDING", (0, 0), (-1, -1), 0),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 0),
    ]))

    table_x = HEADER_MARGIN_LEFT
    table_y = y_top - row_h

    table.wrapOn(c, header_w, PAGE_H)
    table.drawOn(c, table_x, table_y)

    # garis bawah header diturunkan (jarak = HEADER_MARGIN_BOTTOM)
    line_y = table_y - HEADER_MARGIN_BOTTOM
    c.setLineWidth(1.2)
    c.line(HEADER_MARGIN_LEFT, line_y, PAGE_W - HEADER_MARGIN_RIGHT, line_y)

    return line_y - 24


# =========================
# TABLE HELPERS
# =========================
def draw_table(c: canvas.Canvas, table: Table, x: float, y_top: float):
    w, h = table.wrapOn(c, CONTENT_W, PAGE_H)
    table.drawOn(c, x, y_top - h)
    return y_top - h - 24


# =========================
# PAGE 1
# =========================
def draw_sp_page1(c: canvas.Canvas, d: dict):
    y = draw_header(c)

    title = "SURAT PEMBERITAHUAN"
    c.setFont(fnt("Cambria-Bold"), 12)
    c.drawCentredString(PAGE_W / 2, y, title)
    t_w = pdfmetrics.stringWidth(title, fnt("Cambria-Bold"), 12)
    c.setLineWidth(1)
    c.line((PAGE_W / 2) - t_w / 2, y - 2, (PAGE_W / 2) + t_w / 2, y - 2)
    y -= 32

    label_x = M_LEFT
    val_x = M_LEFT + 70  # jarak label ke titik dua

    # Nomor
    draw_left(c, label_x, y, "Nomor", fnt("Cambria"), 12)
    draw_left(c, val_x, y, f": {d.get('no_sp', '-')}", fnt("Cambria"), 12)
    y -= 16

    # Lampiran
    draw_left(c, label_x, y, "Lampiran", fnt("Cambria"), 12)
    draw_left(c, val_x, y, ": 1 Halaman", fnt("Cambria"), 12)
    y -= 16

    # Perihal
    draw_left(c, label_x, y, "Perihal", fnt("Cambria"), 12)
    draw_left(
        c,
        val_x,
        y,
        ": Pemberitahuan Hasil Ujian Kompetensi Sertifikasi Person LSP Edukia",
        fnt("Cambria"),
        12
    )
    y -= 24

    draw_left(c, M_LEFT, y, "Kepada Yth:", fnt("Cambria-Bold"), 12)
    y -= 18
    panggilan = d.get("panggilan", "").strip()
    nama = d.get("nama_lengkap", "-")

    nama_tampil = f"{panggilan} {nama}" if panggilan else nama
    draw_left(c, M_LEFT, y, nama_tampil, fnt("Cambria"), 12)
    y -= 18
    draw_left(c, M_LEFT, y, "Di Tempat", fnt("Cambria"), 12)
    y -= 22

    parag = (
        f"Berdasarkan hasil Uji Kompetensi Sertifikasi Person LSP Edukasi Global Cendekia (Edukia) "
        f"dengan skema {d.get('skema', '-')} telah  dilaksanakan pada {d.get('held_on', '-')} "
        f"dengan ini dinyatakan bahwa:"
    )
    y = draw_justify_paragraph(c, M_LEFT, y, parag, CONTENT_W, font_name=fnt("Cambria"), font_size=12, leading=LEADING,indent=28)

    y -= 16

    label_w = 80
    label_x = M_LEFT
    val_x = M_LEFT + label_w

    # ==== 3 kolom: LABEL | ":" | VALUE (wrap) ====
    label_x = M_LEFT
    label_w = 80  # lebar kolom label (sesuaikan)
    colon_w = 6  # lebar kolom ":" (sesuaikan)
    value_x = label_x + label_w + colon_w

    max_value_w = (PAGE_W - M_RIGHT) - value_x

    def draw_field_3col(c, y, label, value, value_font, value_size=12, leading=LEADING):
        # LABEL
        draw_left(c, label_x, y, label, fnt("Cambria"), 12)

        # COLON
        draw_left(c, label_x + label_w, y, ":", fnt("Cambria"), 12)

        # VALUE (wrap)
        y = draw_paragraph(
            c,
            value_x,
            y,
            safe_str(value) if value is not None else "-",
            value_font,
            value_size,
            max_value_w,
            leading=leading
        )
        return y

    # Nama
    y = draw_field_3col(c, y, "Nama", d.get("nama_lengkap", "-"), fnt("Cambria-Bold"))
    y -= 2

    # No Registrasi
    y = draw_field_3col(c, y, "No Registrasi", d.get("no_reg", "-"), fnt("Cambria-Bold"))
    y -= 2

    # Skema (panjang aman wrap, baris lanjutannya sejajar value)
    y = draw_field_3col(c, y, "Skema", d.get("skema", "-"), fnt("Cambria-Bold"))
    y -= 6  # jarak setelah blok terakhir

    # =========================
    # TABLE NILAI (HEADER 2 ROW)
    # =========================
    header_bg = colors.Color(0.72, 0.84, 0.94)

    status_raw = (str(d.get("status", "-")) or "-").strip().upper()

    # paksa 2 baris untuk yang panjang
    status_cell = status_raw
    if status_raw == "TIDAK KOMPETEN":
        status_cell = "TIDAK\nKOMPETEN"
    elif status_raw == "KOMPETEN":
        status_cell = "KOMPETEN"

    table_data = [
        ["NILAI\nWAWANCARA", "NILAI PILIHAN\nGANDA", "NILAI ESAI", "REKAPITULASI HASIL ASESMEN", ""],
        ["", "", "", "HASIL NILAI", "STATUS"],
        [
            d.get("nil_wawancara", "-"),
            d.get("nil_pilgan", "-"),
            d.get("nil_esai", "-"),
            d.get("hasil_nilai", "-"),
            status_cell,
        ],
    ]

    col_widths = [
        CONTENT_W * 0.18,
        CONTENT_W * 0.20,
        CONTENT_W * 0.16,
        CONTENT_W * 0.26,
        CONTENT_W * 0.20,
    ]

    style_cmds = [
        # base
        ("FONTNAME", (0, 0), (-1, -1), fnt("Cambria")),
        ("FONTSIZE", (0, 0), (-1, -1), 12),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("ALIGN", (0, 0), (-1, -1), "CENTER"),
        ("GRID", (0, 0), (-1, -1), 0.5, colors.black),

        # header background 2 baris
        ("BACKGROUND", (0, 0), (-1, 1), header_bg),
        ("FONTNAME", (0, 0), (-1, 1), fnt("Cambria-Bold")),

        # === SPAN / MERGE HEADER ===
        ("SPAN", (0, 0), (0, 1)),
        ("SPAN", (1, 0), (1, 1)),
        ("SPAN", (2, 0), (2, 1)),
        ("SPAN", (3, 0), (4, 0)),

        # padding
        ("LEFTPADDING", (0, 0), (-1, -1), 7),
        ("RIGHTPADDING", (0, 0), (-1, -1), 7),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),

        # kecilkan font khusus kolom STATUS biar muat
        ("FONTSIZE", (4, 2), (4, 2), 11),
        ("FONTNAME", (4, 2), (4, 2), fnt("Cambria-Bold")),
    ]

    # ===== conditional style untuk STATUS =====
    if status_raw == "TIDAK KOMPETEN":
        style_cmds += [
            ("BACKGROUND", (4, 2), (4, 2), colors.HexColor("#E06666")),  # merah
            ("TEXTCOLOR", (4, 2), (4, 2), colors.white),
        ]
    elif status_raw == "KOMPETEN":
        style_cmds += [
            ("BACKGROUND", (4, 2), (4, 2), colors.HexColor("#D9EAD3")),  # hijau muda
            ("TEXTCOLOR", (4, 2), (4, 2), colors.HexColor("#274E13")),  # hijau tua
        ]
    else:
        style_cmds += [
            ("BACKGROUND", (4, 2), (4, 2), colors.HexColor("#FFF2CC")),  # kuning netral
            ("TEXTCOLOR", (4, 2), (4, 2), colors.black),
        ]

    t = Table(table_data, colWidths=col_widths)
    t.setStyle(TableStyle(style_cmds))
    y = draw_table(c, t, M_LEFT, y)

    info = (
        "Info lebih lanjut silahkan hubungi narahubung tim Edukia (+62 858-3270-4071). "
        "Demikian pemberitahuan ini kami sampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih."
    )
    y = draw_paragraph_with_indent(c, M_LEFT, y, info, fnt("Cambria"), 12, CONTENT_W, indent=28, leading=LEADING)
    y -= 12  # space setelah paragraf

    # ====== BLOK TANDA TANGAN (KANAN, ALIGN LEFT) ======
    today_id = d.get("today_id", to_id_date(datetime.date.today()))

    ttd_w_cm, ttd_h_cm = 6.97, 2.0
    ttd_w_pt, ttd_h_pt = ttd_w_cm * CM, ttd_h_cm * CM

    sig_block_w = ttd_w_pt
    sig_right = PAGE_W - HEADER_MARGIN_RIGHT
    sig_left = sig_right - sig_block_w

    # base_y adaptif: ikut posisi konten (y), tapi tetap aman di bawah
    base_y = max(M_BOTTOM + 5, y - 120)

    SPACE_BEFORE_TTD = 12
    SPACE_AFTER_TTD = 10
    TTD_SHIFT_LEFT = 18

    # Teks (align-left, blok di kanan)
    c.setFont(fnt("Cambria"), 12)
    c.drawString(sig_left, base_y + 95, f"Semarang, {today_id}")
    c.drawString(sig_left, base_y + 78, "Mengetahui")

    # Gambar TTD (geser kiri sedikit)
    img_x = sig_left - TTD_SHIFT_LEFT
    img_y = base_y + 30 - SPACE_BEFORE_TTD
    if os.path.exists(TTD_PATH):
        RLImage(TTD_PATH, width=ttd_w_pt, height=ttd_h_pt).drawOn(c, img_x, img_y)

    # Nama & Jabatan
    name_y = img_y - SPACE_AFTER_TTD
    c.drawString(sig_left, name_y, "Dr. Agung Yulianto, M.Si.")
    c.drawString(sig_left, name_y - 16, "Ketua LSP Edukia")

def draw_sp_page2(c: canvas.Canvas):
    y = draw_header(c)

    draw_centered(c, y, "LAMPIRAN", fnt("Cambria-Bold"), 12)
    y -= 24

    title = "STANDAR PEMBOBOTAN PENILAIAN DAN KELULUSAN"
    draw_left(c, M_LEFT, y, title, fnt("Cambria-Bold"), 12)
    t_w = pdfmetrics.stringWidth(title, fnt("Cambria-Bold"), 12)
    c.setLineWidth(1)
    c.line(M_LEFT, y - 2, M_LEFT + t_w, y - 2)
    y -= 18

    draw_left(c, M_LEFT, y, "A. Pembobotan Penilaian", fnt("Cambria-Bold"), 12)
    y -= 18

    # ====== a. Bobot Penilaian Pada Setiap Metode Ujian (INDENT BERTINGKAT) ======
    INDENT_A = 0.5 * CM  # indent untuk teks "a."
    INDENT_TABLE = 0.8 * CM # tabel = 2x indent a.

    # Judul "a." ikut indent A
    draw_left(
        c,
        M_LEFT + INDENT_A,
        y,
        "a.  Bobot Penilaian Pada Setiap Metode Ujian",
        fnt("Cambria-Bold"),
        12
    )
    y -= 12

    # Tabel pakai indent 2x
    table_x = M_LEFT + INDENT_TABLE

    # Lebar tabel disesuaikan supaya tidak melebar keluar
    TABLE_W = CONTENT_W - INDENT_TABLE

    data1 = [
        ["Metode Ujian", "Jumlah Soal", "Lama Pengerjaan", "Proporsi Nilai"],
        ["Evaluasi per Unit Kompetensi", "", "", ""],
        ["Pilihan Ganda", "100", "120 Menit", "65%"],
        ["Esai", "10", "90 Menit", "35%"],
    ]

    colw1 = [TABLE_W * 0.22, TABLE_W * 0.22, TABLE_W * 0.28, TABLE_W * 0.28]

    style1 = TableStyle([
        ("FONTNAME", (0, 0), (-1, -1), fnt("Cambria")),
        ("FONTSIZE", (0, 0), (-1, -1), 12),

        # border tipis
        ("GRID", (0, 0), (-1, -1), 0.5, colors.black),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),

        # header row
        ("FONTNAME", (0, 0), (-1, 0), fnt("Cambria-Bold")),
        ("ALIGN", (0, 0), (-1, 0), "CENTER"),

        # merged row: Evaluasi per Unit Kompetensi
        ("SPAN", (0, 1), (-1, 1)),
        ("ALIGN", (0, 1), (-1, 1), "CENTER"),
        ("FONTNAME", (0, 1), (-1, 1), fnt("Cambria-Bold")),

        # padding
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
    ])

    t1 = Table(data1, colWidths=colw1)
    t1.setStyle(style1)

    # gambar tabel dengan indent 2x
    y = draw_table(c, t1, table_x, y)

    # space setelah tabel
    y -= 8

    # ====== b. Rekapitulasi Hasil Pembobotan Penilaian ======
    draw_left(
        c,
        M_LEFT + INDENT_A,
        y,
        "b. Rekapitulasi Hasil Pembobotan Penilaian",
        fnt("Cambria-Bold"),
        12
    )
    y -= 12

    # TABEL INDENT = sama dengan tabel sebelumnya (2x indent judul)
    table_x = M_LEFT + INDENT_TABLE
    TABLE_W = CONTENT_W - INDENT_TABLE

    data2 = [
        ["Metode Ujian", "Proporsi Nilai"],
        ["Ujian Tulis (Pilihan Ganda + Esai)", "70%"],
        ["Ujian Lisan + Keterampilan", "30%"],
    ]

    # kolom dihitung dari TABLE_W (bukan CONTENT_W)
    colw2 = [
        TABLE_W * 0.70,
        TABLE_W * 0.30
    ]

    style2 = TableStyle([
        ("FONTNAME", (0, 0), (-1, -1), fnt("Cambria")),
        ("FONTSIZE", (0, 0), (-1, -1), 12),

        # border tipis konsisten
        ("GRID", (0, 0), (-1, -1), 0.5, colors.black),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),

        # header
        ("FONTNAME", (0, 0), (-1, 0), fnt("Cambria-Bold")),
        ("ALIGN", (0, 0), (-1, 0), "CENTER"),

        # isi
        ("ALIGN", (0, 1), (0, -1), "LEFT"),
        ("ALIGN", (1, 1), (1, -1), "CENTER"),

        # padding biar rapi
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 3),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3),
    ])

    t2 = Table(data2, colWidths=colw2)
    t2.setStyle(style2)

    # gambar tabel dengan indent yang sama
    y = draw_table(c, t2, table_x, y)

    # space setelah tabel
    y -= 10

    draw_left(c, M_LEFT, y, "B. Standar Kelulusan", fnt("Cambria-Bold"), 12)
    y -= 16

    # ====== B. Standar Kelulusan (BULLET INDENT SEPERTI a.) ======
    INDENT_BULLET = INDENT_A  # indent sama dengan "a."
    BULLET_GAP = 10  # jarak angka ke teks (pt)

    bullets = [
        ("1.", "Asesi (Peserta) dinyatakan lulus uji kompetensi apabila nilai minimal >=60"),
        ("2.", "Asesi (Peserta) dinyatakan tidak lulus jika tidak memenuhi poin 1"),
        ("3.", "Asesi (Peserta) yang dinyatakan tidak lulus dapat mengikuti kegiatan remidial"),
    ]

    for num, text in bullets:
        # angka
        draw_left(
            c,
            M_LEFT + INDENT_BULLET,
            y,
            num,
            fnt("Cambria"),
            12
        )

        # teks (indent setelah angka)
        y = draw_paragraph(
            c,
            M_LEFT + INDENT_BULLET + BULLET_GAP,
            y,
            text,
            fnt("Cambria"),
            12,
            CONTENT_W - INDENT_BULLET - BULLET_GAP,
            leading=14
        )

        y -= 4

def main():
    register_cambria_fonts()
    df = pd.read_excel(EXCEL_FILE)

    # ===== BUAT FOLDER SESUAI NAMA FILE EXCEL =====
    excel_name = os.path.splitext(os.path.basename(EXCEL_FILE))[0]
    excel_folder = sanitize_filename(excel_name)

    output_dir = os.path.join(OUT_DIR_SP, excel_folder)
    os.makedirs(output_dir, exist_ok=True)

    print(f"📁 Output folder: {output_dir}")

    # ===== GENERATE PDF =====
    for idx, row in df.iterrows():
        try:
            no_sp = safe_str(row.get("no_sp", ""))
            if not no_sp:
                continue

            d = {
                "no_sp": no_sp,
                "nama_lengkap": safe_str(row.get("nama_lengkap", "-")) or "-",
                "panggilan": safe_str(row.get("panggilan", "-")) or "-",
                "skema": safe_str(row.get("skema", "-")) or "-",
                "held_on": safe_str(row.get("held_on", "-")) or "-",
                "no_reg": safe_str(row.get("no_reg", "-")) or "-",
                "nil_wawancara": safe_str(row.get("nil_wawancara", "-")) or "-",
                "nil_pilgan": safe_str(row.get("nil_pilgan", "-")) or "-",
                "nil_esai": safe_str(row.get("nil_esai", "-")) or "-",
                "hasil_nilai": safe_str(row.get("hasil_nilai", "-")) or "-",
                "status": safe_str(row.get("status", "-")) or "-",
                "today_id": parse_excel_date_to_id(row.get("today", "")),
            }

            # ===== NAMA FILE PDF =====
            nama_file = f"SP_{sanitize_filename(no_sp)}_{sanitize_filename(d['nama_lengkap'])}.pdf"
            out_pdf = os.path.join(output_dir, nama_file)

            c = canvas.Canvas(out_pdf, pagesize=A4)
            draw_sp_page1(c, d)
            c.showPage()
            draw_sp_page2(c)
            c.showPage()
            c.save()

            print(f"✅ SP dibuat: {out_pdf}")

        except Exception as e:
            print(f"❌ Error row {idx}: {e}")


if __name__ == "__main__":
    main()
