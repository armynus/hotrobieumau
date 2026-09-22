<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SupportForm;
use App\Services\FormWorkspaceService;
use App\Services\SupportFormBundleDocumentService;
use App\Services\SupportFormDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormBundleController extends Controller
{
    public function __construct(private FormWorkspaceService $workspace) {}

    private function forms(Request $request)
    {
        $data = $request->validate([
            'form_ids' => 'required|array|min:2|max:'.FormWorkspaceService::MAX_FORMS,
            'form_ids.*' => 'required|integer|min:1|distinct',
        ], ['form_ids.*' => 'Hãy chọn từ 2 đến 10 biểu mẫu khác nhau.']);

        return $this->workspace->selected($data['form_ids']);
    }

    public function show(Request $request, UserSupportFormController $controller)
    {
        $forms = $this->forms($request);
        $signature = $this->workspace->signature($forms);
        $form = new SupportForm([
            'name' => 'Bộ hồ sơ giao dịch',
            'fields' => $forms->flatMap(fn ($item) => FormWorkspaceService::fieldCodes($item->fields))->unique()->values()->all(),
        ]);
        $form->id = 0;

        return $controller->workspace($form, null, [
            'bundleForms' => $forms, 'bundleSignature' => $signature,
        ]);
    }

    public function download(
        Request $request,
        SupportFormDocumentService $renderer,
        SupportFormBundleDocumentService $bundleRenderer
    ) {
        $forms = $this->forms($request);
        $data = $request->validate([
            'signature' => 'required|string|size:64',
            'payload' => 'required|array|max:500',
            'payload.*' => [function ($attribute, $value, $fail) {
                $values = is_array($value) ? $value : [$value];
                if (count($values) > 100) {
                    $fail('Quá nhiều lựa chọn.');
                }
                foreach ($values as $item) {
                    if ((! is_scalar($item) && $item !== null) || mb_strlen((string) $item) > 10000) {
                        $fail('Giá trị biểu mẫu không hợp lệ hoặc quá dài.');
                    }
                }
            }],
        ]);
        if (! hash_equals($this->workspace->signature($forms), $data['signature'])) {
            throw ValidationException::withMessages(['signature' => 'Biểu mẫu đã được cập nhật. Nội dung nháp vẫn được giữ; hãy tải lại trang để dùng phiên bản mới.']);
        }

        $files = [];
        $document = null;
        try {
            foreach ($forms as $form) {
                $file = $renderer->generate($this->workspace->templatePath($form), $this->workspace->payloadFor($form, $data['payload']));
                $files[] = $file;
            }
            $document = $bundleRenderer->merge($files);
            // Only a complete bundle counts as an export. No customer/account writes.
            DB::connection('mysql')->transaction(function () use ($forms) {
                foreach ($forms as $form) {
                    $form->timestamps = false;
                    $form->increment('usage_count');
                    \App\Services\FormUsageService::log($form->id);
                }
            });

            return response()->download($document, 'bo-ho-so-'.now()->format('Ymd-His').'.docx', [
                'Cache-Control' => 'private, no-store',
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $error) {
            if ($document && is_file($document)) {
                unlink($document);
            }
            report($error);

            return response()->json(['message' => 'Chưa xuất được bộ hồ sơ. Dữ liệu đang nhập vẫn được giữ; hãy thử lại hoặc liên hệ quản trị viên.'], 500);
        } finally {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
