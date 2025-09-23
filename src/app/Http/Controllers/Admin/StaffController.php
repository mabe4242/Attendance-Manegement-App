<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('admin.staff_index', compact('users'));
    }
}
