/**
 * NA FRESH – Fruits & Coconuts
 * Vanilla JavaScript Client Controller
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Branded Intro Screen Logic
  const splash = document.getElementById('brand-splash');
  if (splash) {
    const hasSeenSplash = sessionStorage.getItem('na_splash_seen');
    if (hasSeenSplash) {
      splash.style.display = 'none';
    } else {
      setTimeout(() => {
        splash.classList.add('fade-out');
        sessionStorage.setItem('na_splash_seen', 'true');
        setTimeout(() => {
          splash.style.display = 'none';
        }, 500);
      }, 1200);
    }
  }

  // 2. Setup Toast Notifications
  window.showToast = function (message, icon = 'success') {
    if (window.Swal) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });
      Toast.fire({
        icon: icon,
        title: message
      });
    } else {
      alert(message);
    }
  };

  // 3. Add to Cart AJAX Helper
  document.querySelectorAll('.btn-add-to-cart').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const productId = this.getAttribute('data-product-id');
      const unitId = this.getAttribute('data-unit-id') || null;
      const qtyInput = document.getElementById('qty-input-' + productId) || document.getElementById('product-qty');
      const quantity = qtyInput ? parseFloat(qtyInput.value) || 1 : 1;

      addToCart(productId, unitId, quantity, this);
    });
  });

  window.addToCart = function (productId, unitId, quantity, triggerBtn) {
    const originalText = triggerBtn ? triggerBtn.innerHTML : null;
    if (triggerBtn) {
      triggerBtn.disabled = true;
      triggerBtn.classList.add('opacity-75');
    }

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    if (unitId) formData.append('unit_id', unitId);
    formData.append('quantity', quantity);

    fetch('/api/cart.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(res => res.json())
      .then(data => {
        if (triggerBtn) {
          triggerBtn.disabled = false;
          triggerBtn.classList.remove('opacity-75');
        }

        if (data.success) {
          updateCartBadges(data.item_count);
          showToast(data.message || 'Added to cart!', 'success');
        } else {
          showToast(data.error || 'Failed to add item', 'error');
        }
      })
      .catch(err => {
        if (triggerBtn) {
          triggerBtn.disabled = false;
          triggerBtn.classList.remove('opacity-75');
        }
        showToast('Could not reach server', 'error');
      });
  };

  function updateCartBadges(count) {
    document.querySelectorAll('.cart-badge-count').forEach(badge => {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
    });
  }

  // 4. Quantity Adjusters on Product & Cart Pages
  document.querySelectorAll('.btn-qty-minus').forEach(btn => {
    btn.addEventListener('click', function () {
      const input = this.parentElement.querySelector('input.qty-input');
      if (input) {
        let val = parseFloat(input.value) || 1;
        if (val > 1) {
          input.value = val - 1;
          input.dispatchEvent(new Event('change'));
        }
      }
    });
  });

  document.querySelectorAll('.btn-qty-plus').forEach(btn => {
    btn.addEventListener('click', function () {
      const input = this.parentElement.querySelector('input.qty-input');
      if (input) {
        let val = parseFloat(input.value) || 1;
        input.value = val + 1;
        input.dispatchEvent(new Event('change'));
      }
    });
  });

  // 5. Carousel Horizontal Scroll Buttons
  document.querySelectorAll('.carousel-scroll-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const container = document.getElementById(targetId);
      const direction = this.getAttribute('data-dir') === 'left' ? -260 : 260;
      if (container) {
        container.scrollBy({ left: direction, behavior: 'smooth' });
      }
    });
  });
});
