<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category', 'images']);

        $search = $request->query('search');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $products = $query->orderBy('id', 'DESC')->paginate(10);

        return response()->json([
            'message' => 'Lấy danh sách sản phẩm thành công.',
            'data' => $products,
            'search_parameters' => $request->query(),
        ], 200);
    }

    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'brand_id' => 'required|exists:brands,id',
            'subcategory_id' => 'required|exists:categories,id',
            'barcode' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'manufacture_country' => 'nullable|string|max:255',
            'skin_type' => 'nullable|string|max:255',
            'volume' => 'nullable|string|max:255',
            'scent' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'brand_id' => $request->brand_id,
                'subcategory_id' => $request->subcategory_id,
                'barcode' => $request->barcode,
                'origin' => $request->origin,
                'manufacture_country' => $request->manufacture_country,
                'skin_type' => $request->skin_type,
                'volume' => $request->volume,
                'scent' => $request->scent,
            ]);

            DB::commit();

            $product->load(['brand', 'category']);

            return response()->json([
                'message' => 'Thêm sản phẩm thành công.',
                'data' => $product,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LỖI SERVER: Giao dịch bị Rollback. Chi tiết: ' . $e->getMessage());
            return response()->json([
                'error' => 'Lỗi server khi tạo sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);

        $product->load(['brand', 'category', 'images']);

        return response()->json([
            'message' => 'Lấy chi tiết sản phẩm thành công.',
            'data' => $product,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->ignore($product->id),
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'brand_id' => 'required|exists:brands,id',
            'subcategory_id' => 'required|exists:categories,id',
            'barcode' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'manufacture_country' => 'nullable|string|max:255',
            'skin_type' => 'nullable|string|max:255',
            'volume' => 'nullable|string|max:255',
            'scent' => 'nullable|string|max:255',
        ]);

        try {
            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'brand_id' => $request->brand_id,
                'subcategory_id' => $request->subcategory_id,
                'barcode' => $request->barcode,
                'origin' => $request->origin,
                'manufacture_country' => $request->manufacture_country,
                'skin_type' => $request->skin_type,
                'volume' => $request->volume,
                'scent' => $request->scent,
            ]);

            $product->load(['brand', 'category', 'images']);


            return response()->json([
                'message' => 'Cập nhật thông tin sản phẩm thành công.',
                'data' => $product,
            ], 200);
        } catch (\Exception $e) {
            Log::error('LỖI SERVER: Cập nhật thông tin bị lỗi. Chi tiết: ' . $e->getMessage());
            return response()->json([
                'error' => 'Lỗi server khi cập nhật thông tin sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadImages(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $oldImages = $product->images;
            foreach ($oldImages as $image) {
                if (Storage::disk('public')->exists($image->url)) {
                    Storage::disk('public')->delete($image->url);
                }
                $image->delete();
            }

            $uploadedFiles = Arr::wrap($request->file('images'));
            foreach ($uploadedFiles as $file) {
                $filePath = $file->store('product_images', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $filePath,
                ]);
            }

            DB::commit();

            $product->load(['images']);

            return response()->json([
                'message' => 'Cập nhật hình ảnh sản phẩm thành công.',
                'data' => $product->images,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LỖI SERVER: Giao dịch Update Image bị Rollback. Chi tiết: ' . $e->getMessage());
            return response()->json([
                'error' => 'Lỗi server khi cập nhật hình ảnh.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            $product->images->each(function ($image) {
                Storage::disk('public')->delete($image->url);
            });

            $product->images()->delete();

            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Xóa sản phẩm thành công.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Không thể xóa sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}