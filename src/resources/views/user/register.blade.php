@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/user/register.css') }}">
@endsection

@section('content')
    <x-auth_header></x-auth_header>
    <div class="register-form__content">
        <form class="form" action="/register" method="post">
            @csrf
            <div class="register-form__heading">
                <h2>会員登録</h2>
            </div>
            <div class="form__group">
                <label class="form__label--item" for="name">ユーザー名</label>
                <input class="form__label--input" name="name" value="{{ old('name') }}" id="name"/>
                <div class="form__error">
                    @error('name')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form__group">
                <label class="form__label--item" for="email">メールアドレス</label>
                <input class="form__label--input" name="email" value="{{ old('email') }}" id="email"/>
                <div class="form__error">
                    @error('email')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form__group">
                <label class="form__label--item" for="password">パスワード</label>
                <input class="form__label--input" name="password" value="{{ old('password') }}" type="password" id="password"/>
                <div class="form__error">
                    @error('password')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form__group">
                <label class="form__label--item" for="password_confirmation">確認用パスワード</label>
                <input class="form__label--input" name="password_confirmation" value="{{ old('password_confirmation') }}" type="password" id="password_confirmation"/>
                <div class="form__error">
                    @error('password_confirmation')
                        {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form__button">
                <button class="form__button-submit" type="submit">登録する</button>
            </div>
            <div class="login__link">
                <a class="login__button-submit" href="/login">ログインはこちら</a>
            </div>
        </form>
    </div>
@endsection
