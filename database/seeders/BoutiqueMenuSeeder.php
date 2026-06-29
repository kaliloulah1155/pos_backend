<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute les menus "Commandes en ligne" et "QR Code boutique"
 * (menus de premier niveau) dans le système de menus/habilitations,
 * et donne tous les droits au profil Super admin.
 */
class BoutiqueMenuSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $menuIds = [
            $this->menu([
                'libelle'  => 'COMMANDES EN LIGNE',
                'target'   => '/commandes-en-ligne',
                'icon'     => 'shopping_bag',
                'type'     => 'item',
                'position' => 15,
            ], $now),
            $this->menu([
                'libelle'  => 'QR CODE BOUTIQUE',
                'target'   => '/qrcode',
                'icon'     => 'qr_code_2',
                'type'     => 'item',
                'position' => 16,
            ], $now),
        ];

        // Droits complets pour le Super admin (profil SAD)
        $superAdmin = DB::table('profils')->where('code', 'SAD')->value('id');
        if ($superAdmin) {
            $actionIds = DB::table('actions')->where('statut', 1)->pluck('id');
            foreach ($menuIds as $menuId) {
                foreach ($actionIds as $actionId) {
                    $exists = DB::table('permissions')
                        ->where('menu_id', $menuId)
                        ->where('profil_id', $superAdmin)
                        ->where('action_id', $actionId)
                        ->exists();
                    if (!$exists) {
                        DB::table('permissions')->insert([
                            'menu_id'    => $menuId,
                            'profil_id'  => $superAdmin,
                            'action_id'  => $actionId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Crée le menu de premier niveau s'il n'existe pas (par libellé) et renvoie son id.
     */
    private function menu(array $data, $now): int
    {
        $data['menu_id'] = null; // menu de premier niveau

        $existing = DB::table('menus')
            ->where('libelle', $data['libelle'])
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            DB::table('menus')->where('id', $existing->id)->update(array_merge($data, [
                'statut'     => 1,
                'updated_at' => $now,
            ]));
            return $existing->id;
        }

        return DB::table('menus')->insertGetId(array_merge($data, [
            'statut'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }
}
