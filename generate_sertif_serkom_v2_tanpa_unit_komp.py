import os
import re
import pandas as pd
import qrcode
from PIL import Image

from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont


# =========================
# PATH CONFIG
# =========================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

EXCEL_FILE = os.path.join(BASE_DIR, "data", "ToTUPDTidar.xlsx")
BG_PATH    = os.path.join(BASE_DIR, "assets", "DepanV2.png")
LOGO_PATH  = os.path.join(BASE_DIR, "assets", "logo2.jpg")

# Fonts
FONT_RADLEY            = os.path.join(BASE_DIR, "assets", "fonts", "Radley-Regular.ttf")
FONT_RADLEY_ITALIC     = os.path.join(BASE_DIR, "assets", "fonts", "Radley-Italic.ttf")
FONT_LIBRE_BASKERVILLE = os.path.join(BASE_DIR, "assets", "fonts", "LibreBaskerville-VariableFont_wght.ttf")

# Output
EXCEL_BASENAME = os.path.splitext(os.path.basename(EXCEL_FILE))[0]

OUT_ROOT_DIR  = os.path.join(BASE_DIR, "output", "sertifikat")
OUT_BATCH_DIR = os.path.join(OUT_ROOT_DIR, EXCEL_BASENAME)

OUT_DIR_QR   = os.path.join(OUT_BATCH_DIR, "qr")
OUT_DIR_CERT = OUT_BATCH_DIR

os.makedirs(OUT_DIR_QR, exist_ok=True)
os.makedirs(OUT_DIR_CERT, exist_ok=True)

# Page 2 assets
PAGE2_DIR = os.path.join(BASE_DIR, "assets", "page2")

PAGE2_MAP = {
    "EDUKIA-AIL-2024-001": "Tanpa-Unit-Komp-KAN.png",
    "EDUKIA-LAD-2024-002": "LAD-00002.png",
    "EDUKIA-IMR-2024-003": "IMR-00003.png",
    "EDUKIA-ToT-2024-004": "Tanpa-Unit-Komp-KAN.png",
    "EDUKIA-TKO-2024-005": "TKO-005.png",
    "EDUKIA-AUI-2024-006": "AUI-00006.png",
    "EDUKIA-LIM-2024-007": "LIM-00007.png",
    "EDUKIA-LQO-2025-012": "LQO-00001.png",
    "EDUKIA-FMO-2025-013": "FMO-00001.png",
    "EDUKIA-PSP-2025-014": "PSP-00001.png",
    "EDUKIA-LHC-2025-009": "LHC-002-trial.png",
    "EDUKIA-LDT-2025-010": "LDT-010-trial.png",
    "EDUKIA-LML-2025-008": "LML-008-trial.png",
    "EDUKIA-DLD-2025-011": "DLD-0011-trial.png",
    "EDUKIA-GLP-2026-015": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-K3L-2026-016": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-LOP-2026-017": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-QMS-2026-018": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-QCA-2026-019": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-QAO-2026-020": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-RDO-2026-021": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-RAO-2026-022": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-SBO-2026-023": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-ESG-2026-024": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-EMS-2026-025": "Tanpa-Unit-Komp-trial.png",
    "EDUKIA-CLO-2026-026": "Tanpa-Unit-Komp-trial.png",
}

TANPA_UNIT_KOMP_FILES = {"Tanpa-Unit-Komp-KAN.png", "Tanpa-Unit-Komp-trial.png"}

