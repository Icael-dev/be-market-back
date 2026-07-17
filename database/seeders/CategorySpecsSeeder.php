<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySpecsSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSpecs = [
            'telephones' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Samsung, Apple, Xiaomi'],
                ['key' => 'Modele', 'placeholder' => 'Ex: Galaxy S24'],
                ['key' => 'Couleur', 'placeholder' => 'Ex: Noir, Blanc, Bleu'],
                ['key' => 'Stockage', 'placeholder' => 'Ex: 128GB, 256GB'],
                ['key' => 'RAM', 'placeholder' => 'Ex: 8GB'],
                ['key' => 'Ecran', 'placeholder' => 'Ex: 6.1 pouces'],
            ],
            'ordinateurs-portables' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: HP, Dell, Lenovo'],
                ['key' => 'Processeur', 'placeholder' => 'Ex: Intel i7-13700H'],
                ['key' => 'RAM', 'placeholder' => 'Ex: 16GB DDR5'],
                ['key' => 'Stockage', 'placeholder' => 'Ex: 512GB SSD'],
                ['key' => 'Ecran', 'placeholder' => 'Ex: 15.6 pouces FHD'],
                ['key' => 'Carte graphique', 'placeholder' => 'Ex: RTX 4060'],
            ],
            'ordinateurs-de-bureau' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: HP, Dell'],
                ['key' => 'Processeur', 'placeholder' => 'Ex: Intel i7'],
                ['key' => 'RAM', 'placeholder' => 'Ex: 16GB'],
                ['key' => 'Stockage', 'placeholder' => 'Ex: 1TB SSD'],
            ],
            'casques-audio' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Sony, JBL'],
                ['key' => 'Type', 'placeholder' => 'Ex: Circum-auriculaire, Intra'],
                ['key' => 'Connectivite', 'placeholder' => 'Ex: Bluetooth, Filaire'],
                ['key' => 'Autonomie', 'placeholder' => 'Ex: 30h'],
            ],
            'airpods-ecouteurs' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Apple, Samsung'],
                ['key' => 'Connectivite', 'placeholder' => 'Ex: Bluetooth 5.0'],
                ['key' => 'Autonomie', 'placeholder' => 'Ex: 6h'],
            ],
            'baffles-enceintes' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: JBL, Bose'],
                ['key' => 'Puissance', 'placeholder' => 'Ex: 20W'],
                ['key' => 'Connectivite', 'placeholder' => 'Ex: Bluetooth'],
            ],
            'huffers-power-banks' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Xiaomi, Anker'],
                ['key' => 'Capacite', 'placeholder' => 'Ex: 10000mAh'],
                ['key' => 'Connectivite', 'placeholder' => 'Ex: USB-C'],
            ],
            'montres-connectees' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Apple, Samsung'],
                ['key' => 'Taille', 'placeholder' => 'Ex: 42mm, 46mm'],
                ['key' => 'Connectivite', 'placeholder' => 'Ex: Bluetooth, GPS'],
            ],
            'tablettes' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Apple, Samsung'],
                ['key' => 'Taille', 'placeholder' => 'Ex: 10.9 pouces'],
                ['key' => 'Stockage', 'placeholder' => 'Ex: 64GB, 256GB'],
            ],
            'appareils-photo' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Canon, Nikon'],
                ['key' => 'Resolution', 'placeholder' => 'Ex: 24MP'],
                ['key' => 'Type', 'placeholder' => 'Ex: Hybride, Reflex'],
            ],
            'gaming' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Sony, Microsoft'],
                ['key' => 'Type', 'placeholder' => 'Ex: Console, Manette'],
            ],
            'reseau-wifi' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: TP-Link, Netgear'],
                ['key' => 'Standard', 'placeholder' => 'Ex: Wi-Fi 6'],
                ['key' => 'Debit', 'placeholder' => 'Ex: 1200 Mbps'],
            ],
            'stockage' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Samsung, SanDisk'],
                ['key' => 'Capacite', 'placeholder' => 'Ex: 1TB'],
                ['key' => 'Type', 'placeholder' => 'Ex: SSD, HDD'],
            ],
            'peripheriques' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Logitech'],
                ['key' => 'Type', 'placeholder' => 'Ex: Clavier, Souris'],
                ['key' => 'Connectivite', 'placeholder' => 'Ex: USB, Bluetooth'],
            ],
        ];

        foreach ($defaultSpecs as $slug => $specs) {
            $category = Category::where('slug', $slug)->first();
            if (! $category) {
                continue;
            }

            $category->specs()->delete();
            foreach ($specs as $spec) {
                $category->specs()->create($spec);
            }
        }
    }
}
