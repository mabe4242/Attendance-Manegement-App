<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('user.register');
    }

    public function create()
    {
        return view('user.verify_email');
    }
}
