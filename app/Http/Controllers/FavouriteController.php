<?php

namespace App\Http\Controllers;

use App\Models\Product; // Cần dùng để tìm sản phẩm
use App\Models\Favourite; // Cần dùng nếu muốn thao tác trực tiếp với bảng favourites
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavouriteController extends Controller
{

    // --- Phương thức index() (Hiển thị danh sách yêu thích) ---
    
    /**
     * Lấy danh sách sản phẩm yêu thích của người dùng hiện tại (GET /favourites).
     */
    public function index()
    {
        $user = Auth::user();
        
        // Sử dụng quan hệ favourites() đã định nghĩa trong User Model
        // Eager load các quan hệ cần thiết của Product nếu có
        $favourites = $user->favourites()->with(['brand', 'logo'])->get();
        
        return response()->json([
            'message' => 'Lấy danh sách sản phẩm yêu thích thành công.',
            'data' => $favourites,
        ], 200);
    }

    // --- Phương thức store() (Thêm vào yêu thích) ---

    /**
     * Thêm một sản phẩm vào danh sách yêu thích (POST /favourites/add/{product}).
     */
    public function store(Product $product)
    {
        $user = Auth::user();

        // 1. Kiểm tra Validation/Logic
        // Không cần validation phức tạp vì đầu vào là đối tượng Product từ route binding
        
        DB::beginTransaction();

        try {
            // Sử dụng quan hệ BelongsToMany tiện lợi (favourites) để thêm bản ghi.
            // Phương thức attach() sẽ chèn một dòng vào bảng `favourites`.
            // Check nếu đã tồn tại trước khi attach (để tránh QueryException):
            if ($user->favourites()->where('product_id', $product->id)->exists()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Sản phẩm này đã có trong danh sách yêu thích.',
                    'data' => $product,
                ], 409); // 409 Conflict
            }
            
            $user->favourites()->attach($product->id);

            DB::commit();

            // Tải lại dữ liệu Product trước khi trả về (nếu cần, nhưng ở đây không cần)
            
            return response()->json([
                'message' => 'Đã thêm sản phẩm vào danh sách yêu thích thành công.',
                'data' => $product->load(['brand', 'logo']) // Tải lại dữ liệu để trả về đầy đủ
            ], 201); // 201 Created

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi thêm sản phẩm yêu thích.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // --- Phương thức destroy() (Xóa khỏi yêu thích) ---
    
    /**
     * Xóa một sản phẩm khỏi danh sách yêu thích (DELETE /favourites/remove/{product}).
     * @param \App\Models\Product $product
     */
    public function destroy(Product $product)
    {
        $user = Auth::user();

        DB::beginTransaction();

        try {
            // Sử dụng detach() để xóa mối quan hệ từ bảng `favourites`.
            // detach() trả về số lượng bản ghi đã xóa (1 hoặc 0).
            $deletedCount = $user->favourites()->detach($product->id);

            if ($deletedCount === 0) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Sản phẩm không có trong danh sách yêu thích của bạn.',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'message' => 'Đã xóa sản phẩm khỏi danh sách yêu thích thành công.',
                'data' => $product
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi xóa sản phẩm yêu thích.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Phương thức edit() hoặc show() không cần thiết cho API favourites cơ bản này.
}