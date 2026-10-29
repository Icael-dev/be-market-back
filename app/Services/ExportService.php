<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\Profile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function csv(string $type): StreamedResponse
    {
        $config = match ($type) {
            'customers' => [
                'columns' => ['email', 'full_name', 'phone', 'created_at'],
                'headers' => ['Email', 'Nom', 'Telephone', 'Inscrit le'],
                'filename' => 'customers.csv',
                'query' => Profile::orderBy('created_at', 'desc'),
            ],
            'products' => [
                'columns' => ['name', 'price', 'stock', 'is_active'],
                'headers' => ['Nom', 'Prix', 'Stock', 'Actif'],
                'filename' => 'products.csv',
                'query' => Product::orderBy('name'),
            ],
            'newsletter' => [
                'columns' => ['email', 'is_active', 'subscribed_at'],
                'headers' => ['Email', 'Actif', 'Inscrit le'],
                'filename' => 'newsletter_subscribers.csv',
                'query' => NewsletterSubscriber::orderBy('subscribed_at', 'desc'),
            ],
            default => [
                'columns' => ['order_number', 'status', 'payment_status', 'total', 'created_at'],
                'headers' => ['Numero', 'Statut', 'Paiement', 'Total', 'Date'],
                'filename' => 'orders.csv',
                'query' => Order::orderBy('created_at', 'desc'),
            ],
        };

        return response()->streamDownload(function () use ($config) {
            $output = fopen('php://output', 'w');

            fprintf($output, "\xEF\xBB\xBF");
            fputcsv($output, $config['headers']);

            $config['query']->lazyById(500)->each(function ($row) use ($config, $output) {
                $values = array_map([self::class, 'formatValue'], $row, $config['columns']);
                fputcsv($output, $values);
            });

            fclose($output);
        }, $config['filename'], [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    /**
     * @param  object  $row
     */
    protected static function formatValue($row, string $column): string
    {
        $value = $row->{$column} ?? '';

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
