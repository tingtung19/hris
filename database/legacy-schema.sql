-- ============================================================
-- Internal HRIS - Database Schema
-- MySQL 8+ / InnoDB / utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS hris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hris;

-- ============================================================
-- 1. ORGANIZATION
-- ============================================================

CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    legal_name VARCHAR(150) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    postal_code VARCHAR(10) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(100) NULL,
    logo VARCHAR(255) NULL,
    npwp VARCHAR(30) NULL,
    established_date DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(150) NOT NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    is_head_office TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_branch_code (company_id, code),
    CONSTRAINT fk_branch_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(150) NOT NULL,
    head_employee_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_department_code (company_id, code),
    CONSTRAINT fk_department_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_department_branch FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE divisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(150) NOT NULL,
    head_employee_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_division_code (department_id, code),
    CONSTRAINT fk_division_department FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    division_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(150) NOT NULL,
    head_employee_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_section_code (division_id, code),
    CONSTRAINT fk_section_division FOREIGN KEY (division_id) REFERENCES divisions(id)
) ENGINE=InnoDB;

CREATE TABLE job_levels (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    level_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE job_grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    grade_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE positions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    job_level_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_position_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_position_job_level FOREIGN KEY (job_level_id) REFERENCES job_levels(id)
) ENGINE=InnoDB;

CREATE TABLE work_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    radius_meter INT NOT NULL DEFAULT 100,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE cost_centers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_costcenter_department FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

-- ============================================================
-- 2. EMPLOYEE
-- ============================================================

CREATE TABLE employees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_number VARCHAR(30) NOT NULL UNIQUE,
    nip VARCHAR(50) NULL UNIQUE,
    nik VARCHAR(20) NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NULL,
    photo VARCHAR(255) NULL,
    birth_place VARCHAR(100) NULL,
    birth_date DATE NULL,
    gender ENUM('male','female') NOT NULL DEFAULT 'male',
    religion VARCHAR(30) NULL,
    marital_status ENUM('single','married','divorced','widowed') NOT NULL DEFAULT 'single',
    phone VARCHAR(30) NULL,
    personal_email VARCHAR(100) NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    department_id BIGINT UNSIGNED NULL,
    division_id BIGINT UNSIGNED NULL,
    section_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    job_level_id BIGINT UNSIGNED NULL,
    job_grade_id BIGINT UNSIGNED NULL,
    work_location_id BIGINT UNSIGNED NULL,
    cost_center_id BIGINT UNSIGNED NULL,
    supervisor_id BIGINT UNSIGNED NULL,
    manager_id BIGINT UNSIGNED NULL,
    join_date DATE NOT NULL,
    appointment_date DATE NULL,
    resign_date DATE NULL,
    employment_status ENUM('active','probation','resigned','terminated') NOT NULL DEFAULT 'probation',
    employment_type ENUM('permanent','contract','intern','daily','freelance') NOT NULL DEFAULT 'contract',
    bank_name VARCHAR(100) NULL,
    bank_account_number VARCHAR(50) NULL,
    bank_account_holder VARCHAR(150) NULL,
    npwp VARCHAR(30) NULL,
    ptkp_status VARCHAR(10) NULL,
    bpjs_health_number VARCHAR(30) NULL,
    bpjs_employment_number VARCHAR(30) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_employee_department (department_id),
    INDEX idx_employee_status (employment_status),
    INDEX idx_employee_supervisor (supervisor_id),
    CONSTRAINT fk_employee_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_employee_branch FOREIGN KEY (branch_id) REFERENCES branches(id),
    CONSTRAINT fk_employee_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_employee_division FOREIGN KEY (division_id) REFERENCES divisions(id),
    CONSTRAINT fk_employee_section FOREIGN KEY (section_id) REFERENCES sections(id),
    CONSTRAINT fk_employee_position FOREIGN KEY (position_id) REFERENCES positions(id),
    CONSTRAINT fk_employee_joblevel FOREIGN KEY (job_level_id) REFERENCES job_levels(id),
    CONSTRAINT fk_employee_jobgrade FOREIGN KEY (job_grade_id) REFERENCES job_grades(id),
    CONSTRAINT fk_employee_worklocation FOREIGN KEY (work_location_id) REFERENCES work_locations(id),
    CONSTRAINT fk_employee_costcenter FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id),
    CONSTRAINT fk_employee_supervisor FOREIGN KEY (supervisor_id) REFERENCES employees(id),
    CONSTRAINT fk_employee_manager FOREIGN KEY (manager_id) REFERENCES employees(id)
) ENGINE=InnoDB;

