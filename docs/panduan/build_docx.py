"""Buat versi Word (.docx) dari panduan HTML (docs/panduan/sumber/*.html) — isinya sama dengan PDF.

    pip install python-docx beautifulsoup4
    python docs/panduan/build_docx.py

Elemen HTML yang dipakai panduan dipetakan ke Word: sampul (.cover), daftar isi (.toc), h2/h3, paragraf, daftar,
tabel, gambar (figure), kotak catatan (.note), langkah (.step), alur (.flow), dua kolom (.two/.card), FAQ (dl), penutup (.end).
"""
import pathlib
import re

from bs4 import BeautifulSoup, NavigableString, Tag
from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_TAB_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor, Emu
from PIL import Image

ROOT = pathlib.Path(__file__).resolve().parent
SRC = ROOT / "sumber"
DOCS = {
    "peserta-pendaftaran.html": ("Panduan Peserta - 1 Pendaftaran Sertifikasi.docx", "Panduan Peserta — Pendaftaran Sertifikasi"),
    "peserta-ujian.html": ("Panduan Peserta - 2 Mengerjakan Ujian.docx", "Panduan Peserta — Mengerjakan Ujian"),
    "asesor.html": ("Panduan Asesor - Persiapan dan Penilaian Ujian.docx", "Panduan Asesor — Persiapan & Penilaian Ujian"),
}

FONT = "Calibri"
NAVY = "1F2937"
BLUE = "2361CE"
ORANGE = "F4891F"
TEXT = "374151"
MUTED = "6B7280"
LINE = "E5E7EB"
SOFT = "F3F4F6"
CONTENT_W = Cm(17.0)          # A4 21 cm - 2 × 2 cm margin

BTN = {  # kelas tombol → (latar, teks)
    "b-blue": (BLUE, "FFFFFF"), "b-navy": (NAVY, "FFFFFF"), "b-green": ("10B981", "FFFFFF"),
    "b-red": ("E11D48", "FFFFFF"), "b-amber": ("F0BC74", NAVY), "b-gray": ("E5E7EB", NAVY), "b-info": ("0EA5E9", "FFFFFF"),
}
NOTE = {  # kelas catatan → (garis kiri, latar)
    "": (BLUE, "EFF6FF"), "warn": (ORANGE, "FFF7ED"), "danger": ("E11D48", "FFF1F2"), "ok": ("10B981", "ECFDF5"),
}


# ---------- utilitas XML ----------
# Urutan anak elemen menurut skema WordprocessingML; elemen yang disisipkan di luar urutan membuat Word
# menampilkan dialog "unreadable content".
ORDER = {
    "tcPr": "cnfStyle tcW gridSpan hMerge vMerge tcBorders shd noWrap tcMar textDirection tcFitText vAlign hideMark",
    "tblPr": "tblStyle tblpPr tblOverlap bidiVisual tblStyleRowBandSize tblStyleColBandSize tblW jc tblCellSpacing "
             "tblInd tblBorders shd tblLayout tblCellMar tblLook",
    "pPr": "pStyle keepNext keepLines pageBreakBefore framePr widowControl numPr suppressLineNumbers pBdr shd tabs "
           "suppressAutoHyphens kinsoku wordWrap overflowPunct topLinePunct autoSpaceDE autoSpaceDN bidi adjustRightInd "
           "snapToGrid spacing ind contextualSpacing mirrorIndents suppressOverlap jc textDirection textAlignment "
           "textboxTightWrap outlineLvl divId cnfStyle rPr sectPr pPrChange",
    "rPr": "rStyle rFonts b bCs i iCs caps smallCaps strike dstrike outline shadow emboss imprint noProof snapToGrid "
           "vanish webHidden color spacing w kern position sz szCs highlight u effect bdr shd fitText vertAlign rtl cs "
           "em lang eastAsianLayout specVanish oMath",
}


