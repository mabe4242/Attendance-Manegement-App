<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 会員登録後、認証メールが送信される
     */
    public function verification_email_registration()
    {
        Notification::fake();

        // 会員登録をする
        $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // ユーザーが作成されているか確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // 認証メールが送信されているか確認
        Notification::assertSentTo(
            [$user], VerifyEmail::class
        );
    }

    /**
     * @test
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function mailhog_is_accessible_for_verify_email_page()
    {
        Notification::fake();

        // 会員登録
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // ユーザーが作成されているか確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // メール認証誘導画面にアクセス
        $response = $this->get('/email/verify');
        $response->assertStatus(200);
        $response->assertSee('http://localhost:8025');

        // Mailhog にアクセス
        $url = 'http://mailhog:8025';

        //get_headers() でHTTP レスポンスの最初の行を取得
        $headers = @get_headers($url);
        $httpCode = 0;
        if ($headers && isset($headers[0])) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches)) {
                $httpCode = (int)$matches[1];
            }
        }

        $this->assertEquals(
            200,
            $httpCode,
            "Mailhog がブラウザで開けません ($url)"
        );
    }

    /**
     * @test
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function verify_email_and_redirect_to_attendance()
    {
        Notification::fake();

        // 会員登録
        $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // ユーザーが作成されていることを確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // Notification::fake() でメール送信を捕捉
        Notification::assertSentTo($user, VerifyEmail::class, function ($notification, $channels) use ($user) {

            // メール本文から認証リンクを取得
            $verificationUrl = $notification->toMail($user)->actionUrl;

            // メール認証を実行する
            $this->actingAs($user);
            $verifyResponse = $this->get($verificationUrl);

            // 認証後、勤怠登録画面にリダイレクトされることを確認
            $verifyResponse->assertRedirect('/attendance');
            $this->assertNotNull($user->fresh()->email_verified_at);

            return true;
        });
    }
}
