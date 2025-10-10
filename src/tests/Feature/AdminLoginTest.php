<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function email_is_required_for_login()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        $formData = [
            'email' => '',
            'password' => 'password123',
        ];
        $response = $this->post('/admin/login', $formData);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function password_is_required_for_login()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        $formData = [
            'email' => 'test@example.com',
            'password' => '',
        ];
        $response = $this->post('/admin/login', $formData);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function invalid_login_credentials_show_error_message()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        $formData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];
        $response = $this->from('/admin/login')->post('/admin/login', $formData);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * @test
     * 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function admin_can_login_with_correct_credentials()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create([
            'password' => bcrypt($password = '11111111'),
        ]);
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        $formData = [
            'email' => $admin->email,
            'password' => $password,
        ];
        $response = $this->post('/admin/login', $formData);

        $this->assertAuthenticatedAs($admin, 'admin');
        $response->assertRedirect('/admin/attendance/list');
    }
}