# =========================
# UNIT KOMPETENSI DATA
# Source: Unit Kompetensi Skema Lab - Edukia REV 1.docx
# Format: { no_skema: [(kode, text_id, text_en), ...] }
# =========================
UNIT_KOMP = {
"EDUKIA-ToT-2024-004": [
        ("SP.TOT.00I.0I",
         "Mendesain Program Pembelajaran Outcome Based Education (OBE)",
         "Designing Outcome-Based Education (OBE) Learning Program",),
        ("SP.TOT.002.01",
         "Menyusun RPS dan Bahan Ajar Pembelajaran Outcome Based Education (OBE)",
         "Developing the Semester Learning Plan (RPS) and Teaching Materials Based on Outcome-Based Education (OBE)",),
        ("SP.TOT.003.01",
         "Merancang Pembelajaran Outcome Based Education (OBE)",
         "Designing Outcome-Based Education (OBE) Learning",),
        ("SP.TOT.004.01",
         "Melaksanakan Pembelajaran Outcome Based Education (OBE)",
         "Implementing Outcome-Based Education (OBE) Learning",),
        ("SP.TOT.005.01",
         "Mengevaluasi Hasil Pembelajaran Outcome Based Education (OBE)",
         "Evaluating Outcome-Based Education (OBE) Learning Outcomes",),
        ("SP.TOT.006.01",
         "Mengembangkan Program Pembelajaran Outcome Based Education (OBE)",
         "Developing Outcome-Based Education (OBE) Learning Program",)
    ],
    "EDUKIA-EMS-2026-025": [
        ("SP.EMS.001.01",
         "Menerapkan Konteks Organisasi dalam SML",
         "Implementing the Context of the Organization in EMS",),
        ("SP.EMS.002.01",
         "Mengidentifikasi Aspek dan Dampak Lingkungan",
         "Identifying Environmental Aspects and Impacts",),
        ("SP.EMS.003.01",
         "Mengidentifikasi dan Mengevaluasi Kewajiban Kepatuhan",
         "Identifying and Evaluating Compliance Obligations",),
        ("SP.EMS.004.01",
         "Menyusun Sasaran dan Program Lingkungan",
         "Establishing Environmental Objectives and Programmes",),
        ("SP.EMS.005.01",
         "Mengendalikan Operasional dan Dokumen SML",
         "Controlling Operations and EMS Documents",),
        ("SP.EMS.006.01",
         "Melaksanakan Pemantauan dan Pengukuran Kinerja Lingkungan",
         "Conducting Monitoring and Measurement of Environmental Performance",),
        ("SP.EMS.007.01",
         "Melaksanakan Audit Internal SML",
         "Conducting EMS Internal Audits",),
        ("SP.EMS.008.01",
         "Menindaklanjuti Ketidaksesuaian dan Tindakan Perbaikan",
         "Following Up on Nonconformities and Corrective Actions",),
    ],
    "EDUKIA-AIL-2024-001": [
        ("SP.AIL.OOI.OI",
         "Memahami Pengetahuan Dasar Terkait Audit",
         "Understanding Basic Knowledge Related to Auditing"),
        ("SP.AIL.002.01",
         "Melaksanakan Kegiatan Audit Internal",
         "Conducting Internal Audit Activities"),
        ("SP.AIL.003.01",
         "Memahami Konsep Integrasi SPMI dan ISO 21001:2018",
         "Understanding the Concept of SPMI and ISO 21001:2018 Integration"),
        ("SP.AIL.004.01",
         "Mengevaluasi Penerapan Integrasi Siklus Plan ISO 21001:2018 ke Dalam SPMI",
         "Evaluating the Integration of the Plan Cycle from ISO 21001:2018 into SPMI"),
        ("SP.AIL.005.01",
         "Mengevaluasi Penerapan Integrasi Siklus Do ISO 21001:2018 ke Dalam SPMI",
         "Evaluating the Implementation of the Do Cycle Integration of ISO 21001:2018 into SPMI"),
        ("SP.AIL.006.01",
         "Mengevaluasi Penerapan Integrasi Siklus Check ISO 21001:2018 ke Dalam SPMI",
         "Evaluating the Implementation of the Check Cycle Integration of ISO 21001:2018 into SPMI"),
        ("SP.AIL.007.0I",
         "Mengevaluasi Penerapan Integrasi Siklus Act ISO 21001:2018 ke Dalam SPMI",
         "Evaluating the Implementation of the Act Cycle Integration of ISO 21001:2018 into SPMI"),
    ],
    "EDUKIA-GLP-2026-015": [
        ("SP.GLP.001.01",
         "Melakukan Persiapan Penerapan GLP",
         "Preparing for GLP Implementation"),
        ("SP.GLP.002.01",
         "Melaksanakan Pengujian Sesuai Prinsip GLP",
         "Conducting Testing in Accordance with GLP Principles"),
        ("SP.GLP.003.01",
         "Melakukan Pengendalian Mutu dan Data",
         "Performing Quality and Data Control"),
        ("SP.GLP.004.01",
         "Mengelola Limbah dan Pasca Pengujian",
         "Managing Laboratory Waste and Post-Testing Activities"),
    ],
    "EDUKIA-K3L-2026-016": [
        ("SP.K3L.001.01",
         "Melakukan Identifikasi Bahaya dan Penilaian Risiko (HIRADC) di Laboratorium",
         "Conducting Hazard Identification, Risk Assessment, and Determining Controls (HIRADC) in the Laboratory"),
        ("SP.K3L.002.01",
         "Mengelola Penyimpanan dan Penanganan Bahan Kimia Berbahaya (B3)",
         "Managing the Storage and Handling of Hazardous Chemicals"),
        ("SP.K3L.003.01",
         "Melakukan Pengelolaan dan Penyimpanan Limbah B3 Laboratorium",
         "Managing the Storage and Disposal of Hazardous Laboratory Waste"),
        ("SP.K3L.004.01",
         "Mengelola Tindakan Tanggap Darurat di Laboratorium",
         "Managing Laboratory Emergency Response Procedures"),
        ("SP.K3L.005.01",
         "Melakukan Inspeksi K3 dan Lingkungan Kerja Laboratorium",
         "Conducting Occupational Health and Safety (OHS) and Workplace Environment Inspections in the Laboratory"),
    ],
    "EDUKIA-LOP-2026-017": [
        ("SP.SPL.001.01",
         "Menetapkan Konteks Organisasi dan Perencanaan Mutu (Plan)",
         "Establishing Organizational Context and Quality Planning (Plan)"),
        ("SP.SPL.002.01",
         "Mengelola Sumber Daya dan Operasional (Do)",
         "Managing Resources and Operations (Do)"),
        ("SP.SPL.003.01",
         "Melakukan Evaluasi Kinerja (Check)",
         "Conducting Performance Evaluation (Check)"),
        ("SP.SPL.004.01",
         "Melakukan Peningkatan Berkelanjutan (Act)",
         "Implementing Continual Improvement (Act)"),
    ],
    "EDUKIA-QMS-2026-018": [
        ("SP.SMM.001.01",
         "Menganalisis Konteks Organisasi dan Pihak Berkepentingan",
         "Analyzing Organizational Context and Interested Parties"),
        ("SP.SMM.002.01",
         "Menyusun Perencanaan Mutu dan Manajemen Risiko",
         "Developing Quality Planning and Risk Management"),
        ("SP.SMM.003.01",
         "Mengelola Sumber Daya dan Informasi Terdokumentasi",
         "Managing Resources and Documented Information"),
        ("SP.SMM.004.01",
         "Mengendalikan Operasional dan Penyedia Eksternal",
         "Controlling Operations and External Providers"),
        ("SP.SMM.005.01",
         "Melakukan Evaluasi Kinerja dan Peningkatan Berkelanjutan",
         "Conducting Performance Evaluation and Continual Improvement"),
    ],
    "EDUKIA-QCA-2026-019": [
        ("SP.AQL.001.01",
         "Melakukan Kaji Ulang Permintaan, Tender, dan Kontrak Pengujian",
         "Review of Requests, Tenders, and Contracts for Testing"),
        ("SP.AQL.002.01",
         "Memilih, Memverifikasi, dan Memvalidasi Metode Pengujian",
         "Selection, Verification, and Validation of Testing Methods"),
        ("SP.AQL.003.01",
         "Melaksanakan Pengambilan Sampel (Sampling)",
         "Conducting Sampling"),
        ("SP.AQL.004.01",
         "Menangani dan Menyiapkan Sampel Untuk Analisis",
         "Handling and Preparing Samples for Analysis"),
        ("SP.AQL.005.01",
         "Membuat dan Mengelola Rekaman Teknis Pengujian",
         "Creating and Managing Technical Records of Testing"),
        ("SP.AQL.006.01",
         "Melaksanakan Penjaminan Mutu Hasil Pengujian",
         "Ensuring the Quality of Test Results"),
        ("SP.AQL.007.01",
         "Mengevaluasi Ketidakpastian Pengukuran",
         "Evaluating Measurement Uncertainty"),
        ("SP.AQL.008.01",
         "Menyusun Laporan Hasil Uji",
         "Compiling Test Reports"),
        ("SP.AQL.009.01",
         "Mengidentifikasi dan Mengendalikan Pekerjaan Yang Tidak Sesuai",
         "Identifying and Controlling Non-conforming Work"),
    ],
    "EDUKIA-QAO-2026-020": [
        ("SP.QAO.001.01",
         "Mengelola dan Mengendalikan Dokumen Sistem Manajemen Mutu",
         "Managing and Controlling Quality Management System Documents"),
        ("SP.QAO.002.01",
         "Mengimplementasikan Sistem Manajemen Mutu Sesuai Standar yang Berlaku",
         "Implementing the Quality Management System in Accordance with Applicable Standards"),
        ("SP.QAO.003.01",
         "Melaksanakan Audit Internal Sistem Manajemen Mutu",
         "Conducting Internal Audits of the Quality Management System"),
        ("SP.QAO.004.01",
         "Mengidentifikasi dan Mengendalikan Ketidaksesuaian",
         "Identifying and Controlling Non-conformities"),
        ("SP.QAO.005.01",
         "Melaksanakan Tindakan Korektif dan Tindakan Pencegahan",
         "Implementing Corrective and Preventive Actions"),
        ("SP.QAO.006.01",
         "Melakukan Analisis Risiko dan Peluang dalam Sistem Manajemen Mutu",
         "Performing Risk and Opportunity Analysis within the Quality Management System"),
        ("SP.QAO.007.01",
         "Melakukan Pemantauan, Pengukuran, dan Evaluasi Kinerja Mutu",
         "Monitoring, Measuring, and Evaluating Quality Performance"),
        ("SP.QAO.008.01",
         "Melaksanakan Pengendalian Rekaman dan Pelaporan Kinerja Mutu",
         "Controlling Records and Quality Performance Reporting"),
        ("SP.QAO.009.01",
         "Menerapkan Prinsip Perbaikan Berkelanjutan (Continuous Improvement)",
         "Applying Principles of Continuous Improvement"),
    ],
    "EDUKIA-RDO-2026-021": [
        ("SP.RDO.001.01",
         "Merencanakan Kegiatan Penelitian dan Pengembangan",
         "Planning Research and Development Activities"),
        ("SP.RDO.002.01",
         "Melaksanakan Kegiatan Penelitian dan Pengembangan",
         "Executing Research and Development Activities"),
        ("SP.RDO.003.01",
         "Melakukan Analisis dan Validasi Hasil Penelitian",
         "Analyzing and Validating Research Results"),
        ("SP.RDO.004.01",
         "Mengelola Dokumentasi dan Pelaporan Kegiatan R&D",
         "Managing Documentation and Reporting of R&D Activities"),
        ("SP.RDO.005.01",
         "Mengelola Implementasi dan Peningkatan Berkelanjutan Hasil Pengembangan",
         "Managing the Implementation and Continual Improvement of Development Results"),
    ],
    "EDUKIA-RAO-2026-022": [
        ("SP.RAO.001.01",
         "Menerapkan Prinsip Kepatuhan Regulasi dan Etika Profesi",
         "Applying Regulatory Compliance Principles and Professional Ethics"),
        ("SP.RAO.002.01",
         "Menyusun dan Mengevaluasi Dokumen Registrasi dan Perizinan Produk",
         "Drafting and Evaluating Product Registration and Licensing Documents"),
        ("SP.RAO.003.01",
         "Melakukan Proses Pengajuan Registrasi dan Perizinan Produk kepada Otoritas Terkait",
         "Conducting the Submission Process for Product Registration and Licensing to Relevant Authorities"),
        ("SP.RAO.004.01",
         "Melakukan Pemantauan Perubahan Regulasi dan Analisis Dampaknya terhadap Produk/Perusahaan",
         "Monitoring Regulatory Changes and Analyzing Their Impact on Products and the Company"),
        ("SP.RAO.005.01",
         "Mengelola Arsip dan Sistem Dokumentasi Regulatory Affairs",
         "Managing Regulatory Affairs Archives and Documentation Systems"),
        ("SP.RAO.006.01",
         "Melakukan Evaluasi Kepatuhan Produk dan Menyusun Tindak Lanjut Ketidaksesuaian",
         "Evaluating Product Compliance and Formulating Corrective Actions for Non-Conformities"),
    ],
    "EDUKIA-SBO-2026-023": [
        ("SP.SDR.001.01",
         "Mengidentifikasi Aspek dan Dampak Keberlanjutan Operasional",
         "Identifying Operational Sustainability Aspects and Impacts"),
        ("SP.SDR.002.01",
         "Merencanakan Program Peningkatan Kinerja Lingkungan dan Sosial",
         "Planning Environmental and Social Performance Improvement Programs"),
        ("SP.SDR.003.01",
         "Mengimplementasikan Program Keberlanjutan Organisasi",
         "Implementing Organizational Sustainability Programs"),
        ("SP.SDR.004.01",
         "Memantau dan Mengevaluasi Capaian Target Keberlanjutan",
         "Monitoring and Evaluating Sustainability Target Achievements"),
        ("SP.SDR.005.01",
         "Mengomunikasikan Kinerja Keberlanjutan Internal",
         "Communicating Internal Sustainability Performance"),
        ("SP.SDR.006.01",
         "Mendukung Pengelolaan Data Kinerja Keberlanjutan",
         "Supporting Sustainability Performance Data Management"),
    ],
    "EDUKIA-ESG-2026-024": [
        ("SP.ESG.001.01",
         "Mengidentifikasi dan Memetakan Pemangku Kepentingan",
         "Identifying and Mapping Stakeholders"),
        ("SP.ESG.002.01",
         "Mengidentifikasi Isu dan Risiko ESG",
         "Identifying ESG Issues and Risks"),
        ("SP.ESG.003.01",
         "Melakukan Penilaian Dampak dan Risiko ESG",
         "Conducting ESG Impact and Risk Assessments"),
        ("SP.ESG.004.01",
         "Menyusun Matriks Materialitas",
         "Developing a Materiality Matrix"),
        ("SP.ESG.005.01",
         "Mengintegrasikan Risiko ESG ke dalam Manajemen Risiko Organisasi",
         "Integrating ESG Risks into Enterprise Risk Management (ERM)"),
        ("SP.ESG.006.01",
         "Menyiapkan Informasi Pengungkapan ESG",
         "Preparing ESG Disclosure Information"),
        ("SP.ESG.007.01",
         "Mendukung Tata Kelola dan Kebijakan ESG Organisasi",
         "Supporting Organizational ESG Governance and Policy"),
    ],
    "EDUKIA-CLO-2026-026": [
        ("SP.CLO.001.01",
         "Melakukan Pemenuhan Perizinan Usaha dan Legalitas Korporasi",
         "Ensuring Business Licensing Compliance and Corporate Legality"),
        ("SP.CLO.002.01",
         "Menyusun dan Meninjau Dokumen Hukum Perusahaan",
         "Drafting and Reviewing Corporate Legal Documents"),
        ("SP.CLO.003.01",
         "Menyusun Legal Opinion dan Rekomendasi Hukum",
         "Formulating Legal Opinions and Recommendations"),
        ("SP.CLO.004.01",
         "Mengelola Administrasi dan Arsip Hukum Korporasi",
         "Managing Corporate Legal Administration and Archives"),
        ("SP.CLO.005.01",
         "Menyusun Laporan Legal dan Kepatuhan Secara Berkala",
         "Preparing Periodic Legal and Compliance Reports"),
        ("SP.CLO.006.01",
         "Melakukan Monitoring dan Analisis Perubahan Regulasi",
         "Monitoring and Analyzing Regulatory Changes"),
        ("SP.CLO.007.01",
         "Melakukan Legal Due Diligence & Audit Kepatuhan Hukum",
         "Conducting Legal Due Diligence and Compliance Audits"),
        ("SP.CLO.008.01",
         "Mendukung Audit Eksternal dan Pemeriksaan",
         "Supporting External Audits and Inspections"),
        ("SP.CLO.009.01",
         "Mengelola Hubungan dengan Regulasi dan Stakeholder",
         "Managing Relations with Regulators and Stakeholders"),
        ("SP.CLO.010.01",
         "Menangani Pemeriksaan dan Investigasi oleh Regulator",
         "Handling Regulatory Inquiries and Investigations"),
    ],
}

