document.addEventListener('DOMContentLoaded', () => {
  const categoriesTable = document.querySelector('#categoriesTable tbody');
  const productsTable = document.querySelector('#productsTable tbody');
  const productForm = document.getElementById('productForm');
  const searchBtn = document.getElementById('searchProductBtn');
  const deleteBtn = document.getElementById('deleteProductBtn');

  // Fetch and display categories
  async function loadCategories() {
    const res = await fetch('get_categories.php');
    const data = await res.json();
    categoriesTable.innerHTML = data.map(c => `
      <tr>
        <td>${c.category_id}</td>
        <td>${c.name}</td>
        <td>${c.parent_id ?? 'NULL'}</td>
        <td>${c.description ?? ''}</td>
      </tr>
    `).join('');
  }

  // Fetch and display products
  async function loadProducts() {
    const res = await fetch('get_products_admin.php');
    const data = await res.json();
    productsTable.innerHTML = data.map(p => `
      <tr>
        <td>${p.product_id}</td>
        <td>${p.category_id}</td>
        <td>${p.name}</td>
        <td>RM ${parseFloat(p.price).toFixed(2)}</td>
        <td>${p.stock_qty}</td>
        <td>${p.active == 1 ? 'Yes' : 'No'}</td>
      </tr>
    `).join('');
  }

  // Search product by ID
  searchBtn.addEventListener('click', async () => {
    const id = document.getElementById('product_id').value.trim();
    if (!id) return alert('Please enter a Product ID.');

    const res = await fetch(`get_product_by_id.php?id=${id}`);
    const data = await res.json();

    if (data.error) {
      alert(data.error);
      productForm.reset();
      return;
    }

    // Fill form with retrieved product
    document.getElementById('category_id').value = data.category_id;
    document.getElementById('name').value = data.name;
    document.getElementById('price').value = data.price;
    document.getElementById('stock_qty').value = data.stock_qty;
    document.getElementById('active').value = data.active;
    document.getElementById('image_url').value = data.image_url || '';
  });

  // Add / Update product
  productForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(productForm);
    formData.append('product_id', document.getElementById('product_id').value);

    const res = await fetch('manage_products.php', {
      method: 'POST',
      body: formData
    });

    const result = await res.json();
    alert(result.message);
    loadProducts();
    productForm.reset();
  });

  // Delete product
  deleteBtn.addEventListener('click', async () => {
    const id = document.getElementById('product_id').value.trim();
    if (!id) return alert('Please enter a Product ID to delete.');
    if (!confirm('Are you sure you want to delete this product?')) return;

    const res = await fetch('manage_products.php', {
      method: 'POST',
      body: new URLSearchParams({ delete_id: id })
    });

    const result = await res.json();
    alert(result.message);
    loadProducts();
    productForm.reset();
  });

  // Load everything on page start
  loadCategories();
  loadProducts();
});
