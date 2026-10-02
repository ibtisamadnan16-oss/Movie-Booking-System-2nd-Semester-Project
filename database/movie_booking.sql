
CREATE DATABASE IF NOT EXISTS `movie_booking_db`
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `movie_booking_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `booking_seats`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `seats`;
DROP TABLE IF EXISTS `shows`;
DROP TABLE IF EXISTS `screens`;
DROP TABLE IF EXISTS `cinemas`;
DROP TABLE IF EXISTS `movies`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(25) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('superadmin', 'manager', 'cashier') DEFAULT 'superadmin',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cinemas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `city` VARCHAR(100) NOT NULL DEFAULT 'Karachi',
    `location` TEXT NOT NULL,
    `contact_number` VARCHAR(30) NOT NULL,
    `email` VARCHAR(150) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `screens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cinema_id` INT NOT NULL,
    `screen_name` VARCHAR(100) NOT NULL, -- e.g. "Screen 1 - Gold", "IMAX Hall"
    `screen_type` ENUM('2D', '3D', 'IMAX', '4DX') DEFAULT '2D',
    `total_seats` INT NOT NULL DEFAULT 60,
    `rows_count` INT NOT NULL DEFAULT 6,
    `cols_count` INT NOT NULL DEFAULT 10,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_screens_cinema`
        FOREIGN KEY (`cinema_id`) REFERENCES `cinemas`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_screens_cinema_id` (`cinema_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `movies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `genre` VARCHAR(100) NOT NULL,
    `language` VARCHAR(50) NOT NULL DEFAULT 'English',
    `duration_minutes` INT NOT NULL,
    `release_date` DATE NOT NULL,
    `rating` DECIMAL(3, 1) DEFAULT 8.0,
    `poster_image` VARCHAR(255) NULL,
    `trailer_url` VARCHAR(255) NULL,
    `status` ENUM('now_showing', 'upcoming', 'archived') DEFAULT 'now_showing',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_movies_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `shows` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `movie_id` INT NOT NULL,
    `cinema_id` INT NOT NULL,
    `screen_id` INT NOT NULL,
    `show_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `ticket_price` DECIMAL(10, 2) NOT NULL DEFAULT 800.00,
    `status` ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_shows_movie`
        FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_shows_cinema`
        FOREIGN KEY (`cinema_id`) REFERENCES `cinemas`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_shows_screen`
        FOREIGN KEY (`screen_id`) REFERENCES `screens`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_shows_date_status` (`show_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `screen_id` INT NOT NULL,
    `seat_number` VARCHAR(10) NOT NULL, -- e.g. "A1", "A2", "B5"
    `seat_row` VARCHAR(5) NOT NULL,    -- e.g. "A", "B", "C"
    `seat_column` INT NOT NULL,         -- e.g. 1, 2, 3
    `seat_type` ENUM('Standard', 'Premium', 'VIP') DEFAULT 'Standard',
    `price_multiplier` DECIMAL(3, 2) DEFAULT 1.00,
    `status` ENUM('active', 'under_maintenance') DEFAULT 'active',
    UNIQUE KEY `uk_screen_seat` (`screen_id`, `seat_number`),
    CONSTRAINT `fk_seats_screen`
        FOREIGN KEY (`screen_id`) REFERENCES `screens`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_seats_screen` (`screen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_code` VARCHAR(30) NOT NULL UNIQUE, -- e.g. "CP-BK-1001"
    `user_id` INT NOT NULL,
    `show_id` INT NOT NULL,
    `total_seats` INT NOT NULL DEFAULT 1,
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `booking_status` ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'confirmed',
    `booking_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_bookings_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bookings_show`
        FOREIGN KEY (`show_id`) REFERENCES `shows`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_bookings_user` (`user_id`),
    INDEX `idx_bookings_show` (`show_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `booking_seats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `show_id` INT NOT NULL,
    `seat_id` INT NOT NULL,
    `seat_price` DECIMAL(10, 2) NOT NULL,
    UNIQUE KEY `uk_show_seat` (`show_id`, `seat_id`), -- DOUBLE-BOOKING PREVENTION
    CONSTRAINT `fk_bseats_booking`
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bseats_show`
        FOREIGN KEY (`show_id`) REFERENCES `shows`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_bseats_seat`
        FOREIGN KEY (`seat_id`) REFERENCES `seats`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_bseats_booking` (`booking_id`),
    INDEX `idx_bseats_show` (`show_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `payment_method` ENUM('Cash', 'Debit/Credit Card', 'EasyPaisa', 'JazzCash') DEFAULT 'Cash',
    `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
    `amount` DECIMAL(10, 2) NOT NULL,
    `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payments_booking`
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payments_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX `idx_payments_booking` (`booking_id`),
    INDEX `idx_payments_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `full_name`, `email`, `password`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@moviebooking.com', '$2y$10$wgQAhg9ZVxf3K/1tzhlPPulsG9ECZFDyiem.Z7IvLB/JBoKKdVVsm', 'superadmin', 'active'),
(2, 'Atrium Hall Manager', 'manager.atrium@moviebooking.com', '$2y$10$wgQAhg9ZVxf3K/1tzhlPPulsG9ECZFDyiem.Z7IvLB/JBoKKdVVsm', 'manager', 'active');

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `status`) VALUES
(1, 'Ali Khan', 'user@example.com', '03001234567', '$2y$10$DJPtbJGyWywqp8SQ6luhx.n77PL2D/HZrBNnwg9Vu/m/TwqRXHWz.', 'active'),
(2, 'Sara Ahmed', 'sara.ahmed@gmail.com', '03129876543', '$2y$10$DJPtbJGyWywqp8SQ6luhx.n77PL2D/HZrBNnwg9Vu/m/TwqRXHWz.', 'active'),
(3, 'Bilal Tariq', 'bilal.tariq@yahoo.com', '03335557788', '$2y$10$DJPtbJGyWywqp8SQ6luhx.n77PL2D/HZrBNnwg9Vu/m/TwqRXHWz.', 'active');

INSERT INTO `cinemas` (`id`, `name`, `city`, `location`, `contact_number`, `email`) VALUES
(1, 'Atrium Cinemas', 'Karachi', '3rd Floor, Atrium Mall, Staff Lines, Saddar', '021-111-287-486', 'info@atriumcinemas.com.pk'),
(2, 'Nueplex Cinemas DHA', 'Karachi', 'The Place Mall, Khayaban-e-Shaheen, Phase 8, DHA', '021-111-683-683', 'contact@nueplex.com'),
(3, 'Cinepax Ocean Mall', 'Karachi', '4th Floor, Ocean Mall, Clifton Block 9', '021-111-246-372', 'support@cinepax.com');

INSERT INTO `screens` (`id`, `cinema_id`, `screen_name`, `screen_type`, `total_seats`, `rows_count`, `cols_count`) VALUES
(1, 1, 'Cinema Hall 1 (Gold)', '3D', 30, 3, 10),
(2, 1, 'Cinema Hall 2 (Silver)', '2D', 30, 3, 10),
(3, 2, 'Royal IMAX Hall', 'IMAX', 40, 4, 10),
(4, 3, 'Platinum Screen', '4DX', 30, 3, 10);

INSERT INTO `movies` (`id`, `title`, `description`, `genre`, `language`, `duration_minutes`, `release_date`, `rating`, `poster_image`, `trailer_url`, `status`) VALUES
(1, 'Oppenheimer', 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb during World War II.', 'Drama, History, Biography', 'English', 180, '2023-07-21', 8.9, 'oppenheimer.jpg', 'https://www.youtube.com/watch?v=uYPbbksJxIg', 'now_showing'),
(2, 'Dune: Part Two', 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.', 'Action, Adventure, Sci-Fi', 'English', 166, '2024-03-01', 8.6, 'dune2.jpg', 'https://www.youtube.com/watch?v=Way9Dexny3w', 'now_showing'),
(3, 'Interstellar', 'When Earth becomes uninhabitable, a team of astronauts travels through a wormhole near Saturn in search of a new home for humanity.', 'Adventure, Drama, Sci-Fi', 'English', 169, '2014-11-07', 8.7, 'interstellar.jpg', 'https://www.youtube.com/watch?v=zSWdZVtXT7E', 'now_showing'),
(4, 'Gladiator II', 'Years after witnessing the death of Maximus, Lucius must enter the Colosseum to fight against the emperors of Rome.', 'Action, Adventure, Drama', 'English', 148, '2024-11-22', 8.2, 'gladiator2.jpg', 'https://www.youtube.com/watch?v=4rgYUipGJNo', 'upcoming');

INSERT INTO `shows` (`id`, `movie_id`, `cinema_id`, `screen_id`, `show_date`, `start_time`, `end_time`, `ticket_price`, `status`) VALUES
(1, 1, 1, 1, CURDATE(), '15:00:00', '18:00:00', 900.00, 'scheduled'),
(2, 1, 1, 1, CURDATE(), '19:00:00', '22:00:00', 1000.00, 'scheduled'),
(3, 2, 2, 3, CURDATE(), '18:30:00', '21:15:00', 1200.00, 'scheduled'),
(4, 3, 3, 4, CURDATE(), '21:00:00', '23:50:00', 850.00, 'scheduled'),
(5, 2, 1, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00', '18:45:00', 950.00, 'scheduled');

INSERT INTO `seats` (`screen_id`, `seat_number`, `seat_row`, `seat_column`, `seat_type`, `price_multiplier`, `status`) VALUES
(1, 'A1', 'A', 1, 'Standard', 1.00, 'active'),
(1, 'A2', 'A', 2, 'Standard', 1.00, 'active'),
(1, 'A3', 'A', 3, 'Standard', 1.00, 'active'),
(1, 'A4', 'A', 4, 'Standard', 1.00, 'active'),
(1, 'A5', 'A', 5, 'Standard', 1.00, 'active'),
(1, 'A6', 'A', 6, 'Standard', 1.00, 'active'),
(1, 'A7', 'A', 7, 'Standard', 1.00, 'active'),
(1, 'A8', 'A', 8, 'Standard', 1.00, 'active'),
(1, 'A9', 'A', 9, 'Standard', 1.00, 'active'),
(1, 'A10', 'A', 10, 'Standard', 1.00, 'active'),
(1, 'B1', 'B', 1, 'Premium', 1.15, 'active'),
(1, 'B2', 'B', 2, 'Premium', 1.15, 'active'),
(1, 'B3', 'B', 3, 'Premium', 1.15, 'active'),
(1, 'B4', 'B', 4, 'Premium', 1.15, 'active'),
(1, 'B5', 'B', 5, 'Premium', 1.15, 'active'),
(1, 'B6', 'B', 6, 'Premium', 1.15, 'active'),
(1, 'B7', 'B', 7, 'Premium', 1.15, 'active'),
(1, 'B8', 'B', 8, 'Premium', 1.15, 'active'),
(1, 'B9', 'B', 9, 'Premium', 1.15, 'active'),
(1, 'B10', 'B', 10, 'Premium', 1.15, 'active'),
(1, 'C1', 'C', 1, 'VIP', 1.30, 'active'),
(1, 'C2', 'C', 2, 'VIP', 1.30, 'active'),
(1, 'C3', 'C', 3, 'VIP', 1.30, 'active'),
(1, 'C4', 'C', 4, 'VIP', 1.30, 'active'),
(1, 'C5', 'C', 5, 'VIP', 1.30, 'active'),
(1, 'C6', 'C', 6, 'VIP', 1.30, 'active'),
(1, 'C7', 'C', 7, 'VIP', 1.30, 'active'),
(1, 'C8', 'C', 8, 'VIP', 1.30, 'active'),
(1, 'C9', 'C', 9, 'VIP', 1.30, 'active'),
(1, 'C10', 'C', 10, 'VIP', 1.30, 'active');

INSERT INTO `seats` (`screen_id`, `seat_number`, `seat_row`, `seat_column`, `seat_type`, `price_multiplier`, `status`) VALUES
(2, 'A1', 'A', 1, 'Standard', 1.00, 'active'),
(2, 'A2', 'A', 2, 'Standard', 1.00, 'active'),
(2, 'A3', 'A', 3, 'Standard', 1.00, 'active'),
(2, 'B1', 'B', 1, 'Premium', 1.15, 'active'),
(2, 'B2', 'B', 2, 'Premium', 1.15, 'active'),
(2, 'C1', 'C', 1, 'VIP', 1.30, 'active');

INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `show_id`, `total_seats`, `total_amount`, `booking_status`, `booking_date`) VALUES
(1, 'CP-BK-202610-001', 1, 1, 2, 1800.00, 'confirmed', '2026-10-01 14:15:00'),
(2, 'CP-BK-202610-002', 2, 3, 1, 1200.00, 'confirmed', '2026-10-02 10:30:00');

INSERT INTO `booking_seats` (`id`, `booking_id`, `show_id`, `seat_id`, `seat_price`) VALUES
(1, 1, 1, 1, 900.00), -- Seat A1 on Show 1
(2, 1, 1, 2, 900.00); -- Seat A2 on Show 1

INSERT INTO `payments` (`id`, `booking_id`, `user_id`, `payment_method`, `transaction_id`, `amount`, `payment_status`, `payment_date`) VALUES
(1, 1, 1, 'Debit/Credit Card', 'TXN-984210356', 1800.00, 'completed', '2026-10-01 14:16:30'),
(2, 2, 2, 'EasyPaisa', 'TXN-773190244', 1200.00, 'completed', '2026-10-02 10:31:15');
