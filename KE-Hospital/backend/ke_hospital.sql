-- Create database
CREATE DATABASE IF NOT EXISTS ke_hospital;
USE ke_hospital;

-- =============================================
-- USERS TABLE (Patients) with Image Support
-- =============================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Will store hashed passwords
    phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT '/frontend/assets/profile_pic.png', -- User profile picture
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    medical_history TEXT, -- Store patient medical history
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_blood_group (blood_group)
);

-- =============================================
-- DOCTORS TABLE (with enhanced image support)
-- =============================================
CREATE TABLE doctors (
    doctor_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255), -- For doctor login (optional)
    profile_image VARCHAR(255) DEFAULT '/frontend/assets/upload_area.png', -- Doctor profile picture
    image_path VARCHAR(255), -- Legacy field for backward compatibility
    speciality VARCHAR(100) NOT NULL,
    degree VARCHAR(50) NOT NULL,
    experience VARCHAR(50) NOT NULL,
    about TEXT,
    fees DECIMAL(10, 2) NOT NULL,
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    consultation_duration INT DEFAULT 30, -- Minutes per consultation
    is_available BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3,2) DEFAULT 0, -- Average rating
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_speciality (speciality),
    INDEX idx_is_available (is_available),
    INDEX idx_rating (rating)
);

-- =============================================
-- ADMIN TABLE (with image support)
-- =============================================
CREATE TABLE admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT '/assets/admin/default-admin.png',
    role ENUM('Super Admin', 'Manager', 'Staff') DEFAULT 'Staff',
    phone VARCHAR(20),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- =============================================
-- APPOINTMENTS TABLE
-- =============================================
CREATE TABLE appointments (
    appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id VARCHAR(50) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled', 'Rescheduled') DEFAULT 'Pending',
    symptoms TEXT,
    notes TEXT,
    payment_status ENUM('Pending', 'Paid', 'Refunded') DEFAULT 'Pending',
    amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_date (appointment_date),
    INDEX idx_status (status),
    UNIQUE KEY unique_appointment (doctor_id, appointment_date, appointment_time)
);

-- =============================================
-- DOCTOR SCHEDULE TABLE
-- =============================================
CREATE TABLE doctor_schedule (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id VARCHAR(50) NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_start TIME,
    break_end TIME,
    is_holiday BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_doctor (doctor_id),
    INDEX idx_day (day_of_week),
    CONSTRAINT chk_working_hours CHECK (
        start_time >= '08:00:00' AND end_time <= '18:00:00'
    )
);

-- =============================================
-- PRESCRIPTIONS TABLE
-- =============================================
CREATE TABLE prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    doctor_id VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    medicine_name VARCHAR(200),
    dosage VARCHAR(100),
    duration VARCHAR(100),
    instructions TEXT,
    prescription_file VARCHAR(255), -- For uploaded prescription files
    prescribed_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    INDEX idx_appointment (appointment_id),
    INDEX idx_user (user_id)
);

-- =============================================
-- REVIEWS & RATINGS TABLE
-- =============================================
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    doctor_id VARCHAR(50) NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    review_images TEXT, -- Comma-separated image paths
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (appointment_id),
    INDEX idx_doctor (doctor_id),
    INDEX idx_rating (rating)
);

-- =============================================
-- MEDICAL RECORDS TABLE
-- =============================================
CREATE TABLE medical_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id VARCHAR(50) NOT NULL,
    record_type ENUM('Lab Report', 'X-Ray', 'Prescription', 'Vaccination', 'Other') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    record_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_record_type (record_type)
);

-- =============================================
-- NOTIFICATIONS TABLE
-- =============================================
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    admin_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Appointment', 'Payment', 'Reminder', 'Announcement') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read)
);

-- =============================================
-- PAYMENTS TABLE
-- =============================================
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'Mobile Money', 'Insurance') DEFAULT 'Cash',
    payment_status ENUM('Pending', 'Completed', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receipt_file VARCHAR(255), -- Payment receipt image
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_appointment (appointment_id),
    INDEX idx_user (user_id),
    INDEX idx_status (payment_status)
);