INCH = 72

# =========================
# GLOBAL TEXT OFFSET
# =========================
TEXT_SHIFT_X_IN = 0.6


# =========================
# HELPER
# =========================
def sanitize_filename(s: str) -> str:
    s = str(s).strip()
    s = re.sub(r'[\\/:*?"<>|]+', '', s)
    s = s.replace("_", "").replace("-", "")
    s = re.sub(r"\s+", " ", s)
    return s or "TANPA NAMA"

def safe_str(v) -> str:
    if v is None:
        return ""
    if isinstance(v, float) and pd.isna(v):
        return ""
    s = str(v).strip()
    return "" if s.lower() == "nan" else s

def pt(x_in: float, y_in: float):
    return x_in * INCH, y_in * INCH

def pt_text(x_in: float, y_in: float):
    return (x_in + TEXT_SHIFT_X_IN) * INCH, y_in * INCH

def canva_top_to_rl_bottom_y(y_from_top_in: float, page_h_in: float, obj_h_in: float = 0.0) -> float:
    return page_h_in - y_from_top_in - obj_h_in


# =========================
# FONT REGISTER
# =========================
def register_fonts():
    if os.path.exists(FONT_RADLEY):
        pdfmetrics.registerFont(TTFont("Radley", FONT_RADLEY))
    else:
        print(f"⚠️ Font Radley tidak ditemukan: {FONT_RADLEY}")

    if os.path.exists(FONT_RADLEY_ITALIC):
        pdfmetrics.registerFont(TTFont("RadleyItalic", FONT_RADLEY_ITALIC))
    else:
        print(f"⚠️ Font Radley Italic tidak ditemukan: {FONT_RADLEY_ITALIC}")

    if os.path.exists(FONT_LIBRE_BASKERVILLE):
        pdfmetrics.registerFont(TTFont("LibreBaskerville", FONT_LIBRE_BASKERVILLE))
    else:
        print(f"⚠️ Font LibreBaskerville tidak ditemukan: {FONT_LIBRE_BASKERVILLE}")


