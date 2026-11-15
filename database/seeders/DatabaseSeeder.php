<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with Indonesian Rupiah prices.
     */
    public function run(): void
    {
        // 1. User Kasir & Admin (Sesuai Screenshot: Alex Morgan Admin)
        $admin = User::firstOrCreate(
            ['email' => 'alex@baknusdresto.com'],
            [
                'name' => 'Alex Morgan',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'kasir@baknusdresto.com'],
            [
                'name' => 'Sarah Cashier',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        // User Manajer (Tidak bertindak seperti kasir, fokus monitoring & laporan)
        $manager = User::firstOrCreate(
            ['email' => 'manager@baknusdresto.com'],
            [
                'name' => 'Hendro Wibowo',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'is_active' => true,
            ]
        );

        // 2. Active Shift Kasir (Starting cash Rp 500.000)
        $activeShift = Shift::create([
            'user_id' => $admin->id,
            'starting_cash' => 500000.00,
            'cash_sales' => 125000.00,
            'opened_at' => now()->startOfDay()->addHours(8),
            'status' => 'open',
            'notes' => 'Shift Pagi Kasir',
        ]);

        // 3. Tables (Meja Restoran)
        $tablesData = [
            ['table_number' => 'Meja 01', 'capacity' => 2, 'status' => 'available', 'floor_section' => 'Main Hall'],
            ['table_number' => 'Meja 02', 'capacity' => 4, 'status' => 'occupied', 'floor_section' => 'Main Hall'],
            ['table_number' => 'Meja 03', 'capacity' => 4, 'status' => 'available', 'floor_section' => 'Main Hall'],
            ['table_number' => 'Meja 04', 'capacity' => 6, 'status' => 'available', 'floor_section' => 'Main Hall'],
            ['table_number' => 'Meja 05', 'capacity' => 2, 'status' => 'reserved', 'floor_section' => 'Main Hall'],
            ['table_number' => 'VIP 01', 'capacity' => 8, 'status' => 'available', 'floor_section' => 'VIP Room'],
            ['table_number' => 'VIP 02', 'capacity' => 10, 'status' => 'available', 'floor_section' => 'VIP Room'],
            ['table_number' => 'Outdoor 01', 'capacity' => 4, 'status' => 'available', 'floor_section' => 'Terrace Garden'],
            ['table_number' => 'Outdoor 02', 'capacity' => 4, 'status' => 'available', 'floor_section' => 'Terrace Garden'],
        ];

        $tables = [];
        foreach ($tablesData as $t) {
            $tables[$t['table_number']] = RestaurantTable::create($t);
        }

        // 4. Categories
        $categoriesData = [
            ['name' => 'Starters', 'slug' => 'starters', 'icon' => 'cutlery', 'sort_order' => 1],
            ['name' => 'Soups', 'slug' => 'soups', 'icon' => 'soup', 'sort_order' => 2],
            ['name' => 'Salads', 'slug' => 'salads', 'icon' => 'salad', 'sort_order' => 3],
            ['name' => 'Main Course', 'slug' => 'main-course', 'icon' => 'dish', 'sort_order' => 4],
            ['name' => 'Pizza', 'slug' => 'pizza', 'icon' => 'pizza', 'sort_order' => 5],
            ['name' => 'Pasta', 'slug' => 'pasta', 'icon' => 'pasta', 'sort_order' => 6],
            ['name' => 'Burgers & Sandwiches', 'slug' => 'burgers-sandwiches', 'icon' => 'burger', 'sort_order' => 7],
            ['name' => 'Beverages', 'slug' => 'beverages', 'icon' => 'drink', 'sort_order' => 8],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::create($c);
        }

        // 5. Products (Harga Rupiah)
        $productsData = [
            // Starters
            [
                'category_id' => $categories['starters']->id,
                'code' => 'VSR-01',
                'barcode' => '899000101',
                'name' => 'Veg Spring Rolls',
                'description' => 'Lumpia renyah isi sayuran segar renyah gurih',
                'price' => 22000.00,
                'cost_price' => 10000.00,
                'stock' => 50,
                'icon_type' => 'cutlery',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['starters']->id,
                'code' => 'CW-02',
                'barcode' => '899000102',
                'name' => 'Chicken Wings',
                'description' => 'Sayap ayam krispi bumbu BBQ khas Baknus',
                'price' => 35000.00,
                'cost_price' => 18000.00,
                'stock' => 40,
                'icon_type' => 'cutlery',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['starters']->id,
                'code' => 'PT-03',
                'barcode' => '899000103',
                'name' => 'Paneer Tikka',
                'description' => 'Keju panggang rempah istimewa',
                'price' => 28000.00,
                'cost_price' => 14000.00,
                'stock' => 30,
                'icon_type' => 'cutlery',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['starters']->id,
                'code' => 'GB-04',
                'barcode' => '899000104',
                'name' => 'Garlic Bread',
                'description' => 'Roti panggang bawang putih mentega harum',
                'price' => 18000.00,
                'cost_price' => 8000.00,
                'stock' => 60,
                'icon_type' => 'cutlery',
                'is_available' => true,
            ],

            // Soups
            [
                'category_id' => $categories['soups']->id,
                'code' => 'TS-05',
                'barcode' => '899000105',
                'name' => 'Tomato Soup',
                'description' => 'Sup tomat kental hangat disajikan dengan crouton',
                'price' => 20000.00,
                'cost_price' => 9000.00,
                'stock' => 35,
                'icon_type' => 'soup',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['soups']->id,
                'code' => 'SCS-06',
                'barcode' => '899000106',
                'name' => 'Sweet Corn Soup',
                'description' => 'Sup jagung manis gurih lembut dengan mentega',
                'price' => 22000.00,
                'cost_price' => 10000.00,
                'stock' => 30,
                'icon_type' => 'soup',
                'is_available' => true,
            ],

            // Salads
            [
                'category_id' => $categories['salads']->id,
                'code' => 'CS-07',
                'barcode' => '899000107',
                'name' => 'Caesar Salad',
                'description' => 'Selada romaine segar dengan keju parmesan & dressing caesar',
                'price' => 30000.00,
                'cost_price' => 14000.00,
                'stock' => 25,
                'icon_type' => 'salad',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['salads']->id,
                'code' => 'GS-08',
                'barcode' => '899000108',
                'name' => 'Greek Salad',
                'description' => 'Salad mentimun, tomat, zaitun hitam dan keju feta',
                'price' => 32000.00,
                'cost_price' => 15000.00,
                'stock' => 25,
                'icon_type' => 'salad',
                'is_available' => true,
            ],

            // Main Course
            [
                'category_id' => $categories['main-course']->id,
                'code' => 'GC-09',
                'barcode' => '899000109',
                'name' => 'Grilled Chicken',
                'description' => 'Ayam panggang bumbu rempah dengan saus lada hitam & kentang',
                'price' => 48000.00,
                'cost_price' => 25000.00,
                'stock' => 20,
                'icon_type' => 'dish',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['main-course']->id,
                'code' => 'BC-10',
                'barcode' => '899000110',
                'name' => 'Butter Chicken',
                'description' => 'Ayam saus mentega tomat krim lembut kaya rasa',
                'price' => 45000.00,
                'cost_price' => 22000.00,
                'stock' => 20,
                'icon_type' => 'dish',
                'is_available' => true,
            ],

            // Pizza
            [
                'category_id' => $categories['pizza']->id,
                'code' => 'PZ-11',
                'barcode' => '899000111',
                'name' => 'Margherita Pizza',
                'description' => 'Pizza khas Napoli keju mozzarella leleh dan saus tomat segar',
                'price' => 55000.00,
                'cost_price' => 25000.00,
                'stock' => 30,
                'icon_type' => 'pizza',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['pizza']->id,
                'code' => 'PZ-12',
                'barcode' => '899000112',
                'name' => 'Pepperoni Supreme',
                'description' => 'Pizza dengan taburan beef pepperoni tebal dan keju melimpah',
                'price' => 65000.00,
                'cost_price' => 30000.00,
                'stock' => 25,
                'icon_type' => 'pizza',
                'is_available' => true,
            ],

            // Pasta
            [
                'category_id' => $categories['pasta']->id,
                'code' => 'PST-13',
                'barcode' => '899000113',
                'name' => 'Spaghetti Carbonara',
                'description' => 'Spaghetti saus krim telur gurih dengan smoked beef & parmesan',
                'price' => 42000.00,
                'cost_price' => 20000.00,
                'stock' => 30,
                'icon_type' => 'pasta',
                'is_available' => true,
            ],

            // Burgers & Sandwiches
            [
                'category_id' => $categories['burgers-sandwiches']->id,
                'code' => 'BGR-14',
                'barcode' => '899000114',
                'name' => 'Classic Cheeseburger',
                'description' => 'Burger daging sapi empuk, keju cheddar leleh, selada segar',
                'price' => 38000.00,
                'cost_price' => 18000.00,
                'stock' => 35,
                'icon_type' => 'burger',
                'is_available' => true,
            ],

            // Beverages
            [
                'category_id' => $categories['beverages']->id,
                'code' => 'DRK-15',
                'barcode' => '899000115',
                'name' => 'Iced Lemon Tea',
                'description' => 'Teh segar dengan irisan lemon asli menyegarkan',
                'price' => 12000.00,
                'cost_price' => 4000.00,
                'stock' => 100,
                'icon_type' => 'drink',
                'is_available' => true,
            ],
            [
                'category_id' => $categories['beverages']->id,
                'code' => 'DRK-16',
                'barcode' => '899000116',
                'name' => 'Fresh Orange Juice',
                'description' => 'Jus jeruk peras murni tanpa pemanis buatan',
                'price' => 18000.00,
                'cost_price' => 7000.00,
                'stock' => 60,
                'icon_type' => 'drink',
                'is_available' => true,
            ],
        ];

        $savedProducts = [];
        foreach ($productsData as $p) {
            $savedProducts[$p['code']] = Product::create($p);
        }

        // 6. Held Orders (Rupiah)
        $held1 = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-001',
            'user_id' => $admin->id,
            'table_id' => $tables['Meja 02']->id,
            'shift_id' => $activeShift->id,
            'customer_name' => 'Budi Santoso',
            'order_type' => 'dine-in',
            'status' => 'held',
            'subtotal' => 63000.00,
            'discount_type' => 'percent',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 10.00,
            'tax_amount' => 6300.00,
            'service_charge' => 0,
            'tip_amount' => 0,
            'total_amount' => 69300.00,
            'notes' => 'Tunggu teman datang',
        ]);

        OrderItem::create([
            'order_id' => $held1->id,
            'product_id' => $savedProducts['CW-02']->id,
            'product_name' => $savedProducts['CW-02']->name,
            'quantity' => 1,
            'unit_price' => 35000.00,
            'subtotal' => 35000.00,
            'notes' => 'Saus agak pedas',
        ]);

        OrderItem::create([
            'order_id' => $held1->id,
            'product_id' => $savedProducts['PT-03']->id,
            'product_name' => $savedProducts['PT-03']->name,
            'quantity' => 1,
            'unit_price' => 28000.00,
            'subtotal' => 28000.00,
            'notes' => '',
        ]);

        $held2 = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-002',
            'user_id' => $admin->id,
            'table_id' => null,
            'shift_id' => $activeShift->id,
            'customer_name' => 'Pelanggan Bungkus',
            'order_type' => 'takeaway',
            'status' => 'held',
            'subtotal' => 78000.00,
            'discount_type' => 'percent',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 10.00,
            'tax_amount' => 7800.00,
            'service_charge' => 0,
            'tip_amount' => 0,
            'total_amount' => 85800.00,
            'notes' => 'Bungkus rapi',
        ]);

        OrderItem::create([
            'order_id' => $held2->id,
            'product_id' => $savedProducts['GC-09']->id,
            'product_name' => $savedProducts['GC-09']->name,
            'quantity' => 1,
            'unit_price' => 48000.00,
            'subtotal' => 48000.00,
            'notes' => '',
        ]);

        OrderItem::create([
            'order_id' => $held2->id,
            'product_id' => $savedProducts['CS-07']->id,
            'product_name' => $savedProducts['CS-07']->name,
            'quantity' => 1,
            'unit_price' => 30000.00,
            'subtotal' => 30000.00,
            'notes' => 'Dressing pisah',
        ]);

        // 7. Completed Order (Sample Bills)
        $completedOrder = Order::create([
            'order_number' => 'ORD-' . date('Ymd') . '-000',
            'user_id' => $admin->id,
            'table_id' => $tables['Meja 01']->id,
            'shift_id' => $activeShift->id,
            'customer_name' => 'Walk-in',
            'order_type' => 'dine-in',
            'status' => 'completed',
            'subtotal' => 110000.00,
            'discount_type' => 'percent',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 10.00,
            'tax_amount' => 11000.00,
            'service_charge' => 0,
            'tip_amount' => 0,
            'total_amount' => 121000.00,
            'notes' => 'Lunas tunai',
        ]);

        $item1 = OrderItem::create([
            'order_id' => $completedOrder->id,
            'product_id' => $savedProducts['PZ-11']->id,
            'product_name' => $savedProducts['PZ-11']->name,
            'quantity' => 2,
            'unit_price' => 55000.00,
            'subtotal' => 110000.00,
        ]);

        Payment::create([
            'order_id' => $completedOrder->id,
            'user_id' => $admin->id,
            'invoice_number' => 'INV/' . date('Ymd') . '/0001',
            'payment_method' => 'cash',
            'amount_paid' => 150000.00,
            'change_due' => 29000.00,
            'paid_at' => now()->subHours(1),
        ]);

        $kot = KitchenOrder::create([
            'order_id' => $completedOrder->id,
            'kot_number' => 'KOT-' . date('Ymd') . '-001',
            'status' => 'served',
            'sent_to_kitchen_at' => now()->subHours(1)->subMinutes(30),
            'completed_at' => now()->subHours(1)->subMinutes(10),
        ]);

        KitchenOrderItem::create([
            'kitchen_order_id' => $kot->id,
            'order_item_id' => $item1->id,
            'quantity' => 2,
            'status' => 'ready',
        ]);

        // 8. Data Historis Pesanan 7 Hari Terakhir untuk Grafik Manajer
        $sampleItems = [
            ['prod' => $savedProducts['GC-09'], 'qty' => 3],
            ['prod' => $savedProducts['PZ-12'], 'qty' => 2],
            ['prod' => $savedProducts['CW-02'], 'qty' => 4],
            ['prod' => $savedProducts['BGR-14'], 'qty' => 3],
            ['prod' => $savedProducts['DRK-15'], 'qty' => 6],
            ['prod' => $savedProducts['PST-13'], 'qty' => 2],
        ];

        for ($i = 6; $i >= 1; $i--) {
            $date = now()->subDays($i);
            $ordersPerDay = rand(3, 7);

            for ($j = 1; $j <= $ordersPerDay; $j++) {
                $itemSample = $sampleItems[array_rand($sampleItems)];
                $subtotal = $itemSample['prod']->price * $itemSample['qty'];
                $tax = $subtotal * 0.1;
                $total = $subtotal + $tax;

                $histOrder = Order::create([
                    'order_number' => 'ORD-' . $date->format('Ymd') . '-' . str_pad($j, 3, '0', STR_PAD_LEFT),
                    'user_id' => $admin->id,
                    'table_id' => null,
                    'shift_id' => $activeShift->id,
                    'customer_name' => 'Pelanggan #' . $j,
                    'order_type' => $j % 2 == 0 ? 'dine-in' : 'takeaway',
                    'status' => 'completed',
                    'subtotal' => $subtotal,
                    'discount_type' => 'percent',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_rate' => 10.00,
                    'tax_amount' => $tax,
                    'service_charge' => 0,
                    'tip_amount' => 0,
                    'total_amount' => $total,
                    'notes' => 'Pesanan selesai',
                    'created_at' => $date->copy()->addHours(rand(10, 20)),
                    'updated_at' => $date->copy()->addHours(rand(10, 20)),
                ]);

                OrderItem::create([
                    'order_id' => $histOrder->id,
                    'product_id' => $itemSample['prod']->id,
                    'product_name' => $itemSample['prod']->name,
                    'quantity' => $itemSample['qty'],
                    'unit_price' => $itemSample['prod']->price,
                    'subtotal' => $subtotal,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                Payment::create([
                    'order_id' => $histOrder->id,
                    'user_id' => $admin->id,
                    'invoice_number' => 'INV/' . $date->format('Ymd') . '/' . str_pad($j, 4, '0', STR_PAD_LEFT),
                    'payment_method' => $j % 3 == 0 ? 'qris' : 'cash',
                    'amount_paid' => $total,
                    'change_due' => 0,
                    'paid_at' => $date,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
}
