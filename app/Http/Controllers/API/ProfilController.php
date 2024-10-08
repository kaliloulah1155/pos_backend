<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\StoreRequest;
use App\Models\Profil;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ProfilController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $size = 5;
            // Start building the query
            $query = Profil::query();
            // Eager load parent and enfants categories
            // Add dynamic filters based on query parameters
            if ($request['field'] == "libelle") {
                $query->where('libelle', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }
            if ($request['field'] == "statut") {
                $query->where('statut', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }

            $size = $request->size;

            if ($request->sorters) {
                $sortField = $request->sorters[0]['field'];
                $sortDirection = $request->sorters[0]['dir'];
                $query->orderBy($sortField, $sortDirection);
            }

            // Paginate the filtered data
            $perPage = $request->input('per_page', $size); // Default per page to 10 if not specified
            $profils = $query->paginate($perPage);

            // Add last_page, page, per_page, and total information
            $lastPage = $profils->lastPage();
            $currentPage = $profils->currentPage();
            $perPage = $profils->perPage();
            $total = $profils->total();

            // Return the paginated data with additional information
            return response()->json([
                'data' => $profils->items(),
                'last_page' => $lastPage,
                'page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.index',
            ]);
        }

    }

    /**
     * Display a listing of the profils by employee.
     */
    public function index_e(Request $request)
    {
        try {
            $profils = DB::table('profils')
                ->whereNotIn('libelle', ['Client','Fournisseur','Super admin'])
                ->select('id','libelle')
                ->get();

          return response()->json([
                'data' => $profils,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.index_e',
            ]);
        }

    }

     /**
     * Display a listing of the profils by employee.
     */
    public function index_e_type(Request $request,$tp)
    {
        try {
            $profils = DB::table('profils')
                ->whereIn('libelle', [$tp])
                ->select('id','libelle')
                ->get();

          return response()->json([
                'data' => $profils,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.index_e par type',
            ]);
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {
            $profil = Profil::create([
                'libelle' => $request->libelle,
                'description' => $request->description,
                'created_user' => Auth::id(),
            ]);
            return response()->json($profil, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.store',
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {

            $profil = Profil::find($id);

            return response()->json([
                'success' => false,
                'profil' => $profil,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.show',
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {

            $profil = Profil::find($id);

            if (!$profil) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }
            $record = Profil::where('libelle', $profil->libelle)->first();
            if ($record) {
                $profil->description = $request->description ?? $profil->description;
                $profil->statut = $request->statut ?? $profil->statut;

            } else {
                $profil->libelle = $request->libelle ?? $profil->libelle;
                $profil->description = $request->description ?? $profil->description;
                $profil->statut = $request->statut ?? $profil->statut;

            }

            $profil->updated_user = Auth::id();

            $profil->save();
            return response()->json([
                'success' => true,
                'message' => 'Profil avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.update',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {

            $record = Profil::where('id', $id)->get();
            $current = Carbon::now();
            if (count($record) > 0) {
                Profil::where('id', $id)->update([
                    'deleted_at' => $current,
                    'deleted_user' => Auth::id(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Profil avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Cet Profil n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.destroy',
            ]);
        }
    }
    public function delete(int $id)
    {
        try {
            $record = Profil::where('id', $id)->get();
            if (count($record)) {
                DB::table('profils')->where('id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Profil avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Cet Profil n'existe pas",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ProfilController.delete',
            ]);
        }

    }
}