# =========================
# SUPERSCRIPT DATE DRAWING
# =========================
_DATE_ORD_RE = re.compile(r"^\s*(\d{1,2})(st|nd|rd|th)\s+(.*)\s*$", re.IGNORECASE)

def split_ordinal_date(date_str: str):
    s = (date_str or "").strip()
    m = _DATE_ORD_RE.match(s)
    if not m:
        return None, None, s
    return m.group(1), m.group(2), m.group(3)

def measure_ordinal_date_width(date_str: str, font_main: str, size_main: int) -> float:
    num, suf, rest = split_ordinal_date(date_str)
    if num is None:
        return pdfmetrics.stringWidth(date_str, font_main, size_main)

    suf_size = max(6, int(size_main * 0.60))
    w_num = pdfmetrics.stringWidth(num, font_main, size_main)
    w_suf = pdfmetrics.stringWidth(suf, font_main, suf_size)
    w_rest = pdfmetrics.stringWidth(" " + rest, font_main, size_main)
    return w_num + w_suf + w_rest

def draw_ordinal_date(c, x_pt: float, y_pt: float, date_str: str, font_main: str, size_main: int):
    num, suf, rest = split_ordinal_date(date_str)

    if num is None:
        c.setFont(font_main, size_main)
        c.drawString(x_pt, y_pt, date_str)
        return

    suf_size = max(6, int(size_main * 0.60))
    rise = size_main * 0.45

    c.setFont(font_main, size_main)
    c.drawString(x_pt, y_pt, num)
    w_num = pdfmetrics.stringWidth(num, font_main, size_main)

    c.setFont(font_main, suf_size)
    c.drawString(x_pt + w_num, y_pt + rise, suf)
    w_suf = pdfmetrics.stringWidth(suf, font_main, suf_size)

    c.setFont(font_main, size_main)
    c.drawString(x_pt + w_num + w_suf, y_pt, " " + rest)

