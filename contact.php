<?php
$pageTitle = "Contact Us & Box Office Support";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$flash = getFlash('contact_msg');
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="cine-hero">
    <div class="container text-center">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3 border border-danger bg-black bg-opacity-50 small">
            <span class="badge bg-danger rounded-pill">Customer Support</span>
            <span class="text-secondary">We're Here to Help</span>
        </div>
        <h1 class="text-white display-4 fw-bold mb-3">Get in <span class="text-danger">Touch</span></h1>
        <p class="text-secondary lead fs-6 mx-auto mb-0" style="max-width: 680px;">
            Have questions about ticket bookings, show schedules, private hall reservations, or feedback? Drop us a message or reach our 24/7 cinema helpline.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show mb-4 shadow" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <div class="col-lg-7">
                <div class="cine-card p-4 p-md-5 shadow-lg">
                    <h3 class="text-white fw-bold mb-2">Send Us a Message</h3>
                    <p class="text-secondary small mb-4">Fill out the form below and our box-office staff will respond within 24 hours.</p>

                    <form action="<?= url('actions/contact_action.php') ?>" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-light small fw-semibold">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control cine-form-control" placeholder="e.g. Ali Khan" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-light small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control cine-form-control" placeholder="name@example.com" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-light small fw-semibold">Subject <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control cine-form-control" placeholder="e.g. Booking Query / Private Screening" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label text-light small fw-semibold">Your Message <span class="text-danger">*</span></label>
                                <textarea name="message" rows="5" class="form-control cine-form-control" placeholder="Write your message or inquiry here..." required></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-cine-primary w-100 py-2">
                                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Inquiry
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="d-flex flex-column gap-4">
                    <div class="cine-card p-4">
                        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary pb-2">
                            <i class="fa-solid fa-headset text-danger me-2"></i> Box Office Helpline
                        </h5>
                        <div class="d-flex flex-column gap-3 small">
                            <div class="d-flex gap-3">
                                <span class="brand-icon" style="width: 36px; height: 36px;"><i class="fa-solid fa-phone"></i></span>
                                <div>
                                    <div class="text-white fw-semibold">Phone Inquiries</div>
                                    <div class="text-secondary">+92 (021) 111-287-486</div>
                                    <div class="text-secondary">+92 (0300) 123-4567</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <span class="brand-icon" style="width: 36px; height: 36px;"><i class="fa-solid fa-envelope"></i></span>
                                <div>
                                    <div class="text-white fw-semibold">Email Address</div>
                                    <div class="text-secondary"><a href="mailto:ibtisamadnan06@gmail.com" class="text-secondary text-decoration-none">ibtisamadnan06@gmail.com</a></div>
                                    <div class="text-secondary">support@cinepass.pk</div>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <span class="brand-icon" style="width: 36px; height: 36px;"><i class="fa-solid fa-clock"></i></span>
                                <div>
                                    <div class="text-white fw-semibold">Operating Hours</div>
                                    <div class="text-secondary">Monday - Sunday: 10:00 AM - 12:00 Midnight</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cine-card p-4">
                        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary pb-2">
                            <i class="fa-solid fa-location-dot text-danger me-2"></i> Project Center
                        </h5>
                        <div class="small text-secondary">
                            <strong class="text-white d-block mb-1">Aptech Computer Education</strong>
                            Shahr-e-Faisal Centre, Karachi, Pakistan.<br>
                            Semester 2 Final Project Presentation & Evaluation.
                        </div>
                    </div>

                    <div class="cine-card p-4">
                        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary pb-2">
                            <i class="fa-solid fa-circle-question text-warning me-2"></i> Quick FAQs
                        </h5>
                        <div class="accordion accordion-flush small" id="faqAccordion">
                            <div class="accordion-item bg-transparent text-secondary border-secondary">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-white px-0 py-2 small shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        Can I cancel or refund my ticket?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body px-0 py-2 text-secondary">
                                        Yes, bookings can be cancelled up to 2 hours prior to showtime from your User Dashboard.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item bg-transparent text-secondary border-secondary">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-white px-0 py-2 small shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        How do I collect physical tickets?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body px-0 py-2 text-secondary">
                                        Show your SMS or Digital Booking Code (`CP-BK-...`) at the cinema counter or kiosk scanner.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
