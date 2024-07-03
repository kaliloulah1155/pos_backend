<?php

use App\Http\Controllers\API\ActionController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategorieController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\ProfilController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\DepenseController;
use App\Http\Controllers\API\SalaireController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\TestCartController;
use App\Http\Controllers\API\ProduitController;
use Illuminate\Support\Facades\Route;
         
Route::group(['prefix' => '/v1'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('permissions/menu/{id}', [PermissionController::class, 'menu_profil']); // passage de l'id profil

    //Password reset
    Route::post('forgot', [AuthController::class, 'forgot']);
    Route::post('reset', [AuthController::class, 'reset']);
    
    Route::get('test',function(){
        return response()->json(['message' => 'API test successful']);
    });
    
     Route::get('printOrder/{id}', [TestCartController::class, 'pdfOrder']); //imprimer de la commande
});

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['prefix' => '/v1'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('infoUser',[AuthController::class, 'getUserInfo']);

        //USER
        Route::get('users', [UserController::class, 'index']);
        Route::get('employes', [UserController::class, 'employes']);
        Route::get('clients', [UserController::class, 'clients']);
        Route::get('fournisseurs', [UserController::class, 'fournisseurs']);
        Route::post('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
        Route::delete('users/dl/{id}', [UserController::class, 'delete']);
        Route::get('users/{id}', [UserController::class, 'show']);
        //PROFIL
        Route::post('profils', [ProfilController::class, 'store']);
        Route::get('profils', [ProfilController::class, 'index']);
        Route::get('profils_e', [ProfilController::class, 'index_e']);
        Route::get('profils_e/{tp}', [ProfilController::class, 'index_e_type']);
        Route::post('profils/{id}', [ProfilController::class, 'update']);
        Route::delete('profils/{id}', [ProfilController::class, 'destroy']);
        Route::delete('profils/dl/{id}', [ProfilController::class, 'delete']);
        Route::get('profils/{id}', [ProfilController::class, 'show']);
        //CATEGORIE
        Route::get('categories', [CategorieController::class, 'index']);
        Route::get('categories_slug/{slug}', [CategorieController::class, 'index_slug']);
        Route::post('categories', [CategorieController::class, 'store']);
        Route::post('categories/{id}', [CategorieController::class, 'update']);
        Route::get('categories/{id}', [CategorieController::class, 'show']);
        Route::delete('categories/{id}', [CategorieController::class, 'destroy']);
        Route::delete('categories/dl/{id}', [CategorieController::class, 'delete']);
    
        //ACTION
        Route::get('actions', [ActionController::class, 'index']);
        Route::post('actions', [ActionController::class, 'store']);
        Route::post('actions/{id}', [ActionController::class, 'update']);
        Route::get('actions/{id}', [ActionController::class, 'show']);
        Route::delete('actions/{id}', [ActionController::class, 'destroy']);
        Route::delete('actions/dl/{id}', [ActionController::class, 'delete']);

        //MENU
        Route::get('menus', [MenuController::class, 'index']);
        Route::post('menus', [MenuController::class, 'store']);
        Route::post('menus/{id}', [MenuController::class, 'update']);
        Route::get('menus/{id}', [MenuController::class, 'show']);
        Route::delete('menus/{id}', [MenuController::class, 'destroy']);
        Route::delete('menus/dl/{id}', [MenuController::class, 'delete']);

        //Permissions 
        Route::post('permissions', [PermissionController::class, 'store'])->withoutMiddleware("throttle:api");
        Route::get('permissions/{id}', [PermissionController::class, 'show']); ///passage de l'id profil
        Route::delete('permissions/{id}', [PermissionController::class, 'destroy']); // passage de l'id profil
        Route::post('test_permission', [PermissionController::class, 'test_permission']);    
        
        
        //DEPENSES
        Route::get('depenses', [DepenseController::class, 'index']);
        Route::post('depenses', [DepenseController::class, 'store']);
        Route::get('depenses/{id}', [DepenseController::class, 'show']);
        Route::post('depenses/{id}', [DepenseController::class, 'update']);
        Route::delete('depenses/{id}', [DepenseController::class, 'destroy']);
        Route::delete('depenses/dl/{id}', [DepenseController::class, 'delete']);
        
        //SALAIRES
        Route::get('salaires', [SalaireController::class, 'index']);
        Route::post('salaires', [SalaireController::class, 'store']);
        Route::get('salaires/{id}', [SalaireController::class, 'show']);
        Route::post('salaires/{id}', [SalaireController::class, 'update']);
        Route::delete('salaires/{id}', [SalaireController::class, 'destroy']);
        Route::delete('salaires/dl/{id}', [SalaireController::class, 'delete']);
        
        //PRODUITS
        Route::get('produits', [ProduitController::class, 'index']);
        Route::get('produits_stock', [ProduitController::class, 'stocks'])->name("stocks");
        Route::post('produits', [ProduitController::class, 'store']);
        Route::post('produits/{id}', [ProduitController::class, 'update']);
        Route::delete('produits/{id}', [ProduitController::class, 'destroy']);
        Route::delete('produits/dl/{id}', [ProduitController::class, 'delete']);
        //CART
        Route::get('cart', [TestCartController::class, 'content']);
        Route::post('cart/{productId}', [TestCartController::class, 'addItem']);
        Route::get('cartclear', [TestCartController::class, 'clearCart']);
        Route::post('/cart/{rowId}/increase', [TestCartController::class,'increaseQuantity']);
        Route::post('/cart/{rowId}/decrease', [TestCartController::class,'decreaseQuantity']);
        Route::delete('cart/{id}', [TestCartController::class, 'removeItem']);
        Route::post('addOrder', [TestCartController::class, 'addOrder']);
        Route::get('pos_categories', [ProduitController::class, 'pos_categorie']);   //liste des categories du pos
        Route::get('pos_produit_by_categorie/{categoryId}', [ProduitController::class, 'pos_produit_by_categorie']);   //liste des categories du pos
       
    

    });

});
