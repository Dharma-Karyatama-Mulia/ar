<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pencarian item untuk dropdown (Tom Select remote, lihat partials/item-lookup) — master items
 * berisi ~31rb baris hasil import Accurate, terlalu besar untuk dirender semua sebagai <option>.
 */
class LookupController extends Controller
{
    public function items(Request $request): JsonResponse
    {
        $terms = array_filter(preg_split('/\s+/', trim((string) $request->query('q'))));

        $items = Item::where('is_active', true)
            ->when($request->boolean('sold'), fn ($q) => $q->where('is_sold', true))
            ->where(function ($q) use ($terms) {
                // Tiap kata harus muncul di kode atau nama. item_no ber-collation biner
                // (case-sensitive), jadi dipaksa _ci khusus untuk pencarian.
                foreach ($terms as $term) {
                    $like = '%'.addcslashes($term, '%_\\').'%';
                    $q->where(fn ($w) => $w->whereRaw('item_no COLLATE utf8mb4_unicode_ci LIKE ?', [$like])->orWhere('description', 'like', $like));
                }
            })
            ->orderBy('item_no')
            ->limit(30)
            ->get(['id', 'item_no', 'description', 'unit', 'sales_price']);

        return response()->json($items->map(fn (Item $item) => [
            'id' => $item->id,
            'text' => "{$item->item_no} - {$item->description}",
            'unit' => $item->unit,
            'price' => (float) $item->sales_price,
        ]));
    }
}
