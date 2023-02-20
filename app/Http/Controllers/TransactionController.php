<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request, User $user)
    {
        $request->validate([
            'sort' => 'in:count,-count,created_at,-created_at'
        ]);

        $query = $user->transactions();

        if ($request->has('sort')) {

            $sort = $request->get('sort');
            $firstSymbol = mb_substr($sort, 0, 1);

            $direction = $firstSymbol === '-' ? 'DESC' : 'ASC';
            $column = $firstSymbol === '-' ? mb_substr($sort, 1) : $sort;

            $query->orderBy($column, $direction);
        }

        return $query->paginate();
    }
}
