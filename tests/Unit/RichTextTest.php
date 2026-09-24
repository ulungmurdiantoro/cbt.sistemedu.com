<?php

namespace Tests\Unit;

use App\Support\RichText;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    public function test_removes_script_event_handlers_and_dangerous_urls(): void
    {
        $dirty = '<p onclick="x()">a</p><script>alert(1)</script><img src=x onerror="alert(2)">'
            . '<iframe src="https://evil.test"></iframe><svg onload="alert(3)"></svg>'
            . '<a href="javascript:alert(4)">b</a>';

        $clean = RichText::clean($dirty);

        foreach (['<script', 'onclick', 'onerror', 'onload', '<iframe', '<svg', 'javascript:'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $clean);
        }
        $this->assertStringContainsString('<p>a</p>', $clean);
    }

    public function test_only_pasted_data_images_are_kept(): void
    {
        $png   = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAC0lEQVQImWNgQAYAAA4AAbGa6gYAAAAASUVORK5CYII=';
        $clean = RichText::clean("<img src=\"https://evil.test/t.png\"><img src=\"/admin/x\"><img src=\"{$png}\">");

        $this->assertStringNotContainsString('evil.test', $clean);
        $this->assertStringNotContainsString('/admin/x', $clean);
        $this->assertStringContainsString($png, $clean);
    }

    public function test_keeps_quill_formatting(): void
    {
        $html = '<p class="ql-align-center"><strong>Tebal</strong> <em>miring</em> <u>garis</u></p>'
            . '<ol><li data-list="bullet">poin</li><li data-list="ordered" class="ql-indent-1">nomor</li></ol>'
            . '<pre class="ql-syntax">kode</pre>';

        $this->assertSame($html, RichText::clean($html));
    }

    public function test_plain_text_like_file_paths_is_untouched(): void
    {
        $path = 'essay_migas_answers/1/2/3/jawaban-20260924-abc123.pdf';

        $this->assertSame($path, RichText::clean($path));
        $this->assertNull(RichText::clean(null));
        $this->assertSame('', RichText::clean(''));
    }
}
