console.log('Form submitted');
document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    selectable: true,
    editable: false,
    events: 'get_bookings.php',

    eventClick: function(info) {
      const e = info.event;
      const f = document.getElementById('bookingForm');
      f.booking_id.value = e.id;
      f.user_id.value = e.extendedProps.user_id;
      f.pet_id.value = e.extendedProps.pet_id;
      f.service_id.value = e.extendedProps.service_id;
      f.booking_date.value = e.startStr.split('T')[0];
      f.start_time.value = e.startStr.split('T')[1].substring(0,5);
      f.end_time.value = e.endStr ? e.endStr.split('T')[1].substring(0,5) : '';
      f.booking_status.value = e.extendedProps.status;
      f.notes.value = e.extendedProps.notes;
    },

    select: function(info) {
      const f = document.getElementById('bookingForm');
      f.reset();
      f.booking_date.value = info.startStr.split('T')[0];
      f.start_time.value = info.startStr.split('T')[1].substring(0,5);
    }
  });
  calendar.render();

  // Handle form submit
  document.getElementById('bookingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch('manage_booking.php', { method: 'POST', body: formData });
    const result = await res.json();
    alert(result.message);
    calendar.refetchEvents();
  });

  // Delete booking
  document.getElementById('deleteBtn').addEventListener('click', async () => {
    const id = document.getElementById('booking_id').value;
    if (!id) return alert('Select a booking first.');
    if (!confirm('Delete this booking?')) return;
    const res = await fetch('manage_booking.php', {
      method: 'POST',
      body: new URLSearchParams({ delete_id: id })
    });
    const result = await res.json();
    alert(result.message);
    calendar.refetchEvents();
  });

  // Search booking by ID
document.getElementById('searchBookingBtn').addEventListener('click', async () => {
  const id = document.getElementById('booking_id').value.trim();
  if (!id) return alert('Please enter a booking ID.');

  const res = await fetch(`get_booking_by_id.php?id=${id}`);
  const data = await res.json();

  if (data.error) {
    alert(data.error);
    return;
  }

  // Fill the form with retrieved data
  const f = document.getElementById('bookingForm');
  f.user_id.value = data.user_id || '';
  f.pet_id.value = data.pet_id || '';
  f.service_id.value = data.service_id || '';
  f.booking_date.value = data.booking_date || '';
  f.start_time.value = data.start_time?.substring(0,5) || '';
  f.end_time.value = data.end_time?.substring(0,5) || '';
  f.booking_status.value = data.booking_status || 'pending';
  f.notes.value = data.notes || '';
});

});