ALTER TABLE departments ADD CONSTRAINT fk_department_head FOREIGN KEY (head_employee_id) REFERENCES employees(id);
ALTER TABLE divisions ADD CONSTRAINT fk_division_head FOREIGN KEY (head_employee_id) REFERENCES employees(id);
ALTER TABLE sections ADD CONSTRAINT fk_section_head FOREIGN KEY (head_employee_id) REFERENCES employees(id);

CREATE TABLE employee_addresses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    type ENUM('ktp','domicile') NOT NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    postal_code VARCHAR(10) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employee_address (employee_id, type),
    CONSTRAINT fk_address_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employee_contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    relationship VARCHAR(50) NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contact_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employee_families (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    nik VARCHAR(20) NULL,
    relationship ENUM('spouse','child','other') NOT NULL DEFAULT 'child',
    birth_date DATE NULL,
    occupation VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    is_dependent TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_family_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employee_educations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    level ENUM('sd','smp','sma','d3','s1','s2','s3') NOT NULL,
    school_name VARCHAR(150) NOT NULL,
    major VARCHAR(100) NULL,
    graduation_year YEAR NULL,
    gpa DECIMAL(3,2) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_education_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employee_experiences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    position VARCHAR(150) NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_experience_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employee_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    category ENUM('ktp','kk','npwp','bpjs','ijazah','certificate','cv','contract','appointment_letter','promotion_letter','mutation_letter','other') NOT NULL,
    name VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NULL,
    mime_type VARCHAR(100) NULL,
    expiry_date DATE NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_document_expiry (expiry_date),
    CONSTRAINT fk_document_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employee_contracts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    contract_number VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('probation','pkwt','pkwtt') NOT NULL DEFAULT 'pkwt',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active','expired','terminated','renewed') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    document_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_contract_end (end_date),
    CONSTRAINT fk_contract_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_contract_document FOREIGN KEY (document_id) REFERENCES employee_documents(id)
) ENGINE=InnoDB;