def draw_label_plus_date(c, x_pt: float, y_pt: float, label: str, date_str: str, font_main: str, size_main: int):
    c.setFont(font_main, size_main)
    c.drawString(x_pt, y_pt, label)
    w_label = pdfmetrics.stringWidth(label, font_main, size_main)
    draw_ordinal_date(c, x_pt + w_label, y_pt, date_str, font_main, size_main)

def draw_centered_label_plus_date(c, center_x_pt: float, y_pt: float, label: str, date_str: str, font_main: str, size_main: int):
    w_label = pdfmetrics.stringWidth(label, font_main, size_main)
    w_date = measure_ordinal_date_width(date_str, font_main, size_main)
    total = w_label + w_date
    start_x = center_x_pt - (total / 2.0)
    draw_label_plus_date(c, start_x, y_pt, label, date_str, font_main, size_main)


# =========================
# MULTILINE NAME HELPER
# =========================
def wrap_text_to_lines(text, font_name, font_size, max_width):
    words = str(text).split()
    if not words:
        return ["-"]

    lines = []
    current = words[0]

    for word in words[1:]:
        test = current + " " + word
        w = pdfmetrics.stringWidth(test, font_name, font_size)
        if w <= max_width:
            current = test
        else:
            lines.append(current)
            current = word

    lines.append(current)
    return lines

def fit_name_text(text, font_name, font_size, max_width, max_lines=2, min_font_size=12):
    size = font_size
    while size >= min_font_size:
        lines = wrap_text_to_lines(text, font_name, size, max_width)
        if len(lines) <= max_lines:
            return lines, size
        size -= 1

    lines = wrap_text_to_lines(text, font_name, min_font_size, max_width)

    if len(lines) > max_lines:
        kept = lines[:max_lines]
        last = kept[-1]

        while pdfmetrics.stringWidth(last + "...", font_name, min_font_size) > max_width and len(last) > 1:
            last = last[:-1].rstrip()

        kept[-1] = last + "..."
        lines = kept

    return lines, min_font_size

def draw_centered_multiline_name(c, text, x_center, y_top, max_width, font_name, font_size, line_gap_pt=4, max_lines=2, min_font_size=12):
    lines, final_size = fit_name_text(
        text=text,
        font_name=font_name,
        font_size=font_size,
        max_width=max_width,
        max_lines=max_lines,
        min_font_size=min_font_size
    )

    c.setFont(font_name, final_size)

    line_height = final_size + line_gap_pt
    start_y = y_top
    for i, line in enumerate(lines):
        c.drawCentredString(x_center, start_y - (i * line_height), line)

    return {
        "lines_count": len(lines),
        "font_size": final_size,
        "used_height_pt": (len(lines) - 1) * line_height
    }


