<?php
$pageTitle = "About Us - CinePass Movie Booking System";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="cine-hero">
    <div class="container text-center">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3 border border-danger bg-black bg-opacity-50 small">
            <span class="badge bg-danger rounded-pill">Premier Cinema</span>
            <span class="text-secondary">The Ultimate Movie Experience</span>
        </div>
        <h1 class="text-white display-4 fw-bold mb-3">About <span class="text-danger">CinePass</span></h1>
        <p class="text-secondary lead fs-6 mx-auto mb-0" style="max-width: 720px;">
            A premier online cinema ticketing and auditorium reservation platform designed to give movie enthusiasts real-time access to seat maps, show schedules, and seamless booking.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="d-inline-block text-danger fw-bold text-uppercase small mb-2">Our Mission</div>
                <h2 class="text-white fw-bold mb-4">Empowering Cinema Lovers with Effortless Ticketing</h2>
                <p class="text-secondary mb-3">
                    CinePass was engineered to modernize the traditional box-office line experience into an intuitive, high-speed digital web portal. From browsing upcoming Hollywood releases to picking your favorite recliner seats in IMAX, our system handles every touchpoint in seconds.
                </p>
                <p class="text-secondary mb-4">
                    Engineered with modern web standards and real-time database transaction controls, our platform provides instant seat reservation locks and seamless digital ticketing across premier cinema auditoriums.
                </p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="cine-card p-3">
                            <h3 class="text-danger fw-bold mb-0">10,000+</h3>
                            <small class="text-secondary">Tickets Processed</small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="cine-card p-3">
                            <h3 class="text-warning fw-bold mb-0">4+</h3>
                            <small class="text-secondary">Premier Cinema Branches</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="cine-card p-4 p-md-5">
                    <h4 class="text-white fw-bold mb-4 border-bottom border-secondary pb-3">
                        <i class="fa-solid fa-clapperboard text-danger me-2"></i> The CinePass Experience
                    </h4>
                    <div class="d-flex flex-column gap-3 small">
                        <div class="d-flex align-items-center gap-3">
                            <span class="brand-icon" style="width: 36px; height: 36px;"><i class="fa-solid fa-tv"></i></span>
                            <div>
                                <div class="text-white fw-bold">IMAX & 4K Laser Projection</div>
                                <div class="text-secondary">Experience breathtaking visuals with floor-to-ceiling screens and pristine laser clarity.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="brand-icon" style="width: 36px; height: 36px;"><i class="fa-solid fa-volume-high"></i></span>
                            <div>
                                <div class="text-white fw-bold">Dolby Atmos Spatial Sound</div>
                                <div class="text-secondary">360-degree multi-dimensional audio that places you right inside the movie universe.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="brand-icon" style="width: 36px; height: 36px;"><i class="fa-solid fa-couch"></i></span>
                            <div>
                                <div class="text-white fw-bold">Luxury VIP Recliner Seating</div>
                                <div class="text-secondary">Ergonomic premium plush recliners with generous legroom for the ultimate comfort.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="brand-icon" style="width: 36px; height: 36px;"><i class="fa-solid fa-qrcode"></i></span>
                            <div>
                                <div class="text-white fw-bold">Instant Contactless E-Tickets</div>
                                <div class="text-secondary">Skip box-office lines with scannable QR vouchers delivered directly to your device.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-black bg-opacity-25">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-danger fw-bold small text-uppercase">Why Choose CinePass</span>
            <h2 class="text-white fw-bold">Designed for the Ultimate Cinematic Experience</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="cine-card p-4 text-center h-100">
                    <div class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.3rem;">
                        <i class="fa-solid fa-chair"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2">Real-Time Seat Picker</h5>
                    <p class="text-secondary small mb-0">
                        Choose your exact seats (Standard, Premium, or VIP Recliners) in real time with automated collision lock preventing duplicate reservations.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cine-card p-4 text-center h-100">
                    <div class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.3rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2">Instant Ticket Confirmation</h5>
                    <p class="text-secondary small mb-0">
                        Receive instant digital booking vouchers with unique QR / alphanumeric codes for rapid box-office counter check-in.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cine-card p-4 text-center h-100">
                    <div class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.3rem;">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2">Flexible Payment Channels</h5>
                    <p class="text-secondary small mb-0">
                        Support for on-counter cash payments, credit/debit cards, and Pakistani mobile wallets (EasyPaisa & JazzCash).
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 text-center">
    <div class="container">
        <div class="cine-card p-5 border-danger shadow-lg">
            <h2 class="text-white fw-bold mb-3">Ready for Tonight's Premiere?</h2>
            <p class="text-secondary small mx-auto mb-4" style="max-width: 600px;">
                Browse our curated movie lineup and reserve your preferred seats right now.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?= url('movies.php') ?>" class="btn btn-cine-primary">
                    <i class="fa-solid fa-film me-2"></i> Browse Now Showing
                </a>
                <a href="<?= url('contact.php') ?>" class="btn btn-outline-light">
                    <i class="fa-solid fa-envelope me-2"></i> Contact Box Office
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
