// /assets/tablero.js
// JavaScript for Olive Wood Countertop calculator, cart, and checkout

// === Load Prices ===
window.pricesPerM2 = typeof pricesPerM2 !== 'undefined' ? pricesPerM2 : { 3: 900, 5: 1200, 7: 1500 };

// === Cart State ===
let cart = [];
try {
  const saved = sessionStorage.getItem('oliveWoodCart');
  cart = saved ? JSON.parse(saved) : [];
} catch (e) {
  console.warn(window.translations?.errors?.loadCart || 'Failed to load cart', e);
  cart = [];
}

// === Edge + Usage State ===
let edges = { north: 'recto', east: 'recto', south: 'recto', west: 'recto' };
let uso = window.translations?.usageOptions?.default || 'tabletop';
let usoOtros = '';

// === SVG paths for live edges ===
const liveEdgePath = {
  north: "M30,50 Q50,40 70,50 T110,50 T150,50 T190,50 T210,50",
  east: "M210,50 Q220,70 210,90 T210,130",
  south: "M210,130 Q190,140 170,130 T130,130 T90,130 T50,130 T30,130",
  west: "M30,130 Q20,110 30,90 T30,50"
};

// === Update edge visuals ===
function updateEdge(side) {
  const path = document.getElementById(`edge-${side}`);
  const value = document.querySelector(`input[name="edge-${side}"]:checked`).value;
  edges[side] = value;

  if (value === 'rustico') {
    path.setAttribute('d', liveEdgePath[side]);
    path.setAttribute('stroke', '#5D4037');
  } else {
    const d = {
      north: 'M30,50 H210',
      east: 'M210,50 V130',
      south: 'M210,130 H30',
      west: 'M30,130 V50'
    };
    path.setAttribute('d', d[side]);
    path.setAttribute('stroke', '#999');
  }
  updateEdgesSummary();
}

// === Update edge summary text ===
function updateEdgesSummary() {
  const labels = window.translations?.edgeLabels || {
    north: 'back', east: 'right', south: 'front', west: 'left'
  };
  const parts = Object.entries(edges).map(([key, val]) => {
    const edgeName = labels[key] || key;
    const edgeValue = window.translations?.edgeValues?.[val] || val;
    return `${edgeName}: <strong>${edgeValue}</strong>`;
  });
  const el = document.getElementById('edgesSummary');
  if (el) {
    el.innerHTML = `${window.translations?.edgesSummaryPrefix || 'Edge Configuration'}: ${parts.join(', ')}`;
  }
}

// === Update usage (tabletop, shelf, etc.) ===
function updateUsage() {
  const radios = document.querySelectorAll('input[name="uso"]');
  uso = window.translations?.usageOptions?.default || 'tabletop';

  for (const radio of radios) {
    if (radio.checked) {
      uso = radio.value;
      break;
    }
  }

  const otroInput = document.getElementById('uso-otro-input');
  if (uso === 'other' && otroInput) {
    otroInput.style.display = 'block';
    usoOtros = otroInput.value.trim() || window.translations?.usageOptions?.otherDefault || 'other solid olive wood countertop';
  } else if (otroInput) {
    otroInput.style.display = 'none';
  }

  updateUsageSummary();
}

// === Update usage summary ===
function updateUsageSummary() {
  const text = uso === 'other'
    ? (usoOtros || window.translations?.usageOptions?.otherDefault || 'other solid olive wood countertop')
    : (window.translations?.usageOptions?.labels?.[uso] || uso);
  const el = document.getElementById('usageSummary');
  if (el) {
    el.innerHTML = `${window.translations?.usageSummaryPrefix || 'Usage'}: <strong>${text}</strong>`;
  }
}

// === Update price dynamically ===
function updatePrice() {
  const t = parseFloat(document.getElementById('thickness').value);
  const l = parseFloat(document.getElementById('length').value);
  const w = parseFloat(document.getElementById('width').value);
  if (isNaN(t) || isNaN(l) || isNaN(w)) return;
  const area = (l / 100) * (w / 100);
  const price = (area * window.pricesPerM2[t]).toFixed(2);
  document.getElementById('priceDisplay').textContent = price + ' €';

  if (typeof updateSchemaPrice === 'function') {
    updateSchemaPrice(parseFloat(price));
  }
}

// === +/- buttons ===
function changeValue(id, delta) {
  const input = document.getElementById(id);
  let value = parseInt(input.value) || parseInt(input.min);
  value += delta;
  value = Math.max(parseInt(input.min), Math.min(value, parseInt(input.max)));
  input.value = value;
  updatePrice();
}

// === Validate inputs ===
function validateInput(input) {
  let value = parseInt(input.value);
  const min = parseInt(input.min);
  const max = parseInt(input.max);
  if (isNaN(value) || value < min) input.value = min;
  else if (value > max) input.value = max;
  updatePrice();
}

