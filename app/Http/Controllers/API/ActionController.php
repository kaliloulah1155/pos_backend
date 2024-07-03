<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Action\StoreRequest;
use App\Models\Action;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;
use Carbon\Carbon;



class ActionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $size = 5;
            // Start building the query
            $query = Action::query();
            if ($request['field'] == "libelle") {
                $query->where('libelle', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }
    
            if ($request['field'] == "code") {
                $query->where('code', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }

            if ($request['field'] == "statut") {
                $query->where('statut', $request['type'], $request['type'] == 'like' ? '%' . $request['value'] . '%' : $request['value']);
            }
            $size = $request->size;
            if ($request->sorters) {
                $sortField = $request->sorters[0]['field'];
                $sortDirection = $request->sorters[0]['dir'];
                $query->orderBy($sortField, $sortDirection);
            }else{
                $query->orderBy('position', 'ASC');
            }

            // Paginate the filtered data
            $perPage = $request->input('per_page', $size); // Default per page to 10 if not specified
            $actions = $query->paginate($perPage);

            // Add last_page, page, per_page, and total information
            $lastPage = $actions->lastPage();
            $currentPage = $actions->currentPage();
            $perPage = $actions->perPage();
            $total = $actions->total();
            // Return the paginated data with additional information
            return response()->json([
                'data' => $actions->items(),
                'last_page' => $lastPage,
                'page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ActionController.index',
            ]);

        }
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        try {   
            
            $r_positions = DB::select('CALL sp_action_max()');
            $action = Action::create([
                'libelle' => $request->libelle,
                'code' => $request->code,
                'icon' => $request->icon,
                'position' => intval($r_positions[0]->positions)+1,
                'statut' => $request->statut,
                'created_user' => Auth::id(),
            ]);

            return response()->json($action, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ActionController.store',
            ]);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {

            $action = Action::find($id);
            if (!$action) {
                return response()->json([
                    'success' => false,
                    'message' => "Action introuvable",
                ]);
            }
            return response()->json([
                'success' => true,
                'action' => $action,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ActionController.show',
            ]);
        }

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {

            $action = Action::find($id);
            if (!$action) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action avec l\'id ' . $id . ' n\'existe pas!',
                ], 200);
            }

            $action->libelle = $request->libelle ?? $action->libelle;
            $action->code = $request->code ?? $action->code;
            $action->icon = $request->icon ?? $action->icon;
            $action->position = $request->position ?? $action->position;
            $action->statut = $request->statut ?? $action->statut;
            $action->updated_user = Auth::id();

            $action->save();
            return response()->json([
                'success' => true,
                'message' => 'Action avec l\'id ' . $id . ' a été mis à jour!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ActionController.update',
            ]);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        try {

            $record = Action::where('id', $id)->get();
            $current = Carbon::now();
            if (count($record) > 0) {
                Action::where('id', $id)->update([
                    'deleted_at' => $current,
                    'deleted_user' => Auth::id(),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Action avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "Cette action n'existe pas",
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ActionController.destroy',
            ]);
        }

    }

    public function delete(int $id)
    {
        try {
            $record = Action::where('id', $id)->get();
            if (count($record)) {
                DB::table('actions')->where('id', $id)->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Action avec l\'id ' . $id . ' a été supprimé!']
                    , 201);
            } else {
                return response()->json([
                    'result' => false,
                    "message" => "La action n'existe pas",
                ]);
            }
        } catch (\Exception$e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Something went wrong in ActionController.delete',
            ]);
        }
    }
}
