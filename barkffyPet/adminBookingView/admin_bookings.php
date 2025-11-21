<?php
//used for multi role control
//session_start();
//if ($_SESSION['user_role'] !== 'admin') {
//    header('Location: unauthorized.php');
//    exit;
//}
include '../db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Booking Management - Barkffy Pet</title>
    <link rel="stylesheet" href="../styles.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <style>
        main.admin-booking-main {
            max-width: 1200px;
            margin: 50px auto;
            background: #f6f6f6;
            border-radius: 15px;
            padding: 20px 30px;
        }
        #calendar {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        section.manage-booking {
            background: #e9e9e9;
            padding: 20px;
            border-radius: 10px;
        }
        section.manage-booking label {
            display: block;
            margin: 10px 0;
        }
        section.manage-booking input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
        }
        button {
            padding: 10px 18px;
            margin: 10px 5px;
            border: none;
            border-radius: 8px;
            background: #4a5568;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: #2d3748;
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">Barkffy Pet</div>
        <div class="search-box">
            <input type="text" placeholder="Search...">
            <button>🔍</button>
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="../home.html">Home</a></li> |
                <li><a href="../about.html">About Us</a></li> |
                <li><a href="../shop.html">Shop</a></li> |
                <li><a href="../booking.html">Services</a></li> |
                <li><a href="../blog.html">Blog</a></li> |
            </ul>
        </nav>
        <div class="nav-buttons">
            <button class="book-btn">Book Appointment</button>
            <button class="cart-btn">Bookings</button>
            <a href="../signin.html" class="login-link">Sign In | Sign Up</a>
        </div>
    </header>

    <main class="admin-booking-main">
        <h1 class="booking-title">Booking Calendar</h1>
        <div id="calendar"></div>

        <section class="manage-booking">
            <h2>Manage Bookings</h2>
            <form id="bookingForm">
                <div style="display: flex; align-items: center; gap: 10px;">
                <label for="booking_id" style="flex: 0 0 100px;">Booking ID:</label>
                <input type="number" name="booking_id" id="booking_id" placeholder="Enter Booking ID" style="flex: 1;">
                <button type="button" id="searchBookingBtn" style="padding: 8px 14px;">Search</button>
            </div>
            <br>
                <label>User ID: <input type="number" name="user_id" required></label>
                <label>Pet ID: <input type="number" name="pet_id"></label>
                <label>Service ID: <input type="number" name="service_id" required></label>
                <label>Booking Date: <input type="date" name="booking_date" required></label>
                <label>Start Time: <input type="time" name="start_time" required></label>
                <label>End Time: <input type="time" name="end_time"></label>
                <label>Status:
                    <select name="booking_status">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </label>
                <label>Notes: <textarea name="notes" rows="3"></textarea></label>
                <button type="submit">Save Booking</button>
                <button type="button" id="deleteBtn">Delete Booking</button>
            </form>
        </section>
    </main>
    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h3>Barkffy Pet</h3>
                <p>abc</p>
            </div>
            <div class="footer-section">
                <h3>Useful Links</h3>
                <ul>
                    <li><a href="../home.html">Home</a></li>
                    <li><a href="../shop.html">Shop</a></li>
                    <li><a href="../booking.html">Services</a></li>
                    <li><a href="../blog.html">Blog</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Address Text</h3>
                <div class="map-box">map</div>
            </div>
            <div class="footer-section">
                <h3>Social Links</h3>
                <div class="social-icons">
                    <a href="#">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                </div>
            </div>
        </div>
        <p class="copyright">© 2025 Barkffy Pet Sdn Bhd. All rights reserved</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
    <script src="admin_booking.js"></script>
</body>
</html>
