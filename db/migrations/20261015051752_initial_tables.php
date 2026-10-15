<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialTables extends AbstractMigration
{
    public function change(): void
    {
        // 1. ตาราง users (สำหรับระบบ Login)
        $users = $this->table('users');
        $users->addColumn('username', 'string', ['limit' => 50])
              ->addColumn('password', 'string', ['limit' => 255])
              ->addColumn('fullname', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('role', 'string', ['limit' => 20, 'default' => 'staff'])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();

        // 2. ตาราง customers (ข้อมูลลูกค้า)
        $customers = $this->table('customers');
        $customers->addColumn('customer_name', 'string', ['limit' => 100])
                  ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
                  ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
                  ->addColumn('address', 'text', ['null' => true])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->create();

        // 3. ตาราง suppliers (ข้อมูลผู้จัดจำหน่าย)
        $suppliers = $this->table('suppliers');
        $suppliers->addColumn('supplier_name', 'string', ['limit' => 100])
                  ->addColumn('contact_person', 'string', ['limit' => 100, 'null' => true])
                  ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
                  ->addColumn('address', 'text', ['null' => true])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->create();

        // 4. ตาราง products (สินค้าและสต็อก)
        $products = $this->table('products');
        $products->addColumn('product_name', 'string', ['limit' => 150])
                 ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2])
                 ->addColumn('stock_qty', 'integer', ['default' => 0])
                 ->addColumn('description', 'text', ['null' => true])
                 ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                 ->create();

        // 5. ตาราง orders (รายการสั่งซื้อ)
        $orders = $this->table('orders');
        $orders->addColumn('order_code', 'string', ['limit' => 50, 'null' => true])
               ->addColumn('customer_id', 'integer')
               ->addColumn('total_amount', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00])
               ->addColumn('status', 'string', ['limit' => 30, 'default' => 'pending'])
               ->addColumn('slip_image', 'string', ['limit' => 255, 'null' => true])
               ->addColumn('order_date', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
               ->create();

        // 6. ตาราง order_details (รายละเอียดสินค้าในแต่ละออเดอร์)
        $orderDetails = $this->table('order_details');
        $orderDetails->addColumn('order_id', 'integer')
                     ->addColumn('product_id', 'integer')
                     ->addColumn('quantity', 'integer')
                     ->addColumn('unit_price', 'decimal', ['precision' => 10, 'scale' => 2])
                     ->addColumn('subtotal', 'decimal', ['precision' => 10, 'scale' => 2])
                     ->create();
    }
}