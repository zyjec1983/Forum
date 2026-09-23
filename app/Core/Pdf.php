<?php
/**
 * Minimal text-only PDF generator (A4, Courier) with no external libraries.
 * Used by the plain-text participant activity report.
 */
class Pdf
{
    const PAGE_W   = 595.28;
    const PAGE_H   = 841.89;
    const MARGIN   = 50;
    const LINE_H   = 12;
    const FONT_SZ  = 10;
    const MAX_WRAP = 82;   // printable chars per full-width line
    const IND_WRAP = 76;   // printable chars per indented line

    protected $page  = '';
    protected $pages = [];
    protected $y     = self::PAGE_H - self::MARGIN;

    private function ensureSpace(): void
    {
        if ($this->y - self::LINE_H < self::MARGIN) {
            $this->pages[] = $this->page;
            $this->page    = '';
            $this->y       = self::PAGE_H - self::MARGIN;
        }
    }

    private function emit(string $s, bool $bold = false, int $indent = 0): void
    {
        $this->ensureSpace();
        $x = self::MARGIN + $indent * self::FONT_SZ * 0.6;
        $this->page .= "BT\n" . ($bold ? '/F2' : '/F1') . ' ' . self::FONT_SZ . " Tf\n"
            . $x . ' ' . $this->y . " Td\n(" . $this->esc($s) . ") Tj\nET\n";
        $this->y -= self::LINE_H;
    }

    public function write(string $s): void
    {
        $this->emit($s);
    }

    public function writeBold(string $s): void
    {
        $this->emit($s, true);
    }

    public function blank(): void
    {
        $this->ensureSpace();
        $this->y -= self::LINE_H;
    }

    public function rule(string $char = '=', int $count = 75): void
    {
        $this->emit(str_repeat($char, $count));
    }

    public function paragraph(string $s, int $indent = 0): void
    {
        $width = $indent > 0 ? self::IND_WRAP : self::MAX_WRAP;
        foreach ($this->wrap($s, $width) as $line) {
            $this->emit($line, false, $indent);
        }
    }

    public function wrap(string $s, int $width): array
    {
        $s     = trim(preg_replace('/\s+/u', ' ', $s));
        $lines = [];
        while (mb_strlen($s) > $width) {
            $cut = mb_substr($s, 0, $width);
            $pos = mb_strrpos($cut, ' ');
            if ($pos === false || $pos === 0) {
                $pos = $width;
            }
            $lines[] = mb_substr($s, 0, $pos);
            $s       = ltrim(mb_substr($s, $pos));
        }
        if ($s !== '') {
            $lines[] = $s;
        }
        return $lines;
    }

    /** Escapes PDF string characters and converts UTF-8 to Windows-1252. */
    private function esc(string $s): string
    {
        $s = mb_convert_encoding($s, 'CP1252', 'UTF-8');
        return strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    }

    public function render(string $title, string $author): string
    {
        $this->pages[] = $this->page;
        $n             = count($this->pages);
        $font1         = 3 + $n;
        $font2         = $font1 + 1;
        $contentStart  = $font2 + 1;

        $out  = "%PDF-1.4\n";
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids    = [];
        for ($i = 0; $i < $n; $i++) {
            $kids[] = ($i + 3) . ' 0 R';
        }
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        for ($i = 0; $i < $n; $i++) {
            $c = $contentStart + $i;
            $objs[$i + 3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_W . ' ' . self::PAGE_H
                . '] /Resources << /ProcSet [/PDF /Text] /Font << /F1 ' . $font1 . ' 0 R /F2 ' . $font2 . ' 0 R >> >> /Contents ' . $c . ' 0 R >>';
        }
        $objs[$font1] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>';
        $objs[$font2] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold /Encoding /WinAnsiEncoding >>';
        for ($i = 0; $i < $n; $i++) {
            $body = $this->pages[$i];
            $objs[$contentStart + $i] = '<< /Length ' . strlen($body) . " >>\nstream\n" . $body . "endstream";
        }

        $offsets = [];
        $maxId   = $contentStart + $n - 1;
        for ($id = 1; $id <= $maxId; $id++) {
            $offsets[$id] = strlen($out);
            $out .= $id . ' 0 obj' . "\n" . $objs[$id] . "\nendobj\n";
        }
        $xrefPos = strlen($out);
        $out .= "xref\n0 " . ($maxId + 1) . "\n";
        $out .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $out .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF\n";
        return $out;
    }
}