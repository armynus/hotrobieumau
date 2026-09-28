<?php

namespace Tests\Unit;

use App\Services\DocumentReviewMetadataParser;
use PHPUnit\Framework\TestCase;

class DocumentReviewMetadataParserTest extends TestCase
{
    public function test_slip_header_is_not_the_document_date_or_content(): void
    {
        $text = <<<'TEXT'
Đồng Tháp, ngày 03 tháng 03 năm 2026
PHIẾU TRÌNH CHUYỂN VĂN BẢN
Ý kiến của lãnh đạo                 Nội dung trình
Số: 2878/NHNo-PTD
Ngày: 12/02/2026
Nơi gởi: Agribank.
Nội dung: Cảnh báo, phòng ngừa rủi ro
hoạt động.
TRƯỞNG PHÒNG TỔNG HỢP
Đặng Khả Văn
TEXT;
        $r = (new DocumentReviewMetadataParser)->parse($text);
        $this->assertSame('2878/NHNo-PTD', $r['document_code']);
        $this->assertSame('2026-02-12', $r['issued_date']);
        $this->assertSame('Agribank.', $r['issuing_agency']);
        $this->assertSame('Cảnh báo, phòng ngừa rủi ro hoạt động.', $r['title']);
    }

    public function test_missing_or_invalid_slip_date_never_uses_creation_date(): void
    {
        foreach (['', 'Ngày: 31/02/2026'] as $date) {
            $r = (new DocumentReviewMetadataParser)->parse("Đồng Tháp, ngày 03 tháng 03 năm 2026\nPHIẾU TRÌNH\n{$date}\nNội dung: Cảnh báo rủi ro hoạt động.");
            $this->assertNull($r['issued_date']);
            $this->assertNotEmpty($r['warnings']);
        }
    }

    public function test_conflicting_explicit_dates_are_left_for_review(): void
    {
        $r = (new DocumentReviewMetadataParser)->parse("Ngày: 12/02/2026\nNgày: 13/02/2026", true);
        $this->assertNull($r['issued_date']);
        $this->assertNotEmpty($r['warnings']);
    }

    public function test_crop_retains_slip_context_and_blank_lines_inside_content(): void
    {
        $r = (new DocumentReviewMetadataParser)->parse("Số: 001/NHNo\n\nNgày: 12/02/2026\nNơi gửi: Ngân hàng\nNông nghiệp Việt Nam\nNội dung: Cảnh báo rủi ro\n\ntrong hoạt động ngân hàng.\nGIÁM ĐỐC\nHọ tên", true);
        $this->assertSame('Ngân hàng Nông nghiệp Việt Nam', $r['issuing_agency']);
        $this->assertSame('Cảnh báo rủi ro trong hoạt động ngân hàng.', $r['title']);
    }

    public function test_original_letter_can_use_written_date_and_vv_title(): void
    {
        $r = (new DocumentReviewMetadataParser)->parse("Đồng Tháp, ngày 3 tháng 3 năm 2026\nSố: 001/NHNo\nV/v phối hợp cung cấp thông tin\nkhách hàng theo quy định\nKính gửi: Các chi nhánh");
        $this->assertSame('2026-03-03', $r['issued_date']);
        $this->assertSame('phối hợp cung cấp thông tin khách hàng theo quy định', $r['title']);
    }

    public function test_dates_cited_in_body_are_not_document_dates(): void
    {
        $r = (new DocumentReviewMetadataParser)->parse("Số: 11580/NHNN-PHKQ     Hà Nội, ngày 30 tháng 12 năm 2025\nV/v ban hành mẫu tiền\nkhông đủ tiêu chuẩn lưu thông\n\nNGÂN HÀNG NG&PTNT VIỆT NAM\nCăn cứ Thông tư ngày 02/12/2013 của Thống đốc.");
        $this->assertSame('2025-12-30', $r['issued_date']);
        $this->assertSame('11580/NHNN-PHKQ', $r['document_code']);
        $this->assertSame('ban hành mẫu tiền không đủ tiêu chuẩn lưu thông', $r['title']);
    }

    public function test_bold_number_label_and_party_slip_signature(): void
    {
        $r = (new DocumentReviewMetadataParser)->parse("36: 79-NQ/TW\nNgày: 06/01/2026\nNơi gởi: Ban Chấp hành Trung ương\nNội dung: Nghị quyết của Bộ Chính trị\nvề phát triển kinh tế nhà nước\n.N THAM MƯU GIÚP VIỆC ĐẢNG ỦY\nTên người ký", true);
        $this->assertSame('79-NQ/TW', $r['document_code']);
        $this->assertSame('Nghị quyết của Bộ Chính trị về phát triển kinh tế nhà nước', $r['title']);
        $this->assertNotEmpty($r['warnings']);
    }

