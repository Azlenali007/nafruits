-- ========================================================
-- NA FRESH – Fruits & Coconuts
-- Database Seed Data (Matches Brand & Reference UI)
-- ========================================================

-- 1. Roles & Initial Admin
INSERT INTO `admin_roles` (`id`, `role_name`, `description`, `permissions`) VALUES
(1, 'Super Admin', 'Full unrestricted control of shop, settings, and finances', '{"all": true}'),
(2, 'Manager', 'Order fulfillment, inventory updates, and customer support', '{"orders": true, "inventory": true, "products": true}');

-- Password for admin: admin123 (BCRYPT hash)
INSERT INTO `admins` (`id`, `role_id`, `name`, `email`, `password_hash`, `phone`, `status`) VALUES
(1, 1, 'NA Fresh Admin', 'admin@nafresh.com', '$2y$10$wN9iL6M4eTz9uP6N1w0ZpeG2S.K0u5t0p5S5zV5hF2qL7kM9aVj9O', '+91 98765 43210', 'active');

-- 2. Initial Customer (customer@example.com / password123)
INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `password_hash`, `status`) VALUES
(1, 'Rahul Sharma', 'customer@example.com', '+91 98980 12345', '$2y$10$wN9iL6M4eTz9uP6N1w0ZpeG2S.K0u5t0p5S5zV5hF2qL7kM9aVj9O', 'active');

INSERT INTO `customer_addresses` (`id`, `customer_id`, `type`, `contact_name`, `contact_phone`, `address_line1`, `address_line2`, `landmark`, `city`, `state`, `pincode`, `is_default`) VALUES
(1, 1, 'Home', 'Rahul Sharma', '+91 98980 12345', '123 Green Park Colony', 'Near Annapurna Temple', 'Green Park Gate 2', 'Indore', 'Madhya Pradesh', '452001', 1),
(2, 1, 'Office', 'Rahul Sharma', '+91 98980 12345', 'Business Tower, MG Road', '4th Floor, Suite 402', 'Opposite City Center', 'Indore', 'Madhya Pradesh', '452001', 0),
(3, 1, 'Friends', 'Rahul Sharma', '+91 98980 12345', 'Near City Mall', 'A-Block 104', 'Behind Metro Pillar 32', 'Indore', 'Madhya Pradesh', '452010', 0);

