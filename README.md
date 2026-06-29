php artisan l5-swagger:generate


important pour la lecture des images
sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/public/images/entreprise
sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/public/images/users
sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/public/images/products
 sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/storage
 sudo chown -R www-data:www-data /home/keho-pos/htdocs/pos.kehogroupe-ci.com/storage
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm

---

## Boutique en ligne (Landing page + QR code)

Page publique de commande (front Nuxt) :

    http://localhost:3000/boutique          (dev)
    https://VOTRE-DOMAINE/boutique           (production)

C'est cette URL qui est encodée dans le **QR code** à coller devant le magasin.

### Endpoints publics (aucune authentification)

    GET  /api/v1/boutique/products           Liste des produits disponibles en ligne (online = 1)
    GET  /api/v1/boutique/categories         Catégories ayant au moins un produit en ligne
    POST /api/v1/boutique/order              Enregistre une commande client
    GET  /api/v1/boutique/qrcode?url=...     Génère le QR code (SVG) pointant vers l'URL fournie

Exemple de génération du QR (SVG) :

    GET /api/v1/boutique/qrcode?url=https://VOTRE-DOMAINE/boutique

### Endpoints back-office (auth:sanctum)

    GET  /api/v1/boutique/orders             Liste des commandes en ligne (+ ?statut=pending|processed|cancelled)
    GET  /api/v1/boutique/orders/{id}        Détail d'une commande
    POST /api/v1/boutique/orders/{id}/status Change le statut (processed décrémente le stock)

### Disponibilité produit en ligne

    POST /api/v1/produits/{id}/online        Bascule rapide online = 0/1 (champ `online` sur la table produits)

> La page d'affiche QR côté admin ("QR CODE BOUTIQUE") permet de télécharger
> une affiche PNG (nom de la boutique + logo + WhatsApp) prête à imprimer.
