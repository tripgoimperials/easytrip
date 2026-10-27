<?php require 'includes/init.php';require_login();Booking::releaseExpired();$tid=(int)($_GET['trip']??0);$trip=Bus::trip($tid);if(!$trip)exit('Trip not found.');$seats=Seat::forTrip($tid);include 'includes/header.php'; ?>
<main class="page"><div class="wrap"><div class="steps"><div class="step active"><b>1</b>Search</div><div class="step active"><b>2</b>Select Bus</div><div class="step active"><b>3</b>Choose Seat</div><div class="step"><b>4</b>Payment</div><div class="step"><b>5</b>Confirmation</div></div><div class="card seat-layout"><h2>Choose Your Seat</h2><p class="subtitle"><?= e($trip['origin'].' → '.$trip['destination']) ?> · <?= naira($trip['price']) ?> per seat · maximum 5 seats</p><?php if(isset($_GET['err'])): ?><div class="err"><?= e($_GET['err']) ?></div><?php endif; ?><div class="legend"><span><i class="avail"></i>Available</span><span><i class="booked"></i>Booked</span><span><i class="sel"></i>Selected</span></div><div class="bus-shell"><div class="front">Front</div><div class="seats" id="liveSeats"><?php foreach($seats as $s): ?><button type="button" class="seat<?= $s['taken']?' taken':'' ?>" data-id="<?= (int)$s['id'] ?>" data-number="<?= (int)$s['seat_number'] ?>" <?= $s['taken']?'disabled':'' ?>><?= (int)$s['seat_number'] ?></button><?php endforeach; ?></div><div class="front">Back</div></div><form method="post" action="book.php"><input type="hidden" name="trip" value="<?= $tid ?>"><div id="seatInputs"></div><div class="seat-total"><span>Selected: <b id="seatNo">None</b></span><strong id="total">₦0.00</strong></div><button id="continueSeat" class="btn primary full" style="margin-top:15px" disabled>Continue to Payment</button></form></div></div></main>
<script>
const price = <?= (float)$trip['price'] ?>;
const selected = new Map();
const tripId = <?= $tid ?>;
const seatStatus = document.createElement('p');
seatStatus.className = 'meta';
seatStatus.id = 'seatStatus';
seatStatus.textContent = 'Live seat availability is active.';
document.querySelector('.bus-shell').after(seatStatus);

function syncSeats() {
    document.getElementById('seatInputs').innerHTML = [...selected.keys()]
        .map(id => `<input type="hidden" name="seats[]" value="${id}">`).join('');
    document.getElementById('seatNo').textContent = selected.size ? [...selected.values()].join(', ') : 'None';
    document.getElementById('total').textContent = '₦' + (selected.size * price).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('continueSeat').disabled = selected.size === 0;
}

document.querySelectorAll('#liveSeats .seat').forEach(el => el.addEventListener('click', () => {
    if (el.classList.contains('taken')) return;
    const id = Number(el.dataset.id);
    if (selected.has(id)) {
        selected.delete(id);
        el.classList.remove('selected');
    } else if (selected.size < 5) {
        selected.set(id, el.dataset.number);
        el.classList.add('selected');
    }
    syncSeats();
}));

async function refreshSeatAvailability() {
    const status = document.getElementById('seatStatus');
    try {
        const response = await fetch(`api/seats.php?trip=${encodeURIComponent(tripId)}`, {
            headers: {'Accept': 'application/json'},
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Seat refresh failed.');
        const taken = data.taken.map(Number);
        document.querySelectorAll('#liveSeats .seat').forEach(el => {
            const id = Number(el.dataset.id);
            const isTaken = taken.includes(id);
            el.classList.toggle('taken', isTaken);
            el.disabled = isTaken;
            if (isTaken && selected.has(id)) {
                selected.delete(id);
                el.classList.remove('selected');
            }
        });
        syncSeats();
        status.textContent = 'Seat availability refreshed automatically.';
    } catch (error) {
        status.textContent = 'Could not refresh seats automatically. Server validation will still check before booking.';
    }
}

refreshSeatAvailability();
setInterval(refreshSeatAvailability, 4000);
</script><?php include 'includes/footer.php'; ?>
