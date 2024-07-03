<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categorie\StoreRequest;
use App\Models\Categorie;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $size = 5;
            // Start building the query
            $query = Categorie::query();
            // Eager load parent and enfants categories
            $query->with('parent')->with('enfants');
            // Add dynamic filters based on query parameters
            if ($request['field'] == "libelle") {
                $query->where('libelle', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }

            if ($request['field'] == "slug") {
                $query->where('slug', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }

            if ($request['field'] == "statut") {
                $query->where('statut', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }

            if ($request['field'] == "parent") {
                $query->where('categories.libelle', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }
            $size = $request->size;

            if ($request->sorters) {
                $sortField = $request->sorters[0]['field'];
                $sortDirection = $request->sorters[0]['dir'];
                $query->orderBy($sortField, $sortDirection);
            }

            // Paginate the filtered data
            $perPage = $request->input('per_page', $size); // Default per page to 10 if not specified
            $categories = $query->paginate($perPage);

            // Add last_page, page, per_page, and total information
            $lastPage = $categories->lastPage();
            $currentPage = $categories->currentPage();
            $perPage = $categories->perPage();
            $total = $categories->total();

            // Return the paginated data with additional information
            return response()->json([
                'data' => $categories->items(),
                'last_page' => $lastPage,
                'page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in CategorieController.index',
            ]);
        }
    }

    public function index_slug($slug)
    {
        try {
            $categories = Categorie::where('slug', $slug)->get();
            $mappedCategories = $categories->map(function ($categorie) {
                $lib_active = "Désactivé";
                if ($categorie->statut == 1) {
                    $lib_active = "Activé";
                }
                return [
                    'id' => $categorie->id,
                    'libelle' => $categorie->libelle,
                    'position' => $categorie->position,
                    'icone' => $categorie->icone,
                    'code' => $categorie->code,
                    'statut'=>$categorie->statut,
                    'lib_active' => $lib_active,
                    'parent' => Categorie::where('id', $categorie->categorie_id)->value('libelle'),
                    
                ];
            });
            return response()->json([
                'success' => true,
                'data' => $mappedCategories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in CategorieController.index_slug',
            ]);

        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {
            
            $code = Categorie::formatChaine($request->libelle);
            
            
            $existingCategory = Categorie::where('code', $code)->first();
             while ($existingCategory) {
                $code = Categorie::formatChaine(Categorie::generateRandomAlphaCode());
                $existingCategory = Categorie::where('code', $code)->first();
            }
            
            $categorie = Categorie::create([
                'libelle' => $request->libelle,
                'code' => $code,
                'slug' => $request->slug,
                'icone' => $request->icone,
                'position' => $request->position,
                'statut' => $request->statut,
                'categorie_id' => $request->parent,
                'created_user' => Auth::id(),
            ]);

            return response()->json($categorie, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in CategorieController.store',
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {

            $categorie = Categorie::with('parent')->with('enfants')->find($id);
            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => "Catégorie introuvable",
                ]);
            }
            return response()->json([
                'success' => true,
                'categorie' => $categorie,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in CategorieController.show',
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {

         
            $categorie = Categorie::find($id);
            if (!$categorie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categorie avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }

            $categorie->libelle = $request->libelle ?? $categorie->libelle;
            $categorie->slug = $request->slug ?? $categorie->slug;
            $categorie->icone = $request->icone ?? $categorie->icone;
            $categorie->code = $request->code ?? $categorie->code;
            $categorie->position = $request->position ?? $categorie->position;
            $categorie->statut = $request->statut ?? $categorie->statut;
            // $categorie->categorie_id = $request->categorie_id ?? $categorie->categorie_id;
            $categorie->categorie_id = $request->parent;
            $categorie->updated_user = Auth::id();

            $categorie->save();
            return response()->json([
                'success' => true,
                'message' => 'Categorie avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in CategorieController.update',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {

            $record = Categorie::where('id', $id)->get();
            $current = Carbon::now();
            if (count($record) > 0) {
                Categorie::where('id', $id)->update([
                    'deleted_at' => $current,
                    'deleted_user' => Auth::id(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Categorie avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Cette Categorie n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in CategorieController.destroy',
            ]);
        }
    }

    public function delete(int $id)
    {
        try {
            $record = Categorie::where('id', $id)->get();
            if (count($record)) {
                DB::table('categories')->where('id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Catégorie avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "La catégorie n'existe pas",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in CategorieController.delete',
            ]);
        }
    }
}
