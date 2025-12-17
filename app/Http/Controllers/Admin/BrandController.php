<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BrandImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($search = $request->query('search')) {
            $query->where('brand_name', 'LIKE', "%{$search}%");
        }

        $brands = $query->with('logo')->orderBy('id', 'DESC')->paginate(15);

        return response()->json([
            'message' => 'Lấy danh sách thương hiệu thành công.',
            'data' => $brands,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $brand = Brand::create([
                'brand_name' => $request->brand_name,
            ]);

            // Upload ảnh lên Cloudinary
            if ($request->hasFile('logo_image')) {
                $uploadedFile = Cloudinary::upload(
                    $request->file('logo_image')->getRealPath(),
                    [
                        'folder' => 'brands',
                        'resource_type' => 'image'
                    ]
                );

                $url = $uploadedFile->getSecurePath();
                $publicId = $uploadedFile->getPublicId();

                BrandImage::create([
                    'brand_id' => $brand->id,
                    'url' => $url,
                    'public_id' => $publicId, // Lưu public_id để xóa sau này
                ]);
            }

            DB::commit();

            $brand->load('logo');

            return response()->json([
                'message' => 'Thêm thương hiệu thành công.',
                'data' => $brand,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi thêm thương hiệu.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $brand = Brand::with('logo')->find($id);

        if (!$brand) {
            return response()->json(['error' => 'Không tìm thấy thương hiệu.'], 404);
        }

        return response()->json([
            'message' => 'Lấy chi tiết thương hiệu thành công.',
            'data' => $brand,
        ]);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $brand->id . ',id',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $brand->update([
                'brand_name' => $request->brand_name,
            ]);

            // Nếu có ảnh mới
            if ($request->hasFile('logo_image')) {
                
                // Xóa ảnh cũ trên Cloudinary
                if ($oldLogo = $brand->logo) {
                    if ($oldLogo->public_id) {
                        Cloudinary::destroy($oldLogo->public_id);
                    }
                    $oldLogo->delete();
                }

                // Upload ảnh mới
                $uploadedFile = Cloudinary::upload(
                    $request->file('logo_image')->getRealPath(),
                    [
                        'folder' => 'brands',
                        'resource_type' => 'image'
                    ]
                );

                $url = $uploadedFile->getSecurePath();
                $publicId = $uploadedFile->getPublicId();

                BrandImage::create([
                    'brand_id' => $brand->id,
                    'url' => $url,
                    'public_id' => $publicId,
                ]);
            }

            DB::commit();

            $brand->load('logo');

            return response()->json([
                'message' => 'Cập nhật thương hiệu thành công.',
                'data' => $brand,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi cập nhật thương hiệu.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);

        if ($brand->products()->exists()) {
            return response()->json([
                'error' => 'Không thể xóa vì thương hiệu này có sản phẩm đang sử dụng.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Xóa ảnh trên Cloudinary
            if ($brand->logo && $brand->logo->public_id) {
                Cloudinary::destroy($brand->logo->public_id);
                $brand->logo()->delete();
            }

            $brand->delete();
            DB::commit();

            return response()->json(['message' => 'Xóa thương hiệu thành công.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi xóa thương hiệu.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}