-- Property Marketplace Database Schema
-- Created: 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:30";

-- Database: property_marketplace
CREATE DATABASE IF NOT EXISTS `property_marketplace` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `property_marketplace`;

-- --------------------------------------------------------

-- Table: users
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('buyer','seller','agent','builder','admin') NOT NULL DEFAULT 'buyer',
  `profile_image` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pincode` varchar(10) DEFAULT NULL,
  `aadhaar_number` varchar(12) DEFAULT NULL,
  `aadhaar_document` varchar(255) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `pan_document` varchar(255) DEFAULT NULL,
  `kyc_verified` enum('pending','approved','rejected') DEFAULT 'pending',
  `kyc_rejection_reason` text DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `account_status` enum('active','inactive','suspended','deleted') DEFAULT 'active',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_badge` enum('none','verified_owner','verified_agent','verified_builder') DEFAULT 'none',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `account_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: user_preferences
CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `language` varchar(10) DEFAULT 'en',
  `currency` varchar(10) DEFAULT 'INR',
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `whatsapp_notifications` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: states
CREATE TABLE `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `country` varchar(100) DEFAULT 'India',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: cities
CREATE TABLE `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `state_id` (`state_id`),
  FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: areas
CREATE TABLE `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`),
  FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_categories
CREATE TABLE `property_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_types
CREATE TABLE `property_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  FOREIGN KEY (`category_id`) REFERENCES `property_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: amenities
CREATE TABLE `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: properties
CREATE TABLE `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `price_per_sqft` decimal(10,2) DEFAULT NULL,
  `area_size` decimal(10,2) NOT NULL,
  `area_unit` enum('sqft','sqyd','sqm','acre','ground','cents') DEFAULT 'sqft',
  `bhk` int(11) DEFAULT NULL,
  `bathroom` int(11) DEFAULT NULL,
  `balcony` int(11) DEFAULT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `total_floors` int(11) DEFAULT NULL,
  `furnishing` enum('unfurnished','semi_furnished','fully_furnished') DEFAULT 'unfurnished',
  `construction_status` enum('ready_to_move','under_construction','new_launch') DEFAULT 'ready_to_move',
  `possession_date` date DEFAULT NULL,
  `age_of_property` int(11) DEFAULT NULL,
  `facing` enum('north','south','east','west','north_east','north_west','south_east','south_west') DEFAULT NULL,
  `parking` enum('none','open','covered','both') DEFAULT 'none',
  `parking_slots` int(11) DEFAULT NULL,
  `water_supply` enum('corporation','borewell','both') DEFAULT NULL,
  `power_backup` tinyint(1) DEFAULT 0,
  `lift` tinyint(1) DEFAULT 0,
  `security` tinyint(1) DEFAULT 0,
  `gated_community` tinyint(1) DEFAULT 0,
  `club_house` tinyint(1) DEFAULT 0,
  `gym` tinyint(1) DEFAULT 0,
  `pool` tinyint(1) DEFAULT 0,
  `garden` tinyint(1) DEFAULT 0,
  `play_area` tinyint(1) DEFAULT 0,
  `street` varchar(255) DEFAULT NULL,
  `area_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `rera_id` varchar(50) DEFAULT NULL,
  `rera_verified` tinyint(1) DEFAULT 0,
  `property_status` enum('available','sold','rented','under_review','rejected') DEFAULT 'under_review',
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `featured_expiry` datetime DEFAULT NULL,
  `is_premium` tinyint(1) DEFAULT 0,
  `premium_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_badge` enum('none','verified_property','verified_owner','verified_agent','verified_builder') DEFAULT 'none',
  `view_count` int(11) DEFAULT 0,
  `inquiry_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `type_id` (`type_id`),
  KEY `area_id` (`area_id`),
  KEY `city_id` (`city_id`),
  KEY `state_id` (`state_id`),
  KEY `property_status` (`property_status`),
  KEY `approval_status` (`approval_status`),
  KEY `is_featured` (`is_featured`),
  KEY `is_premium` (`is_premium`),
  KEY `is_verified` (`is_verified`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `property_categories` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`type_id`) REFERENCES `property_types` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_amenities
