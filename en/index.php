<?php
// en/index.php
session_start();

// Include files
try {
    require_once 'assets/config.php';
    require_once 'stripe/init.php';
} catch (Exception $e) {
    error_log('Include Error: ' . $e->getMessage());
    die('Server error. Please try again later.');
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;
    if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
        // Validate form data
        $length = filter_input(INPUT_POST, 'length', FILTER_VALIDATE_FLOAT);
        $width = filter_input(INPUT_POST, 'width', FILTER_VALIDATE_FLOAT);
        $thickness = filter_input(INPUT_POST, 'thickness', FILTER_VALIDATE_FLOAT);
        $edges = $_POST['edges'] ?? []; // Array or fallback
        $usage = filter_input(INPUT_POST, 'usage', FILTER_DEFAULT) ?? '';
        $usage = htmlspecialchars($usage, ENT_QUOTES, 'UTF-8'); // Sanitize for XSS

        // Check for valid inputs
        if (!$length || !$width || !$thickness || $length <= 0 || $width <= 0 || $thickness <= 0) {
            $error = 'Please provide valid dimensions.';
        } else {
            // Calculate price (using your pricing logic from tablero.js)
            $prices_per_m2 = [3 => 900, 5 => 1200, 7 => 1500];
            $area = ($length / 100) * ($width / 100); // Convert cm to m²
            $price = $area * ($prices_per_m2[$thickness] ?? 900);

            // Initialize cart
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Add item to cart
            $_SESSION['cart'][] = [
                'name' => 'Custom Olive Woodtop',
                'description' => "Dimensions: {$length}x{$width}x{$thickness} cm | Edges: " . implode(', ', (array)$edges) . " | Usage: $usage",
                'price' => $price,
                'quantity' => 1
            ];
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'checkout') {
        // Validate cart
        if (empty($_SESSION['cart'])) {
            $error = 'Cart is empty.';
        } else {
            $total = array_sum(array_column($_SESSION['cart'], 'price'));
            if ($total * 100 < MIN_ORDER_AMOUNT) {
                $error = 'Minimum order is 175 €.';
            } else {
                try {
                    $line_items = [];
                    foreach ($_SESSION['cart'] as $item) {
                        $line_items[] = [
                            'price_data' => [
                                'currency' => CURRENCY,
                                'product_data' => [
                                    'name' => $item['name'],
                                    'description' => $item['description'],
                                ],
                                'unit_amount' => $item['price'] * 100,
                            ],
                            'quantity' => $item['quantity'],
                        ];
                    }
$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => $line_items,
    'mode' => 'payment',
    'success_url' => 'https://yevea.com/en/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => 'https://yevea.com/en/cancel.php',
]);
                    header('Location: ' . $session->url);
                    exit;
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    error_log('Stripe Error: ' . $e->getMessage());
                    $error = 'Payment error: ' . htmlspecialchars($e->getMessage());
                } catch (Exception $e) {
                    error_log('General Error: ' . $e->getMessage());
                    $error = 'Error: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Custom Handmade Olive Wood Countertop | Yevea</title>
  <meta name="description" content="Solid olive wood countertop, handmade in Spain. Customize size and edges. Free shipping in Spain. 5-year warranty." />

  <!-- Canonical and Hreflang -->
  <link rel="canonical" href="https://yevea.com/en/olive-wood-countertop/" />
  <link rel="alternate" hreflang="es" href="https://yevea.com/es/tablero-madera-olivo/" />
  <link rel="alternate" hreflang="en" href="https://yevea.com/en/olive-wood-countertop/" />
  <link rel="alternate" hreflang="fr" href="https://yevea.com/fr/plan-bois-olivier/" />
  <link rel="alternate" hreflang="de" href="https://yevea.com/de/olivenholz-platte/" />
  <link rel="alternate" hreflang="pl" href="https://yevea.com/pl/blat-drewno-oliwne/" />

  <!-- Web App Manifest -->
  <link rel="manifest" href="/en/olive-wood-countertop/manifest.json" />
  <meta name="theme-color" content="#000000" />
  <link rel="apple-touch-icon" href="/assets/logos/logo-olivo-192x192.webp" sizes="192x192" />

  <!-- Schema.org -->
  <script type="application/ld+json" id="product-schema">
  {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "Custom Handmade Olive Wood Countertop",
    "image": "https://yevea.com/assets/fotos/olivo-tablero.jpg",
    "description": "Solid olive wood countertop, handcrafted by a Spanish artisan. Custom-made to your dimensions. Ideal for kitchens, bathrooms, or tables.",
    "sku": "TAB-OLIVE-ARTESANAL",
    "brand": { "@type": "Brand", "name": "Yevea" },
    "offers": {
      "@type": "Offer",
      "url": "https://yevea.com/en/olive-wood-countertop/",
      "priceCurrency": "EUR",
      "price": "",
      "availability": "https://schema.org/InStock",
      "seller": {
        "@type": "Organization",
        "name": "Yevea",
        "url": "https://yevea.com"
      }
    }
  }
  </script>

  <!-- Open Graph / Twitter -->
  <meta property="og:title" content="Custom Handmade Olive Wood Countertop | Yevea" />
  <meta property="og:description" content="Handcrafted in Spain. Free shipping." />
  <meta property="og:image" content="https://yevea.com/assets/fotos/olivo-tablero.jpg" />
  <meta property="og:url" content="https://yevea.com/en/olive-wood-countertop/" />
  <meta name="twitter:card" content="summary_large_image" />

  <!-- CSS -->
<link rel="preload" href="/assets/yevea.css" as="style" onload="this.onload=null;this.rel='stylesheet'" />
<noscript><link rel="stylesheet" href="/assets/yevea.css" /></noscript>

  <!-- Inline CSS (same as Spanish) -->
  <style>
section {
      max-width: 600px;
      margin: 1rem auto;
      padding: 0;
      border: 1px solid #ccc;
    }
section > h2 {
  margin: 0;
  padding:0.5rem 1rem;
  border-bottom: 1px solid #ccc;
}
section > div {
margin:0;
  padding:0.5rem 1rem;
}
section > p {
margin:0;
  padding:0.5rem 1rem;
}
    input[type="radio"] {
      width: auto;
      margin: 0 6px 0 0;
      vertical-align: middle;
    }
    input:not([type="radio"]), select, textarea {
      width: 100%;
      padding: 8px;
      margin: 4px 0;
      border: 1px solid var(--color-line);
      border-radius: var(--radius);
      box-sizing: border-box;
      background: var(--color-light);
    }

    .trust-line {
      font-size: 0.85em;
      color: #555;
    }
    #board-svg {
      max-width: 100%;
      height: auto;
      border: 1px solid var(--color-line);
      border-radius: var(--radius);
    }
    #edgesSummary, #usageSummary {
      font-size: 0.95em;
      margin: 10px 0;
      color: #444;
    }
    .edges-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 30px 0;
      gap: 32px;
      font-size: 0.95em;
    }

    @media (min-width: 768px) { 

      .edges-container {
        flex-direction: row;
        justify-content: center;
        align-items: center;
      }
      .usage-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px 12px;
      }

    .edges-options {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
      text-align: left;
    }
    .usage-option {
      margin: 0;
    }
    .usage-option input[type="radio"] {
      margin-right: 8px;
    }
    .usage-option label {
      display: inline-flex;
      align-items: center;
    }
    #uso-otro-input {
      width: 90%;
      margin: 4px 0 0 26px;
      padding: 6px;
      display: none;
    }
}
    
