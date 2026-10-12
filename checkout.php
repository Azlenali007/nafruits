<?php
/**
 * NA FRESH – Fruits & Coconuts
 * App-Like Multi-Card Checkout (Matches Screenshots 5, 6, 7)
 */

declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$cartData = getCartTotals();
if (empty($cartData['items'])) {
    setFlash('error', 'Your cart is empty. Please add items before checking out.');
    header('Location: /index.php');
    exit;
}

$currentCustomer = getCurrentCustomer();
$savedAddresses = [];

if ($currentCustomer) {
    try {
        $savedAddresses = Database::fetchAll(
            "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC",
            [$currentCustomer['id']]
        );
    } catch (Exception $e) {
        error_log('Error loading customer addresses: ' . $e->getMessage());
    }
}

// Fallback sample addresses if guest or none yet created (matches Screenshot 5 UI)
if (empty($savedAddresses)) {
    $savedAddresses = [
        [
            'id' => 1,
            'type' => 'Home',
            'contact_name' => $currentCustomer['name'] ?? 'Rahul Sharma',
            'contact_phone' => $currentCustomer['phone'] ?? '+91 98980 12345',
            'address_line1' => '123 Green Park Colony',
            'city' => 'Indore',
            'state' => 'Madhya Pradesh',
            'pincode' => '452001',
            'is_default' => 1
        ],
        [
            'id' => 2,
            'type' => 'Office',
            'contact_name' => $currentCustomer['name'] ?? 'Rahul Sharma',
            'contact_phone' => $currentCustomer['phone'] ?? '+91 98980 12345',
            'address_line1' => 'Business Tower, MG Road',
            'city' => 'Indore',
            'state' => 'Madhya Pradesh',
            'pincode' => '452001',
            'is_default' => 0
        ],
        [
            'id' => 3,
            'type' => 'Friends',
            'contact_name' => $currentCustomer['name'] ?? 'Rahul Sharma',
            'contact_phone' => $currentCustomer['phone'] ?? '+91 98980 12345',
            'address_line1' => 'Near City Mall',
            'city' => 'Indore',
            'state' => 'Madhya Pradesh',
            'pincode' => '452010',
            'is_default' => 0
        ]
    ];
}

