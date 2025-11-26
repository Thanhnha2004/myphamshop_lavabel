<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * GET /admin/orders
     * Lấy danh sách Đơn hàng (có phân trang và lọc).
     */
    public function index(Request $request)
    {
        $query = Order::query();

        // Lọc theo trạng thái
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Lọc theo từ khóa
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_phone', 'LIKE', "%{$search}%");
            });
        }
        
        // Sắp xếp theo ngày tạo mới nhất
        $orders = $query->with('user')->latest()->paginate(15); 

        return response()->json([
            'message' => 'Lấy danh sách đơn hàng thành công.',
            'data' => $orders,
        ], 200);
    }

    /**
     * GET /admin/orders/{id}
     * Xem chi tiết Đơn hàng
     */
    public function show($id)
    {
        // Tải thông tin người dùng, chi tiết đơn hàng và sản phẩm liên quan
        $order = Order::with(['user', 'items.product'])->findOrFail($id);

        return response()->json([
            'message' => 'Lấy chi tiết đơn hàng thành công.',
            'data' => $order,
        ], 200);
    }

    /**
     * PUT/PATCH /admin/orders/{id}
     * Cập nhật trạng thái Đơn hàng.
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        // Định nghĩa mảng trạng thái hợp lệ để Validation
        $validStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];
        
        // Validation: Đảm bảo 'status' là số nguyên hợp lệ và nằm trong danh sách hằng số
        $request->validate([
            'status' => ['required', 'integer', Rule::in($validStatuses)],
        ]);

        DB::beginTransaction();

        try {
            // Cập nhật trạng thái
            $order->update([
                'status' => $request->status,
            ]);

            DB::commit();

            // Tải lại dữ liệu quan hệ sau khi cập nhật
            $order->load(['user', 'items.product']);

            return response()->json([
                'message' => 'Cập nhật trạng thái đơn hàng thành công.',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi cập nhật trạng thái đơn hàng.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /admin/orders/{id}
     * Xóa Đơn hàng.
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        DB::beginTransaction();

        try {
            // Xóa tất cả Order Details liên quan
            $order->items()->delete();

            // Xóa Đơn hàng
            $order->delete();

            DB::commit();

            return response()->json([
                'message' => 'Xóa đơn hàng thành công.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi xóa đơn hàng.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}