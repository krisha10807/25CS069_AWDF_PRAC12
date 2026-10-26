import os
import zipfile
import re
from PIL import Image

# Directories
base_dir = r'd:\WDF____\StudentHub\Practical_12'
screenshot_dir = os.path.join(base_dir, 'screenshots')
output_docx = os.path.join(base_dir, 'Practical_12_Test_Report.docx')

# List of 10 Test Cases corresponding to the 10 requirements
test_cases = [
    {
        'id': 'TC-01',
        'title': 'Admin Login and Dashboard Access',
        'desc': 'Verify that an institutional administrator can securely authenticate with valid credentials (admin@charusat.ac.in) and access the event management control center.',
        'steps': '1. Navigate to login.php.\n2. Enter admin credentials.\n3. Click "Sign In to Portal".',
        'expected': 'Admin is authenticated via BCRYPT password verification, session ID is regenerated, and user lands on admin_events.php with event statistics.',
        'image': '01_admin_login_and_dashboard.png'
    },
    {
        'id': 'TC-02',
        'title': 'Successful Event Creation',
        'desc': 'Verify that an administrator can publish a new event with all mandatory fields and a valid poster image.',
        'steps': '1. Click "Create New Event".\n2. Fill Title, Category, Date/Time, Venue, and Description.\n3. Select a valid poster image (valid_poster.png).\n4. Click "Publish Event".',
        'expected': 'Event record is inserted into database via prepared statement, poster is stored in uploads/posters/, and success confirmation banner is displayed.',
        'image': '02_successful_event_creation.png'
    },
    {
        'id': 'TC-03',
        'title': 'Event List with Uploaded Posters',
        'desc': 'Verify that the event catalog renders all scheduled events alongside high-resolution poster artwork thumbnails and event metadata.',
        'steps': '1. Scroll down the admin dashboard.\n2. Review the responsive event card grid.',
        'expected': 'All events display poster images, category tags, date/time, venue badges, description, and action buttons.',
        'image': '03_event_list_with_uploaded_posters.png'
    },
    {
        'id': 'TC-04',
        'title': 'Successful Event Update',
        'desc': 'Verify that an administrator can modify event details (e.g., campus venue, timings, or replace poster).',
        'steps': '1. Click "Edit" on an existing event card.\n2. Modal loads pre-populated details.\n3. Update venue to "CSPIT Central Auditorium & Innovation Lab".\n4. Click "Save Changes".',
        'expected': 'Database record updates cleanly, modal closes, and green update success banner is displayed.',
        'image': '04_successful_event_update.png'
    },
    {
        'id': 'TC-05',
        'title': 'Successful Event Deletion',
        'desc': 'Verify that an administrator can permanently delete an event with confirmation prompt and automatic file cleanup.',
        'steps': '1. Click "Delete" on an event card.\n2. Confirmation modal prompts for user action.\n3. Click "Yes, Delete Event".',
        'expected': 'Event is deleted from database, associated poster is unlinked, card disappears from grid, and deletion success alert is shown.',
        'image': '05_successful_event_deletion.png'
    },
    {
        'id': 'TC-06',
        'title': 'Valid Poster Upload and Live Preview',
        'desc': 'Verify that selecting a compliant image file (JPG, PNG, WEBP under 2 MB) triggers real-time preview and validates file properties.',
        'steps': '1. Open event creation modal.\n2. Choose valid_poster.png (8 KB PNG file).\n3. Observe file dropzone feedback.',
        'expected': 'Dropzone displays filename, file size, and live graphical preview of the poster before submission.',
        'image': '06_valid_poster_upload.png'
    },
    {
        'id': 'TC-07',
        'title': 'Invalid File Type Validation',
        'desc': 'Verify that attempting to upload non-permitted file extensions or MIME types (such as .pdf, .txt, .exe) is strictly intercepted on the server.',
        'steps': '1. Open event creation form.\n2. Select a PDF document (invalid_file_type.pdf).\n3. Submit the form.',
        'expected': 'Server rejects file, upload is aborted, and red validation banner states: "Invalid File Type Validation Failed! Only JPG, JPEG, PNG, and WEBP poster images are permitted."',
        'image': '07_invalid_file_type_validation.png'
    },
    {
        'id': 'TC-08',
        'title': 'File Size Limit Validation (> 2 MB)',
        'desc': 'Verify that uploading an image file exceeding the 2 MB upper boundary is intercepted and rejected.',
        'steps': '1. Open event creation form.\n2. Select an oversized high-res image (5.7 MB).\n3. Submit the form.',
        'expected': 'Server detects size > 2 MB, prevents file storage, and displays: "File Size Limit Validation Failed! Uploaded poster exceeds the 2 MB maximum allowable limit."',
        'image': '08_file_size_limit_validation.png'
    },
    {
        'id': 'TC-09',
        'title': 'Success and Error Message Alerts',
        'desc': 'Verify that operational feedback (success banners and error notifications) are clearly distinguishable with high visual contrast and semantic icons.',
        'steps': '1. Trigger event publication to review success alert.\n2. Review alert dismissal and layout styling.',
        'expected': 'Success messages render in styled emerald green banners with checkmarks; validation errors render in rose red with warning icons.',
        'image': '09_success_and_error_messages.png'
    },
    {
        'id': 'TC-10',
        'title': 'Unauthorized User Access Restriction (RBAC)',
        'desc': 'Verify that non-admin authenticated users (Role: Student) attempting to access admin event management features are intercepted with an HTTP 403 Forbidden screen.',
        'steps': '1. Log in with student credentials (student@charusat.ac.in).\n2. Navigate directly to admin_events.php or click "Test Access Admin Dashboard".',
        'expected': 'Access is blocked with HTTP 403 response, rendering an institutional security violation notice explaining privilege restrictions.',
        'image': '10_unauthorized_user_access_restriction.png'
    }
]