CREATE TABLE employee_career_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    type ENUM('promotion','mutation','transfer','demotion','position_change','department_change','salary_change','supervisor_change') NOT NULL,
    effective_date DATE NOT NULL,
    from_position_id BIGINT UNSIGNED NULL,
    to_position_id BIGINT UNSIGNED NULL,
    from_department_id BIGINT UNSIGNED NULL,
    to_department_id BIGINT UNSIGNED NULL,
    from_salary DECIMAL(15,2) NULL,
    to_salary DECIMAL(15,2) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_career_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_career_from_position FOREIGN KEY (from_position_id) REFERENCES positions(id),
    CONSTRAINT fk_career_to_position FOREIGN KEY (to_position_id) REFERENCES positions(id),
    CONSTRAINT fk_career_from_department FOREIGN KEY (from_department_id) REFERENCES departments(id),
    CONSTRAINT fk_career_to_department FOREIGN KEY (to_department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

-- ============================================================
-- 3. AUTH & RBAC
-- ============================================================

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    failed_login_attempts INT NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    remember_token VARCHAR(64) NULL,
    password_reset_token VARCHAR(64) NULL,
    password_reset_expires_at DATETIME NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_permission (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_role (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE login_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    username_attempt VARCHAR(50) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    status ENUM('success','failed') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_login_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE employee_documents ADD CONSTRAINT fk_document_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id);
ALTER TABLE employee_contracts ADD CONSTRAINT fk_contract_creator FOREIGN KEY (created_by) REFERENCES users(id);
ALTER TABLE employee_career_histories ADD CONSTRAINT fk_career_creator FOREIGN KEY (created_by) REFERENCES users(id);

CREATE TABLE employee_salaries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    basic_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    effective_date DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_salary_employee_active (employee_id, is_active),
    CONSTRAINT fk_salary_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_salary_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 4. SHIFT & SCHEDULE
-- ============================================================

CREATE TABLE shifts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_start TIME NULL,
    break_end TIME NULL,
    grace_period_minutes INT NOT NULL DEFAULT 0,
    is_overnight TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE work_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE work_schedule_days (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_schedule_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL,
    shift_id BIGINT UNSIGNED NULL,
    is_working_day TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_schedule_day (work_schedule_id, day_of_week),
    CONSTRAINT fk_scheduleday_schedule FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_scheduleday_shift FOREIGN KEY (shift_id) REFERENCES shifts(id)
) ENGINE=InnoDB;

CREATE TABLE shift_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    work_schedule_id BIGINT UNSIGNED NULL,
    shift_id BIGINT UNSIGNED NULL,
    date DATE NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_shiftassign_employee (employee_id, start_date, end_date),
    CONSTRAINT fk_shiftassign_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_shiftassign_schedule FOREIGN KEY (work_schedule_id) REFERENCES work_schedules(id),
    CONSTRAINT fk_shiftassign_shift FOREIGN KEY (shift_id) REFERENCES shifts(id)
) ENGINE=InnoDB;

CREATE TABLE holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    type ENUM('national','company','collective_leave','custom') NOT NULL DEFAULT 'national',
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_holiday_date (date, name)
) ENGINE=InnoDB;

-- ============================================================
-- 5. ATTENDANCE
-- ============================================================

CREATE TABLE attendances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    clock_in DATETIME NULL,
    clock_out DATETIME NULL,
    clock_in_lat DECIMAL(10,7) NULL,
    clock_in_lng DECIMAL(10,7) NULL,
    clock_out_lat DECIMAL(10,7) NULL,
    clock_out_lng DECIMAL(10,7) NULL,
    clock_in_ip VARCHAR(45) NULL,
    clock_out_ip VARCHAR(45) NULL,
    clock_in_device VARCHAR(255) NULL,
    clock_out_device VARCHAR(255) NULL,
    shift_id BIGINT UNSIGNED NULL,
    status ENUM('present','late','absent','sick','permission','leave','wfh','business_trip','early_checkout') NOT NULL DEFAULT 'present',
    late_minutes INT NOT NULL DEFAULT 0,
    early_minutes INT NOT NULL DEFAULT 0,
    work_minutes INT NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance_employee_date (employee_id, date),
    INDEX idx_attendance_date (date),
    CONSTRAINT fk_attendance_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_attendance_shift FOREIGN KEY (shift_id) REFERENCES shifts(id)
) ENGINE=InnoDB;

CREATE TABLE attendance_corrections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    attendance_id BIGINT UNSIGNED NULL,
    date DATE NOT NULL,
    requested_clock_in DATETIME NULL,
    requested_clock_out DATETIME NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_correction_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_correction_attendance FOREIGN KEY (attendance_id) REFERENCES attendances(id),
    CONSTRAINT fk_correction_approver FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 6. LEAVE & PERMISSION
-- ============================================================

CREATE TABLE leave_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    default_days_per_year INT NOT NULL DEFAULT 0,
    is_paid TINYINT(1) NOT NULL DEFAULT 1,
    carry_forward TINYINT(1) NOT NULL DEFAULT 0,
    carry_forward_max_days INT NOT NULL DEFAULT 0,
    requires_attachment TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE leave_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    year SMALLINT NOT NULL,
    allocated_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    used_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    carried_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    adjustment_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_leave_balance (employee_id, leave_type_id, year),
    CONSTRAINT fk_leavebalance_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_leavebalance_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
) ENGINE=InnoDB;

CREATE TABLE leave_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(5,1) NOT NULL,
    reason VARCHAR(255) NULL,
    attachment_path VARCHAR(255) NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    current_step INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_leave_status (status),
    CONSTRAINT fk_leaverequest_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_leaverequest_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
) ENGINE=InnoDB;

