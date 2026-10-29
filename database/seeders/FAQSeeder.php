<?php

namespace Database\Seeders;

use App\Models\FAQ;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FAQSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Quels sont les modes de paiement acceptes ?',
                'answer' => 'Nous acceptons les paiements par Mobile Money (Mixx by Yas, Flooz), carte bancaire et paiement a la livraison. Toutes les transactions sont securisees.',
                'category' => 'paiement',
                'sort_order' => 1,
            ],
            [
                'question' => 'Le paiement a la livraison est-il disponible ?',
                'answer' => 'Oui, vous pouvez payer en especes a la reception de votre commande. Ce mode de paiement est disponible sur tout le territoire togolais.',
                'category' => 'paiement',
                'sort_order' => 2,
            ],
            [
                'question' => 'Comment suivre ma commande ?',
                'answer' => 'Vous pouvez suivre votre commande en temps reel depuis votre compte client, section "Mes commandes". Vous recevrez egalement des notifications par email a chaque etape.',
                'category' => 'commandes',
                'sort_order' => 1,
            ],
            [
                'question' => 'Quels sont les delais de livraison ?',
                'answer' => 'La livraison prend generalement 24 a 48 heures a Lome et 2 a 3 jours ouvrables pour les autres villes du Togo. La livraison est gratuite pour toute commande superieure a 50 000 FCFA.',
                'category' => 'livraison',
                'sort_order' => 1,
            ],
            [
                'question' => 'Proposez-vous la livraison gratuite ?',
                'answer' => 'Oui, la livraison est gratuite pour toute commande de 50 000 FCFA ou plus. En dessous de ce montant, les frais de livraison sont de 2 500 FCFA.',
                'category' => 'livraison',
                'sort_order' => 2,
            ],
            [
                'question' => 'Comment puis-je retourner un produit ?',
                'answer' => 'Vous disposez de 7 jours apres reception pour retourner un produit. Le produit doit etre dans son etat d\'origine. Contactez notre support pour initier un retour.',
                'category' => 'retours',
                'sort_order' => 1,
            ],
            [
                'question' => 'Les produits sont-ils garantis ?',
                'answer' => 'Tous nos produits beneficient d\'une garantie constructeur. Pour les produits reconditionnes, une garantie de 3 mois est appliquee. Conservez votre facture pour toute demande de garantie.',
                'category' => 'garantie',
                'sort_order' => 1,
            ],
            [
                'question' => 'Comment contacter le service client ?',
                'answer' => 'Vous pouvez nous contacter via le formulaire de support sur cette page, par email a support@be-market.tg ou par telephone au +228 90 00 00 00 du lundi au vendredi de 8h a 18h.',
                'category' => 'general',
                'sort_order' => 1,
            ],
            [
                'question' => 'Proposez-vous des codes promo ?',
                'answer' => 'Oui, nous proposons regulierement des codes promo et des ventes flash. Inscrivez-vous a notre newsletter pour etre informe des offres exclusives.',
                'category' => 'general',
                'sort_order' => 2,
            ],
            [
                'question' => 'Comment creer un compte ?',
                'answer' => 'Cliquez sur "S\'inscrire" en haut a droite, remplissez le formulaire avec votre nom, email et mot de passe. Vous recevrez un email de confirmation pour activer votre compte.',
                'category' => 'general',
                'sort_order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            FAQ::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['id' => (string) Str::uuid()])
            );
        }
    }
}
