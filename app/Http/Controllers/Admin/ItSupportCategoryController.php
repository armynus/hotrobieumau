<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItSupportCategory;
use App\Models\ItSupportTemplate;
use Illuminate\Http\Request;

class ItSupportCategoryController extends Controller
{
    public function index()
    {
        $categories = ItSupportCategory::withCount('templates')
            ->orderBy('display_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.it_support.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:it_support_categories,name',
            'description'   => 'nullable|string|max:1000',
            'icon'          => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'status'        => 'nullable|in:active,inactive',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique'   => 'Tên danh mục đã tồn tại trong hệ thống.',
            'name.max'      => 'Tên danh mục không được vượt quá 255 ký tự.',
        ]);

        $category = ItSupportCategory::create([
            'name'          => trim($validated['name']),
            'description'   => isset($validated['description']) ? trim($validated['description']) : null,
            'icon'          => !empty($validated['icon']) ? trim($validated['icon']) : 'fas fa-folder',
            'display_order' => $validated['display_order'] ?? 10,
            'status'        => $validated['status'] ?? 'active',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => true,
                'message'  => 'Đã thêm danh mục thành công!',
                'category' => $category,
            ]);
        }

        return redirect()->route('admin.it_support.categories.index')->with('success', 'Đã thêm danh mục thành công!');
    }

    public function edit($id)
    {
        $category = ItSupportCategory::withCount('templates')->findOrFail($id);

        return response()->json([
            'status'   => true,
            'category' => $category,
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = ItSupportCategory::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:255|unique:it_support_categories,name,' . $id,
            'description'   => 'nullable|string|max:1000',
            'icon'          => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'status'        => 'nullable|in:active,inactive',
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.unique'   => 'Tên danh mục đã tồn tại trong hệ thống.',
        ]);

        $category->update([
            'name'          => trim($validated['name']),
            'description'   => isset($validated['description']) ? trim($validated['description']) : null,
            'icon'          => !empty($validated['icon']) ? trim($validated['icon']) : 'fas fa-folder',
            'display_order' => $validated['display_order'] ?? $category->display_order,
            'status'        => $validated['status'] ?? $category->status,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => true,
                'message'  => 'Đã cập nhật danh mục thành công!',
                'category' => $category,
            ]);
        }

        return redirect()->route('admin.it_support.categories.index')->with('success', 'Đã cập nhật danh mục thành công!');
    }

    public function destroy(Request $request, $id)
    {
        try {
            $category = ItSupportCategory::findOrFail($id);

            // Gỡ bỏ liên kết từ các mẫu trước khi xóa để an toàn
            ItSupportTemplate::where('category_id', $id)->update(['category_id' => null]);

            $category->delete();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'Đã xóa danh mục thành công!',
                ]);
            }

            return redirect()->route('admin.it_support.categories.index')->with('success', 'Đã xóa danh mục thành công!');
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Không thể xóa danh mục: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Lỗi khi xóa danh mục: ' . $e->getMessage());
        }
    }
}