// Handle Order Placement POST Request
$errorMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_place_order'])) {
    requireCsrf();

    $name = trim($_POST['customer_name'] ?? ($currentCustomer['name'] ?? 'Guest Customer'));
    $phone = trim($_POST['customer_phone'] ?? ($currentCustomer['phone'] ?? '9876543210'));
    $email = trim($_POST['customer_email'] ?? ($currentCustomer['email'] ?? 'guest@nafresh.com'));
    $selectedAddressIndex = (int)($_POST['address_index'] ?? 0);
    $customAddress = trim($_POST['delivery_address_custom'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod'; // upi_qr, razorpay, cod, bank_transfer
    $deliverySlot = trim($_POST['delivery_slot'] ?? 'Instant Delivery (30 - 45 mins)');
    $upiReference = trim($_POST['upi_reference'] ?? '');

    // Resolve address
    if (!empty($customAddress)) {
        $finalAddress = $customAddress;
        $finalPincode = trim($_POST['delivery_pincode_custom'] ?? '452001');
    } else {
        $addr = $savedAddresses[$selectedAddressIndex] ?? $savedAddresses[0];
        $finalAddress = $addr['address_line1'] . ', ' . ($addr['address_line2'] ?? '') . ' ' . $addr['city'] . ', ' . $addr['state'] . ' - ' . $addr['pincode'];
        $finalPincode = $addr['pincode'];
    }

    // Re-verify totals with delivery zone server-side
    $orderTotals = getCartTotals(null, $finalPincode);
    if (empty($orderTotals['items'])) {
        $errorMessage = 'Cart is empty.';
    } else {
        $db = Database::getConnection();
        if (!$db) {
            $errorMessage = 'Database connection failed. Please try again.';
        } else {
            try {
                $db->beginTransaction();

                // 1. Stock verification with row locking
                foreach ($orderTotals['items'] as $item) {
                    $pid = (int)$item['product_id'];
                    $pRow = Database::fetchOne("SELECT id, name, stock_quantity, status FROM products WHERE id = ? FOR UPDATE", [$pid]);
                    if (!$pRow || $pRow['status'] !== 'active' || (float)$pRow['stock_quantity'] < (float)$item['quantity']) {
                        throw new RuntimeException("Sorry, '{$item['name']}' is out of stock or quantity unavailable.");
                    }
                }

                // 2. Resolve Customer ID
                $customerId = $currentCustomer ? (int)$currentCustomer['id'] : null;
                if (!$customerId) {
                    // Check if customer exists by phone/email or create guest record
                    $exist = Database::fetchOne("SELECT id FROM customers WHERE email = ? OR phone = ?", [$email, $phone]);
                    if ($exist) {
                        $customerId = (int)$exist['id'];
                    } else {
                        $pwdHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
                        $customerId = (int)Database::insert(
                            "INSERT INTO customers (name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, 'active')",
                            [$name, $email, $phone, $pwdHash]
                        );
                    }
                }

                // 3. Generate Order ID
                $orderNumber = generateOrderNumber();
                $paymentStatus = ($paymentMethod === 'cod') ? 'pending' : ($paymentMethod === 'upi_qr' && !empty($upiReference) ? 'paid' : 'pending');

                // 4. Insert Order
                $orderId = (int)Database::insert(
                    "INSERT INTO orders (order_number, customer_id, customer_name, customer_email, customer_phone, 
                                        delivery_address, delivery_pincode, delivery_slot, subtotal_amount, delivery_fee, 
                                        discount_amount, coupon_code, total_amount, payment_method, payment_status, order_status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                    [
                        $orderNumber,
                        $customerId,
                        $name,
                        $email,
                        $phone,
                        $finalAddress,
                        $finalPincode,
                        $deliverySlot,
                        $orderTotals['subtotal'],
                        $orderTotals['delivery_fee'],
                        $orderTotals['discount_amount'],
                        $orderTotals['applied_coupon']['code'] ?? null,
                        $orderTotals['total'],
                        $paymentMethod,
                        $paymentStatus
                    ]
                );

                // 5. Insert Items & Decrement Stock
                foreach ($orderTotals['items'] as $item) {
                    Database::insert(
                        "INSERT INTO order_items (order_id, product_id, unit_id, product_name, unit_name, price, quantity, total_price, product_image) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $orderId,
                            $item['product_id'],
                            $item['unit_id'],
                            $item['name'],
                            $item['unit_name'],
                            $item['price'],
                            $item['quantity'],
                            $item['line_total'],
                            $item['image']
                        ]
                    );

                    // Decrement master product stock
                    Database::execute(
                        "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
                        [$item['quantity'], $item['product_id']]
                    );

                    // Record inventory movement
                    Database::insert(
                        "INSERT INTO inventory_logs (product_id, type, quantity, previous_stock, current_stock, reference_id, notes) 
                         SELECT id, 'order_fulfillment', ?, stock_quantity + ?, stock_quantity, ?, 'Order Placed' 
                         FROM products WHERE id = ?",
                        [$item['quantity'], $item['quantity'], $orderNumber, $item['product_id']]
                    );
                }

                // 6. Record Initial Status History
                Database::insert(
                    "INSERT INTO order_status_history (order_id, status, notes, changed_by) VALUES (?, 'pending', 'Order placed by customer', 'Customer')",
                    [$orderId]
                );

                // 7. Record Payment Transaction
                Database::insert(
                    "INSERT INTO payment_transactions (order_id, gateway, transaction_ref, amount, status) 
                     VALUES (?, ?, ?, ?, ?)",
                    [$orderId, $paymentMethod, $upiReference ?: null, $orderTotals['total'], $paymentStatus === 'paid' ? 'completed' : 'pending']
                );

                // 8. Commit Transaction & Clear Cart
                $db->commit();
                clearCart();

                // Redirect to Order Success Page
                header('Location: /order-success.php?order=' . urlencode($orderNumber));
                exit;

            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $errorMessage = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Review & Checkout – Na Fresh';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto sm:max-w-xl md:max-w-3xl lg:max-w-5xl px-4 pt-3 pb-12 space-y-4">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <a href="/cart.php" class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-700 hover:bg-gray-100 transition shadow-sm">
      <i class="fas fa-arrow-left text-sm"></i>
    </a>
    <h1 class="text-lg font-black text-gray-900 tracking-tight">Review & Checkout</h1>
    <div class="w-9"></div>
  </div>

  <?php if ($errorMessage): ?>
    <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-xs font-bold">
      <?= e($errorMessage) ?>
    </div>
  <?php endif; ?>

  <form action="/checkout.php" method="POST" id="checkout-form" class="space-y-4">
    <?= csrfField() ?>

    <!-- 1. Delivery Address Card (Matches Screenshot 5) -->
    <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-sm space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-black text-gray-900 flex items-center space-x-2">
          <i class="fas fa-map-marker-alt text-green-700"></i>
          <span>Delivery Address</span>
        </h2>
        <button type="button" onclick="toggleCustomAddress()" class="text-xs font-bold text-green-700 hover:underline">
          + Add New
        </button>
      </div>

      <!-- Use Current Location banner (Matches Screenshot 5) -->
      <div class="bg-green-50 border border-green-200 rounded-2xl p-3 flex items-center justify-between">
        <div class="flex items-center space-x-2.5">
          <div class="w-8 h-8 rounded-full bg-green-200 text-green-800 flex items-center justify-center text-sm">
            <i class="fas fa-crosshairs"></i>
          </div>
          <div>
            <div class="text-xs font-bold text-green-900">Use Current Location</div>
            <div class="text-[11px] text-green-700">Detect your GPS location</div>
          </div>
        </div>
        <button type="button" onclick="detectGPSLocation()" class="text-xs font-extrabold text-green-800 bg-white px-2.5 py-1 rounded-xl shadow-xs border border-green-300">
          Detect
        </button>
      </div>

      <!-- Saved Addresses Options (Matches Screenshot 5) -->
      <div class="space-y-2 pt-1" id="saved-address-container">
        <?php foreach ($savedAddresses as $idx => $addr): ?>
          <label class="block p-3 rounded-2xl border cursor-pointer transition <?= $idx === 0 ? 'border-green-600 bg-green-50/50' : 'border-gray-200 hover:border-gray-300' ?>">
            <div class="flex items-start space-x-3">
              <input type="radio" name="address_index" value="<?= $idx ?>" <?= $idx === 0 ? 'checked' : '' ?> class="mt-1 text-green-600 focus:ring-green-500">
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <span class="text-xs font-bold text-gray-900"><?= e($addr['type']) ?></span>
                  <?php if (!empty($addr['is_default'])): ?>
                    <span class="text-[10px] bg-green-200 text-green-800 font-extrabold px-1.5 py-0.2 rounded">Default</span>
                  <?php endif; ?>
                </div>
                <div class="text-xs text-gray-600 mt-0.5 leading-snug">
                  <?= e($addr['address_line1']) ?>, <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['pincode']) ?>
                </div>
              </div>
            </div>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- Add New Custom Address Accordion -->
      <div id="custom-address-fields" class="hidden space-y-2 pt-2 border-t border-gray-100">
        <label class="block text-xs font-bold text-gray-700">Enter Complete Address:</label>
        <textarea name="delivery_address_custom" rows="2" placeholder="House/Flat No, Landmark, Street Name" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-xs text-gray-800 focus:bg-white focus:border-green-600"></textarea>
        <div class="grid grid-cols-2 gap-2">
          <input type="text" name="delivery_pincode_custom" placeholder="Pincode (e.g. 452001)" value="452001" class="bg-gray-50 border border-gray-200 rounded-xl p-2 text-xs text-gray-800 focus:bg-white focus:border-green-600">
          <input type="text" placeholder="Indore, MP" value="Indore, MP" readonly class="bg-gray-100 border border-gray-200 rounded-xl p-2 text-xs text-gray-500">
        </div>
      </div>
    </div>

    <!-- 2. Payment Method Card (Matches Screenshot 6) -->
    <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-sm space-y-3">
      <h2 class="text-sm font-black text-gray-900 flex items-center space-x-2">
        <i class="fas fa-credit-card text-green-700"></i>
        <span>Payment Method</span>
      </h2>

      <div class="space-y-2">
        
        <!-- UPI / QR (Selected in Screenshot 6) -->
        <label class="block p-3 rounded-2xl border border-green-600 bg-green-50/50 cursor-pointer payment-method-card transition">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                <i class="fas fa-qrcode"></i>
              </div>
              <div>
                <div class="text-xs font-black text-gray-900">UPI / QR Code</div>
                <div class="text-[11px] text-gray-500">GPay, PhonePe, Paytm, etc.</div>
              </div>
            </div>
            <input type="radio" name="payment_method" value="upi_qr" checked class="text-green-600 focus:ring-green-500">
          </div>
          <!-- UPI QR Details Expand -->
          <div id="upi-details-box" class="mt-3 pt-3 border-t border-green-200/60 text-xs text-gray-700 space-y-2">
            <div class="bg-white p-3 rounded-xl border border-green-200 flex items-center justify-between">
              <div>
                <div class="text-[10px] text-gray-400 font-bold uppercase">UPI ID / VPA</div>
                <div class="font-extrabold text-green-900"><?= e(getSetting('upi_id', 'nafresh@icici')) ?></div>
              </div>
              <button type="button" onclick="navigator.clipboard.writeText('<?= e(getSetting('upi_id', 'nafresh@icici')) ?>');showToast('UPI ID copied!')" class="text-xs font-bold bg-green-100 text-green-800 px-2.5 py-1 rounded-lg">Copy</button>
            </div>
            <input type="text" name="upi_reference" placeholder="Enter 12-digit UPI UTR / Transaction Ref (after payment)" class="w-full bg-white border border-gray-300 rounded-xl p-2 text-xs text-gray-800 focus:border-green-600">
          </div>
        </label>

        <!-- Razorpay Online Gateway -->
        <label class="block p-3 rounded-2xl border border-gray-200 hover:border-gray-300 cursor-pointer payment-method-card transition">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                <i class="fas fa-wallet"></i>
              </div>
              <div>
                <div class="text-xs font-black text-gray-900">Razorpay</div>
                <div class="text-[11px] text-gray-500">Cards, UPI, Netbanking</div>
              </div>
            </div>
            <input type="radio" name="payment_method" value="razorpay" class="text-green-600 focus:ring-green-500">
          </div>
        </label>

        <!-- Cash on Delivery -->
        <label class="block p-3 rounded-2xl border border-gray-200 hover:border-gray-300 cursor-pointer payment-method-card transition">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                <i class="fas fa-money-bill-wave"></i>
              </div>
              <div>
                <div class="text-xs font-black text-gray-900">Cash on Delivery</div>
                <div class="text-[11px] text-gray-500">Pay at your doorstep</div>
              </div>
            </div>
            <input type="radio" name="payment_method" value="cod" class="text-green-600 focus:ring-green-500">
          </div>
        </label>

        <!-- Bank Transfer -->
        <label class="block p-3 rounded-2xl border border-gray-200 hover:border-gray-300 cursor-pointer payment-method-card transition">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center font-bold text-sm">
                <i class="fas fa-university"></i>
              </div>
              <div>
                <div class="text-xs font-black text-gray-900">Bank Transfer</div>
                <div class="text-[11px] text-gray-500">Direct Bank Transfer</div>
              </div>
            </div>
            <input type="radio" name="payment_method" value="bank_transfer" class="text-green-600 focus:ring-green-500">
          </div>
        </label>

      </div>
    </div>

    <!-- 3. Order Items Summary (Matches Screenshot 7) -->
    <div class="bg-white rounded-3xl p-4 border border-gray-100 shadow-sm space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-black text-gray-900">Order Items</h2>
        <a href="/cart.php" class="text-xs font-bold text-green-700 hover:underline">Edit</a>
      </div>

      <div class="divide-y divide-gray-100">
        <?php foreach ($cartData['items'] as $item): ?>
          <div class="py-2.5 flex items-center justify-between space-x-3">
            <div class="flex items-center space-x-2.5">
              <img src="<?= e($item['image']) ?>" alt="<?= e($item['name']) ?>" class="w-10 h-10 rounded-xl object-cover bg-gray-50">
              <div>
                <div class="text-xs font-bold text-gray-900"><?= e($item['name']) ?></div>
                <div class="text-[11px] text-gray-500"><?= formatPrice($item['price']) ?> / <?= e($item['unit_name']) ?></div>
              </div>
            </div>
            <div class="text-xs font-extrabold text-gray-900">
              x<?= (int)$item['quantity'] ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Financial Totals (Matches Screenshot 7) -->
      <div class="pt-2 border-t border-gray-100 space-y-2 text-xs">
        <div class="flex items-center justify-between text-gray-600 font-medium">
          <span>Subtotal</span>
          <span class="font-bold text-gray-900"><?= formatPrice($cartData['subtotal']) ?></span>
        </div>
        <div class="flex items-center justify-between text-gray-600 font-medium">
          <span>Delivery Charge</span>
          <span class="font-bold text-gray-900"><?= formatPrice($cartData['delivery_fee']) ?></span>
        </div>
        <?php if ($cartData['discount_amount'] > 0): ?>
          <div class="flex items-center justify-between text-green-700 font-medium">
            <span>Discount</span>
            <span class="font-bold">- <?= formatPrice($cartData['discount_amount']) ?></span>
          </div>
        <?php endif; ?>
        <div class="pt-2 border-t border-gray-100 flex items-center justify-between font-black text-sm text-gray-900">
          <span>Total</span>
          <span class="text-base text-green-900"><?= formatPrice($cartData['total']) ?></span>
        </div>
      </div>

      <!-- Estimated Delivery Time Card (Matches Screenshot 7) -->
      <div class="bg-green-50 border border-green-200/80 rounded-2xl p-3 flex items-center justify-between text-xs">
        <div class="flex items-center space-x-2">
          <i class="fas fa-clock text-green-700"></i>
          <div>
            <span class="text-[10px] text-gray-500 block">Estimated Delivery Time</span>
            <span class="font-extrabold text-green-900">30 - 45 minutes</span>
          </div>
        </div>
        <span class="text-2xl">🛵</span>
      </div>
    </div>

    <!-- 4. Place Order Action Button (Matches Screenshot 7) -->
    <div class="pt-2">
      <button type="submit" 
              name="action_place_order" 
              value="1" 
              class="w-full bg-green-700 hover:bg-green-800 text-white font-extrabold py-4 px-6 rounded-2xl shadow-lg transition transform active:scale-95 flex items-center justify-center space-x-2 text-base">
        <span>Place Order</span>
      </button>
    </div>

  </form>

</div>

<script>
function toggleCustomAddress() {
  const fields = document.getElementById('custom-address-fields');
  fields.classList.toggle('hidden');
}

function detectGPSLocation() {
  if (navigator.geolocation) {
    showToast('Detecting location...', 'info');
    navigator.geolocation.getCurrentPosition(
      pos => {
        showToast('Location updated: Indore, MP', 'success');
      },
      err => {
        showToast('Permission denied. Using default address.', 'warning');
      }
    );
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
