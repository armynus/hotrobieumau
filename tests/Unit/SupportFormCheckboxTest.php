<?php

namespace Tests\Unit;

use App\Services\SupportFormService;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class SupportFormCheckboxTest extends TestCase
{
    public function test_batch_updates_checkboxes_preserves_other_controls_and_formatting(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'checkbox_test_');
        try {
            $xml = '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"><w:body><w:p>';
            foreach (['MB_SMS', 'MB_APLUS', 'untouched'] as $tag) {
                $xml .= '<w:sdt><w:sdtPr><w:tag w:val="'.$tag.'"/><w14:checkbox><w14:checked w14:val="1"/></w14:checkbox></w:sdtPr><w:sdtContent><w:r><w:rPr><w:sz w:val="24"/></w:rPr><w:t>original</w:t></w:r></w:sdtContent></w:sdt>';
            }
            $xml .= '</w:p></w:body></w:document>';
            $zip = new ZipArchive;
            $zip->open($file, ZipArchive::OVERWRITE);
            $zip->addFromString('word/document.xml', $xml);
            $zip->addFromString('word/styles.xml', 'unchanged');
            $zip->close();
            (new SupportFormService)->updateCheckboxContentControls($file, ['MB_SMS' => true, 'MB_APLUS' => false, 'absent' => true]);
            $zip->open($file);
            $updated = $zip->getFromName('word/document.xml');
            $this->assertSame('unchanged', $zip->getFromName('word/styles.xml'));
            $zip->close();
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($updated));
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $xpath->registerNamespace('w14', 'http://schemas.microsoft.com/office/word/2010/wordml');
            $this->assertSame('0', $xpath->evaluate('string(//w:sdt[w:sdtPr/w:tag[@w:val="MB_APLUS"]]/w:sdtPr/w14:checkbox/w14:checked/@w14:val)'));
            $this->assertSame(1, $xpath->query('//w:sdt[w:sdtPr/w:tag[@w:val="MB_SMS"]]//w:sym')->length);
            $this->assertSame('☐', $xpath->evaluate('string(//w:sdt[w:sdtPr/w:tag[@w:val="MB_APLUS"]]//w:t)'));
            $this->assertSame('original', $xpath->evaluate('string(//w:sdt[w:sdtPr/w:tag[@w:val="untouched"]]//w:t)'));
            $this->assertSame(3, $xpath->query('//w:rPr/w:sz[@w:val="24"]')->length);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