def insert_ordered(parent, child):
    """Sisipkan child ke parent (tcPr/tblPr/pPr/rPr) pada posisi yang benar; ganti elemen sejenis yang sudah ada."""
    seq = ORDER[parent.tag.split("}")[1]].split()
    name = child.tag.split("}")[1]
    for old in parent.findall(child.tag):
        parent.remove(old)
    idx = seq.index(name)
    for i, existing in enumerate(list(parent)):
        ename = existing.tag.split("}")[1]
        if ename in seq and seq.index(ename) > idx:
            existing.addprevious(child)
            return
    parent.append(child)


def shade(el, fill):
    """Latar sel tabel (tcPr) atau run (rPr)."""
    pr = el.get_or_add_tcPr() if hasattr(el, "get_or_add_tcPr") else el.get_or_add_rPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), fill)
    insert_ordered(pr, shd)


def cell_borders(cell, **edges):
    """edges: top/left/bottom/right = (warna, ukuran 1/8 pt) atau None untuk tanpa garis."""
    tcPr = cell._tc.get_or_add_tcPr()
    b = OxmlElement("w:tcBorders")
    for edge in ("top", "left", "bottom", "right"):
        e = OxmlElement(f"w:{edge}")
        spec = edges.get(edge)
        if spec:
            e.set(qn("w:val"), "single")
            e.set(qn("w:sz"), str(spec[1]))
            e.set(qn("w:color"), spec[0])
        else:
            e.set(qn("w:val"), "nil")
        b.append(e)
    insert_ordered(tcPr, b)


def cell_margins(cell, top=80, bottom=80, left=140, right=140):
    tcPr = cell._tc.get_or_add_tcPr()
    m = OxmlElement("w:tcMar")
    for k, v in (("top", top), ("bottom", bottom), ("start", left), ("end", right)):
        e = OxmlElement(f"w:{k}")
        e.set(qn("w:w"), str(v))
        e.set(qn("w:type"), "dxa")
        m.append(e)
    insert_ordered(tcPr, m)


def para_border_bottom(p, color, size=12, space=4):
    pPr = p._p.get_or_add_pPr()
    bdr = OxmlElement("w:pBdr")
    e = OxmlElement("w:bottom")
    e.set(qn("w:val"), "single")
    e.set(qn("w:sz"), str(size))
    e.set(qn("w:space"), str(space))
    e.set(qn("w:color"), color)
    bdr.append(e)
    insert_ordered(pPr, bdr)


def table_no_borders(table):
    tblPr = table._tbl.tblPr
    b = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        e = OxmlElement(f"w:{edge}")
        e.set(qn("w:val"), "nil")
        b.append(e)
    insert_ordered(tblPr, b)


def keep_row_together(row):
    trPr = row._tr.get_or_add_trPr()
    c = OxmlElement("w:cantSplit")
    trPr.append(c)


def set_col_widths(table, widths):
    table.autofit = False
    for row in table.rows:
        for i, w in enumerate(widths):
            row.cells[i].width = w
    grid = table._tbl.tblGrid
    for i, gc in enumerate(grid.findall(qn("w:gridCol"))):
        gc.set(qn("w:w"), str(int(widths[i] / 635)))   # EMU → twip


def add_field(paragraph, instr, fmt):
    """Field Word (PAGE/NUMPAGES) sebagai rangkaian run terpisah: begin, instrText, separate, hasil, end."""
    def run_with(el):
        r = paragraph.add_run()
        style_run(r, fmt)
        r._r.append(el)

    def fld(kind):
        f = OxmlElement("w:fldChar")
        f.set(qn("w:fldCharType"), kind)
        return f

    instr_el = OxmlElement("w:instrText")
    instr_el.set(qn("xml:space"), "preserve")
    instr_el.text = f" {instr} "
    result = OxmlElement("w:t")
    result.text = "1"
    for el in (fld("begin"), instr_el, fld("separate"), result, fld("end")):
        run_with(el)


# ---------- teks inline ----------
def text_of(node):
    return re.sub(r"\s+", " ", node.get_text())