/* Media Queries */
@media (max-width: 768px) {
        
    section, section > h2, article, article > h1 {
      border: none;
    }
}

</style>
  <!-- Translations -->
  <script>
    window.translations = {
      edgeDescriptions: {
        'ssss': 'all live edges',
        'iiii': 'all straight edges',
        'sisi': 'longitudinal live edges | transverse straight',
        'isis': 'longitudinal straight | transverse live edges'
      },
      skuPrefix: {
        'tabletop': '31',
        'kitchen-countertop': '32',
        'kitchen-island': '33',
        'bathroom-countertop': '34',
        'shelf': '35',
        'other': '36'
      },
      edgeLabels: {
        north: 'back',
        east: 'right',
        south: 'front',
        west: 'left'
      },
      edgeValues: {
        recto: 'straight',
        rustico: 'live edge'
      },
      edgesSummaryPrefix: 'Edge Configuration',
      usageOptions: {
        default: 'tabletop',
        labels: {
          'tabletop': 'Tabletop',
          'kitchen-countertop': 'Kitchen Countertop',
          'kitchen-island': 'Kitchen Island',
          'bathroom-countertop': 'Bathroom Countertop',
          'shelf': 'Shelf',
          'other': 'Other'
        },
        otherDefault: 'other solid olive wood countertop'
      },
      usageSummaryPrefix: 'Usage',
      errors: {
        loadCart: 'Failed to load cart',
        saveCart: 'Failed to save cart',
        invalidPrice: 'Invalid price. Please check the dimensions.',
        minOrder: 'The minimum order is 175 €.',
        checkoutFailed: 'Payment processing failed. Please try again.'
      },
      cartEmpty: 'Your cart is empty.',
      removeItem: '🗑️ Remove',
      checkoutProcessing: 'Processing… ⏳',
      checkoutButton: '💳 Complete Order →'
    };
    const pricesPerM2 = <?php echo json_encode($prices ?: [3 => 900, 5 => 1200, 7 => 1500]); ?>;
  </script>
