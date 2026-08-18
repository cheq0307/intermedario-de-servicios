<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MoreController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $following = $user->following()
            ->with(['vendor:id,user_id,display_name,specialty,status', 'community:id,name,municipality,state'])
            ->latest('user_follows.created_at')
            ->limit(12)
            ->get();

        return view('more.index', compact('user', 'following'));
    }
}