def add_inline(p, node, fmt=None):
    """Tambah isi inline node (teks, b, i, code, span.btn, br, a) ke paragraf p."""
    fmt = dict(fmt or {})
    for child in node.children if isinstance(node, Tag) else [node]:
        if isinstance(child, NavigableString):
            s = re.sub(r"\s+", " ", str(child))
            if not s:
                continue
            r = p.add_run(s)
            style_run(r, fmt)
            continue
        if not isinstance(child, Tag):
            continue
        name, cls = child.name, child.get("class", [])
        if name in ("b", "strong"):
            add_inline(p, child, {**fmt, "bold": True})
        elif name in ("i", "em"):
            add_inline(p, child, {**fmt, "italic": True})
        elif name == "code":
            r = p.add_run(text_of(child))
            style_run(r, {**fmt, "font": "Consolas", "size": Pt(9), "shade": SOFT})
        elif name == "span" and "btn" in cls:
            bg, fg = next((BTN[c] for c in cls if c in BTN), ("E5E7EB", NAVY))
            r = p.add_run(" " + text_of(child).strip() + " ")
            style_run(r, {**fmt, "bold": True, "size": Pt(9), "color": fg, "shade": bg})
        elif name == "span" and "no" in cls:
            add_inline(p, child, {**fmt, "color": BLUE})
            p.add_run("  ")
        elif name == "br":
            p.add_run().add_break()
        elif name in ("ul", "ol", "figure", "table", "div"):
            continue   # blok — ditangani pemanggil
        else:
            style = child.get("style", "")
            m = re.search(r"color:\s*var\(--red\)", style)
            add_inline(p, child, {**fmt, "color": "E11D48"} if m else fmt)


def style_run(r, fmt):
    r.font.name = fmt.get("font", FONT)
    if fmt.get("font"):
        r._r.get_or_add_rPr().get_or_add_rFonts().set(qn("w:eastAsia"), fmt["font"])
    r.bold = fmt.get("bold")
    r.italic = fmt.get("italic")
    if fmt.get("size"):
        r.font.size = fmt["size"]
    if fmt.get("color"):
        r.font.color.rgb = RGBColor.from_string(fmt["color"])
    if fmt.get("shade"):
        shade(r._r, fmt["shade"])


def has_inline_content(li):
    return any(isinstance(c, NavigableString) and c.strip() or isinstance(c, Tag) and c.name not in ("ul", "ol") for c in li.children)