</head>
<body>
    <?php include '../topnav.php'; ?>
<header class="yevea">
    <div class="container">

      <h1 id="olive-wood-countertop" style="line-height:100%;margin:0 1rem;"><strong>Olive Wood Countertop</strong></h1>
      <p>
        — Buy your olive wood countertop here.<br>
        — Handcrafted in Spain by an artisan.<br>
        — Each countertop is a unique piece.<br>
      </p>
    </div>
  </header>

<section>
    <h2 id="dimensions">1. Dimensions</h2>
    <p>Countertops are handmade with solid olive wood on order. Customize your countertop to your specifications. <a href="https://yevea.com/en/faq.html#countertop-dimensions" target="_blank" rel="noopener" aria-label="Countertop dimensions">Learn about dimension posibilities&nbsp;→</a>.</p>
    <div style="max-width: 500px; margin: 0 auto; font-size: 0.95em;">
      <label for="thickness">Thickness (cm):</label>
      <select id="thickness" onchange="updatePrice()" style="margin-left:1.075rem;width:7.75rem;">
        <option value="3">3 cm</option>
        <option value="5">5 cm</option>
        <option value="7">7 cm</option>
      </select>
      <br>
      <label for="length">Length (cm) | <span class="trust-line">minimum 50 cm - maximum 300 cm</span>:</label>
      <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" onclick="changeValue('length', -10)">−10</button>
        <button type="button" onclick="changeValue('length', -1)">−1</button>
        <input type="number" id="length" min="50" max="300" value="80" oninput="validateInput(this)">
        <button type="button" onclick="changeValue('length', 1)">+1</button>
        <button type="button" onclick="changeValue('length', 10)">+10</button>
      </div>
      <label for="width">Width (cm) | <span class="trust-line">minimum 20 cm - maximum 100 cm</span>:</label>
      <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" onclick="changeValue('width', -10)">−10</button>
        <button type="button" onclick="changeValue('width', -1)">−1</button>
        <input type="number" id="width" min="20" max="100" value="30" oninput="validateInput(this)">
        <button type="button" onclick="changeValue('width', 1)">+1</button>
        <button type="button" onclick="changeValue('width', 10)">+10</button>
      </div>
      <p>Estimated Price: <span id="priceDisplay">0.00 €</span></p>
    </div>
