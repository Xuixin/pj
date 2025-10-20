SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `admin` (
  `admin_id` int(9) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `tel` varchar(18) NOT NULL,
  `role` enum('admin','employee') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `brand` (
  `brand_id` int(9) NOT NULL,
  `brand_name` varchar(255) NOT NULL,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `device` (
  `device_id` int(9) NOT NULL,
  `serial_number` varchar(255) NOT NULL,
  `status` enum('ว่าง','เช่าแล้ว') NOT NULL,
  `model_id` int(9) NOT NULL,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `model` (
  `model_id` int(9) NOT NULL,
  `brand_id` int(9) NOT NULL,
  `model_name` varchar(255) NOT NULL,
  `spec` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_id` int(9) NOT NULL DEFAULT 1,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `price_per_month` decimal(6,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `model_img` (
  `model_img_id` int(9) NOT NULL,
  `model_id` int(9) NOT NULL,
  `img_path` varchar(255) NOT NULL,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `due_date` datetime NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('ยังไม่ชำระ','ชำระแล้ว') NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `type` enum('เต็มจำนวน','งวด') NOT NULL,
  `rent_id` int(11) NOT NULL,
  `slip_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pm` (
  `pm_id` int(9) NOT NULL,
  `rent_id` int(9) NOT NULL,
  `pm_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `rent` (
  `rent_id` int(9) NOT NULL,
  `user_id` int(9) NOT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `file_lease` varchar(255) DEFAULT NULL,
  `rent_status` enum('อยู่ระหว่างการเช่า','คืนอุปกรณ์เรียบร้อย','เกินระยะเวลาคืน') NOT NULL DEFAULT 'อยู่ระหว่างการเช่า',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_type` enum('all','installment') NOT NULL,
  `pm_latest` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `rent_detail` (
  `rent_detail_id` int(9) NOT NULL,
  `device_id` int(9) NOT NULL,
  `rent_id` int(9) NOT NULL,
  `machine_status` enum('ปกติ','ส่งเคลม','เสีย','สำรอง','กำลังใช้เครื่อง') NOT NULL,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `backup_device` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `type` (
  `type_id` int(9) NOT NULL,
  `type_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `user` (
  `user_id` int(9) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `create_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

ALTER TABLE `brand`
  ADD PRIMARY KEY (`brand_id`);

ALTER TABLE `device`
  ADD PRIMARY KEY (`device_id`),
  ADD UNIQUE KEY `serail_number` (`serial_number`),
  ADD KEY `model_to_serial` (`model_id`);

ALTER TABLE `model`
  ADD PRIMARY KEY (`model_id`),
  ADD KEY `brand_to_model` (`brand_id`),
  ADD KEY `type_to_model` (`type_id`);

ALTER TABLE `model_img`
  ADD PRIMARY KEY (`model_img_id`),
  ADD KEY `img_to_model` (`model_id`);

ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `rent_to_pay` (`rent_id`);

ALTER TABLE `pm`
  ADD PRIMARY KEY (`pm_id`),
  ADD KEY `rent_to_pm` (`rent_id`);

ALTER TABLE `rent`
  ADD PRIMARY KEY (`rent_id`),
  ADD KEY `user_to_rent` (`user_id`);

ALTER TABLE `rent_detail`
  ADD PRIMARY KEY (`rent_detail_id`),
  ADD KEY `serial_to_rent_dt` (`device_id`),
  ADD KEY `rent_to_rent_dt` (`rent_id`);

ALTER TABLE `type`
  ADD PRIMARY KEY (`type_id`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_name` (`user_name`);


ALTER TABLE `admin`
  MODIFY `admin_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `brand`
  MODIFY `brand_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `device`
  MODIFY `device_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `model`
  MODIFY `model_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `model_img`
  MODIFY `model_img_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `pm`
  MODIFY `pm_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rent`
  MODIFY `rent_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rent_detail`
  MODIFY `rent_detail_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `type`
  MODIFY `type_id` int(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user`
  MODIFY `user_id` int(9) NOT NULL AUTO_INCREMENT;


ALTER TABLE `device`
  ADD CONSTRAINT `model_to_serial` FOREIGN KEY (`model_id`) REFERENCES `model` (`model_id`);

ALTER TABLE `model`
  ADD CONSTRAINT `brand_to_model` FOREIGN KEY (`brand_id`) REFERENCES `brand` (`brand_id`),
  ADD CONSTRAINT `type_to_model` FOREIGN KEY (`type_id`) REFERENCES `type` (`type_id`);

ALTER TABLE `model_img`
  ADD CONSTRAINT `img_to_model` FOREIGN KEY (`model_id`) REFERENCES `model` (`model_id`);

ALTER TABLE `payment`
  ADD CONSTRAINT `rent_to_pay` FOREIGN KEY (`rent_id`) REFERENCES `rent` (`rent_id`);

ALTER TABLE `pm`
  ADD CONSTRAINT `rent_to_pm` FOREIGN KEY (`rent_id`) REFERENCES `rent` (`rent_id`) ON DELETE CASCADE;

ALTER TABLE `rent`
  ADD CONSTRAINT `user_to_rent` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

ALTER TABLE `rent_detail`
  ADD CONSTRAINT `rent_to_rent_dt` FOREIGN KEY (`rent_id`) REFERENCES `rent` (`rent_id`),
  ADD CONSTRAINT `serial_to_rent_dt` FOREIGN KEY (`device_id`) REFERENCES `device` (`device_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
