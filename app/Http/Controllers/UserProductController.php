<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Repository chịu trách nhiệm xử lý các truy vấn phức tạp hoặc chung chung liên quan đến Sản phẩm và Thương hiệu.
 */
class UserProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category', 'images']);

        // Lấy tham số từ request
        $search = $request->query('search');
        $brandId = $request->query('brand_id');
        $categoryId = $request->query('category_id');

        // Tìm kiếm theo tên sản phẩm
        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // Lọc theo thương hiệu
        if ($brandId) {
            $query->where('brand_id', $brandId);
        }

        // Lọc theo danh mục
        if ($categoryId) {
            $query->where('subcategory_id', $categoryId);
        }

        // Sắp xếp và phân trang
        $products = $query->orderBy('id', 'DESC')->paginate(10);

        return response()->json([
            'message' => 'Lấy danh sách sản phẩm thành công.',
            'data' => $products,
            'search_parameters' => $request->query(),
        ], 200);
    }


    /**
     * Lấy các sản phẩm hot (Sản phẩm mới nhất).
     *
     * @param int $limit
     * @return Collection
     */
    public function getHotProducts(int $limit = 5): Collection
    {
        // Eloquent tự động xử lý việc lấy ảnh đại diện thông qua quan hệ 'images'
        // và chỉ lấy ảnh đầu tiên (tại thời điểm truy vấn).
        return Product::with(['brand', 'images'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            // Sau khi lấy dữ liệu, chúng ta xử lý để format lại cấu trúc trả về
            ->map(function ($product) {
                return $this->formatProductData($product);
            });
    }

    /**
     * Lấy danh sách Thương hiệu.
     *
     * @param int $limit
     * @return Collection
     */
    public function getBrands(int $limit = 6): Collection
    {
        // Lấy thương hiệu kèm theo ảnh đại diện
        // Giả định Model Brand có quan hệ 'logo' (BrandImage).
        return Brand::with('logo')
            ->orderBy('brand_name')
            ->limit($limit)
            ->get()
            ->map(function ($brand) {
                // Tương tự, lấy url của ảnh đầu tiên nếu có
                $imageUrl = $brand->logo->url ?? '../default_brand.webp';

                return [
                    'id' => $brand->id,
                    'brand_name' => $brand->brand_name,
                    'image' => $imageUrl,
                ];
            });
    }

    /**
     * Lấy Sản phẩm theo danh mục (Trang điểm).
     * ID danh mục Trang điểm (46) và Son môi (55)
     *
     * @param int $limit
     * @return Collection
     */
    public function getMakeupProducts(int $limit = 8): Collection
    {
        $makeup_category_id = 46;

        return Product::with(['brand', 'images'])
            ->where('subcategory_id', $makeup_category_id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return $this->formatProductData($product);
            });
    }

    /**
     * Lấy Sản phẩm Son môi.
     *
     * @param int $limit
     * @return Collection
     */
    public function getLipstickProducts(int $limit = 8): Collection
    {
        $lipstick_category_id = 55;

        return Product::with(['brand', 'images'])
            ->where('subcategory_id', $lipstick_category_id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return $this->formatProductData($product);
            });
    }

    /**
     * Lấy Sản phẩm Chăm sóc da (Mặt và Cơ thể).
     *
     * @param int $limit
     * @return Collection
     */
    public function getSkincareProducts(int $limit = 8): Collection
    {
        $skincare_category_ids = [47, 48];

        return Product::with(['brand', 'images'])
            ->whereIn('subcategory_id', $skincare_category_ids)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return $this->formatProductData($product);
            });
    }

    /**
     * Lấy chi tiết và định dạng dữ liệu của một sản phẩm theo ID,
     * bao gồm tất cả các mối quan hệ cần thiết (brand, tất cả images).
     *
     * @param int $id
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Nếu không tìm thấy sản phẩm.
     */
    public function getProductDetail($id): array
    {
        // Sử dụng findOrFail để tự động ném ra ngoại lệ 404 nếu không tìm thấy
        $product = Product::with(['brand', 'images'])
            ->findOrFail($id);

        // Lấy tất cả URLs ảnh
        // pluck('url') lấy mảng chỉ chứa các trường 'url' từ collection images
        $allImages = $product->images->pluck('url')->toArray();
        // Lấy ảnh đại diện (ảnh đầu tiên)
        $mainImage = $allImages[0] ?? '../default.webp';

        // Định dạng giá tiền
        $formattedPrice = number_format($product->price, 0, ',', '.') . 'đ';

        // Định dạng dữ liệu sản phẩm chi tiết
        return [
            'id' => $product->id,
            'name' => $product->name,
            'brand' => $product->brand->brand_name ?? 'Chưa xác định',
            'description' => $product->description ?? 'Đang cập nhật...', // Mô tả chi tiết
            'price' => $product->price,
            'formatted_price' => $formattedPrice,
            'main_image' => $mainImage,
            'all_images' => $allImages, // Tất cả ảnh cho gallery
            'stock_quantity' => $product->stock ?? 0,
        ];
    }

    /**
     * Hàm helper để format dữ liệu sản phẩm tương tự như hàm PHP thuần.
     *
     * @param Product $product
     * @return array
     */
    protected function formatProductData(Product $product): array
    {
        $formattedPrice = number_format($product->price, 0, ',', '.') . 'đ';
        $imageUrl = $product->images->first()->url ?? '../default.webp';

        return [
            'id' => $product->id,
            'image' => $imageUrl,
            'name' => $product->name,
            'brand' => $product->brand->brand_name ?? 'Chưa xác định',
            'price' => $product->price,
            'formatted_price' => $formattedPrice,
        ];
    }
}