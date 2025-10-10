<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TableHeaders;
use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        $headers = TableHeaders::staff();

        return view('admin.staff_index', compact('users', 'headers'));
    }
}