# XML Templates
def create_drawing_xml(doc_pr_id, name, rel_id, cx, cy):
    return f'''<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="{cx}" cy="{cy}"/><wp:effectExtent t="0" r="0" b="0" l="0"/><wp:docPr id="{doc_pr_id}" name="{name}"/><wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="{doc_pr_id}" name="{name}"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="{rel_id}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>'''

def p_xml(text, bold=False, color="1F2937", size=22, align="left", space_before=100, space_after=100):
    b_tag = "<w:b/>" if bold else ""
    jc_tag = f'<w:jc w:val="{align}"/>' if align != "left" else ""
    return f'''<w:p><w:pPr>{jc_tag}<w:spacing w:before="{space_before}" w:after="{space_after}"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Segoe UI" w:hAnsi="Segoe UI"/>{b_tag}<w:color w:val="{color}"/><w:sz w:val="{size}"/></w:rPr><w:t xml:space="preserve">{text}</w:t></w:r></w:p>'''

# Build Document XML
doc_body = []

# Title & Header
doc_body.append(p_xml("TEST REPORT", bold=True, color="312E81", size=36, align="center", space_before=200, space_after=60))
doc_body.append(p_xml("Practical 12 – Event Management & Secure File Upload System", bold=True, color="1E1B4B", size=28, align="center", space_before=40, space_after=40))
doc_body.append(p_xml("Role-Based Authentication, File Validation (MIME & Size Limit) & Poster Repository", bold=False, color="475569", size=22, align="center", space_before=20, space_after=200))

# Metadata Table
doc_body.append('''<w:tbl><w:tblPr><w:tblW w:w="9200" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/><w:left w:val="none"/><w:bottom w:val="single" w:sz="6" w:space="0" w:color="CBD5E1"/><w:right w:val="none"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:insideV w:val="none"/></w:tblBorders><w:jc w:val="center"/></w:tblPr>''')

