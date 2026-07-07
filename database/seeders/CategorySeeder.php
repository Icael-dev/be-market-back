<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Telephones',
                'description' => 'Smartphones, telephones portables et accessoires mobiles',
                'sort_order' => 1,
            ],
            [
                'name' => 'Ordinateurs portables',
                'description' => 'PC portables, ultrabooks et ordinateurs hybrides',
                'sort_order' => 2,
            ],
            [
                'name' => 'Ordinateurs de bureau',
                'description' => 'Tours, mini-PC, moniteurs et peripheriques PC',
                'sort_order' => 3,
            ],
            [
                'name' => 'Pochettes & Coques telephone',
                'description' => 'Coques, etuis, films protecteurs et accessoires pour telephone',
                'sort_order' => 4,
            ],
            [
                'name' => 'Casques audio',
                'description' => 'Casques filaires et sans fil, micro-casques et ecouteurs',
                'sort_order' => 5,
            ],
            [
                'name' => 'AirPods & Ecouteurs',
                'description' => 'Ecouteurs sans fil, AirPods, ecouteurs Bluetooth et sport',
                'sort_order' => 6,
            ],
            [
                'name' => 'Baffles & Enceintes',
                'description' => 'Enceintes Bluetooth, baffles, home cinema et systemes audio',
                'sort_order' => 7,
            ],
            [
                'name' => 'Huffers & Power Banks',
                'description' => 'Batteries externes, chargeurs rapides, chargeurs solaires et cables',
                'sort_order' => 8,
            ],
            [
                'name' => 'Montres connectees',
                'description' => 'Smartwatches, bracelets fitness, trackers d\'activite et wearables',
                'sort_order' => 9,
            ],
            [
                'name' => 'Tablettes',
                'description' => 'Tablettes tactiles, iPad, tablettes Android et accessoires',
                'sort_order' => 10,
            ],
            [
                'name' => 'Appareils photo',
                'description' => 'Appareils photo numeriques, miroirs, cameras professionnelles et drones',
                'sort_order' => 11,
            ],
            [
                'name' => 'Gaming',
                'description' => 'Consoles, manettes, jeux video, cartes graphiques et peripheriques gaming',
                'sort_order' => 12,
            ],
            [
                'name' => 'Reseau & Wifi',
                'description' => 'Routeurs, modems, extenders Wi-Fi, switchs et accessoires reseau',
                'sort_order' => 13,
            ],
            [
                'name' => 'Stockage',
                'description' => 'Clés USB, disques durs externes, SSD, cartes mémoire et stockage cloud',
                'sort_order' => 14,
            ],
            [
                'name' => 'Peripheriques',
                'description' => 'Claviers, souris, tapis de souris, webcams et hubs USB',
                'sort_order' => 15,
            ],
            [
                'name' => 'Logiciels & Abonnements',
                'description' => 'Antivirus, licences logicielles, VPN et abonnements numeriques',
                'sort_order' => 16,
            ],
        ];

        foreach ($categories as $category) {
            $slug = Str::slug($category['name']);
            $existing = Category::where('slug', $slug)->first();
            
            if ($existing) {
                $existing->update([
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                ]);
            } else {
                Category::create([
                    'id' => (string) Str::uuid(),
                    'name' => $category['name'],
                    'slug' => $slug,
                    'description' => $category['description'],
                    'image_url' => null,
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