// === Add item to cart ===
function addToCart() {
  const l = document.getElementById('length').value;
  const w = document.getElementById('width').value;
  const t = document.getElementById('thickness').value;
  const priceText = document.getElementById('priceDisplay').textContent;
  const price = parseFloat(priceText.replace(' €', ''));

  if (isNaN(price)) {
    alert(window.translations?.errors?.invalidPrice || 'Invalid price. Please check the dimensions.');
    return;
  }

  const edgeCode = ['north','east','south','west'].map(s => edges[s] === 'rustico' ? 's' : 'i').join('');
  const edgesText = window.translations?.edgeDescriptions?.[edgeCode] || 'custom configuration';
  const prefix = window.translations?.skuPrefix?.[uso] || '36';
  const usoText = uso === 'other'
    ? (usoOtros || window.translations?.usageOptions?.otherDefault || 'other solid olive wood countertop')
    : (window.translations?.usageOptions?.labels?.[uso] || uso);
  const productName = `${prefix}-${edgeCode}. ${usoText} ${l}x${w}x${t} cm, ${edgesText}`;

  const item = {
    thickness: t,
    length: l,
    width: w,
    price: price,
    edges: { ...edges },
    edgeCode: edgeCode,
    uso: uso,
    usoOtros: usoOtros,
    productName: productName,
    id: Date.now()
  };

  cart.push(item);
  saveCart();
  renderCart();

  // ✅ Visual confirmation (since no console)
  alert("✅ Added to cart: " + productName);
}

// === Save cart in sessionStorage ===
function saveCart() {
  try {
    sessionStorage.setItem('oliveWoodCart', JSON.stringify(cart));
  } catch (e) {
    console.warn(window.translations?.errors?.saveCart || 'Failed to save cart', e);
  }
}

// === Render cart items ===
function renderCart() {
  const container = document.getElementById('cartItems');
  const totalEl = document.getElementById('cartTotal');

  if (!container) return;

  if (cart.length === 0) {
    container.innerHTML = window.translations?.cartEmpty || 'Your cart is empty.';
    if (totalEl) totalEl.style.display = 'none';
    return;
  }

  container.innerHTML = cart.map(item => `
    <div class="cart-item">
      ${item.productName}<br>
      <span style="padding-left:3%;">${item.price.toFixed(2)} €</span>
      <button type="button" onclick="removeItem(${item.id})">${window.translations?.removeItem || '🗑️ Remove'}</button>
    </div>
  `).join('');

  const total = cart.reduce((sum, item) => sum + item.price, 0).toFixed(2);
  if (document.getElementById('totalPrice')) {
    document.getElementById('totalPrice').textContent = total;
  }
  if (totalEl) totalEl.style.display = 'block';
}

// === Remove item from cart ===
function removeItem(id) {
  cart = cart.filter(item => item.id !== id);
  saveCart();
  renderCart();
}

// === Checkout ===
async function checkout() {
  if (cart.reduce((sum, item) => sum + item.price, 0) < 175) {
    alert(window.translations?.errors?.minOrder || 'The minimum order is 175 €.');
    return;
  }

  const btn = document.getElementById('checkoutBtn');
  if (btn) {
    btn.disabled = true;
    btn.textContent = window.translations?.checkoutProcessing || 'Processing… ⏳';
  }

  try {
    const response = await fetch('/stripe-create-session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        lineItems: cart.map(item => ({
          description: item.productName,
          amount: Math.round(item.price * 100),
          currency: 'eur',
          quantity: 1
        }))
      })
    });

    const data = await response.json();
    if (data.url) {
      window.location.href = data.url;
    } else {
      throw new Error(data.error || 'No payment link generated');
    }
  } catch (err) {
    console.error('Error:', err);
    alert(window.translations?.errors?.checkoutFailed || 'Payment processing failed. Please try again.');
    if (btn) {
      btn.disabled = false;
      btn.textContent = window.translations?.checkoutButton || '💳 Complete Order →';
    }
  }
}

// === Update Schema.org dynamically ===
function updateSchemaPrice(price) {
  const schemaEl = document.getElementById('product-schema');
  if (!schemaEl) return;
  const schema = JSON.parse(schemaEl.textContent);
  schema.offers.price = price.toFixed(2);
  schemaEl.textContent = JSON.stringify(schema);
}

// === INIT ===
document.addEventListener('DOMContentLoaded', () => {
  updatePrice();
  updateEdgesSummary();
  updateUsage();
  updateUsageSummary();
  renderCart();

  // ✅ Attach Add to Cart button (fixed)
  const addBtn = document.querySelector('#cart .button-yevea');
  if (addBtn) {
    addBtn.addEventListener('click', (e) => {
      e.preventDefault();
      addToCart();
    });
  }

  // Optional: attach checkout button if exists
  const checkoutBtn = document.getElementById('checkoutBtn');
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      checkout();
    });
  }

  // Listen for dimension changes
  ['#length', '#width', '#thickness'].forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.addEventListener('input', updatePrice);
  });
});

