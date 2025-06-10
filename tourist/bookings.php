<?php 
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

redirectIfNotLoggedIn();

$exchangeRate = 113;

$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services WHERE is_available = TRUE");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching services: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $service_id = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $start_date = filter_input(INPUT_POST, 'start_date');
    $num_days = filter_input(INPUT_POST, 'num_days', FILTER_VALIDATE_INT) ?? 1;
    $start_time = filter_input(INPUT_POST, 'start_time');
    $end_time = filter_input(INPUT_POST, 'end_time');
    $special_requests = filter_input(INPUT_POST, 'special_requests', FILTER_SANITIZE_STRING) ?? '';

    if (!$service_id || !$start_date || !$start_time || !$end_time) {
        $_SESSION['error'] = "Please fill all required fields.";
        header("Location: bookings.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT name, price FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        $tour_name = $service ? $service['name'] : 'Tour';
        $total_price = $service['price'] * $num_days;

        $startDateObj = new DateTime($start_date);
        $endDateObj = (clone $startDateObj)->modify('+' . ($num_days - 1) . ' days');
        $end_date = $endDateObj->format('Y-m-d');

        $start_datetime = $start_date . ' ' . $start_time . ':00';
        $end_datetime = $start_date . ' ' . $end_time . ':00';

        $stmt = $pdo->prepare("INSERT INTO bookings 
            (user_id, customer_id, service_id, booking_date, end_date, start_time, end_time, special_requests, tour_name, num_days, total_price) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $user_id,
            $user_id,
            $service_id,
            $start_date,
            $end_date,
            $start_datetime,
            $end_datetime,
            $special_requests,
            $tour_name,
            $num_days,
            $total_price
        ]);

        $_SESSION['success'] = "Booking successful! Please proceed to payment.";
        header("Location: mpesa_payment.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Booking failed: " . $e->getMessage();
        header("Location: bookings.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Make a Booking | Tourism System - Nairobi</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
<style>
  /* Reset and base */
  *, *::before, *::after {
    box-sizing: border-box;
  }
  body {
    font-family: 'Inter', sans-serif;
    background: #f9fafb;
    margin: 0; padding: 0;
    color: #333;
  }
  .container {
    max-width: 900px;
    margin: 2rem auto 4rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgb(0 0 0 / 0.08);
    padding: 2rem 3rem;
  }
  h1 {
    text-align: center;
    color: #1f2937;
    margin-bottom: 2rem;
  }
  h2 {
    color: #374151;
    border-bottom: 2px solid #3b82f6;
    padding-bottom: 0.3rem;
    margin-top: 2.5rem;
    margin-bottom: 1.2rem;
    font-weight: 600;
  }
  form {
    width: 100%;
  }
  /* Alert styles */
  .alert {
    padding: 1rem 1.5rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    font-weight: 600;
    font-size: 0.95rem;
  }
  .alert.error {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #f87171;
  }
  .alert.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #34d399;
  }

  /* Service options */
  .booking-options {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
  }
  .booking-option {
    flex: 1 1 280px;
    background: #f3f4f6;
    border-radius: 10px;
    padding: 1rem 1.3rem;
    cursor: pointer;
    transition: box-shadow 0.3s ease, background-color 0.3s ease;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }
  .booking-option:hover {
    background: #e0e7ff;
    box-shadow: 0 8px 16px rgb(59 130 246 / 0.25);
  }
  .booking-option.selected {
    border-color: #3b82f6;
    background: #dbeafe;
  }
  .booking-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }
  .service-type {
    font-weight: 700;
    font-size: 0.85rem;
    padding: 0.2rem 0.6rem;
    border-radius: 9999px;
    width: fit-content;
    margin-bottom: 0.6rem;
    color: white;
  }
  .service-type.room { background: #3b82f6; }
  .service-type.pitch { background: #10b981; }
  .service-type.vehicle { background: #f59e0b; }
  .booking-option h3 {
    margin: 0 0 0.4rem 0;
    font-weight: 600;
    color: #1e293b;
  }
  .booking-option p {
    margin: 0 0 0.6rem 0;
    font-size: 0.9rem;
    color: #4b5563;
    flex-grow: 1;
  }
  .booking-option strong {
    font-weight: 700;
    color: #111827;
  }

  /* Price calculator */
  #priceCalculator {
    margin-top: 1rem;
    padding: 1rem 1.5rem;
    background: #eef2ff;
    border-radius: 10px;
    border: 1px solid #c7d2fe;
  }
  .calculator-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.6rem;
    font-size: 0.9rem;
    color: #374151;
  }
  .calculator-label {
    font-weight: 600;
  }
  .calculator-value {
    font-weight: 600;
  }
  .total-price {
    margin-top: 1rem;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e3a8a;
    text-align: right;
  }

  /* Form groups */
  .form-group {
    margin-bottom: 1.6rem;
    display: flex;
    flex-direction: column;
  }
  .form-group label {
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
  }
  input[type="date"],
  input[type="time"],
  input[type="number"],
  textarea {
    font-size: 1rem;
    padding: 0.65rem 1rem;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    transition: border-color 0.3s ease;
    font-family: inherit;
    color: #1f2937;
  }
  input[type="date"]:focus,
  input[type="time"]:focus,
  input[type="number"]:focus,
  textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 6px rgb(59 130 246 / 0.4);
  }
  textarea {
    resize: vertical;
    min-height: 90px;
  }
  input[readonly],
  input[disabled] {
    background-color: #e5e7eb;
    cursor: not-allowed;
  }

  /* Form row (for time inputs) */
  .form-row {
    display: flex;
    gap: 1.5rem;
  }
  .form-row .form-group {
    flex: 1;
  }

  /* Button */
  button.btn-primary {
    background-color: #3b82f6;
    color: white;
    font-weight: 700;
    padding: 0.85rem 2rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1.1rem;
    transition: background-color 0.3s ease;
    display: block;
    margin: 2rem auto 0;
    width: 100%;
    max-width: 280px;
  }
  button.btn-primary:hover {
    background-color: #2563eb;
  }

  /* Responsive */
  @media (max-width: 700px) {
    .booking-options {
      flex-direction: column;
    }
    .form-row {
      flex-direction: column;
    }
    button.btn-primary {
      max-width: 100%;
    }
  }
</style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <h1>Make a Booking - Nairobi</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form action="bookings.php" method="POST" novalidate>
        <h2>1. Select Service Type</h2>
        <?php if (empty($services)): ?>
            <p>No services available at the moment.</p>
        <?php else: ?>
            <div class="booking-options">
                <?php foreach ($services as $service): ?>
                    <?php
                        $usdPrice = $service['price'];
                        $kesPrice = $usdPrice * $exchangeRate;
                    ?>
                    <label class="booking-option" tabindex="0">
                        <input type="radio" name="service_id" value="<?= htmlspecialchars($service['id']) ?>" 
                               required data-price="<?= htmlspecialchars($usdPrice) ?>"
                               data-type="<?= htmlspecialchars($service['type']) ?>">
                        <span class="service-type <?= htmlspecialchars($service['type']) ?>">
                            <?= strtoupper(htmlspecialchars($service['type'])) ?>
                        </span>
                        <h3><?= htmlspecialchars($service['name']) ?></h3>
                        <p><?= htmlspecialchars($service['description']) ?></p>
                        <p><strong>Price: KES <?= number_format($kesPrice, 0) ?> (≈ $<?= number_format($usdPrice, 2) ?>) per day</strong></p>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="price-calculator" id="priceCalculator" style="display: none;">
            <h3>Price Calculator</h3>
            <div class="calculator-row">
                <span class="calculator-label">Daily Price (USD):</span>
                <span class="calculator-value" id="dailyPrice">$0.00</span>
            </div>
            <div class="calculator-row">
                <span class="calculator-label">Daily Price (KES):</span>
                <span class="calculator-value" id="dailyPriceKes">KES 0</span>
            </div>
            <div class="form-group">
                <label for="num_days">Number of Days</label>
                <input type="number" id="num_days" name="num_days" min="1" value="1" required>
            </div>
            <div class="calculator-row">
                <span class="calculator-label">Total Price (USD):</span>
                <span class="calculator-value" id="totalPrice">$0.00</span>
            </div>
            <div class="calculator-row">
                <span class="calculator-label">Total Price (KES):</span>
                <span class="calculator-value" id="totalPriceKes">KES 0</span>
            </div>
            <div class="total-price" id="totalPriceDisplay">Total: KES 0 (≈ $0.00)</div>
        </div>

        <h2>2. Select Date and Time</h2>
        <div class="form-group">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="end_date">End Date (calculated)</label>
            <input type="date" id="end_date" name="end_date" readonly disabled>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>
            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>
        </div>

        <h2>3. Additional Information</h2>
        <div class="form-group">
            <label for="special_requests">Special Requests</label>
            <textarea id="special_requests" name="special_requests" rows="4" placeholder="Any special requests or info?"></textarea>
        </div>

        <button type="submit" class="btn-primary">Confirm Booking</button>
    </form>
</div>

<script>
document.querySelectorAll('.booking-option input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.booking-option').forEach(option => {
            option.classList.remove('selected');
        });
        if (this.checked) {
            this.closest('.booking-option').classList.add('selected');
            
            const serviceType = this.dataset.type;
            const calculator = document.getElementById('priceCalculator');
            
            if (['room', 'pitch', 'vehicle'].includes(serviceType)) {
                calculator.style.display = 'block';
                updatePriceCalculator(this.dataset.price);
            } else {
                calculator.style.display = 'none';
            }
        }
    });
});