    public function test_subject_is_read_from_heading_not_a_quoted_document_in_body(): void
    {
        $text = "Số: 32   /NHNo-CD-UBKT\n;       KẾ HOẠCH        -\nKiểm tra, giám sát của Ủy ban Kiểm tra\nCông đoàn Agribank năm 2026\n\nCăn cứ Điều lệ Công đoàn Việt Nam\nThực hiện văn bản về việc hướng dẫn nội dung trọng tâm công tác kiểm tra";
        $r = (new DocumentReviewMetadataParser)->parse($text);
        $this->assertSame('32/NHNo-CD-UBKT', $r['document_code']);
        $this->assertSame('Kiểm tra, giám sát của Ủy ban Kiểm tra Công đoàn Agribank năm 2026', $r['title']);
        $r = (new DocumentReviewMetadataParser)->parse('Thực hiện văn bản số 123 về việc hướng dẫn nội dung khác');
        $this->assertNull($r['title']);
    }

    public function test_title_with_repeated_letter_digit_substitutions_is_flagged_as_ocr_noise(): void
    {
        $r = (new DocumentReviewMetadataParser)->parse('V/v Hufng d6n tri6n khai dich vu li6n ket vi diqn tu VNPAY v6i tdi khodn kh6ch hing trong he th6ng Agribank');

        $this->assertNotEmpty($r['title']);
        $this->assertContains('Trích yếu có dấu hiệu OCR nhầm chữ/số hoặc ký tự lạ; cần rà soát.', $r['warnings']);
    }

    public function test_body_label_noun_dung_uy_quyen_is_not_misread_as_title(): void
    {
        $r = (new DocumentReviewMetadataParser)->parse("GIẤY ỦY QUYỀN\nNội dung ủy quyền: Xử lý các công việc phát sinh của phòng Tổng hợp thuộc\n\nthẩm quyền Trưởng phòng.");

        $this->assertNull($r['title']);
    }

    public function test_truncated_and_mixed_uppercase_ocr_titles_are_flagged(): void
    {
        $truncated = (new DocumentReviewMetadataParser)->parse("V/v Xử lý công việc phát sinh thuộc");
        $noisy = (new DocumentReviewMetadataParser)->parse("V/v Thu hồi Giấy chứng nhận quyền sử dụng đất của hộ bà A GAR HÀNG NONG NGHIỆP & FINTĐỆNG BUA");

        $this->assertContains('Trích yếu có vẻ bị cắt ở cuối; cần rà soát.', $truncated['warnings']);
        $this->assertContains('Trích yếu có dấu hiệu OCR nhầm chữ/số hoặc ký tự lạ; cần rà soát.', $noisy['warnings']);
    }

    public function test_clean_heading_and_vv_titles_from_the_review_workbook_are_not_blocked(): void
    {
        $meeting = (new DocumentReviewMetadataParser)->parse("THÔNG BÁO\nKết luận họp giao ban tháng 6 năm 2022\n\nNgày 02/6/2022 tại Trụ sở chính");
        $invitation = (new DocumentReviewMetadataParser)->parse("GIẤY MỜI\nHội nghị giao ban quý III năm 2022\nKính gửi: Ban Giám đốc");
        $survey = (new DocumentReviewMetadataParser)->parse("THƯ CÔNG TÁC\nV/v Khảo sát tình hình sử dung sản pham dịch vụ giữa VietNam Post và Agribank.\n\nKính gửi: Các đơn vị");

        $this->assertSame('Kết luận họp giao ban tháng 6 năm 2022', $meeting['title']);
        $this->assertSame('Hội nghị giao ban quý III năm 2022', $invitation['title']);
        $this->assertSame('Khảo sát tình hình sử dung sản pham dịch vụ giữa VietNam Post và Agribank.', $survey['title']);
        $this->assertNotContains('Trích yếu có dấu hiệu OCR nhầm chữ/số hoặc ký tự lạ; cần rà soát.', $meeting['warnings']);
        $this->assertNotContains('Trích yếu đọc từ mục “Nội dung” đánh số trong thân văn bản; cần đối chiếu.', $invitation['warnings']);
        $this->assertNotContains('Trích yếu có dấu hiệu OCR nhầm chữ/số hoặc ký tự lạ; cần rà soát.', $survey['warnings']);
    }
}
