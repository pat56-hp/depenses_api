<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}


/* $product = ProductVariation::where(function ($q) use ($wq) {
    $q->where('barcode', 'LIKE',$wq['q'] . '%')
    ->orWhere('code', 'LIKE',$wq['q'] . '%')
    ->orWhere('name', 'LIKE', '%' . $wq['q'] . '%');
})
->whereHas('product', function ($query) use ($wq) {
    $query->where('name', 'LIKE', '%' . $wq['q'] . '%')
    ->whereHas('boxeproducts', function ($q) use($wq) {
        $q->select(DB::raw('SUM(qty)'))
            ->havingRaw('SUM(qty) > ?', [0]);
        if ($wq['w'] > 0) {
            $q->whereHas('boxe', function ($q) use($wq) {
                $q->where('entrepot_id', $wq['w']);
            });
        }
    });
    if ($wq['cat_id'] > 0) $query->where('productcategory_id', $wq['cat_id']);
    return $query;
})
->where('price', '>', 0)
->limit($limit)->get();

$output = array();

foreach ($product as $row) {
if (($row->product->stock_type > 0 and $row->product->totalQuantityInBoxes() > 0) or !$row->product->stock_type) {
    $output[] = array('name' => $row->product->name . ' ' . $row['name'], 'disrate' => numberFormat($row->disrate), 'price' => numberFormat($row->price), 'id' => $row->id, 'taxrate' => numberFormat($row->product['taxrate']), 'product_des' => $row->product['product_des'], 'unit' => $row->product['unit'], 'code' => $row->code, 'alert' => $row->product->totalQuantityInBoxes(), 'image' => $row->image, 'serial' => '');
}
} */