-- Create Database (Optional, usually done manually or by setup)
-- CREATE DATABASE IF NOT EXISTS catalogodigital;
-- USE catalogodigital;

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Businesses Table (Multi-tenant)
CREATE TABLE IF NOT EXISTS businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE, -- For friendly URLs if needed
    logo VARCHAR(255),
    whatsapp VARCHAR(20),
    payment_info TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NULL, -- NULL for Super Admin
    role_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Roles
INSERT INTO roles (id, name, description) VALUES
(1, 'super_admin', 'Acceso total al sistema'),
(2, 'admin_negocio', 'Administrador de un negocio'),
(3, 'colaborador', 'Colaborador de un negocio')
ON DUPLICATE KEY UPDATE name=name;

-- Insert Super Admin
-- Password: Joselo@72496973
-- Hash: $2y$10$QBt9ZTCWlU1hV9fGWpwjEeMCXsrBM872XZ2lHqMlathXR1PDHlyga
INSERT INTO users (role_id, business_id, name, email, password) VALUES
(1, NULL, 'Super Admin', 'joselodigital.peru@gmail.com', '$2y$10$QBt9ZTCWlU1hV9fGWpwjEeMCXsrBM872XZ2lHqMlathXR1PDHlyga')
ON DUPLICATE KEY UPDATE email=email;

-- WhatsApp Settings (Global)
CREATE TABLE IF NOT EXISTS whatsapp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    base_message_es TEXT NOT NULL,
    base_message_en TEXT NOT NULL,
    default_language ENUM('es','en') NOT NULL DEFAULT 'es',
    include_emojis TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO whatsapp_settings (id, base_message_es, base_message_en, default_language, include_emojis)
VALUES (1, 'Hola, te escribo desde el catálogo de {negocio}. Me interesa {producto} (Precio: ${precio}).', 'Hello, I am messaging from the catalog of {business}. I am interested in {product} (Price: ${price}).', 'es', 1)
ON DUPLICATE KEY UPDATE default_language=default_language;

CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    saas_name VARCHAR(150) NOT NULL,
    main_domain VARCHAR(255) NOT NULL,
    system_logo VARCHAR(255),
    footer_text VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO app_settings (id, saas_name, main_domain, system_logo, footer_text)
VALUES (1, 'Catálogo Digital', 'catalogodigital.local', NULL, 'Powered by Joselo Digital')
ON DUPLICATE KEY UPDATE saas_name=saas_name;