# ---------- penulis dokumen ----------
class Writer:
    def __init__(self, doc, title):
        self.doc = doc
        self.title = title

    # container: Document atau _Cell
    def para(self, container, style=None, space_after=Pt(4), align=None):
        p = container.add_paragraph(style=style)
        p.paragraph_format.space_after = space_after
        p.paragraph_format.space_before = Pt(0)
        if align:
            p.alignment = align
        return p

    def blocks(self, container, node, width=CONTENT_W, list_level=0):
        for child in node.children:
            if isinstance(child, NavigableString):
                if child.strip():
                    p = self.para(container)
                    add_inline(p, child)
                continue
            if not isinstance(child, Tag):
                continue
            self.block(container, child, width, list_level)

    def block(self, container, el, width=CONTENT_W, list_level=0):
        name, cls = el.name, el.get("class", [])
        if name == "div" and "cover" in cls:
            self.cover(el)
        elif name == "div" and "toc" in cls:
            self.boxed(container, el, NAVY, SOFT, width, toc=True)
        elif name == "h2":
            p = container.add_paragraph(style="Heading 1")
            add_inline(p, el)
            para_border_bottom(p, NAVY, 12, 2)
        elif name == "h3":
            p = container.add_paragraph(style="Heading 2")
            add_inline(p, el)
        elif name == "h4":
            p = self.para(container, space_after=Pt(2))
            add_inline(p, el, {"bold": True, "color": NAVY})
        elif name == "p":
            p = self.para(container)
            add_inline(p, el)
        elif name in ("ul", "ol"):
            self.listing(container, el, list_level)
        elif name == "table":
            self.table(container, el, width)
        elif name == "figure":
            self.figure(container, el, width)
        elif name == "div" and "note" in cls:
            kind = next((c for c in cls if c in NOTE and c), "")
            bar, bg = NOTE[kind]
            self.boxed(container, el, bar, bg, width, left_bar=True)
        elif name == "div" and "step" in cls:
            self.step(container, el, width)
        elif name == "div" and "flow" in cls:
            self.flow(container, el, width)
        elif name == "div" and "two" in cls:
            self.two(container, el, width)
        elif name == "div" and "card" in cls:
            self.boxed(container, el, LINE, "FFFFFF", width, outline=True)
        elif name == "div" and "end" in cls:
            self.boxed(container, el, NAVY, NAVY, width, dark=True)
        elif name == "dl":
            for item in el.find_all(["dt", "dd"], recursive=False):
                p = self.para(container, space_after=Pt(1 if item.name == "dt" else 6))
                if item.name == "dt":
                    p.paragraph_format.keep_with_next = True
                    add_inline(p, item, {"bold": True, "color": NAVY})
                else:
                    add_inline(p, item)
        elif name in ("div", "span"):
            self.blocks(container, el, width, list_level)

    def listing(self, container, el, level):
        ordered = el.name == "ol"
        for i, li in enumerate(el.find_all("li", recursive=False), 1):
            p = self.para(container, space_after=Pt(2))
            pf = p.paragraph_format
            pf.left_indent = Cm(0.65 + 0.6 * level)
            pf.first_line_indent = Cm(-0.5)
            pf.tab_stops.add_tab_stop(Cm(0.65 + 0.6 * level))
            marker = f"{i}." if ordered else ("•" if level == 0 else "–")
            r = p.add_run(marker + "\t")
            style_run(r, {"color": BLUE if ordered else TEXT, "bold": ordered})
            if has_inline_content(li):
                add_inline(p, li)
            for sub in li.find_all(["ul", "ol"], recursive=False):
                self.listing(container, sub, level + 1)

    def table(self, container, el, width):
        rows = el.find_all("tr")
        ncols = max(len(r.find_all(["td", "th"])) for r in rows)
        # lebar kolom: pakai style="width:XXmm" pada th bila ada
        first = rows[0].find_all(["td", "th"])
        fixed = []
        for c in first:
            m = re.search(r"width:\s*([\d.]+)mm", c.get("style", ""))
            fixed.append(Cm(float(m.group(1)) / 10) if m else None)
        free = width - sum(w for w in fixed if w)
        nfree = sum(1 for w in fixed if not w) or 1
        widths = [w or int(free / nfree) for w in fixed] + [int(free / nfree)] * (ncols - len(fixed))
        t = container.add_table(rows=len(rows), cols=ncols)
        t.alignment = WD_TABLE_ALIGNMENT.CENTER
        table_no_borders(t)
        for ri, tr in enumerate(rows):
            keep_row_together(t.rows[ri])
            for ci, td in enumerate(tr.find_all(["td", "th"])):
                cell = t.cell(ri, ci)
                cell_margins(cell, 60, 60, 110, 110)
                p = cell.paragraphs[0]
                p.paragraph_format.space_after = Pt(0)
                if td.name == "th":
                    shade(cell._tc, NAVY)
                    add_inline(p, td, {"bold": True, "color": "FFFFFF", "size": Pt(9.5)})
                else:
                    if ri % 2 == 0:
                        shade(cell._tc, "FAFAFA")
                    cell_borders(cell, bottom=(LINE, 6))
                    bold = "k" in td.get("class", [])
                    add_inline(p, td, {"bold": True, "color": NAVY, "size": Pt(9.5)} if bold else {"size": Pt(9.5)})
        for row in t.rows[:-1]:
            for c in row.cells:
                for cp in c.paragraphs:
                    cp.paragraph_format.keep_with_next = True
        if rows[0].find("th"):
            trPr = t.rows[0]._tr.get_or_add_trPr()
            h = OxmlElement("w:tblHeader")
            trPr.append(h)
        set_col_widths(t, widths)
        self.para(container, space_after=Pt(4))

    def figure(self, container, el, width):
        img = el.find("img")
        path = (SRC / img["src"]).resolve()
        cls = el.get("class", [])
        with Image.open(path) as im:
            w_px, h_px = im.size
        max_w = width * (0.74 if "half" in cls else 0.58 if "small" in cls else 1.0)
        max_h = Cm(20.0 if "tall" in cls else 12.0 if "half" not in cls else 9.5)
        w = max_w
        h = int(w * h_px / w_px)
        if h > max_h:
            h = max_h
            w = int(h * w_px / h_px)
        p = self.para(container, space_after=Pt(2), align=WD_ALIGN_PARAGRAPH.CENTER)
        p.paragraph_format.keep_with_next = True
        p.add_run().add_picture(str(path), width=Emu(int(w)))
        cap = el.find("figcaption")
        if cap:
            c = self.para(container, space_after=Pt(8), align=WD_ALIGN_PARAGRAPH.CENTER)
            add_inline(c, cap, {"italic": True, "color": MUTED, "size": Pt(8.5)})

    def boxed(self, container, el, bar, bg, width, left_bar=False, outline=False, dark=False, toc=False):
        t = container.add_table(rows=1, cols=1)
        table_no_borders(t)
        cell = t.cell(0, 0)
        set_col_widths(t, [width])
        shade(cell._tc, bg)
        cell_margins(cell, 120, 120, 200, 200)
        if left_bar:
            cell_borders(cell, left=(bar, 36))
        elif outline:
            cell_borders(cell, top=(LINE, 6), left=(LINE, 6), bottom=(LINE, 6), right=(LINE, 6))
        keep_row_together(t.rows[0])
        cell.paragraphs[0]._p.getparent().remove(cell.paragraphs[0]._p)
        inner = width - Cm(0.8)
        BLOCKS = {"p", "ul", "ol", "table", "figure", "div", "h3", "h4", "dl"}
        base = {"color": "E5E7EB"} if dark else None
        pending = []   # node inline berurutan → satu paragraf

        def flush():
            if any(isinstance(n, Tag) or n.strip() for n in pending):
                p = self.para(cell)
                wrapper = BeautifulSoup("<span></span>", "html.parser").span
                for n in pending:
                    wrapper.append(n.__copy__() if isinstance(n, Tag) else NavigableString(str(n)))
                add_inline(p, wrapper, base)
            pending.clear()

        for child in el.children:
            if isinstance(child, Tag) and child.name == "span" and "t" in child.get("class", []):
                flush()
                p = self.para(cell, space_after=Pt(2))
                add_inline(p, child, {"bold": True, "color": NAVY})
            elif isinstance(child, Tag) and toc and child.name == "h3":
                flush()
                p = self.para(cell, space_after=Pt(3))
                add_inline(p, child, {"bold": True, "color": NAVY, "size": Pt(11)})
            elif isinstance(child, Tag) and child.name in BLOCKS:
                flush()
                self.block(cell, child, inner)
            elif isinstance(child, (Tag, NavigableString)):
                pending.append(child)
        flush()
        # paragraf terakhir di kotak tanpa jarak bawah
        if cell.paragraphs:
            cell.paragraphs[-1].paragraph_format.space_after = Pt(0)
        if dark:
            # teks langsung di .end (bukan anak tag) ditulis di atas; pastikan tebal tetap putih
            for p in cell.paragraphs:
                for r in p.runs:
                    if r.bold:
                        r.font.color.rgb = RGBColor.from_string("FFFFFF")
        if not cell.paragraphs:
            self.para(cell)
        self.para(container, space_after=Pt(4))

    def step(self, container, el, width):
        n = el.find("div", class_="n").get_text(strip=True)
        body = el.find_all("div", recursive=False)[1]
        h = body.find("h3")
        p = container.add_paragraph(style="Heading 3")
        r = p.add_run(f" {n} ")
        style_run(r, {"bold": True, "color": "FFFFFF", "shade": BLUE})
        p.add_run("  ")
        if h:
            add_inline(p, h)
        for child in body.children:
            if isinstance(child, Tag) and child is not h:
                self.block(container, child, width)

    def flow(self, container, el, width):
        items = el.find_all("div", recursive=False)
        t = container.add_table(rows=1, cols=len(items))
        table_no_borders(t)
        keep_row_together(t.rows[0])
        w = int(width / len(items))
        for i, it in enumerate(items):
            cell = t.cell(0, i)
            shade(cell._tc, "EFF6FF" if "hl" in it.get("class", []) else SOFT)
            cell_borders(cell, top=("FFFFFF", 12), left=("FFFFFF", 24), bottom=("FFFFFF", 12), right=("FFFFFF", 24))
            cell_margins(cell, 80, 80, 60, 60)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p.paragraph_format.space_after = Pt(0)
            b = it.find("b")
            if b:
                r = p.add_run(b.get_text(strip=True))
                style_run(r, {"bold": True, "color": BLUE, "size": Pt(10.5)})
                r.add_break()
                b.extract()
            add_inline(p, it, {"size": Pt(8.5)})
        set_col_widths(t, [w] * len(items))
        self.para(container, space_after=Pt(4))

    def two(self, container, el, width):
        cols = [c for c in el.children if isinstance(c, Tag)]
        t = container.add_table(rows=1, cols=len(cols))
        table_no_borders(t)
        keep_row_together(t.rows[0])
        gap = Cm(0.4)
        w = int((width - gap * (len(cols) - 1)) / len(cols))
        for i, c in enumerate(cols):
            cell = t.cell(0, i)
            cell.paragraphs[0]._p.getparent().remove(cell.paragraphs[0]._p)
            cell_margins(cell, 100, 100, 140, 140)
            if "card" in c.get("class", []):
                cell_borders(cell, top=(LINE, 6), left=(LINE, 6), bottom=(LINE, 6), right=(LINE, 6))
                self.blocks(cell, c, w - Cm(0.5))
            else:
                self.block(cell, c, w - Cm(0.5))
            if not cell.paragraphs:
                self.para(cell)
        set_col_widths(t, [w] * len(cols))
        self.para(container, space_after=Pt(4))

    def cover(self, el):
        doc = self.doc
        t = doc.add_table(rows=1, cols=1)
        table_no_borders(t)
        cell = t.cell(0, 0)
        set_col_widths(t, [CONTENT_W])
        shade(cell._tc, NAVY)
        cell_margins(cell, 400, 300, 360, 360)
        cell_borders(cell, bottom=(ORANGE, 36))
        p = cell.paragraphs[0]
        p.paragraph_format.space_after = Pt(14)
        p.add_run().add_picture(str((SRC / el.find("img")["src"]).resolve()), height=Cm(1.6))
        eyebrow = self.para(cell, space_after=Pt(2))
        add_inline(eyebrow, el.find("div", class_="eyebrow"), {"bold": True, "color": "93C5FD", "size": Pt(9)})
        h1 = self.para(cell, space_after=Pt(6))
        add_inline(h1, el.find("h1"), {"bold": True, "color": "FFFFFF", "size": Pt(24)})
        lead = self.para(cell, space_after=Pt(10))
        add_inline(lead, el.find("p", class_="lead"), {"color": "D1D5DB", "size": Pt(11)})
        meta = self.para(cell, space_after=Pt(0))
        for i, s in enumerate(el.find("div", class_="meta").find_all("span", recursive=False)):
            if i:
                meta.add_run("   ·   ").font.color.rgb = RGBColor.from_string(MUTED)
            add_inline(meta, s, {"color": "9CA3AF", "size": Pt(8.5)})
        for r in meta.runs:
            if r.bold:
                r.font.color.rgb = RGBColor.from_string("E5E7EB")
        self.para(doc, space_after=Pt(8))


