<?php

namespace App\Http\Controllers;

class ProductController extends Controller
{
    public function index()
    {
        // This will trigger N+1 query warning
        $products = Product::all();
        
        foreach ($products as $product) {
            echo $product->category->name;  // N+1 here
            echo $product->reviews->count(); // Another N+1
        }
        
        return view('products.index', compact('products'));
    }
    
    public function show($id)
    {
        // Missing validation
        $product = Product::find($id);
        
        return view('products.show', compact('product'));
    }
}