-- =============================================
-- INSERT DOCTOR DATA (Enhanced)
-- =============================================
INSERT INTO doctors (doctor_id, name, profile_image, speciality, degree, experience, about, fees, address_line1, address_line2, rating, total_reviews) VALUES
('doc1', 'Dr. Mwila Banda', '/assets/doctors/doc1.jpg', 'General Physician', 'MBChB', '5 Years', 'Dr. Banda provides comprehensive medical care, focusing on preventive health, chronic disease management, and family medicine.', 250, 'K&E-Hospital', 'Great East Road, Lusaka', 4.5, 28),
('doc2', 'Dr. Mutinta Phiri', '/assets/doctors/doc2.jpg', 'Gynecologist', 'MBChB', '3 Years', 'Dr. Phiri specializes in maternal health and reproductive care, offering antenatal, postnatal, and family planning services.', 300, 'Levy Mwanawasa Medical University Clinic', 'Great East Road, Lusaka', 4.8, 42),
('doc3', 'Dr. Luyando Zulu', '/assets/doctors/doc3.jpg', 'Dermatologist', 'MBChB', '2 Years', 'Dr. Zulu treats skin conditions such as eczema, acne, and fungal infections, with a focus on tropical dermatology.', 220, 'K&E-Hospital, Chainama Hills Hospital', 'Great East Road, Lusaka', 4.2, 15),
('doc4', 'Dr. Christopher Tembo', '/assets/doctors/doc4.jpg', 'Pediatrician', 'MBChB', '4 Years', 'Dr. Tembo provides specialized care for children, including vaccinations, growth monitoring, and treatment of childhood illnesses.', 280, 'K&E-Hospital, Matero Level One Hospital', 'Matero, Lusaka', 4.7, 35),
('doc5', 'Dr. Chipo Mwansa', '/assets/doctors/doc5.jpg', 'Neurologist', 'MBChB', '6 Years', 'Dr. Mwansa is committed to diagnosing and managing neurological conditions such as epilepsy, migraines, and stroke recovery.', 350, 'K&E-Hospital, Coptic Hospital', 'Kafue Road, Lusaka', 4.9, 52),
('doc6', 'Dr. Kelvin Mulenga', '/assets/doctors/doc6.jpg', 'Neurologist', 'MBChB', '5 Years', 'Dr. Mulenga provides advanced neurological care, focusing on rehabilitation and long-term management of chronic conditions.', 320, 'K&E-Hospital, Medland Hospital', 'Great East Road, Lusaka', 4.6, 31),
('doc7', 'Dr. Patrick Tembo', '/assets/doctors/doc7.jpg', 'General Physician', 'MBChB', '4 Years', 'Dr. Tembo emphasizes preventive medicine and holistic care, ensuring patients receive timely diagnosis and effective treatment.', 260, 'K&E-Hospital, Victoria Hospital', 'Independence Avenue, Lusaka', 4.4, 23),
('doc8', 'Dr. Lillian Chanda', '/assets/doctors/doc8.jpg', 'Gynecologist', 'MBChB', '3 Years', 'Dr. Chanda is passionate about women''s health, offering services in reproductive health, safe deliveries, and gynecological surgeries.', 300, 'K&E-Hospital, Fairview Hospital', 'Cairo Road, Lusaka', 4.7, 38),
('doc9', 'Dr. Thandiwe Kapasa', '/assets/doctors/doc9.jpg', 'Dermatologist', 'MBChB', '2 Years', 'Dr. Kapasa treats a wide range of skin conditions and promotes awareness of skin health in Zambia''s tropical climate.', 220, 'K&E-Hospital, St. John''s Hospital', 'Chelstone, Lusaka', 4.3, 19),
('doc10', 'Dr. Joseph Mwansa', '/assets/doctors/doc10.jpg', 'Pediatrician', 'MBChB', '4 Years', 'Dr. Mwansa provides compassionate care for children, ensuring their health and wellbeing through preventive and curative services.', 280, 'K&E-Hospital, Chilenje Level One Hospital', 'Chilenje, Lusaka', 4.5, 27);

-- =============================================
-- INSERT DOCTOR SCHEDULES
-- =============================================
INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time) VALUES
('doc1', 'Monday', '09:00:00', '17:00:00'),
('doc1', 'Tuesday', '09:00:00', '17:00:00'),
('doc1', 'Wednesday', '09:00:00', '17:00:00'),
('doc1', 'Thursday', '09:00:00', '17:00:00'),
('doc1', 'Friday', '09:00:00', '16:00:00'),
('doc2', 'Monday', '08:00:00', '16:00:00'),
('doc2', 'Wednesday', '08:00:00', '16:00:00'),
('doc2', 'Friday', '08:00:00', '14:00:00'),
('doc3', 'Tuesday', '10:00:00', '18:00:00'),
('doc3', 'Thursday', '10:00:00', '18:00:00'),
('doc4', 'Monday', '09:00:00', '17:00:00'),
('doc4', 'Wednesday', '09:00:00', '17:00:00'),
('doc4', 'Friday', '09:00:00', '17:00:00'),
('doc5', 'Tuesday', '09:00:00', '17:00:00'),
('doc5', 'Thursday', '09:00:00', '17:00:00');

-- =============================================
-- INSERT DEFAULT ADMIN (with hashed password example)
-- =============================================
-- Note: In production, always hash passwords using bcrypt
-- The password 'admin123' hashed with bcrypt would look like: $2b$10$YourHashedPasswordHere
INSERT INTO admin (full_name, email, password, role, profile_image, phone) VALUES
('K&E Hospital Admin', 'admin@kehospital.com', '$2b$10$YourHashedPasswordHere', 'Super Admin', '/assets/admin/admin-avatar.jpg', '+260 123 456789');

-- =============================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =============================================