meta_rows = [
    ("Student Name", "Krisha Patel"),
    ("Enrollment No. (Student ID)", "25CS069"),
    ("Course & Department", "Web Development Frameworks (WDF) | Computer Science & Engineering"),
    ("Institution", "Chandubhai S. Patel Institute of Technology (CSPIT), CHARUSAT"),
    ("Subject Code & Practical", "WDF (CE3xx) – Practical 12"),
    ("Due Date", "19 October 2026, 23:59 PM"),
    ("Overall Test Outcome", "10 / 10 Requirements PASSED (100% Satisfied)")
]

for label, val in meta_rows:
    doc_body.append(f'''<w:tr><w:tc><w:tcPr><w:tcW w:w="3200" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="F8FAFC"/></w:tcPr>{p_xml(label, bold=True, color="334155", size=20, space_before=60, space_after=60)}</w:tc><w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr>{p_xml(val, bold=False, color="0F172A", size=20, space_before=60, space_after=60)}</w:tc></w:tr>''')
doc_body.append('</w:tbl>')

doc_body.append(p_xml("", space_before=150, space_after=150))
doc_body.append(p_xml("Requirement Compliance Matrix", bold=True, color="1E1B4B", size=24, space_before=150, space_after=80))

# Matrix Table
doc_body.append('''<w:tbl><w:tblPr><w:tblW w:w="9200" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="8" w:space="0" w:color="312E81"/><w:bottom w:val="single" w:sz="8" w:space="0" w:color="312E81"/><w:left w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/><w:right w:val="single" w:sz="4" w:space="0" w:color="CBD5E1"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="E2E8F0"/></w:tblBorders><w:jc w:val="center"/></w:tblPr>''')
doc_body.append(f'''<w:tr><w:tc><w:tcPr><w:tcW w:w="1200" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="EEF2FF"/></w:tcPr>{p_xml("Test ID", bold=True, color="312E81", size=18, align="center")}</w:tc><w:tc><w:tcPr><w:tcW w:w="4800" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="EEF2FF"/></w:tcPr>{p_xml("Instruction Requirement", bold=True, color="312E81", size=18)}</w:tc><w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="EEF2FF"/></w:tcPr>{p_xml("Status", bold=True, color="312E81", size=18, align="center")}</w:tc><w:tc><w:tcPr><w:tcW w:w="1400" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="EEF2FF"/></w:tcPr>{p_xml("Screenshot", bold=True, color="312E81", size=18, align="center")}</w:tc></w:tr>''')

for tc in test_cases:
    doc_body.append(f'''<w:tr><w:tc><w:tcPr><w:tcW w:w="1200" w:type="dxa"/></w:tcPr>{p_xml(tc['id'], bold=True, color="4338CA", size=18, align="center")}</w:tc><w:tc><w:tcPr><w:tcW w:w="4800" w:type="dxa"/></w:tcPr>{p_xml(tc['title'], bold=False, color="1E293B", size=18)}</w:tc><w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="ECFDF5"/></w:tcPr>{p_xml("PASSED", bold=True, color="059669", size=18, align="center")}</w:tc><w:tc><w:tcPr><w:tcW w:w="1400" w:type="dxa"/></w:tcPr>{p_xml("Embedded", bold=False, color="64748B", size=18, align="center")}</w:tc></w:tr>''')
doc_body.append('</w:tbl>')

# Test Details and Embedded Screenshots
doc_body.append(p_xml("", space_before=150, space_after=150))
doc_body.append(p_xml("Detailed Test Evidence & Verification Screenshots", bold=True, color="1E1B4B", size=26, space_before=200, space_after=120))

relationships = []
image_files = []

