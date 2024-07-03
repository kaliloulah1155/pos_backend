<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreRequest;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use DB;
use Auth;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $size = 5;
            // Start building the query
            $query = Menu::query();
            // Eager load parent and enfants categories
            $query->with('parent')->with('enfants');
            // Add dynamic filters based on query parameters
            if ($request['field'] == "libelle") {
                $query->where('libelle', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }
     
            if ($request['field'] == "name") {
                $query->where('name', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }

            if ($request['field'] == "statut") {
                $query->where('statut', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }

            if ($request['field'] == "parent") {
                $query->where('menus.libelle', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }
            $size = $request->size;

            if ($request->sorters) {
                $sortField = $request->sorters[0]['field'];
                $sortDirection = $request->sorters[0]['dir'];
                $query->orderBy($sortField, $sortDirection);
            }

            // Paginate the filtered data
            $perPage = $request->input('per_page', $size); // Default per page to 10 if not specified
            $menus = $query->paginate($perPage);

            // Add last_page, page, per_page, and total information
            $lastPage = $menus->lastPage();
            $currentPage = $menus->currentPage();
            $perPage = $menus->perPage();
            $total = $menus->total();

            // Return the paginated data with additional information
            return response()->json([
                'data' => $menus->items(),
                'last_page' => $lastPage,
                'page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in MenuController.index',
            ]);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {
            
            $r_positions = DB::select('CALL sp_menu_max()');
            $menu = Menu::create([
                'libelle' => $request->libelle,
                'target' => $request->target,
                'type' => $request->type,
                'icon' => $request->icon,
                'position' => intval($r_positions[0]->positions)+1,
                'name' => $request->name,
                'statut' => $request->statut,
                'menu_id' => $request->menu_id,
                'created_user' => Auth::id(),
            ]);
            return response()->json($menu, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in MenuController.store',
            ]);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {

            $menu = Menu::with('parent')->with('enfants')->find($id);
            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => "Menu introuvable",
                ]);
            }
            return response()->json([
                'success' => true,
                'menu' => $menu,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in MenuController.show',
            ]);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {

            $menu = Menu::find($id);
            if (!$menu) {
                return response()->json([
                    'success' => false,
                    'message' => 'Menu avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }

            $menu->libelle = $request->libelle ?? $menu->libelle;
            $menu->target = $request->target ?? $menu->target;
            $menu->type = $request->type ?? $menu->type;
            $menu->icon = $request->icon ?? $menu->icon;
            $menu->position = $request->position ?? $menu->position;
            $menu->name = $request->name ?? $menu->name;
            $menu->statut = $request->statut ?? $menu->statut;
            $menu->menu_id = $request->menu_id;
            $menu->updated_user = Auth::id();
            $menu->save();
            return response()->json([
                'success' => true,
                'message' => 'Menu avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in MenuController.update',
            ]);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {

            $record = Menu::where('id', $id)->get();
            $current = Carbon::now();
            if (count($record) > 0) {
                Menu::where('id', $id)->update([
                    'deleted_at' => $current,
                    'deleted_user' => Auth::id(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Menu avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Ce menu n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in MenuController.destroy',
            ]);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(int $id)
    {
        try {
            $record = Menu::where('id', $id)->get();
            if (count($record)) {
                DB::table('menus')->where('id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Menu avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Le menu n'existe pas",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in MenuController.delete',
            ]);
        }

    }
}
