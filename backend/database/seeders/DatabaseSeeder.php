<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $categories = [];

            foreach (['Áo', 'Quần', 'Váy', 'Phụ kiện'] as $name) {
                $slug = Str::slug($name);
                $category = Category::query()->where('name', $name)->first();

                if (! $category) {
                    $category = Category::firstOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $name,
                            'description' => null,
                            'status' => 'active',
                        ]
                    );
                }

                if ($category->name !== $name) {
                    throw new RuntimeException("Category slug [{$slug}] is already used by another category.");
                }

                $categories[$name] = $category;
            }

            $brands = [];

            foreach (['Nike', 'Adidas', 'Uniqlo', 'Zara', 'H&M'] as $name) {
                $slug = Str::slug($name);
                $brand = Brand::query()->where('name', $name)->first();

                if (! $brand) {
                    $brand = Brand::firstOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $name,
                            'description' => null,
                            'status' => 'active',
                        ]
                    );
                }

                if ($brand->name !== $name) {
                    throw new RuntimeException("Brand slug [{$slug}] is already used by another brand.");
                }

                $brands[$name] = $brand;
            }

            $sizes = [];

            foreach (['S', 'M', 'L', 'XL'] as $index => $name) {
                $sizes[$name] = Size::firstOrCreate(
                    ['name' => $name],
                    ['sort_order' => $index + 1]
                );
            }

            $colors = [];

            foreach ([
                'Đen' => '#000000',
                'Trắng' => '#FFFFFF',
                'Xám' => '#808080',
                'Xanh' => '#2563EB',
            ] as $name => $hexCode) {
                $colors[$name] = Color::firstOrCreate(
                    ['name' => $name],
                    ['hex_code' => $hexCode]
                );
            }

            $products = [
                [
                    'slug' => 'demo-ao-thun-basic',
                    'name' => 'Áo thun basic cotton',
                    'category' => 'Áo',
                    'brand' => 'Uniqlo',
                    'price' => '249000.00',
                    'sale_price' => '199000.00',
                    'image' => 'products/demo-ao-thun-basic.jpg',
                    'sku_prefix' => 'DEMO-TSHIRT',
                ],
                [
                    'slug' => 'demo-quan-jean-straight',
                    'name' => 'Quần jean straight fit',
                    'category' => 'Quần',
                    'brand' => 'Zara',
                    'price' => '599000.00',
                    'sale_price' => null,
                    'image' => 'products/demo-quan-jean-straight.jpg',
                    'sku_prefix' => 'DEMO-JEAN',
                ],
                [
                    'slug' => 'demo-vay-midi',
                    'name' => 'Váy midi xếp ly',
                    'category' => 'Váy',
                    'brand' => 'H&M',
                    'price' => '499000.00',
                    'sale_price' => '449000.00',
                    'image' => 'products/demo-vay-midi.jpg',
                    'sku_prefix' => 'DEMO-MIDI',
                ],
                [
                    'slug' => 'demo-ao-khoac-the-thao',
                    'name' => 'Áo khoác thể thao nhẹ',
                    'category' => 'Áo',
                    'brand' => 'Adidas',
                    'price' => '799000.00',
                    'sale_price' => null,
                    'image' => 'products/demo-ao-khoac-the-thao.jpg',
                    'sku_prefix' => 'DEMO-JACKET',
                ],
            ];

            foreach ($products as $productData) {
                $attributes = [
                    'category_id' => $categories[$productData['category']]->id,
                    'brand_id' => $brands[$productData['brand']]->id,
                    'name' => $productData['name'],
                    'description' => 'Sản phẩm mẫu dùng để kiểm thử API.',
                    'price' => $productData['price'],
                    'sale_price' => $productData['sale_price'],
                    'status' => 'active',
                    'image' => $productData['image'],
                    'quantity' => 0,
                ];

                $product = Product::firstOrCreate(
                    ['slug' => $productData['slug']],
                    $attributes
                );

                foreach ($attributes as $column => $expected) {
                    $actual = $product->getAttribute($column);

                    if (in_array($column, ['price', 'sale_price'], true)) {
                        $matches = $actual === null
                            ? $expected === null
                            : number_format((float) $actual, 2, '.', '') === $expected;
                    } else {
                        $matches = (string) $actual === (string) $expected;
                    }

                    if (! $matches) {
                        throw new RuntimeException(
                            "Product slug [{$productData['slug']}] exists with conflicting [{$column}] data."
                        );
                    }
                }

                foreach ([
                    $productData['image'] => true,
                    'products/alternate/'.$productData['slug'].'.jpg' => false,
                ] as $imageUrl => $isPrimary) {
                    ProductImage::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'image_url' => $imageUrl,
                        ],
                        [
                            'is_primary' => $isPrimary,
                            'sort_order' => $isPrimary ? 0 : 1,
                        ]
                    );
                }

                foreach ([
                    ['size' => 'S', 'color' => 'Đen', 'stock' => 8, 'color_code' => 'BLK'],
                    ['size' => 'M', 'color' => 'Trắng', 'stock' => 12, 'color_code' => 'WHT'],
                    ['size' => 'L', 'color' => 'Xám', 'stock' => 6, 'color_code' => 'GRY'],
                    ['size' => 'XL', 'color' => 'Xanh', 'stock' => 4, 'color_code' => 'BLU'],
                ] as $variantData) {
                    $sku = $productData['sku_prefix'].'-'.$variantData['size'].'-'.$variantData['color_code'];
                    $variant = ProductVariant::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'size_id' => $sizes[$variantData['size']]->id,
                            'color_id' => $colors[$variantData['color']]->id,
                        ],
                        [
                            'sku' => $sku,
                            'price' => $productData['price'],
                            'stock' => $variantData['stock'],
                            'status' => 'active',
                        ]
                    );

                    if ($variant->sku !== $sku) {
                        throw new RuntimeException(
                            "Product variant combination for [{$sku}] already exists with a different SKU."
                        );
                    }
                }
            }

            $startsAt = now()->subDay();
            $endsAt = now()->addYear();

            foreach ([
                [
                    'code' => 'WELCOME10',
                    'name' => 'Giảm 10% đơn hàng',
                    'discount_type' => 'percent',
                    'discount_value' => '10.00',
                    'min_order_value' => '300000.00',
                    'max_discount_amount' => '100000.00',
                    'usage_limit' => 500,
                ],
                [
                    'code' => 'FASHION50000',
                    'name' => 'Giảm 50.000 VND',
                    'discount_type' => 'fixed',
                    'discount_value' => '50000.00',
                    'min_order_value' => '500000.00',
                    'max_discount_amount' => null,
                    'usage_limit' => 300,
                ],
            ] as $promotionData) {
                Promotion::firstOrCreate(
                    ['code' => $promotionData['code']],
                    [
                        'name' => $promotionData['name'],
                        'description' => 'Mã khuyến mãi mẫu dùng để kiểm thử API.',
                        'discount_type' => $promotionData['discount_type'],
                        'discount_value' => $promotionData['discount_value'],
                        'min_order_value' => $promotionData['min_order_value'],
                        'max_discount_amount' => $promotionData['max_discount_amount'],
                        'usage_limit' => $promotionData['usage_limit'],
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'status' => 'active',
                    ]
                );
            }
        });
    }
}