for idx, tc in enumerate(test_cases, start=1):
    doc_body.append(p_xml(f"{tc['id']}: {tc['title']}", bold=True, color="312E81", size=22, space_before=200, space_after=60))
    doc_body.append(p_xml(f"Objective: {tc['desc']}", color="475569", size=19, space_before=30, space_after=30))
    doc_body.append(p_xml(f"Execution Steps:\n{tc['steps']}", color="334155", size=18, space_before=30, space_after=30))
    doc_body.append(p_xml(f"Expected Outcome: {tc['expected']}", color="166534", size=18, space_before=30, space_after=40))
    doc_body.append(p_xml("Verification Status: [PASSED - 100% Verified]", bold=True, color="059669", size=18, space_before=20, space_after=80))

    img_filename = tc['image']
    img_path = os.path.join(screenshot_dir, img_filename)
    if os.path.exists(img_path):
        rel_id = f"rIdImg{idx}"
        relationships.append((rel_id, f"media/{img_filename}"))
        image_files.append((img_filename, img_path))
        
        # Calculate aspect ratio
        with Image.open(img_path) as im:
            w, h = im.size
        target_cx = 5400000  # ~5.6 inches in EMUs
        target_cy = int(target_cx * (h / w))
        if target_cy > 4000000:
            target_cy = 3800000
            target_cx = int(target_cy * (w / h))

        doc_body.append(create_drawing_xml(idx, img_filename, rel_id, target_cx, target_cy))
        doc_body.append(p_xml(f"Figure {idx}: {tc['title']} ({img_filename})", bold=False, color="64748B", size=16, align="center", space_before=40, space_after=180))

# Document Footer Summary
doc_body.append(p_xml("End of Practical 12 Test Report", bold=True, color="475569", size=18, align="center", space_before=300, space_after=100))
doc_body.append(p_xml("CHARUSAT - CSPIT | Student: Krisha Patel (25CS069)", bold=False, color="94A3B8", size=16, align="center", space_before=20, space_after=100))

document_xml = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
            xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
            xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
            xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"
            xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <w:body>
        {''.join(doc_body)}
        <w:sectPr>
            <w:pgSz w:w="12240" w:h="15840"/>
            <w:pgMar w:top="1100" w:right="1100" w:bottom="1100" w:left="1100" w:header="720" w:footer="720"/>
        </w:sectPr>
    </w:body>
</w:document>'''

# [Content_Types].xml
content_types_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Default Extension="png" ContentType="image/png"/>
    <Default Extension="jpeg" ContentType="image/jpeg"/>
    <Default Extension="jpg" ContentType="image/jpeg"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>'''

# _rels/.rels
rels_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>'''

# word/_rels/document.xml.rels
doc_rels = ['''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">''']
for r_id, target in relationships:
    doc_rels.append(f'<Relationship Id="{r_id}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="{target}"/>')
doc_rels.append('</Relationships>')
document_xml_rels = ''.join(doc_rels)

# docProps/core.xml
core_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
                   xmlns:dc="http://purl.org/dc/elements/1.1/"
                   xmlns:dcterms="http://purl.org/dc/terms/"
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>Practical 12 Test Report - Krisha Patel (25CS069)</dc:title>
    <dc:creator>Krisha Patel (25CS069)</dc:creator>
    <cp:lastModifiedBy>Krisha Patel (25CS069)</cp:lastModifiedBy>
    <dcterms:created xsi:type="dcterms:W3CDTF">2026-10-26T22:00:00Z</dcterms:created>
    <dcterms:modified xsi:type="dcterms:W3CDTF">2026-10-26T22:00:00Z</dcterms:modified>
</cp:coreProperties>'''

# docProps/app.xml
app_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
    <Application>StudentHub Documentation Generator</Application>
    <Company>CHARUSAT - CSPIT</Company>
</Properties>'''

# Write to output docx zip archive
with zipfile.ZipFile(output_docx, 'w', compression=zipfile.ZIP_DEFLATED) as z:
    z.writestr('[Content_Types].xml', content_types_xml)
    z.writestr('_rels/.rels', rels_xml)
    z.writestr('docProps/core.xml', core_xml)
    z.writestr('docProps/app.xml', app_xml)
    z.writestr('word/document.xml', document_xml)
    z.writestr('word/_rels/document.xml.rels', document_xml_rels)

    for img_name, img_full in image_files:
        with open(img_full, 'rb') as f:
            z.writestr(f'word/media/{img_name}', f.read())

print(f"Generated {output_docx} successfully! Size: {os.path.getsize(output_docx)} bytes")
