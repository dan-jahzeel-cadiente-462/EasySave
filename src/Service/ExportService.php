<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Export orders to CSV
     */
    public function exportOrdersToCSV(array $orders): StreamedResponse
    {
        $response = new StreamedResponse(function() use ($orders) {
            $handle = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($handle, [
                'Order ID',
                'Customer',
                'Email',
                'Order Date',
                'Total',
                'Status',
                'Items Count'
            ]);
            
            // Add data rows
            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->getId(),
                    $order->getCustomer() ? $order->getCustomer()->getUsername() : 'Unknown',
                    $order->getCustomer() ? $order->getCustomer()->getEmail() : 'N/A',
                    $order->getCreatedAt() ? $order->getCreatedAt()->format('Y-m-d H:i:s') : '',
                    number_format($order->getTotal(), 2),
                    $order->getStatus(),
                    count($order->getOrderItems())
                ]);
            }
            
            fclose($handle);
        });
        
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="orders_' . date('Y-m-d_His') . '.csv"');
        
        return $response;
    }
    
    /**
     * Export activity logs to CSV
     */
    public function exportActivityLogsToCSV(array $logs): StreamedResponse
    {
        $response = new StreamedResponse(function() use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($handle, [
                'Timestamp',
                'User',
                'Role',
                'Action',
                'Entity Type',
                'Entity ID',
                'Description',
                'IP Address',
                'User Agent'
            ]);
            
            // Add data rows
            foreach ($logs as $log) {
                $roles = $log->getUser() ? implode(', ', $log->getUser()->getRoles()) : 'N/A';
                
                fputcsv($handle, [
                    $log->getCreatedAt() ? $log->getCreatedAt()->format('Y-m-d H:i:s') : '',
                    $log->getUser() ? $log->getUser()->getUsername() : 'System',
                    $roles,
                    $log->getAction(),
                    $log->getEntityType(),
                    $log->getEntityId(),
                    $log->getDescription(),
                    $log->getIpAddress(),
                    $log->getUserAgent()
                ]);
            }
            
            fclose($handle);
        });
        
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="activity_logs_' . date('Y-m-d_His') . '.csv"');
        
        return $response;
    }
    
    /**
     * Export orders to JSON
     */
    public function exportOrdersToJSON(array $orders): StreamedResponse
    {
        $data = [];
        
        foreach ($orders as $order) {
            $items = [];
            foreach ($order->getOrderItems() as $item) {
                $items[] = [
                    'product' => $item->getProduct() ? $item->getProduct()->getName() : 'Unknown',
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                    'subtotal' => $item->getQuantity() * $item->getPrice()
                ];
            }
            
            $data[] = [
                'id' => $order->getId(),
                'customer' => $order->getCustomer() ? $order->getCustomer()->getUsername() : 'Unknown',
                'email' => $order->getCustomer() ? $order->getCustomer()->getEmail() : 'N/A',
                'created_at' => $order->getCreatedAt() ? $order->getCreatedAt()->format('Y-m-d H:i:s') : '',
                'total' => $order->getTotal(),
                'status' => $order->getStatus(),
                'items' => $items,
                'shipping_address' => $order->getShippingAddress() ? [
                    'street' => $order->getShippingAddress()->getStreet(),
                    'city' => $order->getShippingAddress()->getCity(),
                    'state' => $order->getShippingAddress()->getState(),
                    'postal_code' => $order->getShippingAddress()->getPostalCode(),
                    'country' => $order->getShippingAddress()->getCountry()
                ] : null
            ];
        }
        
        $response = new StreamedResponse(function() use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        });
        
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="orders_' . date('Y-m-d_His') . '.json"');
        
        return $response;
    }
    
    /**
     * Export activity logs to JSON
     */
    public function exportActivityLogsToJSON(array $logs): StreamedResponse
    {
        $data = [];
        
        foreach ($logs as $log) {
            $data[] = [
                'id' => $log->getId(),
                'timestamp' => $log->getCreatedAt() ? $log->getCreatedAt()->format('Y-m-d H:i:s') : '',
                'user' => $log->getUser() ? $log->getUser()->getUsername() : 'System',
                'roles' => $log->getUser() ? $log->getUser()->getRoles() : [],
                'action' => $log->getAction(),
                'entity_type' => $log->getEntityType(),
                'entity_id' => $log->getEntityId(),
                'description' => $log->getDescription(),
                'details' => $log->getDetails(),
                'ip_address' => $log->getIpAddress(),
                'user_agent' => $log->getUserAgent()
            ];
        }
        
        $response = new StreamedResponse(function() use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        });
        
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="activity_logs_' . date('Y-m-d_His') . '.json"');
        
        return $response;
    }
}