CREATE TABLE leave_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    leave_request_id BIGINT UNSIGNED NOT NULL,
    approver_id BIGINT UNSIGNED NOT NULL,
    step_order INT NOT NULL,
    step_role VARCHAR(50) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NULL,
    acted_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_leaveapproval_request FOREIGN KEY (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_leaveapproval_approver FOREIGN KEY (approver_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE permission_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permission_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    permission_type_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    reason VARCHAR(255) NOT NULL,
    attachment_path VARCHAR(255) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_permrequest_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_permrequest_type FOREIGN KEY (permission_type_id) REFERENCES permission_types(id),
    CONSTRAINT fk_permrequest_approver FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 7. OVERTIME
-- ============================================================

CREATE TABLE overtime_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    duration_minutes INT NOT NULL,
    reason VARCHAR(255) NULL,
    rate_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.50,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    current_step INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_overtime_status (status),
    CONSTRAINT fk_overtime_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE overtime_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    overtime_request_id BIGINT UNSIGNED NOT NULL,
    approver_id BIGINT UNSIGNED NOT NULL,
    step_order INT NOT NULL,
    step_role VARCHAR(50) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NULL,
    acted_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_overtimeapproval_request FOREIGN KEY (overtime_request_id) REFERENCES overtime_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_overtimeapproval_approver FOREIGN KEY (approver_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 8. PAYROLL
-- ============================================================

CREATE TABLE salary_components (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    type ENUM('income','deduction') NOT NULL,
    calculation_type ENUM('fixed','percentage','formula') NOT NULL DEFAULT 'fixed',
    is_taxable TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE employee_salary_components (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_salary_id BIGINT UNSIGNED NOT NULL,
    salary_component_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_empsalary_component (employee_salary_id, salary_component_id),
    CONSTRAINT fk_esc_salary FOREIGN KEY (employee_salary_id) REFERENCES employee_salaries(id) ON DELETE CASCADE,
    CONSTRAINT fk_esc_component FOREIGN KEY (salary_component_id) REFERENCES salary_components(id)
) ENGINE=InnoDB;

CREATE TABLE payroll_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    payment_date DATE NULL,
    status ENUM('draft','processing','review','approved','paid','locked') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payroll_period_range (start_date, end_date),
    CONSTRAINT fk_period_creator FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_period_approver FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE payrolls (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_period_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    basic_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_income DECIMAL(15,2) NOT NULL DEFAULT 0,
    gross_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_deduction DECIMAL(15,2) NOT NULL DEFAULT 0,
    net_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_overtime_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('draft','processing','review','approved','paid','locked') NOT NULL DEFAULT 'draft',
    notes VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payroll_period_employee (payroll_period_id, employee_id),
    CONSTRAINT fk_payroll_period FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id),
    CONSTRAINT fk_payroll_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE payroll_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_id BIGINT UNSIGNED NOT NULL,
    salary_component_id BIGINT UNSIGNED NULL,
    component_name VARCHAR(100) NOT NULL,
    type ENUM('income','deduction') NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payrolldetail_payroll FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
    CONSTRAINT fk_payrolldetail_component FOREIGN KEY (salary_component_id) REFERENCES salary_components(id)
) ENGINE=InnoDB;

CREATE TABLE payroll_deductions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    payroll_period_id BIGINT UNSIGNED NULL,
    type ENUM('loan','kasbon','other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    installment_no INT NULL,
    total_installments INT NULL,
    status ENUM('pending','processed') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_deduction_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_deduction_period FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id)
) ENGINE=InnoDB;

CREATE TABLE payslips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_id BIGINT UNSIGNED NOT NULL UNIQUE,
    payslip_number VARCHAR(50) NOT NULL UNIQUE,
    generated_at DATETIME NOT NULL,
    pdf_path VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payslip_payroll FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 9. RECRUITMENT
-- ============================================================

CREATE TABLE vacancies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    position_id BIGINT UNSIGNED NULL,
    employment_type ENUM('permanent','contract','intern','daily','freelance') NOT NULL DEFAULT 'contract',
    description TEXT NULL,
    requirements TEXT NULL,
    quota INT NOT NULL DEFAULT 1,
    status ENUM('open','closed','on_hold') NOT NULL DEFAULT 'open',
    posted_date DATE NULL,
    closing_date DATE NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_vacancy_department FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_vacancy_position FOREIGN KEY (position_id) REFERENCES positions(id),
    CONSTRAINT fk_vacancy_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE candidates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vacancy_id BIGINT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    birth_date DATE NULL,
    gender ENUM('male','female') NULL,
    address TEXT NULL,
    cv_path VARCHAR(255) NULL,
    source VARCHAR(50) NULL,
    stage ENUM('applied','screening','interview','test','hr_interview','offering','hired','rejected') NOT NULL DEFAULT 'applied',
    rating DECIMAL(3,1) NULL,
    notes TEXT NULL,
    employee_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_candidate_vacancy FOREIGN KEY (vacancy_id) REFERENCES vacancies(id),
    CONSTRAINT fk_candidate_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE candidate_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    category VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_candidatedoc_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE recruitment_stages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    stage VARCHAR(30) NOT NULL,
    notes VARCHAR(255) NULL,
    changed_by BIGINT UNSIGNED NULL,
    changed_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stage_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    CONSTRAINT fk_stage_user FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE interviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    interviewer_id BIGINT UNSIGNED NULL,
    schedule_at DATETIME NOT NULL,
    location VARCHAR(255) NULL,
    type ENUM('hr','user','technical') NOT NULL DEFAULT 'hr',
    result ENUM('pending','pass','fail') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_interview_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    CONSTRAINT fk_interview_interviewer FOREIGN KEY (interviewer_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE candidate_assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    candidate_id BIGINT UNSIGNED NOT NULL,
    assessment_name VARCHAR(150) NOT NULL,
    score DECIMAL(5,2) NULL,
    notes VARCHAR(255) NULL,
    assessed_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assessment_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    CONSTRAINT fk_assessment_user FOREIGN KEY (assessed_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 10. ONBOARDING & OFFBOARDING
-- ============================================================

CREATE TABLE onboarding (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL UNIQUE,
    candidate_id BIGINT UNSIGNED NULL,
    start_date DATE NOT NULL,
    status ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
    progress_percent INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_onboarding_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_onboarding_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id)
) ENGINE=InnoDB;

CREATE TABLE onboarding_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    onboarding_id BIGINT UNSIGNED NOT NULL,
    task_name VARCHAR(150) NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    completed_by BIGINT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_onboardtask_onboarding FOREIGN KEY (onboarding_id) REFERENCES onboarding(id) ON DELETE CASCADE,
    CONSTRAINT fk_onboardtask_user FOREIGN KEY (completed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE offboarding (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    resignation_date DATE NOT NULL,
    last_working_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    status ENUM('pending','supervisor_approved','hr_approved','finance_approved','completed','rejected') NOT NULL DEFAULT 'pending',
    current_step INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_offboarding_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE exit_interviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offboarding_id BIGINT UNSIGNED NOT NULL,
    conducted_by BIGINT UNSIGNED NULL,
    feedback TEXT NULL,
    reason_category VARCHAR(100) NULL,
    would_recommend TINYINT(1) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_exitinterview_offboarding FOREIGN KEY (offboarding_id) REFERENCES offboarding(id) ON DELETE CASCADE,
    CONSTRAINT fk_exitinterview_user FOREIGN KEY (conducted_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE clearance_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offboarding_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    department VARCHAR(100) NULL,
    is_cleared TINYINT(1) NOT NULL DEFAULT 0,
    cleared_by BIGINT UNSIGNED NULL,
    cleared_at DATETIME NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clearance_offboarding FOREIGN KEY (offboarding_id) REFERENCES offboarding(id) ON DELETE CASCADE,
    CONSTRAINT fk_clearance_user FOREIGN KEY (cleared_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 11. PERFORMANCE
-- ============================================================

CREATE TABLE performance_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('monthly','quarterly','semester','annual') NOT NULL DEFAULT 'quarterly',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE kpis (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    department_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_kpi_department FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE employee_kpis (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performance_period_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    kpi_id BIGINT UNSIGNED NOT NULL,
    target DECIMAL(10,2) NOT NULL DEFAULT 0,
    weight DECIMAL(5,2) NOT NULL DEFAULT 0,
    actual DECIMAL(10,2) NULL,
    score DECIMAL(5,2) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_empkpi_period FOREIGN KEY (performance_period_id) REFERENCES performance_periods(id),
    CONSTRAINT fk_empkpi_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_empkpi_kpi FOREIGN KEY (kpi_id) REFERENCES kpis(id)
) ENGINE=InnoDB;

CREATE TABLE performance_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performance_period_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    self_score DECIMAL(5,2) NULL,
    supervisor_score DECIMAL(5,2) NULL,
    manager_score DECIMAL(5,2) NULL,
    hr_score DECIMAL(5,2) NULL,
    final_score DECIMAL(5,2) NULL,
    final_rating TINYINT NULL,
    status ENUM('draft','self_review','supervisor_review','manager_review','hr_review','completed') NOT NULL DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review_period_employee (performance_period_id, employee_id),
    CONSTRAINT fk_review_period FOREIGN KEY (performance_period_id) REFERENCES performance_periods(id),
    CONSTRAINT fk_review_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE performance_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    performance_review_id BIGINT UNSIGNED NOT NULL,
    reviewer_role ENUM('self','supervisor','manager','hr') NOT NULL,
    reviewer_id BIGINT UNSIGNED NULL,
    comments TEXT NULL,
    rating TINYINT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviewdetail_review FOREIGN KEY (performance_review_id) REFERENCES performance_reviews(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviewdetail_user FOREIGN KEY (reviewer_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 12. TRAINING
-- ============================================================

CREATE TABLE trainings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    trainer_name VARCHAR(150) NULL,
    trainer_employee_id BIGINT UNSIGNED NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    location VARCHAR(255) NULL,
    cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    quota INT NULL,
    status ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_training_trainer FOREIGN KEY (trainer_employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE training_participants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    training_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    status ENUM('registered','attended','absent','completed') NOT NULL DEFAULT 'registered',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_training_participant (training_id, employee_id),
    CONSTRAINT fk_participant_training FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE,
    CONSTRAINT fk_participant_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE training_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    training_participant_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    attended TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_training_attendance (training_participant_id, date),
    CONSTRAINT fk_trainattendance_participant FOREIGN KEY (training_participant_id) REFERENCES training_participants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    training_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    issuer VARCHAR(150) NULL,
    certificate_number VARCHAR(100) NULL,
    issued_date DATE NULL,
    expiry_date DATE NULL,
    file_path VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_certification_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_certification_training FOREIGN KEY (training_id) REFERENCES trainings(id)
) ENGINE=InnoDB;

CREATE TABLE employee_skills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    level TINYINT NOT NULL DEFAULT 1,
    assessed_at DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employee_skill (employee_id, skill_name),
    CONSTRAINT fk_skill_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

-- ============================================================
-- 13. ASSET
-- ============================================================

CREATE TABLE asset_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    asset_category_id BIGINT UNSIGNED NOT NULL,
    brand VARCHAR(100) NULL,
    serial_number VARCHAR(100) NULL,
    purchase_date DATE NULL,
    purchase_price DECIMAL(15,2) NULL,
    condition_status ENUM('new','good','fair','damaged','lost') NOT NULL DEFAULT 'good',
    status ENUM('available','assigned','maintenance','disposed') NOT NULL DEFAULT 'available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_asset_category FOREIGN KEY (asset_category_id) REFERENCES asset_categories(id)
) ENGINE=InnoDB;

CREATE TABLE asset_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    assigned_date DATE NOT NULL,
    returned_date DATE NULL,
    condition_on_assign VARCHAR(50) NULL,
    condition_on_return VARCHAR(50) NULL,
    notes VARCHAR(255) NULL,
    verified_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_assetassign_asset FOREIGN KEY (asset_id) REFERENCES assets(id),
    CONSTRAINT fk_assetassign_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_assetassign_verifier FOREIGN KEY (verified_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE asset_maintenance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id BIGINT UNSIGNED NOT NULL,
    maintenance_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    performed_by VARCHAR(150) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_maintenance_asset FOREIGN KEY (asset_id) REFERENCES assets(id)
) ENGINE=InnoDB;

-- ============================================================
-- 14. BUSINESS TRIP & REIMBURSEMENT
-- ============================================================

CREATE TABLE business_trips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    destination VARCHAR(150) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    transportation VARCHAR(100) NULL,
    hotel VARCHAR(150) NULL,
    budget DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected','completed','settled') NOT NULL DEFAULT 'pending',
    current_step INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_trip_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE business_trip_expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_trip_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL,
    receipt_path VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tripexpense_trip FOREIGN KEY (business_trip_id) REFERENCES business_trips(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reimbursement_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    max_amount DECIMAL(15,2) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE reimbursements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id BIGINT UNSIGNED NOT NULL,
    reimbursement_category_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description VARCHAR(255) NULL,
    receipt_path VARCHAR(255) NULL,
    status ENUM('pending','manager_approved','finance_verified','paid','rejected') NOT NULL DEFAULT 'pending',
    current_step INT NOT NULL DEFAULT 1,
    paid_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reimbursement_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_reimbursement_category FOREIGN KEY (reimbursement_category_id) REFERENCES reimbursement_categories(id)
) ENGINE=InnoDB;

-- ============================================================
-- 15. ANNOUNCEMENT & NOTIFICATION
-- ============================================================

CREATE TABLE announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    target_type ENUM('all','department','branch','role','selected') NOT NULL DEFAULT 'all',
    publish_at DATETIME NULL,
    expire_at DATETIME NULL,
    status ENUM('draft','published','expired') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_announcement_creator FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE announcement_targets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id BIGINT UNSIGNED NOT NULL,
    target_type ENUM('department','branch','role','employee') NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_anntarget_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE announcement_reads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL,
    UNIQUE KEY uq_announcement_read (announcement_id, employee_id),
    CONSTRAINT fk_annread_announcement FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    CONSTRAINT fk_annread_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notification_user_read (user_id, is_read),
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 16. APPROVAL ENGINE (configurable, cross-module)
-- ============================================================

CREATE TABLE approval_workflows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE approval_steps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    approval_workflow_id BIGINT UNSIGNED NOT NULL,
    step_order INT NOT NULL,
    approver_type ENUM('supervisor','manager','role','specific_user','department_head') NOT NULL,
    role_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_workflow_step (approval_workflow_id, step_order),
    CONSTRAINT fk_step_workflow FOREIGN KEY (approval_workflow_id) REFERENCES approval_workflows(id) ON DELETE CASCADE,
    CONSTRAINT fk_step_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_step_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE approval_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    approval_workflow_id BIGINT UNSIGNED NOT NULL,
    reference_type VARCHAR(50) NOT NULL,
    reference_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    current_step INT NOT NULL DEFAULT 1,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_approvalrequest_reference (reference_type, reference_id),
    CONSTRAINT fk_approvalrequest_workflow FOREIGN KEY (approval_workflow_id) REFERENCES approval_workflows(id),
    CONSTRAINT fk_approvalrequest_user FOREIGN KEY (requested_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE approval_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    approval_request_id BIGINT UNSIGNED NOT NULL,
    step_order INT NOT NULL,
    approver_id BIGINT UNSIGNED NULL,
    action ENUM('approved','rejected','delegated') NULL,
    notes VARCHAR(255) NULL,
    acted_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_request FOREIGN KEY (approval_request_id) REFERENCES approval_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_approver FOREIGN KEY (approver_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 17. AUDIT LOG & SETTINGS
-- ============================================================

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id VARCHAR(50) NULL,
    description VARCHAR(255) NULL,
    before_data JSON NULL,
    after_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_module (module),
    INDEX idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