CREATE TABLE `property_amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_amenity` (`property_id`,`amenity_id`),
  KEY `property_id` (`property_id`),
  KEY `amenity_id` (`amenity_id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_images
CREATE TABLE `property_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_title` varchar(255) DEFAULT NULL,
  `is_cover` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_videos
CREATE TABLE `property_videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `video_path` varchar(255) NOT NULL,
  `video_title` varchar(255) DEFAULT NULL,
  `video_type` enum('upload','youtube','vimeo') DEFAULT 'upload',
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_documents
CREATE TABLE `property_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `document_title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_floor_plans
CREATE TABLE `property_floor_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `floor_title` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `area_size` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_price_history
CREATE TABLE `property_price_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `price_date` date NOT NULL,
  `price_change` decimal(10,2) DEFAULT NULL,
  `change_percentage` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_views
CREATE TABLE `property_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: wishlist
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_property` (`user_id`,`property_id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_comparison
CREATE TABLE `property_comparison` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_property` (`user_id`,`property_id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: recently_viewed
CREATE TABLE `recently_viewed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `property_id` (`property_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: inquiries
CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` text DEFAULT NULL,
  `inquiry_type` enum('property','agent','builder') DEFAULT 'property',
  `status` enum('pending','contacted','in_progress','closed','spam') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`),
  KEY `owner_id` (`owner_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: reviews
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `builder_id` int(11) DEFAULT NULL,
  `rating` int(1) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `review` text NOT NULL,
  `review_type` enum('property','agent','builder') NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`),
  KEY `agent_id` (`agent_id`),
  KEY `builder_id` (`builder_id`),
  KEY `review_type` (`review_type`),
  KEY `status` (`status`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`builder_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: property_visits
CREATE TABLE `property_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled','rescheduled') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `whatsapp_sent` tinyint(1) DEFAULT 0,
  `sms_sent` tinyint(1) DEFAULT 0,
  `email_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`),
  KEY `agent_id` (`agent_id`),
  KEY `status` (`status`),
  KEY `visit_date` (`visit_date`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: builder_profiles
CREATE TABLE `builder_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `established_year` int(4) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `total_projects` int(11) DEFAULT 0,
  `completed_projects` int(11) DEFAULT 0,
  `ongoing_projects` int(11) DEFAULT 0,
  `upcoming_projects` int(11) DEFAULT 0,
  `experience_years` int(11) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_document` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: builder_projects
CREATE TABLE `builder_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `builder_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `project_type` enum('residential','commercial','mixed_use') DEFAULT 'residential',
  `status` enum('completed','ongoing','upcoming') DEFAULT 'ongoing',
  `area_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `state_id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `total_units` int(11) DEFAULT NULL,
  `launch_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `possession_date` date DEFAULT NULL,
  `starting_price` decimal(15,2) DEFAULT NULL,
  `rera_id` varchar(50) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `brochure` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `builder_id` (`builder_id`),
  KEY `area_id` (`area_id`),
  KEY `city_id` (`city_id`),
  KEY `state_id` (`state_id`),
  FOREIGN KEY (`builder_id`) REFERENCES `builder_profiles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: agent_profiles
CREATE TABLE `agent_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `agency_name` varchar(255) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `license_document` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `languages` varchar(255) DEFAULT NULL,
  `working_areas` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_document` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `total_properties` int(11) DEFAULT 0,
  `sold_properties` int(11) DEFAULT 0,
  `rented_properties` int(11) DEFAULT 0,
  `active_listings` int(11) DEFAULT 0,
  `subscription_plan` enum('free','basic','premium','pro') DEFAULT 'free',
  `subscription_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: agent_leads
CREATE TABLE `agent_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `inquiry_id` int(11) DEFAULT NULL,
  `lead_source` varchar(50) DEFAULT NULL,
  `lead_status` enum('new','contacted','qualified','converted','lost') DEFAULT 'new',
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `agent_id` (`agent_id`),
  KEY `property_id` (`property_id`),
  KEY `inquiry_id` (`inquiry_id`),
  FOREIGN KEY (`agent_id`) REFERENCES `agent_profiles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: subscription_plans
CREATE TABLE `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_name` varchar(50) NOT NULL,
  `plan_type` enum('agent','builder') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `max_listings` int(11) DEFAULT NULL,
  `featured_listings` int(11) DEFAULT 0,
  `premium_listings` int(11) DEFAULT 0,
  `features` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: subscriptions
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `payment_id` varchar(255) DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: payments
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `payment_type` enum('subscription','featured_listing','premium_listing','advertisement') NOT NULL,
  `payment_for` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_type` (`payment_type`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: advertisements
CREATE TABLE `advertisements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ad_type` enum('banner','sidebar','sponsored','featured') NOT NULL,
  `ad_title` varchar(255) NOT NULL,
  `ad_image` varchar(255) NOT NULL,
  `ad_link` varchar(255) NOT NULL,
  `position` varchar(50) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `click_count` int(11) DEFAULT 0,
  `impression_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ad_type` (`ad_type`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: blogs
CREATE TABLE `blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: faqs
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) DEFAULT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: static_pages
CREATE TABLE `static_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: activity_logs
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: system_settings
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: price_trends
CREATE TABLE `price_trends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_id` int(11) NOT NULL,
  `property_type_id` int(11) NOT NULL,
  `avg_price_per_sqft` decimal(10,2) NOT NULL,
  `min_price` decimal(15,2) NOT NULL,
  `max_price` decimal(15,2) NOT NULL,
  `trend_month` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_type_month` (`area_id`,`property_type_id`,`trend_month`),
  KEY `area_id` (`area_id`),
  KEY `property_type_id` (`property_type_id`),
  FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table: ai_predictions
CREATE TABLE `ai_predictions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `prediction_type` varchar(50) NOT NULL,
  `prediction_value` text NOT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `model_version` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `prediction_type` (`prediction_type`),
  FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Insert Default Data

-- Insert Property Categories
INSERT INTO `property_categories` (`name`, `slug`, `icon`, `description`) VALUES
('Buy', 'buy', 'fa-home', 'Properties for sale'),
('Sell', 'sell', 'fa-tag', 'Properties for selling'),
('Rent', 'rent', 'fa-key', 'Properties for rent'),
('Lease', 'lease', 'fa-file-contract', 'Properties for lease');

-- Insert Property Types
INSERT INTO `property_types` (`category_id`, `name`, `slug`, `icon`, `description`) VALUES
(1, 'Flat/Apartment', 'buy-flat-apartment', 'fa-building', 'Residential flats and apartments'),
(1, 'House/Villa', 'buy-house-villa', 'fa-home', 'Independent houses and villas'),
(1, 'Plot/Land', 'buy-plot-land', 'fa-map', 'Residential plots and land'),
(1, 'Commercial', 'buy-commercial', 'fa-briefcase', 'Commercial properties'),
(2, 'Flat/Apartment', 'sell-flat-apartment', 'fa-building', 'Residential flats and apartments'),
(2, 'House/Villa', 'sell-house-villa', 'fa-home', 'Independent houses and villas'),
(2, 'Plot/Land', 'sell-plot-land', 'fa-map', 'Residential plots and land'),
(2, 'Commercial', 'sell-commercial', 'fa-briefcase', 'Commercial properties'),
(3, 'Flat/Apartment', 'rent-flat-apartment', 'fa-building', 'Residential flats and apartments'),
(3, 'House/Villa', 'rent-house-villa', 'fa-home', 'Independent houses and villas'),
(3, 'Commercial', 'rent-commercial', 'fa-briefcase', 'Commercial properties'),
(4, 'Office Space', 'lease-office-space', 'fa-building', 'Office spaces'),
(4, 'Shop/Showroom', 'lease-shop-showroom', 'fa-store', 'Shops and showrooms'),
(4, 'Warehouse', 'lease-warehouse', 'fa-warehouse', 'Warehouses'),
(4, 'Industrial Land', 'lease-industrial-land', 'fa-industry', 'Industrial land');

-- Insert Amenities
INSERT INTO `amenities` (`name`, `icon`, `category`) VALUES
('Water Supply', 'fa-tint', 'basic'),
('Power Backup', 'fa-bolt', 'basic'),
('Lift', 'fa-elevator', 'basic'),
('Parking', 'fa-car', 'basic'),
('Security', 'fa-shield-alt', 'security'),
('Gated Community', 'fa-door-closed', 'security'),
('Club House', 'fa-users', 'lifestyle'),
('Gym', 'fa-dumbbell', 'lifestyle'),
('Swimming Pool', 'fa-swimming-pool', 'lifestyle'),
('Garden/Park', 'fa-tree', 'lifestyle'),
('Children Play Area', 'fa-child', 'lifestyle'),
('Jogging Track', 'fa-running', 'lifestyle'),
('Community Hall', 'fa-users', 'lifestyle'),
('Intercom', 'fa-phone', 'basic'),
('CCTV', 'fa-video', 'security'),
('Fire Safety', 'fa-fire-extinguisher', 'security'),
('Rain Water Harvesting', 'fa-cloud-rain', 'eco'),
('Solar Power', 'fa-solar-panel', 'eco'),
('Gas Pipeline', 'fa-fire', 'basic'),
('Wi-Fi', 'fa-wifi', 'basic');

-- Insert States
INSERT INTO `states` (`name`, `code`) VALUES
('Maharashtra', 'MH'),
('Delhi', 'DL'),
('Karnataka', 'KA'),
('Tamil Nadu', 'TN'),
('Gujarat', 'GJ'),
('Uttar Pradesh', 'UP'),
('Rajasthan', 'RJ'),
('West Bengal', 'WB'),
('Telangana', 'TS'),
('Kerala', 'KL');

-- Insert Default Admin User (Password: admin123)
INSERT INTO `users` (`email`, `password`, `first_name`, `last_name`, `role`, `account_status`, `is_verified`, `email_verified`) VALUES
('admin@propertymarketplace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active', 1, 1);

-- Insert System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Property Marketplace', 'text', 'Site Name'),
('site_email', 'noreply@propertymarketplace.com', 'text', 'Site Email'),
('admin_email', 'admin@propertymarketplace.com', 'text', 'Admin Email'),
('maintenance_mode', '0', 'boolean', 'Maintenance Mode'),
('allow_registration', '1', 'boolean', 'Allow User Registration'),
('require_email_verification', '1', 'boolean', 'Require Email Verification'),
('default_currency', 'INR', 'text', 'Default Currency'),
('default_language', 'en', 'text', 'Default Language'),
('items_per_page', '12', 'number', 'Items Per Page');

COMMIT;
