<?php

namespace App\Helpers;

use App\Http\Resources\Management\Gen\Product\AqiqahProductResource;
use App\Http\Resources\Management\Gen\Product\QurbanProductResource;
use App\Http\Resources\Management\Gen\Product\SadaqahProductResource;
use App\Http\Resources\Templates\WithoutDataResource;
use App\Models\Animal;
use App\Models\QurbanProduct;
use App\Models\AqiqahProduct;
use App\Models\OrderDetail;
use App\Models\SadaqahProduct;
use App\Models\ServiceType;
use Illuminate\Http\Response;

class OrderDetailHelper
{
  public static function formatOrderDetailByServiceType(int $serviceTypeId, array $items): array
  {
    $details = [];

    $serviceType = ServiceType::findOrFail($serviceTypeId);
    $label = strtolower($serviceType->label);

    foreach ($items as $item) {
      switch ($label) {
        case 'qurban': // Qurban
          if (empty($item['qurban_product'])) {
            response()->json(
              new WithoutDataResource(
                Response::HTTP_BAD_REQUEST,
                'INVALID_DETAIL_PRODUCT',
                'Detail Tidak Sesuai',
                'Detail item tidak sesuai dengan jenis layanan Qurban.'
              ),
              Response::HTTP_BAD_REQUEST
            )->send();
            exit;
          }

          $product = QurbanProduct::with('animals')->findOrFail($item['qurban_product']);
          $details[] = [
            'qurban_product' => new QurbanProductResource($product),
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'shohibul' => $item['shohibul'] ?? [],
          ];
          break;

        case 'aqiqah': // Aqiqah
          if (empty($item['aqiqah_product'])) {
            response()->json(
              new WithoutDataResource(
                Response::HTTP_BAD_REQUEST,
                'INVALID_DETAIL_PRODUCT',
                'Detail Tidak Sesuai',
                'Detail item tidak sesuai dengan jenis layanan Aqiqah.'
              ),
              Response::HTTP_BAD_REQUEST
            )->send();
            exit;
          }

          $product = AqiqahProduct::with('animals')->findOrFail($item['aqiqah_product']);
          $details[] = [
            'aqiqah_product' => new AqiqahProductResource($product),
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'shohibul' => $item['shohibul'] ?? [],
          ];
          break;

        case 'sadaqah': // Sadaqah
          if (empty($item['sadaqah_product'])) {
            response()->json(
              new WithoutDataResource(
                Response::HTTP_BAD_REQUEST,
                'INVALID_DETAIL_PRODUCT',
                'Detail Tidak Sesuai',
                'Detail item tidak sesuai dengan jenis layanan Sadaqah.'
              ),
              Response::HTTP_BAD_REQUEST
            )->send();
            exit;
          }

          $product = SadaqahProduct::findOrFail($item['sadaqah_product']);
          $details[] = [
            'sadaqah_product' => new SadaqahProductResource($product),
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'shohibul' => $item['shohibul'] ?? ['Fulan'],
          ];
          break;

        default:
          throw new \InvalidArgumentException("Unsupported service type ID: {$serviceTypeId}");
      }
    }

    return $details;
  }

  public static function summarizeOrderDetail(OrderDetail $orderDetail): array
  {
    $totalQuantity = 0;
    $totalPrice = 0;

    foreach ($orderDetail->detail as $item) {
      $quantity = $item['quantity'] ?? 0;
      $price = $item['price'] ?? 0;

      $totalQuantity += $quantity;
      $totalPrice += $quantity * $price;
    }

    return [
      'order_detail_id' => $orderDetail->id,
      'service_type' => $orderDetail->service_type->label ?? '-',
      'quantity' => $totalQuantity,
      'price' => $totalPrice,
    ];
  }

  public static function deductAnimalStock(OrderDetail $orderDetail): void
  {
    foreach ($orderDetail->detail as $item) {
      foreach (['qurban_product', 'aqiqah_product', 'sadaqah_product'] as $productType) {
        if (!empty($item[$productType])) {
          $product = $item[$productType];

          // Cek apakah produk memiliki data 'animal'
          $animal = $product['animal'] ?? null;
          $quantity = $item['quantity'] ?? 0;

          // Pastikan animal ada dan memiliki ID
          if ($animal && isset($animal['id']) && $quantity > 0) {
            $animalModel = Animal::find($animal['id']);

            if ($animalModel) {
              $newStock = max(0, $animalModel->stock - $quantity);
              $animalModel->update(['stock' => $newStock]);
            }
          }

          // Setelah ketemu salah satu produk, break supaya tidak double process
          break;
        }
      }
    }
  }

  public static function restoreAnimalStock(OrderDetail $orderDetail): void
  {
    if ($orderDetail->stock_restored) {
      return; // Skip kalau stok sudah direstore sebelumnya
    }

    foreach ($orderDetail->detail as $item) {
      foreach (['qurban_product', 'aqiqah_product', 'sadaqah_product'] as $productType) {
        if (!empty($item[$productType])) {
          $product = $item[$productType];

          $animal = $product['animal'] ?? null;
          $quantity = $item['quantity'] ?? 0;

          if ($animal && isset($animal['id']) && $quantity > 0) {
            $animalModel = Animal::find($animal['id']);

            if ($animalModel) {
              $animalModel->update([
                'stock' => $animalModel->stock + $quantity
              ]);
            }
          }

          break;
        }
      }
    }

    // Update agar tidak direstore lagi
    $orderDetail->update(['stock_restored' => true]);
  }
}
