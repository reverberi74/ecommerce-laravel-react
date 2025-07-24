<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    protected $uploader;

    public function __construct(ImageUploadService $uploader)
    {
        $this->uploader = $uploader;
    }

    // GET /api/categories
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();

        // Aggiunge image_url dinamicamente
        $categories->transform(function ($category) {
            $category->image_url = $category->image ? asset('storage/' . $category->image) : null;
            return $category;
        });

        return response()->json($categories);
    }

    // POST /api/categories
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $result = $this->uploader->upload($request->file('image'), 'categories');
            $validated['image'] = $result['image_path'];
        }

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Categoria creata con successo',
            'category' => [
                ...$category->toArray(),
                'image_url' => $category->image ? asset('storage/' . $category->image) : null,
            ],
        ], 201);
    }

    // PUT /api/categories/{id}
    // PUT /api/categories/{id}
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        // Fix per file upload con PUT: controlla anche $_FILES
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
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($hasFile) {
            logger('Processing image upload...');

            // Elimina la vecchia immagine se esiste
            if ($category->image) {
                $this->uploader->delete($category->image);
            }

            // Usa $_FILES per PUT requests se necessario
            $file = $request->hasFile('image') ? $request->file('image') : new \Illuminate\Http\UploadedFile(
                $_FILES['image']['tmp_name'],
                $_FILES['image']['name'],
                $_FILES['image']['type'],
                $_FILES['image']['error'],
                true
            );

            // Carica la nuova immagine
            $result = $this->uploader->upload($file, 'categories');
            $validated['image'] = $result['image_path'];

            logger('New image uploaded: ' . $validated['image']);
        } else {
            logger('No image file received in request');
        }

        $category->update($validated);

        // Ricarica il modello per ottenere i dati aggiornati
        $category->refresh();

        return response()->json([
            'message' => 'Categoria aggiornata con successo',
            'category' => [
                ...$category->toArray(),
                'image_url' => $category->image ? asset('storage/' . $category->image) : null,
            ],
        ]);
    }

    // Metodo alternativo per update con file upload (usa POST)
    public function updateWithFile(Request $request, $id)
    {
        return $this->update($request, $id);
    }
    // DELETE /api/categories/{id}
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->image) {
            $this->uploader->delete($category->image);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoria eliminata con successo',
        ]);
    }

    // GET /api/categories/{id}
    public function show($id)
    {
        $category = Category::with('children')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoria non trovata'], 404);
        }

        $category->image_url = $category->image ? asset('storage/' . $category->image) : null;

        return response()->json($category);
    }
}
