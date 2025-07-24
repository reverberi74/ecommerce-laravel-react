<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected $uploader;

    public function __construct(ImageUploadService $uploader)
    {
        $this->uploader = $uploader;
    }

    // GET /api/products
    public function index()
    {
        $products = Product::with('category')->get();

        $products->transform(function ($product) {
            $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
            return $product;
        });

        return response()->json($products);
    }

    // GET /api/products/{id}
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Prodotto non trovato'], 404);
        }

        $product->image_url = $product->image ? asset('storage/' . $product->image) : null;

        return response()->json($product);
    }

    // POST /api/products
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $result = $this->uploader->upload($request->file('image'), 'products');
            $validated['image'] = $result['image_path'];
        }

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Prodotto creato con successo',
            'product' => [
                ...$product->toArray(),
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
            ],
        ], 201);
    }

    // PUT /api/products/{id}
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $hasFile = $request->hasFile('image') || (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK);
        // Debug per vedere cosa arriva nella request
        /*logger('Update request files:', $request->allFiles());
        logger('Has image file: ' . ($request->hasFile('image') ? 'true' : 'false'));
        logger('PHP $_FILES:', $_FILES);
        logger('Has file (extended check): ' . ($hasFile ? 'true' : 'false'));
        logger('All input data:', $request->all());
        logger('Content-Type: ' . $request->header('Content-Type'));
        logger('Request method: ' . $request->method());*/

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($hasFile) {
            if ($product->image) {
                $this->uploader->delete($product->image);
            }

            $file = $request->hasFile('image') ? $request->file('image') : new \Illuminate\Http\UploadedFile(
                $_FILES['image']['tmp_name'],
                $_FILES['image']['name'],
                $_FILES['image']['type'],
                $_FILES['image']['error'],
                true
            );

            $result = $this->uploader->upload($file, 'products');
            $validated['image'] = $result['image_path'];
        }

        $product->update($validated);
        $product->refresh();

        return response()->json([
            'message' => 'Prodotto aggiornato con successo',
            'product' => [
                ...$product->toArray(),
                'image_url' => $product->image ? asset('storage/' . $product->image) : null,
            ],
        ]);
    }

    // POST /api/products/{id}/update
    public function updateWithFile(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    // POST /api/products/{id}/image
    public function updateImage(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        if ($product->image) {
            $this->uploader->delete($product->image);
        }

        $result = $this->uploader->upload($request->file('image'), 'products');

        $product->image = $result['image_path'];
        $product->save();

        return response()->json([
            'message' => 'Immagine del prodotto aggiornata',
            'image_url' => asset('storage/' . $product->image),
        ]);
    }

    // DELETE /api/products/{id}
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            $this->uploader->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'message' => 'Prodotto eliminato con successo',
        ]);
    }
}