-- Update doctor rating when new review is added
DELIMITER //
CREATE TRIGGER update_doctor_rating
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE review_count INT;
    
    SELECT AVG(rating), COUNT(*) INTO avg_rating, review_count
    FROM reviews
    WHERE doctor_id = NEW.doctor_id;
    
    UPDATE doctors
    SET rating = avg_rating,
        total_reviews = review_count
    WHERE doctor_id = NEW.doctor_id;
END//
DELIMITER ;

-- Update appointment status when payment is completed
DELIMITER //
CREATE TRIGGER update_appointment_payment
AFTER UPDATE ON payments
FOR EACH ROW
BEGIN
    IF NEW.payment_status = 'Completed' AND OLD.payment_status != 'Completed' THEN
        UPDATE appointments
        SET payment_status = 'Paid'
        WHERE appointment_id = NEW.appointment_id;
    END IF;
END//
DELIMITER ;

-- =============================================
-- VIEWS FOR COMMON QUERIES
-- =============================================

-- View for complete doctor information
CREATE VIEW doctors_full_view AS
SELECT 
    d.*,
    GROUP_CONCAT(DISTINCT ds.day_of_week) as available_days,
    COUNT(DISTINCT a.appointment_id) as total_appointments,
    COUNT(DISTINCT CASE WHEN a.status = 'Completed' THEN a.appointment_id END) as completed_appointments
FROM doctors d
LEFT JOIN doctor_schedule ds ON d.doctor_id = ds.doctor_id AND ds.is_holiday = FALSE
LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
GROUP BY d.doctor_id;

-- View for user appointment history
CREATE VIEW user_appointment_history AS
SELECT 
    u.user_id,
    u.full_name,
    u.profile_image,
    a.appointment_id,
    d.name as doctor_name,
    d.profile_image as doctor_image,
    a.appointment_date,
    a.appointment_time,
    a.status,
    a.created_at
FROM users u
JOIN appointments a ON u.user_id = a.user_id
JOIN doctors d ON a.doctor_id = d.doctor_id;

-- View for popular doctors (based on reviews and appointments)
CREATE VIEW popular_doctors_view AS
SELECT 
    d.doctor_id,
    d.name,
    d.speciality,
    d.profile_image,
    d.rating,
    d.total_reviews,
    COUNT(a.appointment_id) as total_bookings,
    ROUND(d.rating * COUNT(a.appointment_id) / 100, 2) as popularity_score
FROM doctors d
LEFT JOIN appointments a ON d.doctor_id = a.doctor_id
WHERE d.is_available = TRUE
GROUP BY d.doctor_id
ORDER BY popularity_score DESC;

-- =============================================
-- STORED PROCEDURES
-- =============================================

DELIMITER //

-- Procedure to get doctor availability for a specific date
CREATE PROCEDURE GetDoctorAvailability(
    IN p_doctor_id VARCHAR(50),
    IN p_date DATE
)
BEGIN
    SELECT 
        ds.start_time,
        ds.end_time,
        CASE 
            WHEN a.appointment_time IS NOT NULL THEN 'Booked'
            ELSE 'Available'
        END as status,
        a.appointment_time
    FROM doctor_schedule ds
    LEFT JOIN appointments a ON ds.doctor_id = a.doctor_id 
        AND a.appointment_date = p_date 
        AND a.status NOT IN ('Cancelled', 'Completed')
    WHERE ds.doctor_id = p_doctor_id 
        AND ds.day_of_week = DAYNAME(p_date)
        AND ds.is_holiday = FALSE;
END//

-- Procedure to get user dashboard data
CREATE PROCEDURE GetUserDashboard(IN p_user_id INT)
BEGIN
    -- Upcoming appointments
    SELECT 
        a.appointment_id,
        d.name as doctor_name,
        d.speciality,
        d.profile_image as doctor_image,
        a.appointment_date,
        a.appointment_time,
        a.status
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.user_id = p_user_id 
        AND a.appointment_date >= CURDATE()
        AND a.status NOT IN ('Cancelled', 'Completed')
    ORDER BY a.appointment_date, a.appointment_time
    LIMIT 5;
    
    -- Recent medical records
    SELECT *
    FROM medical_records
    WHERE user_id = p_user_id
    ORDER BY record_date DESC
    LIMIT 5;
    
    -- Recent prescriptions
    SELECT 
        p.*,
        d.name as doctor_name
    FROM prescriptions p
    JOIN doctors d ON p.doctor_id = d.doctor_id
    WHERE p.user_id = p_user_id
    ORDER BY p.prescribed_date DESC
    LIMIT 5;
END//

DELIMITER ;

-- =============================================
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- =============================================

CREATE INDEX idx_appointments_user_status ON appointments(user_id, status);
CREATE INDEX idx_appointments_doctor_date ON appointments(doctor_id, appointment_date);
CREATE INDEX idx_doctors_speciality_fees ON doctors(speciality, fees);
CREATE INDEX idx_doctors_rating ON doctors(rating DESC);
CREATE INDEX idx_users_email_active ON users(email, is_active);
CREATE INDEX idx_medical_records_user_date ON medical_records(user_id, record_date DESC);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);