<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /admin/users
     * Lấy danh sách Người dùng (có phân trang và lọc).
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Lọc theo vai trò (role)
        $role = $request->query('role');
        if ($role !== null) { 
            // Đảm bảo role là giá trị hợp lệ (1 hoặc 2)
            if (in_array($role, [User::ROLE_USER, User::ROLE_ADMIN])) {
                $query->where('role', $role);
            }
        }

        // Lọc theo từ khóa (username, email, fullname)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('fullname', 'LIKE', "%{$search}%");
            });
        }
        
        // Sắp xếp theo ID mới nhất và phân trang
        $users = $query->latest('id')->paginate(15); 

        return response()->json([
            'message' => 'Lấy danh sách người dùng thành công.',
            'data' => $users,
        ], 200);
    }

    /**
     * GET /admin/users/{id}
     * Xem chi tiết Người dùng.
     */
    public function show($id)
    {
        // Tải thông tin người dùng và các đơn hàng liên quan
        $user = User::with('orders')->findOrFail(id: $id);

        return response()->json([
            'message' => 'Lấy chi tiết người dùng thành công.',
            'data' => $user,
        ], 200);
    }

    /**
     * PUT/PATCH /admin/users/{id}
     * Cập nhật thông tin cơ bản và vai trò (role) của Người dùng.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validRoles = [User::ROLE_USER, User::ROLE_ADMIN];
        
        $request->validate([
            // Email phải duy nhất
            'email' => [
                'required', 
                'email', 
                'max:100', 
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'fullname' => 'nullable|string|max:145',
            'role' => ['required', 'integer', Rule::in($validRoles)], // Cập nhật vai trò
        ]);

        DB::beginTransaction();

        try {
            // 2. Cập nhật thông tin
            $user->update([
                'email' => $request->email,
                'fullname' => $request->fullname,
                'role' => $request->role,
            ]);

            DB::commit();

            // Tải lại dữ liệu quan hệ sau khi cập nhật
            $user->load('orders');

            return response()->json([
                'message' => 'Cập nhật thông tin người dùng thành công.',
                'data' => $user,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi cập nhật người dùng.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /admin/users/{id}
     * Xóa Người dùng.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        DB::beginTransaction();

        try {
            // Kiểm tra xem người dùng này có đơn hàng không
            if ($user->orders()->exists()) {
                return response()->json([
                    'error' => 'Không thể xóa người dùng này vì họ đã có đơn hàng liên kết. Vui lòng xử lý đơn hàng trước.'
                ], 409); // 409 Conflict
            }

            // Xóa tất cả Sản phẩm Yêu thích của người dùng này
            $user->favourites()->detach();
            
            // Xóa Người dùng
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Xóa người dùng thành công.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi xóa người dùng.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}