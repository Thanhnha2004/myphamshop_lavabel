<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');

    }
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|',
            'fullname' => 'required|string|max:150|',
            'email' => 'required|email|unique:pending_registrations|unique:users',
            'password' => 'required|min:1|confirmed',
        ]);
        $token = Str::random(64);
        \DB::table('pending_registrations')->insert([
            'username' => $request->username,
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'token' => $token,
            'created_at' => now(),

        ]);
        $verifyUrl = url('/verify-email/' . $token);
        Mail::send('emails.verify', ['url' => $verifyUrl], function ($msg) use ($request) {
            $msg->to($request->email);
            $msg->subject('Xác minh tài khoản MyPhamShop');
        });

        return back()->with('message', 'Vui lòng kiểm tra email của bạn để xác minh tài khoản.');

    }
    public function showLoginForm()
    {
        return view('auth.login');
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:1',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user)
            return back()->withErrors(['email' => 'Email không tồn tại trong hệ thống']);
        if (is_null($user->email_verified_at))
            return back()->withErrors(['email' => 'Tài khoản chưa xác minh Gmail. Vui lòng kiểm tra email']);
        if (!Hash::check($request->password, $user->password))
            return back()->withErrors(['password' => 'Mật khẩu không chính xác'])->withInput();

        Auth::login($user);
        $request->session()->regenerate();
        return redirect('/')->with('success', 'Đăng nhập thành công!');



    }
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Đăng xuất thành công');
    }

}
