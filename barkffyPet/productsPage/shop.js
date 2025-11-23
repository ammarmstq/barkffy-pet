document.addEventListener('DOMContentLoaded', () => {
  let currentProduct = null;
  const grid = document.querySelector('.product-grid');
  const modal = document.getElementById('productModal');
  const closeBtn = modal.querySelector('.close');
  let products = [];

  async function loadProducts() {
    const res = await fetch('get_products.php');
    products = await res.json();
    displayProducts(products);
  }

  function displayProducts(items) {
    grid.innerHTML = '';
    items.forEach(p => {
      const card = document.createElement('div');
      card.classList.add('product-card');
      card.innerHTML = `
        <div class="status">${p.in_stock == 1 ? 'In Stock' : 'Out of Stock'}</div>
        <img src="../${p.image_url || 'uploads/products/placeholder.png'}" alt="${p.name}">
        <div class="product-info">
          <p class="name">${p.name}</p>
          <p class="price">RM ${parseFloat(p.price).toFixed(2)}</p>
        </div>
      `;
      card.addEventListener('click', () => openModal(p));
      grid.appendChild(card);
    });
  }

  function addToCart(p) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existing = cart.find(i => i.product_id === p.product_id);
    if (existing) existing.qty++;
    else cart.push({ ...p, qty: 1 });
    localStorage.setItem('cart', JSON.stringify(cart));
    showToast(`${p.name} added to cart`);
    updateCartBadge();
  }

  // Filters
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyFilters();
    });
  });

  document.getElementById('filterFood').addEventListener('change', applyFilters);
  document.getElementById('filterAccessories').addEventListener('change', applyFilters);

  function applyFilters() {
    const activePet = document.querySelector('.filter-btn.active').textContent.trim().toLowerCase();
    const food = document.getElementById('filterFood').checked;
    const acc = document.getElementById('filterAccessories').checked;

    console.log("Active pet:", activePet);

    let filtered = products;

    if (activePet !== 'all') {
        filtered = filtered.filter(p =>
        p.parent_name.toLowerCase() === activePet ||
        p.parent_name.toLowerCase() === 'both'
        );
    }

    if (food || acc) {
        filtered = filtered.filter(p =>
        (food && p.category_name.toLowerCase().includes('food')) ||
        (acc && p.category_name.toLowerCase().includes('accessories'))
        );
    }

    console.log("Filtered results:", filtered);
    displayProducts(filtered);
}

    loadProducts();

    // ----- Product Modal -----
    const addToCartBtn = document.getElementById('addToCartBtn');

    addToCartBtn.addEventListener("click", () => {
      if (currentProduct) {
          addToCart(currentProduct);
          modal.style.display = "none";
        }
      });


    function openModal(product) {
    document.getElementById('modalImage').src = product.image_url ? `../${product.image_url}` : "../uploads/products/placeholder.png";
    document.getElementById('modalName').textContent = product.name;
    document.getElementById('modalPrice').textContent = `RM ${product.price}`;
    document.getElementById('modalStock').textContent = product.stock_qty > 0 ? 'In Stock' : 'Out of Stock';
    document.getElementById('modalDesc').textContent = product.description || 'No description available.';
    modal.style.display = 'block';
    currentProduct = product;
    }

    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };

    // Attach click event after products load
    function attachCardListeners(products) {
    document.querySelectorAll('.product-card').forEach((card, i) => {
        card.addEventListener('click', () => openModal(products[i]));
    });
    }

    // Ensure attachCardListeners is called after rendering products
    // Example: in your displayProducts(products) function, call attachCardListeners(products)

    // --- Cart Sidebar UI ---
    const cartBtn = document.querySelector('.cart-btn');
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    const closeCart = document.getElementById('closeCart');
    const cartItemsDiv = document.getElementById('cartItems');
    const cartTotalSpan = document.getElementById('cartTotal');

    const openCartIcon = document.getElementById("openCart");

    if (openCartIcon) {
        openCartIcon.addEventListener("click", () => {
            loadCartUI();
            cartSidebar.classList.add("open");
            cartOverlay.classList.add("show");
        });
    }

    // Load cart into sidebar
    function loadCartUI() {
      let cart = JSON.parse(localStorage.getItem('cart')) || [];
      cartItemsDiv.innerHTML = '';
      let total = 0;

      cart.forEach(item => {
        total += item.price * item.qty;

        cartItemsDiv.innerHTML += `
          <div class="cart-item">
            <div>
              <strong>${item.name}</strong><br>
              Qty: ${item.qty}
            </div>
            <div>RM ${(item.price * item.qty).toFixed(2)}</div>
          </div>
        `;
      });

      cartTotalSpan.textContent = total.toFixed(2);
    }

    document.getElementById("checkoutBtn").addEventListener("click", async () => {

      let cart = JSON.parse(localStorage.getItem("cart")) || [];
      if (cart.length === 0) {
          alert("Your cart is empty.");
          return;
        }

      const userId = 2; // temporary until login system is integrated

      const shipping_address = "Customer Provided Address"; // later from checkout form

      const response = await fetch("create_order.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
              user_id: userId,
              shipping_address: shipping_address,
              cart: cart
          })
      });

      const result = await response.json();

      if (result.success) {
          // clear local cart
          localStorage.removeItem("cart");
          window.location.href = result.redirect_url;
      } else {
          alert("Order failed: " + result.error);
      }
  });

    // Open cart
    cartBtn.addEventListener('click', () => {
      loadCartUI();
      cartSidebar.classList.add('open');
      cartOverlay.classList.add('show');
    });

    // Close cart
    closeCart.addEventListener('click', () => {
      cartSidebar.classList.remove('open');
      cartOverlay.classList.remove('show');
    });

    // Toast popup
    function showToast(msg) {
      const t = document.getElementById("toast");
      t.textContent = msg;
      t.classList.add("show");

      setTimeout(() => {
        t.classList.remove("show");
      }, 2000);
    }

    // Update cart bubble
    function updateCartBadge() {
      const cart = JSON.parse(localStorage.getItem('cart')) || [];
      const totalQty = cart.reduce((a, c) => a + c.qty, 0);

      const badge = document.getElementById("cartBadge");
      if (badge) badge.textContent = totalQty;
    }
    updateCartBadge();
});