</section><section>
    <h2 id="edges">2. Edges</h2>
    <p>Choose the edge type for each side: <strong>straight</strong> or <strong>live edge</strong>. <a href="https://yevea.com/en/faq.html#countertop-edges" target="_blank" rel="noopener" aria-label="Edges">FAQ countertop edges&nbsp;→</a>.</p>
    <div class="edges-container">
      <svg id="board-svg" role="img" aria-label="Countertop edge configuration diagram" width="240" height="160" viewBox="0 0 240 160">
        <path id="edge-north" d="M30,50 H210" stroke="#999" stroke-width="6" fill="none"/>
        <path id="edge-east" d="M210,50 V130" stroke="#999" stroke-width="6" fill="none"/>
        <path id="edge-south" d="M210,130 H30" stroke="#999" stroke-width="6" fill="none"/>
        <path id="edge-west" d="M30,130 V50" stroke="#999" stroke-width="6" fill="none"/>
        <text x="120" y="16" text-anchor="middle" font-size="14" fill="#555">EDGE DIAGRAM</text>
        <text x="120" y="40" text-anchor="middle" font-size="14" fill="#333">back</text>
        <text x="220" y="25" text-anchor="start" font-size="14" fill="#333" transform="rotate(90,195,50)">right</text>
        <text x="120" y="150" text-anchor="middle" font-size="14" fill="#333">front</text>
        <text x="-25" y="63" text-anchor="end" font-size="14" fill="#333" transform="rotate(-90,5,50)">left</text>
      </svg>
      <div class="edges-options">
        <div>
          <strong>Back Edge</strong><br>
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-north" value="recto" checked onchange="updateEdge('north')"> Straight
          </label> &nbsp;
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-north" value="rustico" onchange="updateEdge('north')"> Live Edge
          </label>
        </div>
        <div>
          <strong>Front Edge</strong><br>
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-south" value="recto" checked onchange="updateEdge('south')"> Straight
          </label> &nbsp;
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-south" value="rustico" onchange="updateEdge('south')"> Live Edge
          </label>
        </div>
        <div>
          <strong>Left Edge</strong><br>
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-west" value="recto" checked onchange="updateEdge('west')"> Straight
          </label> &nbsp;
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-west" value="rustico" onchange="updateEdge('west')"> Live Edge
          </label>
        </div>
        <div>
          <strong>Right Edge</strong><br>
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-east" value="recto" checked onchange="updateEdge('east')"> Straight
          </label> &nbsp;
          <label style="display: inline-flex; align-items: center; gap: 6px; margin: 2px 0;">
            <input type="radio" name="edge-east" value="rustico" onchange="updateEdge('east')"> Live Edge
          </label>
        </div>
      </div>
    </div>
    <div id="edgesSummary"></div>

</section><section>
    <h2 id="usage">3. Usage</h2>
    <p>What will you use the countertop for? <a href="https://yevea.com/en/faq.html#countertop-usage" target="_blank" rel="noopener" aria-label="Terms of payment and sale">Learn about qualties and usage&nbsp;→</a>.</p>
    <div class="usage-options">
      <div class="usage-option">
        <input type="radio" name="uso" value="tabletop" id="uso-tabletop" checked onchange="updateUsage()">
        <label for="uso-tabletop">Tabletop</label>
      </div>
      <div class="usage-option">
        <input type="radio" name="uso" value="kitchen-countertop" id="uso-kitchen-countertop" onchange="updateUsage()">
        <label for="uso-kitchen-countertop">Kitchen Countertop</label>
      </div>
      <div class="usage-option">
        <input type="radio" name="uso" value="kitchen-island" id="uso-kitchen-island" onchange="updateUsage()">
        <label for="uso-kitchen-island">Kitchen Island</label>
      </div>
      <div class="usage-option">
        <input type="radio" name="uso" value="bathroom-countertop" id="uso-bathroom-countertop" onchange="updateUsage()">
        <label for="uso-bathroom-countertop">Bathroom Countertop</label>
      </div>
      <div class="usage-option">
        <input type="radio" name="uso" value="shelf" id="uso-shelf" onchange="updateUsage()">
        <label for="uso-shelf">Shelf</label>
      </div>
      <div class="usage-option">
        <input type="radio" name="uso" value="other" id="uso-other-radio" onchange="updateUsage()">
        <label for="uso-other-radio">Other (specify):</label>
        <input type="text" id="uso-otro-input" placeholder="Specify usage" oninput="updateUsage()">
      </div>
    </div>
    <div id="usageSummary"></div>

</section><section>
    <h2 id="cart">4. Cart</h2>
    <p>Add the countertop as configured to the cart. It is possible to add multiple countertops.</p>
    <div class="button-container">
      <button class="button-yevea" onclick="addToCart()">
        ➕ Add to Cart
      </button>
      <p><small>Handcrafted • Free Shipping in Spain</small></p>
    </div>
    <div id="cartItems">Your cart is empty.</div>
    <div id="cartTotal" style="display: none;">
      <p>Total: <span id="totalPrice">0</span> €</p>
    </div>

</section><section>
    <h2 id="payment">5. Payment</h2>
    <p>Proceed to secure payment. Minimum order: 175 €. <a href="https://yevea.com/en/faq.html#terms" target="_blank" rel="noopener" aria-label="Terms of payment and sale">FAQ about terms of payment and sale&nbsp;→</a>.</p>
    <div class="button-container">
      <button class="button-yevea" id="checkoutBtn" disabled onclick="checkout()">
        💳 Complete Order →
      </button>
      <p><small>Secure Payment • Delivery in 3 Weeks</small></p>
    </div>
</section>

  <?php include '../footer.php'; ?>
  <script src="/assets/tablero.js" defer></script>
</body>
</html>