const exchangeRate = <?= $exchangeRate ?>;
const numDaysInput = document.getElementById('num_days');
const startDateInput = document.getElementById('start_date');
const endDateInput = document.getElementById('end_date');

function updateEndDate() {
    let numDays = parseInt(numDaysInput.value);
    if (isNaN(numDays) || numDays < 1) numDays = 1;

    if (startDateInput.value) {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(startDate);
        endDate.setDate(startDate.getDate() + numDays - 1);
        endDateInput.value = endDate.toISOString().split('T')[0];
    } else {
        endDateInput.value = '';
    }
}

function updatePriceCalculator(dailyPriceUSD) {
    dailyPriceUSD = parseFloat(dailyPriceUSD);
    if (isNaN(dailyPriceUSD)) dailyPriceUSD = 0;

    function calc() {
        let days = parseInt(numDaysInput.value);
        if (isNaN(days) || days < 1) days = 1;

        const totalUSD = dailyPriceUSD * days;
        const totalKES = totalUSD * exchangeRate;

        document.getElementById('dailyPrice').textContent = `$${dailyPriceUSD.toFixed(2)}`;
        document.getElementById('dailyPriceKes').textContent = `KES ${Math.round(dailyPriceUSD * exchangeRate)}`;

        document.getElementById('totalPrice').textContent = `$${totalUSD.toFixed(2)}`;
        document.getElementById('totalPriceKes').textContent = `KES ${Math.round(totalKES)}`;

        document.getElementById('totalPriceDisplay').textContent = `Total: KES ${Math.round(totalKES)} (≈ $${totalUSD.toFixed(2)})`;

        updateEndDate();
    }

    numDaysInput.removeEventListener('input', calc);
    numDaysInput.addEventListener('input', calc);
    calc();
}

numDaysInput.addEventListener('input', updateEndDate);
startDateInput.addEventListener('input', updateEndDate);
</script>

</body>
</html>