# =========================
# QR GENERATOR
# =========================
def build_qr(no_sertif: str, nama_lengkap: str, tgl_rilis_eng: str, out_path: str):
    nama_lengkap = (nama_lengkap or "-").replace("\n", " ").strip()
    no_sertif = (no_sertif or "-").strip()
    tgl_rilis_eng = (tgl_rilis_eng or "-").strip()

    qr_data = (
        "This document has been digitally signed by:\n"
        "Dr. Agung Yulianto, M.Si\n"
        "As Ketua LSP\n"
        "LSP Edukasi Global Cendekia\n\n"
        f"By Certificate No: {no_sertif}\n"
        f"Date of Certificate: {tgl_rilis_eng}\n"
        f"Certificate Holder Name: {nama_lengkap}\n\n"
        "Verification Link:\n"
        f"https://verifikasi-sertifikat.lspedukia.id/{no_sertif}\n"
    )

    qr = qrcode.QRCode(
        version=None,
        error_correction=qrcode.constants.ERROR_CORRECT_H,
        box_size=10,
        border=4,
    )
    qr.add_data(qr_data)
    qr.make(fit=True)

    qr_img = qr.make_image(fill_color="black", back_color="white").convert("RGBA")

    if os.path.exists(LOGO_PATH):
        logo_img = Image.open(LOGO_PATH).convert("RGBA")
        qr_w, qr_h = qr_img.size

        target_w = int(qr_w * 0.40)
        aspect = logo_img.size[1] / logo_img.size[0]
        target_h = int(target_w * aspect)

        logo_resized = logo_img.resize((target_w, target_h), Image.Resampling.LANCZOS)
        pos = ((qr_w - target_w) // 2, (qr_h - target_h) // 2)
        qr_img.alpha_composite(logo_resized, dest=pos)

    qr_img.convert("RGB").save(out_path)


# =========================
# UNIT KOMPETENSI TABLE
# =========================
def draw_unit_komp_table(c, units, left_x_in, y_top_in, table_w_in, page_h_in,
                          font_id, font_en, font_size_id=10.0, font_size_en=9.5,
                          min_row_h_in=0.0):
    COL1_W_IN = 1.65
    COL2_W_IN = table_w_in - COL1_W_IN

    PAD_X_PT   = 9.0
    PAD_TOP_PT = 8.0
    PAD_BOT_PT = 7.0
    GAP_PT     = 2.0   # gap antara baris ID dan EN

    LH_ID = font_size_id * 1.30
    LH_EN = font_size_en * 1.30

    COL2_TEXT_W_PT = COL2_W_IN * INCH - 2 * PAD_X_PT

    HEADER_H_IN = 0.58

    # Header font sizes
    HDR_TOP_FS    = 10.5
    HDR_BOTTOM_FS = 8.5

    # Warna
    HDR_BG     = (36 / 255, 62 / 255, 123 / 255)   # #243e7b
    HDR_TXT    = (1.00, 1.00, 1.00)   # putih
    ROW_BG     = (1.00, 1.00, 1.00)   # semua baris putih (mengikuti referensi)
    BORDER_CLR = (36 / 255, 62 / 255, 123 / 255)   # #243e7b
    TXT_CLR    = (0.06, 0.06, 0.06)   # hampir hitam
    BORDER_W_PT = 1.2

    left_x_pt  = left_x_in * INCH
    table_w_pt = table_w_in * INCH
    col1_w_pt  = COL1_W_IN * INCH
    col2_w_pt  = COL2_W_IN * INCH

    current_y_top = y_top_in

    # ---- HEADER ----
    hdr_h_pt = HEADER_H_IN * INCH
    hdr_y_rl = canva_top_to_rl_bottom_y(current_y_top, page_h_in, HEADER_H_IN) * INCH

    c.setFillColorRGB(*HDR_BG)
    c.rect(left_x_pt, hdr_y_rl, table_w_pt, hdr_h_pt, fill=1, stroke=0)

    c.setStrokeColorRGB(*BORDER_CLR)
    c.setLineWidth(BORDER_W_PT)
    c.rect(left_x_pt, hdr_y_rl, table_w_pt, hdr_h_pt, fill=0, stroke=1)
    c.line(left_x_pt + col1_w_pt, hdr_y_rl, left_x_pt + col1_w_pt, hdr_y_rl + hdr_h_pt)

    c.setFillColorRGB(*HDR_TXT)
    col1_cx = left_x_pt + col1_w_pt / 2
    col2_cx = left_x_pt + col1_w_pt + col2_w_pt / 2

    # Posisi 2 baris teks header (atas: ID besar, bawah: EN italic kecil)
    hdr_top_y    = hdr_y_rl + hdr_h_pt * 0.56
    hdr_bottom_y = hdr_y_rl + hdr_h_pt * 0.22

    c.setFont(font_id, HDR_TOP_FS)
    c.drawCentredString(col1_cx, hdr_top_y, "KODE UNIT")
    c.drawCentredString(col2_cx, hdr_top_y, "JUDUL UNIT KOMPETENSI")

    c.setFont(font_en, HDR_BOTTOM_FS)
    c.drawCentredString(col1_cx, hdr_bottom_y, "UNIT CODES")
    c.drawCentredString(col2_cx, hdr_bottom_y, "UNITS OF COMPETENCY TITLES")

    current_y_top += HEADER_H_IN

    # ---- BARIS DATA ----
    for kode, text_id, text_en in units:
        lines_id = wrap_text_to_lines(text_id, font_id, font_size_id, COL2_TEXT_W_PT)
        lines_en = wrap_text_to_lines(text_en, font_en, font_size_en, COL2_TEXT_W_PT)

        content_h_pt = (PAD_TOP_PT
                        + len(lines_id) * LH_ID
                        + GAP_PT
                        + len(lines_en) * LH_EN
                        + PAD_BOT_PT)
        row_h_pt = max(content_h_pt, min_row_h_in * INCH)
        row_h_in = row_h_pt / INCH

        row_y_rl = canva_top_to_rl_bottom_y(current_y_top, page_h_in, row_h_in) * INCH

        # Latar baris (selalu putih, mengikuti referensi)
        c.setFillColorRGB(*ROW_BG)
        c.rect(left_x_pt, row_y_rl, table_w_pt, row_h_pt, fill=1, stroke=0)

        # Border baris
        c.setStrokeColorRGB(*BORDER_CLR)
        c.setLineWidth(BORDER_W_PT)
        c.rect(left_x_pt, row_y_rl, table_w_pt, row_h_pt, fill=0, stroke=1)
        c.line(left_x_pt + col1_w_pt, row_y_rl, left_x_pt + col1_w_pt, row_y_rl + row_h_pt)

        # Kode unit (col1, tengah horizontal & vertikal)
        c.setFillColorRGB(*TXT_CLR)
        c.setFont(font_id, font_size_id)
        kode_y = row_y_rl + (row_h_pt / 2) - (font_size_id * 0.32)
        c.drawCentredString(left_x_pt + col1_w_pt / 2, kode_y, kode)

        # Teks col2 — mulai dari atas sel dengan padding
        text_x = left_x_pt + col1_w_pt + PAD_X_PT
        top_y  = row_y_rl + row_h_pt - PAD_TOP_PT

        # Bahasa Indonesia (Radley)
        c.setFont(font_id, font_size_id)
        for li, line in enumerate(lines_id):
            c.drawString(text_x, top_y - font_size_id - li * LH_ID, line)

        # Bahasa Inggris (Radley Italic)
        en_top_y = top_y - len(lines_id) * LH_ID - GAP_PT
        c.setFont(font_en, font_size_en)
        for li, line in enumerate(lines_en):
            c.drawString(text_x, en_top_y - font_size_en - li * LH_EN, line)

        current_y_top += row_h_in

    return current_y_top


# =========================
# CERTIFICATE GENERATOR (PAGE 1 + PAGE 2)
# =========================
def generate_certificate_pdf(output_pdf: str, data: dict, qr_path: str):
    page_size = A4
    page_w_pt, page_h_pt = page_size

    PAGE_W_IN = 8.27
    PAGE_H_IN = 11.69

    c = canvas.Canvas(output_pdf, pagesize=page_size)

    font_main   = "Radley"          if "Radley"          in pdfmetrics.getRegisteredFontNames() else "Times-Roman"
    font_italic = "RadleyItalic"    if "RadleyItalic"    in pdfmetrics.getRegisteredFontNames() else "Helvetica-Oblique"
    font_lbv    = "LibreBaskerville" if "LibreBaskerville" in pdfmetrics.getRegisteredFontNames() else font_main

    # ======================
    # PAGE 1
    # ======================
    if os.path.exists(BG_PATH):
        bg = ImageReader(BG_PATH)
        c.drawImage(bg, 0, 0, width=page_w_pt, height=page_h_pt, mask="auto")

    X_CENTER_IN = PAGE_W_IN / 2.0

    # QR PAGE 1
    QR1_X_CANVA_IN = 4.74
    QR1_Y_CANVA_IN = 8.55
    QR1_SIZE_IN = 1.24

    qr1_y_in = canva_top_to_rl_bottom_y(QR1_Y_CANVA_IN, PAGE_H_IN, obj_h_in=QR1_SIZE_IN)

    nama_gelar = (data.get("nama_gelar") or "").strip()
    has_gelar = bool(nama_gelar)

    if has_gelar:
        fs_number = 13
        fs_certify = 12
        fs_name = 18
        fs_exam = 11
        fs_gelar = 13
        fs_scheme = 13
        fs_noskema = 13
        fs_held = 11

        sp1 = 0.35
        sp2 = 0.45
        sp3 = 0.30
        sp4 = 0.30
    else:
        fs_number = 17
        fs_certify = 13
        fs_name = 20
        fs_exam = 13
        fs_gelar = 14
        fs_scheme = 17
        fs_noskema = 14
        fs_held = 14

        sp1 = 0.74
        sp2 = 0.35
        sp3 = 1.2
        sp4 = 1.5

    # --- TEXT PAGE 1 ---
    y = 9.2
    c.setFont(font_main, fs_number)
    c.drawCentredString(*pt_text(X_CENTER_IN, y), f'Number : {data.get("no_sertif", "-")}')

    y -= sp1
    c.setFont(font_italic, fs_certify)
    c.drawCentredString(*pt_text(X_CENTER_IN, y), "This is to certify that:")

    # NAMA LENGKAP AUTO WRAP
    y -= sp2
    name_x_pt, name_y_pt = pt_text(X_CENTER_IN, y)

    name_draw = draw_centered_multiline_name(
        c=c,
        text=data.get("nama_lengkap", "-"),
        x_center=name_x_pt,
        y_top=name_y_pt,
        max_width=5.5 * INCH,
        font_name=font_main,
        font_size=fs_name,
        line_gap_pt=4,
        max_lines=2,
        min_font_size=12
    )

    extra_name_lines = name_draw["lines_count"] - 1
    if extra_name_lines > 0:
        y -= 0.28 * extra_name_lines

    if has_gelar:
        y -= sp1
        c.setFont(font_main, fs_exam)
        c.drawCentredString(*pt_text(X_CENTER_IN, y), "Has followed and successfully passed the exam of:")

        y -= sp3
        c.setFont(font_main, fs_gelar)
        c.drawCentredString(*pt_text(X_CENTER_IN, y), nama_gelar)

    y -= 0.8
    c.setFont(font_main, 14)
    c.drawCentredString(
        *pt_text(X_CENTER_IN, y),
        "The Certification Based on Scheme :"
    )

    y -= 0.3
    c.setFont(font_main, fs_scheme)
    c.drawCentredString(
        *pt_text(X_CENTER_IN, y),
        data.get("skema_eng", "-")
    )

    y -= 0.7
    c.setFont(font_main, fs_noskema)
    c.drawCentredString(*pt_text(X_CENTER_IN, y), data.get("no_skema", "-"))

    y -= 0.3
    held_on = data.get("held_on_eng", "-")
    draw_centered_label_plus_date(
        c,
        *pt_text(X_CENTER_IN, y),
        "Held on ",
        held_on,
        font_main,
        fs_held
    )

    cert_date = data.get("tgl_rilis_eng", "-")
    valid_until = data.get("tgl_berakhir_eng", "-")

    y_left = 3.17
    draw_label_plus_date(c, *pt_text(0.85, y_left), "Certification date  : ", cert_date, font_main, 11)

    y_left -= 0.25
    draw_label_plus_date(c, *pt_text(0.85, y_left), "Valid until               : ", valid_until, font_main, 11)

    # DRAW QR PAGE 1
    if os.path.exists(qr_path):
        qr_img = ImageReader(qr_path)
        c.drawImage(
            qr_img,
            *pt(QR1_X_CANVA_IN, qr1_y_in),
            width=QR1_SIZE_IN * INCH,
            height=QR1_SIZE_IN * INCH,
            mask="auto"
        )

    c.showPage()

    # ======================
    # PAGE 2
    # ======================
    no_skema   = safe_str(data.get("no_skema", ""))
    page2_file = PAGE2_MAP.get(no_skema)

    if page2_file:
        page2_path    = os.path.join(PAGE2_DIR, page2_file)
        is_tanpa_unit = (page2_file in TANPA_UNIT_KOMP_FILES)

        if os.path.exists(page2_path):
            bg2 = ImageReader(page2_path)
            c.drawImage(bg2, 0, 0, width=page_w_pt, height=page_h_pt, mask="auto")

            center_x_pt = page_w_pt / 2.0
            shifted_cx  = center_x_pt + TEXT_SHIFT_X_IN * INCH

            # --- no_sertif ---
            NUMBER2_Y_CANVA_IN = 1.92 if is_tanpa_unit else 2.12
            number2_y_pt = canva_top_to_rl_bottom_y(NUMBER2_Y_CANVA_IN, PAGE_H_IN, 0.0) * INCH

            c.setFont(font_main, 13)
            c.drawCentredString(shifted_cx, number2_y_pt, f'Number : {data.get("no_sertif", "-")}')

            # --- Khusus Tanpa-Unit-Komp: skema_eng + tabel ---
            if is_tanpa_unit:
                # skema_eng dalam LibreBaskerville
                SKEMA_ENG2_Y = 1.72
                skema_eng_y_pt = canva_top_to_rl_bottom_y(SKEMA_ENG2_Y, PAGE_H_IN, 0.0) * INCH
                c.setFont(font_lbv, 13)
                c.drawCentredString(shifted_cx, skema_eng_y_pt, data.get("skema_eng", "-").upper())

                # Tabel unit kompetensi
                units = UNIT_KOMP.get(no_skema, [])
                if units:
                    TABLE_LEFT_X_IN = 1.51
                    TABLE_Y_TOP_IN  = 2.05
                    TABLE_W_IN      = 16.21 / 2.54
                    QR_TOP_Y_IN     = 8.45  # batas atas QR (dengan buffer 0.10)

                    # Pilih font size adaptif agar tabel muat sebelum QR
                    avail_h_in = QR_TOP_Y_IN - TABLE_Y_TOP_IN
                    HDR_H = 0.58
                    chosen_fs_id, chosen_fs_en, chosen_min_h = 10.0, 9.5, 0.60
                    for fs_id, fs_en, min_h in [(10.0, 9.5, 0.60), (9.0, 8.5, 0.0)]:
                        lh_id = fs_id * 1.30
                        lh_en = fs_en * 1.30
                        single_h = (8 + lh_id + 2 + lh_en + 7) / INCH
                        est_h = HDR_H + len(units) * single_h * 1.08
                        if est_h <= avail_h_in:
                            chosen_fs_id, chosen_fs_en, chosen_min_h = fs_id, fs_en, min_h
                            break

                    draw_unit_komp_table(
                        c,
                        units,
                        left_x_in=TABLE_LEFT_X_IN,
                        y_top_in=TABLE_Y_TOP_IN,
                        table_w_in=TABLE_W_IN,
                        page_h_in=PAGE_H_IN,
                        font_id=font_main,
                        font_en=font_italic,
                        font_size_id=chosen_fs_id,
                        font_size_en=chosen_fs_en,
                        min_row_h_in=chosen_min_h,
                    )

            # --- QR Page 2 ---
            QR2_X_CANVA_IN = 4.74
            QR2_Y_CANVA_IN = 8.55
            QR2_SIZE_IN    = 1.24

            qr2_y_in = canva_top_to_rl_bottom_y(QR2_Y_CANVA_IN, PAGE_H_IN, obj_h_in=QR2_SIZE_IN)

            if os.path.exists(qr_path):
                qr_img2 = ImageReader(qr_path)
                c.drawImage(
                    qr_img2,
                    *pt(QR2_X_CANVA_IN, qr2_y_in),
                    width=QR2_SIZE_IN * INCH,
                    height=QR2_SIZE_IN * INCH,
                    mask="auto"
                )

            c.showPage()
        else:
            c.setFont("Helvetica", 12)
            c.drawString(50, page_h_pt - 50, f"Page2 PNG not found: {page2_path}")
            c.showPage()

    c.save()


# =========================
# MAIN
# =========================
def main():
    register_fonts()
    df = pd.read_excel(EXCEL_FILE)

    for idx, row in df.iterrows():
        try:
            no_sertif = safe_str(row.get("no_sertif", ""))
            if not no_sertif:
                print(f"Skip row {idx}: no_sertif kosong.")
                continue

            nama_lengkap = safe_str(row.get("nama_lengkap", ""))
            safe_nama = sanitize_filename(nama_lengkap)

            cert_data = {
                "no_sertif":      no_sertif,
                "nama_lengkap":   nama_lengkap or "-",
                "nama_gelar":     safe_str(row.get("nama_gelar", "")) or "",
                "skema_eng":      safe_str(row.get("skema_eng", "-")) or "-",
                "no_skema":       safe_str(row.get("no_skema", "-")) or "-",
                "held_on_eng":    safe_str(row.get("held_on_eng", "-")) or "-",
                "tgl_rilis_eng":  safe_str(row.get("tgl_rilis_eng", "-")) or "-",
                "tgl_berakhir_eng": safe_str(row.get("tgl_berakhir_eng", "-")) or "-",
            }

            no_skema_raw = cert_data["no_skema"]
            safe_no_skema = re.sub(r'[\\/:*?"<>|]+', '', no_skema_raw).strip() or "NOSKEMA"

            qr_path = os.path.join(OUT_DIR_QR, f"QR_{safe_nama}.png")
            out_pdf  = os.path.join(OUT_DIR_CERT, f"SERTIFIKAT_{safe_nama}_{safe_no_skema}.pdf")

            build_qr(
                no_sertif,
                nama_lengkap,
                cert_data["tgl_rilis_eng"],
                qr_path
            )

            generate_certificate_pdf(out_pdf, cert_data, qr_path)
            print(f"✅ Sertifikat dibuat: {out_pdf}")

        except Exception as e:
            print(f"❌ Error row {idx}: {e}")

    print(f"\n📁 Output batch tersimpan di: {OUT_BATCH_DIR}")
    print(f"   - QR        : {OUT_DIR_QR}")
    print(f"   - Sertifikat: {OUT_DIR_CERT}")


if __name__ == "__main__":
    main()
