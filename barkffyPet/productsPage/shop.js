document.addEventListener('DOMContentLoaded', () => {
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
    alert(`${p.name} added to cart!`);
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

    function openModal(product) {
    document.getElementById('modalImage').src = `../${product.image_url}` || '../uploads/products/placeholder.jpg';
    document.getElementById('modalName').textContent = product.name;
    document.getElementById('modalPrice').textContent = `RM ${product.price}`;
    document.getElementById('modalStock').textContent = product.stock_qty > 0 ? 'In Stock' : 'Out of Stock';
    document.getElementById('modalDesc').textContent = product.description || 'No description available.';
    modal.style.display = 'block';
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

});
