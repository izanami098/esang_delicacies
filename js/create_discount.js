document.addEventListener("DOMContentLoaded", () => {
  const menuData = JSON.parse(localStorage.getItem("menuData")) || [];
  const productList = document.getElementById("productList");

  // Render products from menuData
  menuData.forEach(category => {
    category.items.forEach(item => {
      const div = document.createElement("div");
      div.className = "product-item";
      div.innerHTML = `
        <input type="checkbox" value="${item.id}" id="prod-${item.id}">
        <label for="prod-${item.id}">${item.name}</label>
        <img src="${item.image || "https://placehold.co/40"}" alt="${item.name}">
      `;
      productList.appendChild(div);
    });
  });

  // Toggle button logic
  document.querySelectorAll(".toggle-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const group = btn.parentElement;
      group.querySelectorAll(".toggle-btn").forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      group.querySelector("input[type=hidden]").value = btn.dataset.value;
    });
  });

  // Form submit
  document.getElementById("discountForm").addEventListener("submit", e => {
    e.preventDefault();

    const discount = {
      name: document.getElementById("discountName").value,
      description: document.getElementById("discountDesc").value,
      startDate: document.getElementById("startDate").value,
      endDate: document.getElementById("endDate").value,
      redemptionLimit: document.getElementById("redemptionLimit").value,
      type: document.getElementById("discountType").value,
      value: parseFloat(document.getElementById("discountValue").value),
      products: Array.from(productList.querySelectorAll("input:checked")).map(cb => cb.value)
    };

    let discounts = JSON.parse(localStorage.getItem("discounts")) || [];
    discounts.push(discount);
    localStorage.setItem("discounts", JSON.stringify(discounts));

    alert("Discount created successfully!");
    e.target.reset();
  });

  document.getElementById("cancelBtn").addEventListener("click", () => {
    window.location.href = "admin_dashboard.html"; // go back
  });
});
