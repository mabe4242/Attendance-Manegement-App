<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function name_is_required_on_registration()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function email_is_required_on_registration()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function password_must_be_at_least_8_characters()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function password_and_password_confirmation_must_match()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '11111111',
            'password_confirmation' => '22222222',
        ]);

        $response->assertSessionHasErrors(['password_confirmation']);
        $errors = session('errors')->get('password_confirmation');
        $this->assertTrue(
            collect($errors)->contains('パスワードと一致しません'),
            '期待するエラーメッセージが含まれていません。'
        );
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function password_is_required_on_registration()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function registration_succeeds()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $formData = [
            'name' => 'テストユーザー',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->post('/register', $formData);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'testuser@example.com',
        ]);
    }
}
