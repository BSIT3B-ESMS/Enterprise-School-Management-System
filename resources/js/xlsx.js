// A minimal Excel (.xlsx) writer: one worksheet, a bold header row, plain text and number cells.
// An .xlsx file is a ZIP of XML parts; entries are stored uncompressed, which every spreadsheet
// app reads. Written here because the project has no spreadsheet library.

const encoder = new TextEncoder();

const crcTable = (() => {
    const table = new Uint32Array(256);

    for (let index = 0; index < 256; index++) {
        let value = index;

        for (let bit = 0; bit < 8; bit++) {
            value = value & 1 ? 0xedb88320 ^ (value >>> 1) : value >>> 1;
        }

        table[index] = value >>> 0;
    }

    return table;
})();

function crc32(bytes) {
    let crc = 0xffffffff;

    for (let index = 0; index < bytes.length; index++) {
        crc = crcTable[(crc ^ bytes[index]) & 0xff] ^ (crc >>> 8);
    }

    return (crc ^ 0xffffffff) >>> 0;
}

/**
 * Escapes text for XML and drops control characters XML cannot hold.
 */
function xmlText(value) {
    return String(value)
        .replace(/[\u0000-\u0008\u000b\u000c\u000e-\u001f]/g, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// 0 → "A", 25 → "Z", 26 → "AA".
function columnName(index) {
    let name = '';

    for (let remaining = index + 1; remaining > 0; remaining = Math.floor((remaining - 1) / 26)) {
        name = String.fromCharCode(65 + ((remaining - 1) % 26)) + name;
    }

    return name;
}

/**
 * Style indexes into styles.xml cellXfs: 0 normal, 1 bold (header), 2 number with two decimals.
 */
function cellXml(value, reference, isHeader) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    if (typeof value === 'number' && Number.isFinite(value) && !isHeader) {
        return `<c r="${reference}" s="2"><v>${value}</v></c>`;
    }

    return `<c r="${reference}" t="inlineStr"${isHeader ? ' s="1"' : ''}><is><t xml:space="preserve">${xmlText(value)}</t></is></c>`;
}

function worksheetXml(rows) {
    const columnCount = rows[0]?.length ?? 0;
    const widths = Array.from({ length: columnCount }, (_, column) => {
        const longest = Math.max(...rows.map((row) => String(row[column] ?? '').length));

        return Math.min(50, Math.max(10, longest + 2));
    });
    const cols = widths.map((width, column) => `<col min="${column + 1}" max="${column + 1}" width="${width}" customWidth="1"/>`).join('');
    const sheetRows = rows.map((row, rowIndex) => (
        `<row r="${rowIndex + 1}">${row.map((value, column) => cellXml(value, `${columnName(column)}${rowIndex + 1}`, rowIndex === 0)).join('')}</row>`
    )).join('');

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        + '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        + '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        + (cols ? `<cols>${cols}</cols>` : '')
        + `<sheetData>${sheetRows}</sheetData>`
        + '</worksheet>';
}

function workbookParts(rows, sheetName) {
    return {
        '[Content_Types].xml': '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            + '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            + '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            + '<Default Extension="xml" ContentType="application/xml"/>'
            + '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            + '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            + '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            + '</Types>',
        '_rels/.rels': '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            + '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            + '</Relationships>',
        'xl/workbook.xml': '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            + '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            + `<sheets><sheet name="${xmlText(sheetName.slice(0, 31))}" sheetId="1" r:id="rId1"/></sheets>`
            + '</workbook>',
        'xl/_rels/workbook.xml.rels': '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            + '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            + '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            + '</Relationships>',
        'xl/styles.xml': '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            + '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            + '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            + '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            + '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            + '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            + '<cellXfs count="3">'
            + '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            + '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            + '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            + '</cellXfs>'
            + '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            + '</styleSheet>',
        'xl/worksheets/sheet1.xml': worksheetXml(rows),
    };
}

/**
 * Packs named byte arrays into a ZIP archive (STORE method, no compression).
 */
function zip(entries) {
    const dosDate = (1 << 5) | 1; // 1980-01-01, the earliest ZIP date.
    const localParts = [];
    const centralParts = [];
    let offset = 0;

    entries.forEach(({ name, bytes }) => {
        const nameBytes = encoder.encode(name);
        const crc = crc32(bytes);
        const local = new DataView(new ArrayBuffer(30));
        const central = new DataView(new ArrayBuffer(46));

        local.setUint32(0, 0x04034b50, true);
        local.setUint16(4, 20, true);
        local.setUint16(6, 0x0800, true);
        local.setUint16(12, dosDate, true);
        local.setUint32(14, crc, true);
        local.setUint32(18, bytes.length, true);
        local.setUint32(22, bytes.length, true);
        local.setUint16(26, nameBytes.length, true);

        central.setUint32(0, 0x02014b50, true);
        central.setUint16(4, 20, true);
        central.setUint16(6, 20, true);
        central.setUint16(8, 0x0800, true);
        central.setUint16(14, dosDate, true);
        central.setUint32(16, crc, true);
        central.setUint32(20, bytes.length, true);
        central.setUint32(24, bytes.length, true);
        central.setUint16(28, nameBytes.length, true);
        central.setUint32(42, offset, true);

        localParts.push(new Uint8Array(local.buffer), nameBytes, bytes);
        centralParts.push(new Uint8Array(central.buffer), nameBytes);
        offset += 30 + nameBytes.length + bytes.length;
    });

    const centralSize = centralParts.reduce((total, part) => total + part.length, 0);
    const end = new DataView(new ArrayBuffer(22));

    end.setUint32(0, 0x06054b50, true);
    end.setUint16(8, entries.length, true);
    end.setUint16(10, entries.length, true);
    end.setUint32(12, centralSize, true);
    end.setUint32(16, offset, true);

    return [...localParts, ...centralParts, new Uint8Array(end.buffer)];
}

/**
 * The bytes of an .xlsx workbook whose first row is the header.
 *
 * @param {Array<Array<string|number>>} rows
 * @returns {Uint8Array[]} parts to pass to a Blob
 */
export function buildXlsx(rows, sheetName = 'Sheet1') {
    const entries = Object.entries(workbookParts(rows, sheetName)).map(([name, xml]) => ({ name, bytes: encoder.encode(xml) }));

    return zip(entries);
}