def setup(doc, title):
    sec = doc.sections[0]
    sec.page_width, sec.page_height = Cm(21.0), Cm(29.7)
    sec.left_margin = sec.right_margin = Cm(2.0)
    sec.top_margin, sec.bottom_margin = Cm(1.8), Cm(1.8)
    sec.footer_distance = Cm(0.9)

    st = doc.styles["Normal"]
    st.font.name = FONT
    st.element.rPr.rFonts.set(qn("w:eastAsia"), FONT)
    st.font.size = Pt(10.5)
    st.font.color.rgb = RGBColor.from_string(TEXT)
    st.paragraph_format.line_spacing = 1.15
    for name, size, color, before, after in (("Heading 1", 15, NAVY, 16, 6), ("Heading 2", 12, NAVY, 10, 3),
                                             ("Heading 3", 11.5, NAVY, 10, 3)):
        h = doc.styles[name]
        h.font.name = FONT
        rf =h.element.get_or_add_rPr().get_or_add_rFonts()
        for k in ("w:ascii", "w:hAnsi", "w:eastAsia", "w:cs"):
            rf.set(qn(k), FONT)
        for k in ("w:asciiTheme", "w:hAnsiTheme", "w:eastAsiaTheme", "w:cstheme"):
            if rf.get(qn(k)) is not None:
                del rf.attrib[qn(k)]
        h.font.size = Pt(size)
        h.font.bold = True
        h.font.italic = False
        h.font.color.rgb = RGBColor.from_string(color)
        pf = h.paragraph_format
        pf.space_before, pf.space_after = Pt(before), Pt(after)
        pf.keep_with_next = True

    core = doc.core_properties
    core.title = title
    core.author = "LSP Edukasi Global Cendekia"
    core.subject = "Panduan aplikasi ujian online"

    # Footer: judul kiri, nomor halaman kanan (tabel dua kolom — tab kanan di gaya Footer tidak dapat diandalkan)
    footer = sec.footer
    foot = {"size": Pt(8), "color": MUTED}
    ft = footer.add_table(rows=1, cols=2, width=CONTENT_W)
    table_no_borders(ft)
    left, right = ft.cell(0, 0).paragraphs[0], ft.cell(0, 1).paragraphs[0]
    for cp in (left, right):
        cp.paragraph_format.space_after = Pt(0)
    style_run(left.add_run(f"LSP Edukasi Global Cendekia · {title}"), foot)
    right.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    style_run(right.add_run("Halaman "), foot)
    add_field(right, "PAGE", foot)
    style_run(right.add_run(" / "), foot)
    add_field(right, "NUMPAGES", foot)
    set_col_widths(ft, [Cm(12.5), CONTENT_W - Cm(12.5)])
    # paragraf kosong bawaan footer tetap ada (wajib); kecilkan supaya tidak menambah tinggi
    fp = footer.paragraphs[0]
    fp.paragraph_format.space_after = Pt(0)
    style_run(fp.add_run(""), {"size": Pt(2)})
    footer._element.append(fp._p)   # footer harus diakhiri paragraf, letakkan setelah tabel


def build(src, out, title):
    soup = BeautifulSoup((SRC / src).read_text(encoding="utf-8"), "html.parser")
    doc = Document()
    setup(doc, title)
    # hapus paragraf kosong bawaan
    for p in list(doc.paragraphs):
        p._p.getparent().remove(p._p)
    Writer(doc, title).blocks(doc, soup.body)
    doc.save(ROOT / out)
    print("ok", out)


if __name__ == "__main__":
    for src, (out, title) in DOCS.items():
        build(src, out, title)
