<?php
 
?>
<footer class="cine-footer mt-5 pt-5 pb-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-icon"><i class="fa-solid fa-film"></i></span>
                    <span class="fs-4 fw-bold text-white">Cine<span class="text-danger">Pass</span></span>
                </div>
                <p class="text-secondary small">
                    A comprehensive Online Movie Ticket Booking System built with PHP 8, MySQL, Bootstrap 5, and JavaScript for seamless cinema ticket reservations and multiplex management.
                </p>
                <div class="d-flex gap-3 text-secondary">
                    <a href="mailto:ibtisamadnan06@gmail.com" class="footer-social-icon" title="Email: ibtisamadnan06@gmail.com"><i class="fa-solid fa-envelope"></i></a>
                    <a href="https://github.com/ibtisamadnan16-oss" target="_blank" rel="noopener noreferrer" class="footer-social-icon" title="GitHub: ibtisamadnan16-oss"><i class="fa-brands fa-github"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-3 col-6">
                <h6 class="text-white fw-bold mb-3">Explore CinePass</h6>
                <ul class="list-unstyled footer-links small">
                    <li><a href="<?= url('') ?>">Home</a></li>
                    <li><a href="<?= url('movies.php') ?>">Browse Movies</a></li>
                    <li><a href="<?= url('movies.php?status=now_showing') ?>">Now Showing</a></li>
                    <li><a href="<?= url('movies.php?status=upcoming') ?>">Upcoming</a></li>
                    <li><a href="<?= url('about.php') ?>">About Us</a></li>
                    <li><a href="<?= url('contact.php') ?>">Contact Support</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-3 col-6">
                <h6 class="text-white fw-bold mb-3">User & Admin</h6>
                <ul class="list-unstyled footer-links small">
                    <li><a href="<?= url('user/login.php') ?>">Customer Sign In</a></li>
                    <li><a href="<?= url('user/register.php') ?>">Create Account</a></li>
                    <li><a href="<?= url('admin/index.php') ?>">Admin Portal</a></li>
                    <li><a href="<?= url('documentation/PROJECT_SETUP_GUIDE.md') ?>">Setup Documentation</a></li>
                    <li><a href="<?= url('documentation/DATABASE_DESIGN_ER_DIAGRAM.md') ?>">ER Diagram Specs</a></li>
                </ul>
            </div>
        </div>

        <hr class="border-secondary opacity-25 my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-secondary">
            <div>
                &copy; <?= date('Y') ?> <strong>CinePass</strong>. All Rights Reserved.
            </div>
            <div>
                Designed for Movie Ticket Reservation & Theatre Management.
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
