document.addEventListener('DOMContentLoaded', async () => {
  const serviceGrid = document.querySelector('.service-selection-grid');
  const bookingContainer = document.querySelector('.booking-container');

  try {
    const res = await fetch('get_services.php');
    const services = await res.json();

    if (services.error) {
      serviceGrid.innerHTML = `<p style="color:red;">${services.error}</p>`;
      return;
    }

    serviceGrid.innerHTML = '';

    // ✅ Group services by category
    const grouped = {};
    services.forEach(service => {
      const category = service.category || 'Other';
      if (!grouped[category]) grouped[category] = [];
      grouped[category].push(service);
    });

    // ✅ Render each category as a section
    for (const [category, serviceList] of Object.entries(grouped)) {
      const section = document.createElement('div');
      section.classList.add('service-category');
      section.innerHTML = `
        <h2>${category}</h2>
        <div class="underline-small"></div>
        <div class="service-grid"></div>
      `;

      const grid = section.querySelector('.service-grid');

      serviceList.forEach(service => {
        const card = document.createElement('div');
        card.classList.add('service-card');
        card.dataset.serviceId = service.service_id;

        card.innerHTML = `
          <h4>${service.name}</h4>
          <p>${service.description || ''}</p>
          <div class="price-duration">
            <span>RM ${parseFloat(service.price).toFixed(2)}</span>
            <span>${service.duration_minutes} mins</span>
          </div>
        `;

        // ✅ Click to start booking
        card.addEventListener('click', () => {
          const serviceId = service.service_id;
          const serviceName = service.name;
          const servicePrice = `RM ${parseFloat(service.price).toFixed(2)}`;
          const serviceDuration = `${service.duration_minutes} mins`;

          bookingContainer.innerHTML = `
            <div class="booking-layout">
              <div class="booking-left">
                <div class="service-info">
                  <h2>${serviceName}</h2>
                  <p>${servicePrice} | ${serviceDuration}</p>
                </div>
                <div class="calendar-section">
                  <label for="bookingDate">Select Date:</label>
                  <input type="date" id="bookingDate">
                </div>
              </div>
              <div class="booking-right">
                <h3>Available Time Slots</h3>
                <div id="timeSlots"></div>
              </div>
            </div>
          `;

          const bookingDateInput = document.getElementById('bookingDate');
          const timeSlotsDiv = document.getElementById('timeSlots');

          bookingDateInput.addEventListener('change', async () => {
            const date = bookingDateInput.value;
            const res = await fetch(`get_available_time.php?service_id=${serviceId}&date=${date}`);
            const slots = await res.json();

            if (!Array.isArray(slots) || slots.length === 0) {
              timeSlotsDiv.innerHTML = `<p>No available times.</p>`;
              return;
            }

            timeSlotsDiv.innerHTML = slots.map(t => `
              <button class="time-slot" data-start="${t.start}">${t.start}</button>
            `).join('');

            document.querySelectorAll('.time-slot').forEach(btn => {
              btn.addEventListener('click', () => {
                const selectedTime = btn.dataset.start;
                showBookingForm(serviceId, serviceName, date, selectedTime, serviceDuration);
              });
            });
          });
        });

        grid.appendChild(card);
      });

      serviceGrid.appendChild(section);
    }

  } catch (err) {
    console.error(err);
    serviceGrid.innerHTML = `<p style="color:red;">Error loading services.</p>`;
  }
});


// ✅ Booking Form (same as before)
function showBookingForm(serviceId, serviceName, date, time, duration) {
  const bookingContainer = document.querySelector('.booking-container');
  bookingContainer.innerHTML = `
    <div class="booking-summary">
      <h2>${serviceName}</h2>
      <p><strong>Date:</strong> ${date}</p>
      <p><strong>Time:</strong> ${time}</p>
      <p><strong>Duration:</strong> ${duration}</p>
    </div>
    <form id="bookingForm" class="booking-form">
      <label>First Name: <input type="text" name="first_name" required></label>
      <label>Last Name: <input type="text" name="last_name" required></label>
      <label>Email: <input type="email" name="email" required></label>
      <label>Contact Number: <input type="text" name="phone" required></label>
      <button type="submit">Confirm Booking</button>
    </form>
  `;

  document.getElementById('bookingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = Object.fromEntries(new FormData(e.target).entries());

    const data = {
      user_id: 2,          // still using dummy for now
      pet_id: null,
      service_id: serviceId,
      booking_date: date,
      booking_time: time,
      notes: null,
      customer_name: `${formData.first_name} ${formData.last_name}`,
      customer_email: formData.email,
      customer_phone: formData.phone
    };

    const res = await fetch('submit_booking.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await res.json();

    if (result.error) {
      alert(result.error);
      return;
    }

    // Show popup with QR + details
    showBookingConfirmationModal(result);
  });

  function showBookingConfirmationModal(result) {
  const modal       = document.getElementById('bookingConfirmationModal');
  const idSpan      = document.getElementById('bookingIdText');
  const qrImg       = document.getElementById('bookingQrImage');
  const detailsDiv  = document.getElementById('bookingDetailsText');
  const closeBtn    = document.getElementById('bookingModalClose');

  idSpan.textContent = result.booking_id;

  if (result.qr_path) {
    qrImg.src = result.qr_path;
    qrImg.alt = `QR for booking #${result.booking_id}`;
  }

  const name    = result.customer_name || '';
  const date    = result.booking_date;
  const time    = result.booking_time;
  const service = result.service_name;
  const duration= result.service_duration;
  const price   = result.service_price;

  detailsDiv.innerHTML = `
    <p><strong>Name:</strong> ${name}</p>
    <p><strong>Service:</strong> ${service}</p>
    <p><strong>Date:</strong> ${date}</p>
    <p><strong>Time:</strong> ${time}</p>
    <p><strong>Duration:</strong> ${duration} mins</p>
    <p><strong>Price:</strong> RM ${parseFloat(price).toFixed(2)}</p>
  `;

  modal.style.display = 'flex';

  closeBtn.onclick = () => {
    modal.style.display = 'none';
  };

  window.onclick = (e) => {
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  };
}

}
