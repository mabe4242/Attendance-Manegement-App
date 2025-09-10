<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('admin.attendance_index');
    }
}