-- 3. Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `icon`, `display_order`, `is_active`) VALUES
(1, 'Fresh Fruits', 'fresh-fruits', 'Farm-fresh, crisp, and nutrient-dense wholesome seasonal fruits.', 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?auto=format&fit=crop&w=600&q=80', 'apple', 1, 1),
(2, 'Coconut & Water', 'coconut-water', 'Tender green coconuts and raw chilled electrolyte coconut water.', 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80', 'nut', 2, 1),
(3, 'Popular Deals', 'popular-deals', 'Top-selling everyday fresh essentials loved by our customers.', 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?auto=format&fit=crop&w=600&q=80', 'flame', 3, 1),
(4, 'Seasonal Fruits', 'seasonal-fruits', 'Special limited-harvest exotic and summer fruits handpicked for sweetness.', 'https://images.unsplash.com/photo-1567306226416-28f0efdc88ce?auto=format&fit=crop&w=600&q=80', 'sun', 4, 1),
(5, 'Exotic & Berries', 'exotic-berries', 'Dragon fruits, imported kiwis, strawberries, and blueberries.', 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=600&q=80', 'sparkles', 5, 1);

-- 4. Products matching Reference UI
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `slug`, `short_description`, `description`, `main_image`, `cost_price`, `base_price`, `discount_price`, `default_unit`, `stock_quantity`, `is_featured`, `is_bestseller`, `is_popular`, `is_seasonal`, `badge_text`, `status`, `rating`, `review_count`) VALUES
(1, 2, 'NF-COC-001', 'Fresh Coconut Water', 'fresh-coconut-water', 'Pure and fresh coconut water, rich in electrolytes and natural goodness. Nature''s Energy Drink.', 'Pure and fresh coconut water, rich in electrolytes and natural goodness. Perfect for your health and energy. Served chilled in eco-friendly packaging or as fresh green coconut cut to order.', 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80', 22.00, 40.00, 40.00, 'piece', 120.00, 1, 1, 1, 0, '100% Natural', 'active', 4.8, 2400),
(2, 1, 'NF-FRU-001', 'Mango (Alphonso)', 'mango-alphonso', 'Rich, aromatic, and sweet Alphonso mangoes ripened naturally.', 'Handpicked authentic Alphonso mangoes directly sourced from coastal orchards. Chemical-free natural ripening delivers unmatched sweetness and velvety pulp.', 'https://images.unsplash.com/photo-1553279768-865429fa0078?auto=format&fit=crop&w=600&q=80', 75.00, 120.00, 120.00, 'kg', 85.00, 1, 1, 1, 1, 'Sweet & Ripe', 'active', 4.9, 850),
(3, 1, 'NF-FRU-002', 'Fresh Strawberry', 'fresh-strawberry', 'Ruby-red juicy strawberries packed with vitamins and antioxidants.', 'Crisp, sweet, and fragrant strawberries harvested at peak maturity. Ideal for direct snacking, breakfast bowls, or healthy smoothies.', 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=600&q=80', 60.00, 100.00, 100.00, 'kg', 45.00, 1, 0, 1, 1, 'Fresh Harvest', 'active', 4.7, 430),
(4, 1, 'NF-FRU-003', 'Crisp Royal Apple', 'crisp-royal-apple', 'Crunchy, sweet Royal Gala apples with bright red skin and juicy core.', 'Naturally waxed, crisp mountain apples selected for sweetness and firmness. High in soluble dietary fiber and wholesome vitamin C.', 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=600&q=80', 95.00, 150.00, 150.00, 'kg', 90.00, 1, 0, 1, 0, 'Crunchy', 'active', 4.8, 620),
(5, 3, 'NF-FRU-004', 'Robusta Banana', 'robusta-banana', 'Naturally ripened sweet bananas, creamy texture and energy-packed.', 'Grade-A golden yellow bananas ripened safely without carbide chemicals. Excellent quick snack and natural potassium powerhouse.', 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=600&q=80', 35.00, 60.00, 60.00, 'kg', 110.00, 0, 1, 1, 0, 'Everyday Pick', 'active', 4.6, 910),
(6, 3, 'NF-FRU-005', 'Sweet Pineapple', 'sweet-pineapple', 'Golden ripe pineapple with sweet citrus flavor and fragrant aroma.', 'Carefully trimmed queen pineapple with high juice content and balanced acidity. Perfect for fresh slices, juice, or salads.', 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?auto=format&fit=crop&w=600&q=80', 45.00, 80.00, 80.00, 'kg', 60.00, 0, 0, 1, 0, 'Juicy & Sweet', 'active', 4.7, 340),
(7, 2, 'NF-COC-002', 'Whole Tender Coconut', 'whole-tender-coconut', 'Uncut fresh green tender coconut packed with rich pure coconut water.', 'Whole green coconut containing 350-450ml of sweet electrolyte water and tender soft malai cream inside.', 'https://images.unsplash.com/photo-1544378730-8b5104b18790?auto=format&fit=crop&w=600&q=80', 25.00, 50.00, 50.00, 'piece', 150.00, 1, 1, 1, 0, 'Fresh Green Nut', 'active', 4.9, 1200),
(8, 1, 'NF-FRU-006', 'Nagpur Sweet Orange', 'nagpur-sweet-orange', 'Tangy and sweet high-juice oranges loaded with Vitamin C.', 'Freshly picked bright oranges bursting with sunny citrus juice. Easy to peel and perfect for daily wellness.', 'https://images.unsplash.com/photo-1582979512210-99b6a53386f9?auto=format&fit=crop&w=600&q=80', 50.00, 90.00, 90.00, 'kg', 70.00, 0, 0, 1, 0, 'High Vitamin C', 'active', 4.8, 380),
(9, 5, 'NF-FRU-007', 'Red Dragon Fruit', 'red-dragon-fruit', 'Vibrant magenta flesh dragon fruit with subtle sweetness and kiwi-like crunch.', 'Rich in prebiotic fibers, betalains, and vitamins. Striking exotic appearance and gentle refreshing flavor.', 'https://images.unsplash.com/photo-1527325678964-54921661f888?auto=format&fit=crop&w=600&q=80', 65.00, 110.00, 110.00, 'piece', 35.00, 1, 0, 0, 0, 'Exotic Superfood', 'active', 4.8, 190),
(10, 1, 'NF-FRU-008', 'Honey Drop Papaya', 'honey-drop-papaya', 'Sweet melt-in-mouth golden orange papaya rich in digestive papain enzymes.', 'Locally grown premium honey dew papaya, ripened naturally to deep orange perfection. Delicious breakfast fruit.', 'https://images.unsplash.com/photo-1617112848923-cc2234396a8d?auto=format&fit=crop&w=600&q=80', 28.00, 55.00, 55.00, 'kg', 50.00, 0, 0, 0, 0, 'Melt-in-Mouth', 'active', 4.6, 210);

-- Product Units (Support kg, 500g, 250g, 1 piece, etc. with exact prices!)
INSERT INTO `product_units` (`product_id`, `unit_name`, `unit_multiplier`, `price`, `original_price`, `stock_quantity`, `is_default`) VALUES
-- Fresh Coconut Water
(1, '1 Pack (350ml)', 1.000, 40.00, 45.00, 80, 1),
(1, '2 Packs (700ml)', 2.000, 75.00, 90.00, 40, 0),
-- Mango
(2, '1 kg', 1.000, 120.00, 140.00, 50, 1),
(2, '500 g', 0.500, 65.00, 70.00, 40, 0),
(2, '2 kg (Family Pack)', 2.000, 230.00, 280.00, 25, 0),
-- Strawberry
(3, '1 kg', 1.000, 100.00, 120.00, 30, 1),
(3, '500 g', 0.500, 55.00, 60.00, 30, 0),
(3, '250 g Box', 0.250, 30.00, 35.00, 40, 0),
-- Apple
(4, '1 kg', 1.000, 150.00, 180.00, 60, 1),
(4, '500 g', 0.500, 80.00, 90.00, 40, 0),
-- Banana
(5, '1 kg', 1.000, 60.00, 70.00, 80, 1),
(5, '500 g', 0.500, 32.00, 35.00, 50, 0),
-- Pineapple
(6, '1 kg', 1.000, 80.00, 95.00, 40, 1),
(6, '1 pc (approx 1.2kg)', 1.200, 95.00, 110.00, 30, 0),
-- Whole Tender Coconut
(7, '1 Piece', 1.000, 50.00, 55.00, 90, 1),
(7, 'Pack of 3', 3.000, 140.00, 165.00, 30, 0),
-- Orange
(8, '1 kg', 1.000, 90.00, 105.00, 50, 1),
(8, '500 g', 0.500, 48.00, 55.00, 30, 0),
-- Dragon fruit
(9, '1 Piece', 1.000, 110.00, 130.00, 25, 1),
-- Papaya
(10, '1 kg', 1.000, 55.00, 65.00, 40, 1);

-- Product Additional Images
INSERT INTO `product_images` (`product_id`, `image_url`, `display_order`) VALUES
(1, 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80', 1),
(1, 'https://images.unsplash.com/photo-1544378730-8b5104b18790?auto=format&fit=crop&w=600&q=80', 2),
(1, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=600&q=80', 3),
(2, 'https://images.unsplash.com/photo-1553279768-865429fa0078?auto=format&fit=crop&w=600&q=80', 1),
(3, 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=600&q=80', 1);

-- 5. Homepage Sections
INSERT INTO `homepage_sections` (`id`, `title`, `slug`, `subtitle`, `category_id`, `section_type`, `display_order`, `is_active`) VALUES
(1, 'Fresh Fruits', 'fresh-fruits-section', 'Crisp, sweet, and handpicked daily for your home', 1, 'carousel', 1, 1),
(2, 'Popular', 'popular-section', 'Customer favorites and best-value picks today', 3, 'carousel', 2, 1),
(3, 'Coconut & Coconut Water', 'coconut-water-section', 'Freshly trimmed coconuts & chilled natural hydration', 2, 'carousel', 3, 1),
(4, 'Seasonal Specials', 'seasonal-specials-section', 'Limited season favorites straight from the orchard', 4, 'carousel', 4, 1);

-- Section Product Links
INSERT INTO `homepage_section_products` (`section_id`, `product_id`, `display_order`) VALUES
(1, 2, 1), -- Mango
(1, 3, 2), -- Strawberry
(1, 4, 3), -- Apple
(1, 8, 4), -- Orange
(2, 1, 1), -- Coconut Water
(2, 5, 2), -- Banana
(2, 6, 3), -- Pineapple
(2, 2, 4), -- Mango
(3, 1, 1), -- Fresh Coconut Water
(3, 7, 2), -- Whole Tender Coconut
(4, 2, 1), -- Mango
(4, 3, 2), -- Strawberry
(4, 9, 3); -- Dragon fruit

-- 6. Delivery Zones (Indore)
INSERT INTO `delivery_zones` (`id`, `pincode`, `area_name`, `city`, `delivery_fee`, `min_order_amount`, `estimated_minutes`, `is_active`) VALUES
(1, '452001', 'Green Park, MG Road, Rajwada, South Tukoganj', 'Indore', 40.00, 100.00, '30 - 45 minutes', 1),
(2, '452010', 'Vijay Nagar, Scheme 54, City Mall', 'Indore', 40.00, 100.00, '35 - 50 minutes', 1),
(3, '452002', 'Old Palasia, New Palasia, Race Course Road', 'Indore', 35.00, 100.00, '25 - 40 minutes', 1),
(4, '452016', 'Bhawarkua, Transport Nagar, AB Road', 'Indore', 45.00, 120.00, '35 - 50 minutes', 1);

-- Delivery Slots
INSERT INTO `delivery_slots` (`id`, `title`, `start_time`, `end_time`, `max_orders`, `is_active`) VALUES
(1, 'Instant Delivery (30 - 45 minutes)', '07:00:00', '21:30:00', 80, 1),
(2, 'Morning Slot (07:00 AM - 10:00 AM)', '07:00:00', '10:00:00', 40, 1),
(3, 'Noon Slot (12:00 PM - 03:00 PM)', '12:00:00', '15:00:00', 40, 1),
(4, 'Evening Slot (05:00 PM - 08:30 PM)', '17:00:00', '20:30:00', 50, 1);

-- 7. Coupons
INSERT INTO `coupons` (`id`, `code`, `type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `usage_limit_total`, `usage_limit_per_user`, `start_date`, `end_date`, `is_active`) VALUES
(1, 'FRESH50', 'fixed', 50.00, 200.00, 50.00, 500, 1, '2025-01-01', '2027-12-31', 1),
(2, 'COCONUT10', 'percentage', 10.00, 150.00, 100.00, 1000, 3, '2025-01-01', '2027-12-31', 1),
(3, 'NAFRESHFIRST', 'percentage', 15.00, 250.00, 75.00, 2000, 1, '2025-01-01', '2027-12-31', 1);

-- 8. Sample Historical Orders matching screenshot UI (#NF202510081023, #NF202509280456, #NF202509150321)
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `delivery_pincode`, `delivery_slot`, `subtotal_amount`, `delivery_fee`, `discount_amount`, `coupon_code`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `created_at`) VALUES
(1, 'NF202510081023', 1, 'Rahul Sharma', 'customer@example.com', '+91 98980 12345', '123 Green Park Colony, Indore, Madhya Pradesh - 452001', '452001', 'Instant (30-45 mins)', 260.00, 40.00, 0.00, NULL, 300.00, 'upi_qr', 'paid', 'processing', '2025-10-08 10:23:00'),
(2, 'NF202509280456', 1, 'Rahul Sharma', 'customer@example.com', '+91 98980 12345', '123 Green Park Colony, Indore, Madhya Pradesh - 452001', '452001', 'Morning Slot (07:00 AM - 10:00 AM)', 480.00, 0.00, 0.00, NULL, 480.00, 'razorpay', 'paid', 'delivered', '2025-09-28 09:45:00'),
(3, 'NF202509150321', 1, 'Rahul Sharma', 'customer@example.com', '+91 98980 12345', 'Business Tower, MG Road, Indore - 452001', '452001', 'Instant (30-45 mins)', 210.00, 0.00, 0.00, NULL, 210.00, 'cod', 'paid', 'delivered', '2025-09-15 14:15:00');

INSERT INTO `order_items` (`order_id`, `product_id`, `unit_id`, `product_name`, `unit_name`, `price`, `quantity`, `total_price`, `product_image`) VALUES
(1, 1, 1, 'Fresh Coconut Water', '1 Pack (350ml)', 40.00, 1.00, 40.00, 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80'),
(1, 2, 3, 'Mango (Alphonso)', '1 kg', 120.00, 1.00, 120.00, 'https://images.unsplash.com/photo-1553279768-865429fa0078?auto=format&fit=crop&w=600&q=80'),
(1, 3, 6, 'Fresh Strawberry', '1 kg', 100.00, 1.00, 100.00, 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=600&q=80'),
(2, 1, 2, 'Fresh Coconut Water', '2 Packs (700ml)', 75.00, 2.00, 150.00, 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?auto=format&fit=crop&w=600&q=80'),
(2, 4, 9, 'Crisp Royal Apple', '1 kg', 150.00, 2.00, 300.00, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=600&q=80'),
(2, 5, 11, 'Robusta Banana', '500 g', 32.00, 1.00, 32.00, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=600&q=80'),
(3, 7, 14, 'Whole Tender Coconut', 'Pack of 3', 140.00, 1.00, 140.00, 'https://images.unsplash.com/photo-1544378730-8b5104b18790?auto=format&fit=crop&w=600&q=80'),
(3, 5, 11, 'Robusta Banana', '1 kg', 60.00, 1.00, 60.00, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=600&q=80');

INSERT INTO `order_status_history` (`order_id`, `status`, `notes`, `changed_by`) VALUES
(1, 'pending', 'Order placed by customer via UPI QR', 'Customer'),
(1, 'confirmed', 'UPI transaction reference verified', 'Admin'),
(1, 'processing', 'Fruits packed fresh in insulated bag', 'Inventory Staff'),
(2, 'pending', 'Order placed via Razorpay', 'Customer'),
(2, 'confirmed', 'Payment verified', 'System'),
(2, 'processing', 'Order prepared', 'Staff'),
(2, 'out_for_delivery', 'Rider assigned', 'Dispatch'),
(2, 'delivered', 'Delivered to Rahul Sharma at home', 'Rider'),
(3, 'delivered', 'Cash collected at doorstep: ₹210', 'Delivery Staff');

-- 9. Announcements
INSERT INTO `announcements` (`id`, `title`, `message`, `type`, `target_url`, `action_button_text`, `is_active`) VALUES
(1, 'Summer Special Fresh Coconuts!', 'Get naturally chilled tender coconut water delivered fresh within 35 minutes across Indore.', 'popup', '/category.php?slug=coconut-water', 'Order Now', 1),
(2, 'Join NA Fresh Telegram Club', 'Get daily secret coupon codes, fresh arrival alerts, and orchard updates on our Telegram channel.', 'telegram', 'https://t.me/nafreshfruits', 'Join Telegram', 1);

-- 10. Shop Settings
INSERT INTO `shop_settings` (`key_name`, `key_value`) VALUES
('shop_name', 'Na Fresh – Fruits & Coconuts'),
('shop_tagline', 'Freshness in Every Bite'),
('shop_phone', '+91 98765 43210'),
('shop_email', 'support@nafresh.com'),
('shop_address', 'Shop 12, Annapurna Main Road, Indore, MP 452009'),
('currency_symbol', '₹'),
('currency_code', 'INR'),
('delivery_fee_default', '40.00'),
('free_delivery_threshold', '499.00'),
('min_order_amount', '100.00'),
('maintenance_mode', '0'),
('upi_id', 'nafresh@icici'),
('upi_payee_name', 'NA Fresh Organics'),
('upi_qr_image', 'assets/images/sample-upi-qr.png'),
('razorpay_enabled', '1'),
('razorpay_key_id', 'rzp_test_sample_key_nafresh'),
('razorpay_key_secret', 'sample_secret_key_nafresh'),
('cod_enabled', '1'),
('upi_enabled', '1'),
('bank_transfer_enabled', '1'),
('bank_account_details', 'Bank: HDFC Bank | A/C: 50200012345678 | IFSC: HDFC0001234 | Branch: Indore South'),
('telegram_channel_url', 'https://t.me/nafreshfruits'),
('whatsapp_number', '+919876543210'),
('opening_time', '07:00 AM'),
('closing_time', '09:30 PM');
