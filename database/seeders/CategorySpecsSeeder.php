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
            'ordinateurs' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: HP, Dell, Lenovo'],
                ['key' => 'Processeur', 'placeholder' => 'Ex: Intel i7-13700H'],
                ['key' => 'RAM', 'placeholder' => 'Ex: 16GB DDR5'],
                ['key' => 'Stockage', 'placeholder' => 'Ex: 512GB SSD'],
                ['key' => 'Ecran', 'placeholder' => 'Ex: 15.6 pouces FHD'],
                ['key' => 'Carte graphique', 'placeholder' => 'Ex: RTX 4060'],
            ],
            'vetements-hommes' => [
                ['key' => 'Taille', 'placeholder' => 'Ex: S, M, L, XL'],
                ['key' => 'Couleur', 'placeholder' => 'Ex: Noir, Blanc'],
                ['key' => 'Matiere', 'placeholder' => 'Ex: Coton, Jean'],
                ['key' => 'Style', 'placeholder' => 'Ex: Casual, Sport'],
            ],
            'vetements-femmes' => [
                ['key' => 'Taille', 'placeholder' => 'Ex: S, M, L'],
                ['key' => 'Couleur', 'placeholder' => 'Ex: Rouge, Rose'],
                ['key' => 'Matiere', 'placeholder' => 'Ex: Coton, Soie'],
                ['key' => 'Style', 'placeholder' => 'Ex: Casual, Elegant'],
            ],
            'chaussures-hommes' => [
                ['key' => 'Pointure', 'placeholder' => 'Ex: 42, 43, 44'],
                ['key' => 'Couleur', 'placeholder' => 'Ex: Noir, Marron'],
                ['key' => 'Matiere', 'placeholder' => 'Ex: Cuir, Tissu'],
            ],
            'chaussures-femmes' => [
                ['key' => 'Pointure', 'placeholder' => 'Ex: 37, 38, 39'],
                ['key' => 'Couleur', 'placeholder' => 'Ex: Noir, Beige'],
                ['key' => 'Matiere', 'placeholder' => 'Ex: Cuir, Tissu'],
                ['key' => 'Talons', 'placeholder' => 'Ex: Plat, 5cm'],
            ],
            'accessoires' => [
                ['key' => 'Materiau', 'placeholder' => 'Ex: Acier, Cuir'],
                ['key' => 'Couleur', 'placeholder' => 'Ex: Or, Argent'],
                ['key' => 'Genre', 'placeholder' => 'Ex: Homme, Femme, Unisexe'],
            ],
            'maison' => [
                ['key' => 'Materiau', 'placeholder' => 'Ex: Bois, Metal'],
                ['key' => 'Dimensions', 'placeholder' => 'Ex: 100x50x30 cm'],
                ['key' => 'Couleur', 'placeholder' => 'Ex: Blanc, Gris'],
            ],
            'electronique' => [
                ['key' => 'Marque', 'placeholder' => 'Ex: Sony, Samsung'],
                ['key' => 'Puissance', 'placeholder' => 'Ex: 20W'],
                ['key' => 'Connectivite', 'placeholder' => 'Ex: Bluetooth 5.0'],
                ['key' => 'Autonomie', 'placeholder' => 'Ex: 10 heures'],
            ],
        ];

        foreach ($defaultSpecs as $slug => $specs) {
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                foreach ($specs as $spec) {
                    $category->specs()->create($spec);
                }
            }
        }
    }
}
