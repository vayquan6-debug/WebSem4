/**
 * VietTour — Frontend JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {

  // Auto-dismiss alerts after 5 seconds
  document.querySelectorAll('.alert').forEach(function(alert) {
    setTimeout(function() {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(function() { alert.remove(); }, 500);
    }, 5000);
  });

  // Mobile nav toggle
  var navToggle = document.getElementById('nav-toggle');
  var navMenu = document.getElementById('nav-menu');
  if (navToggle && navMenu) {
    navToggle.addEventListener('click', function() {
      navMenu.classList.toggle('show');
    });
  }

  // Confirm dangerous actions
  document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
      if (!confirm(this.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  // Auto-calculate booking total
  var numPeople = document.getElementById('num_people');
  var priceEl = document.querySelector('.tour-price');
  if (numPeople && priceEl) {
    numPeople.addEventListener('input', function() {
      var basePrice = priceEl.dataset.price;
      if (basePrice) {
        var total = basePrice * this.value;
        var display = new Intl.NumberFormat('vi-VN').format(total) + ' VNĐ';
        var totalEl = document.getElementById('total-display');
        if (totalEl) totalEl.textContent = display;
      }
    });
  }

  // Set minimum date for booking
  var startDate = document.getElementById('start_date');
  if (startDate) {
    var today = new Date().toISOString().split('T')[0];
    startDate.setAttribute('min', today);
  }

});
