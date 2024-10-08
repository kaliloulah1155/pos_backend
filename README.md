php artisan l5-swagger:generate


important pour la lecture des images
sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/public/images/entreprise
sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/public/images/users
sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/public/images/products
 sudo chmod -R 777 /home/keho-pos/htdocs/pos.kehogroupe-ci.com/storage
 sudo chown -R www-data:www-data /home/keho-pos/htdocs/pos.kehogroupe-ci.com/storage
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
