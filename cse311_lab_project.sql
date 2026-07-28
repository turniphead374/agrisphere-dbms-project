-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 17, 2025 at 02:42 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cse311 lab project`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `permission_level` enum('Active','Suspanded','Banned') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `permission_level`) VALUES
(9500101, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `ai_log`
--

CREATE TABLE `ai_log` (
  `log_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `query` varchar(300) DEFAULT NULL,
  `response` varchar(1000) DEFAULT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`) VALUES
(1, 'Vegetables'),
(2, 'Fruits'),
(3, 'Grains'),
(4, 'Spices'),
(5, 'Dairy'),
(6, 'Fish'),
(7, 'Meat'),
(8, 'Poultry');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `address`, `phone`) VALUES
(7029344, 'House-180, Road-38, Block-C, Union/Ward-5, Barishal Sadar, Barishal-8200, Building-41, Lat-23.742941, Lon-90.313654', '01722334455'),
(7065411, 'House-150, Road-42, Block-B, Union/Ward-18, Barishal Sadar, Barishal-8200, Building-39, Lat-23.635330, Lon-90.553752', '01555667722'),
(7123988, 'House-138, Road-11, Block-E, Union/Ward-8, Khulna Sadar, Khulna-9000, Building-47, Lat-24.059179, Lon-90.236704', '01766778812'),
(7159033, 'House-116, Road-19, Block-E, Union/Ward-5, Rajshahi Sadar, Rajshahi-6000, Building-7, Lat-23.522829, Lon-90.623877', '01555667788'),
(7165321, 'House-129, Road-59, Block-F, Union/Ward-16, Mirpur-10, Dhaka-1216, Building-21, Lat-23.877794, Lon-90.440299', '01911223366'),
(7183440, 'House-163, Road-36, Block-D, Union/Ward-1, Pahartali, Chattogram-4212, Building-46, Lat-23.760083, Lon-90.191111', '01988990033'),
(7203401, 'House-108, Road-18, Block-F, Union/Ward-7, Mirpur-10, Dhaka-1216, Building-46, Lat-23.510467, Lon-90.327256', '01799001122'),
(7219033, 'House-116, Road-56, Block-F, Union/Ward-11, Dhanmondi, Dhaka-1209, Building-32, Lat-23.541929, Lon-90.374417', '01800112266'),
(7250934, 'House-157, Road-44, Block-B, Union/Ward-6, Ambarkhana, Sylhet-3100, Building-27, Lat-23.684286, Lon-90.603039', '01988990044'),
(7283590, 'House-161, Road-52, Block-F, Union/Ward-18, Bogura Sadar, Bogura-5800, Building-6, Lat-23.721773, Lon-90.263323', '01766778899'),
(7331220, 'House-44, Road-23, Block-B, Union/Ward-3, Barishal Sadar, Barishal-8200, Building-6, Lat-24.099638, Lon-90.760889', '01944556688'),
(7338912, 'House-31, Road-56, Block-A, Union/Ward-1, Rajshahi Sadar, Rajshahi-6000, Building-45, Lat-23.984952, Lon-90.658644', '01833445599'),
(7412891, 'House-183, Road-10, Block-A, Union/Ward-20, Rajshahi Sadar, Rajshahi-6000, Building-41, Lat-23.785047, Lon-90.535506', '01911223377'),
(7419083, 'House-178, Road-35, Block-B, Union/Ward-9, Pahartali, Chattogram-4212, Building-25, Lat-23.803987, Lon-90.587030', '01800112277'),
(7429834, 'House-36, Road-48, Block-C, Union/Ward-16, Noakhali Sadar, Noakhali-3800, Building-2, Lat-23.582789, Lon-90.439805', '01833445588'),
(7440193, 'House-15, Road-55, Block-B, Union/Ward-17, Uttara Sector-4, Dhaka-1230, Building-37, Lat-24.159114, Lon-90.445989', '01766778891'),
(7569211, 'House-130, Road-46, Block-E, Union/Ward-17, Rajshahi Sadar, Rajshahi-6000, Building-26, Lat-24.050155, Lon-90.385780', '01944556699'),
(7634891, 'House-137, Road-12, Block-F, Union/Ward-20, Mirpur-10, Dhaka-1216, Building-50, Lat-23.786889, Lon-90.141947', '01555667789'),
(7644433, 'House-15, Road-11, Block-D, Union/Ward-15, Rajshahi Sadar, Rajshahi-6000, Building-43, Lat-23.816618, Lon-90.607600', '01722334466'),
(7648201, 'House-54, Road-11, Block-A, Union/Ward-14, Uttara Sector-4, Dhaka-1230, Building-16, Lat-24.011165, Lon-90.613916', '01911223344'),
(7701893, 'House-97, Road-12, Block-D, Union/Ward-4, Gulshan-2, Dhaka-1212, Building-10, Lat-23.895297, Lon-90.272431', '01799001166'),
(7732091, 'House-108, Road-57, Block-A, Union/Ward-17, Khulna Sadar, Khulna-9000, Building-40, Lat-23.522042, Lon-90.647515', '01722334477'),
(7812393, 'House-164, Road-45, Block-B, Union/Ward-19, Comilla Sadar, Comilla-3500, Building-4, Lat-23.806387, Lon-90.782318', '01877889944'),
(7829891, 'House-113, Road-53, Block-E, Union/Ward-19, Zindabazar, Sylhet-3100, Building-34, Lat-24.124729, Lon-90.411814', '01555667790'),
(7845990, 'House-110, Road-25, Block-C, Union/Ward-17, Barishal Sadar, Barishal-8200, Building-7, Lat-24.135016, Lon-90.184372', '01766778890'),
(7882345, 'House-177, Road-3, Block-D, Union/Ward-16, Dhanmondi, Dhaka-1209, Building-48, Lat-23.939228, Lon-90.297852', '01800112244'),
(7932441, 'House-107, Road-49, Block-C, Union/Ward-16, Rajshahi Sadar, Rajshahi-6000, Building-23, Lat-23.882900, Lon-90.388933', '01933445566'),
(7940012, 'House-85, Road-53, Block-A, Union/Ward-19, Kotwali, Chattogram-4000, Building-35, Lat-23.871362, Lon-90.527709', '01766778823'),
(7993544, 'House-93, Road-30, Block-A, Union/Ward-15, Ambarkhana, Sylhet-3100, Building-4, Lat-23.514121, Lon-90.731140', '01833445566'),
(8124593, 'House-90, Road-32, Block-B, Union/Ward-1, Gulshan-2, Dhaka-1212, Building-27, Lat-23.702229, Lon-90.703952', '01822334455'),
(9500102, 'House-69, Road-55, Block-D, Union/Ward-18, Rajshahi Sadar, Rajshahi-6000', '011231231'),
(9500105, 'House-186, Road-53, Block-D, Union/Ward-7, Khulna Sadar, Khulna-9000', '011231231');

-- --------------------------------------------------------

--
-- Table structure for table `customer_cart`
--

CREATE TABLE `customer_cart` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_order`
--

CREATE TABLE `customer_order` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_order`
--

INSERT INTO `customer_order` (`order_id`, `customer_id`, `total_amount`, `order_date`, `status`) VALUES
(2, 7029344, 0.00, '2025-12-16 19:23:31', 'Pending'),
(3, 7732091, 20.00, '2025-12-16 19:34:37', NULL),
(4, 7029344, 492.00, '2025-12-17 14:08:47', NULL),
(5, 7029344, 72.00, '2025-12-17 14:34:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `farmer`
--

CREATE TABLE `farmer` (
  `farmer_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer`
--

INSERT INTO `farmer` (`farmer_id`, `address`, `bank_name`, `bank_account_number`) VALUES
(7029193, 'House-2, Road-30, Village Kaliganj, Union Fulbari, Upazila Matlab, District Chandpur, Bangladesh', 'Janata Bank Limited', 'AC-0115118669'),
(7099123, 'House-50, Road-49, Village Bheramara, Union Sadar, Upazila Bagha, District Chattogram, Bangladesh', 'Janata Bank Limited', 'AC-8459413674'),
(7283944, 'House-140, Road-57, Village Fenchuganj, Union Belkuchi, Upazila Belkuchi, District Chattogram, Bangladesh', 'BRAC Bank Limited', 'AC-9997575804'),
(7300444, 'House-11, Road-16, Village Belkuchi, Union Matlab, Upazila Kaliganj, District Gazipur, Bangladesh', 'Agrani Bank Limited', 'AC-3009528893'),
(7348129, 'House-107, Road-45, Village Belkuchi, Union Bagha, Upazila Belkuchi, District Gazipur, Bangladesh', 'Rupali Bank Limited', 'AC-6460295735'),
(7431984, 'House-186, Road-42, Village Fulbari, Union Bheramara, Upazila Bagha, District Sylhet, Bangladesh', 'BRAC Bank Limited', 'AC-5280083702'),
(7452199, 'House-173, Road-44, Village Charpara, Union Fulbari, Upazila Bheramara, District Gazipur, Bangladesh', 'Islami Bank Bangladesh Limited', 'AC-5227692784'),
(7502341, 'House-176, Road-49, Village Kaliganj, Union Fenchuganj, Upazila Matlab, District Sylhet, Bangladesh', 'BRAC Bank Limited', 'AC-3347037540'),
(7532190, 'House-189, Road-44, Village Matlab, Union Fenchuganj, Upazila Matlab, District Kushtia, Bangladesh', 'Janata Bank Limited', 'AC-4541213823'),
(7567823, 'House-109, Road-21, Village Charpara, Union Kalihati, Upazila Fenchuganj, District Cumilla, Bangladesh', 'Janata Bank Limited', 'AC-3709329127');

-- --------------------------------------------------------

--
-- Table structure for table `farm_inventory`
--

CREATE TABLE `farm_inventory` (
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_available` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farm_inventory`
--

INSERT INTO `farm_inventory` (`inventory_id`, `product_id`, `quantity_available`) VALUES
(1, 1, 200),
(2, 2, 80),
(3, 3, 120),
(4, 4, 498),
(8, 5, 2),
(9, 6, 3),
(10, 7, 6);

-- --------------------------------------------------------

--
-- Table structure for table `farm_product`
--

CREATE TABLE `farm_product` (
  `product_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farm_product`
--

INSERT INTO `farm_product` (`product_id`, `farmer_id`, `category_id`, `product_name`, `description`, `unit`, `price_per_unit`) VALUES
(1, 7029193, 1, 'Fresh Potato', 'Locally grown fresh potatoes', 'kg', 45.00),
(2, 7029193, 1, 'Green Spinach', 'Organic leafy spinach', 'bundle', 30.00),
(3, 7029193, 2, 'Banana', 'Naturally ripened banana', 'dozen', 70.00),
(4, 7029193, 3, 'Miniket Rice', 'Fine quality miniket rice', 'kg', 68.00),
(5, 7502341, 1, 'Potato', 'Straight outta Munshiganj', '5', 20.00),
(6, 7348129, 5, 'Milk', 'Rich with nutrients and vitamins.', '10', 70.00),
(7, 7099123, 3, 'Miniket Rice', 'Quality rice from the fields of manikganj', 'kg', 72.00);

-- --------------------------------------------------------

--
-- Table structure for table `land`
--

CREATE TABLE `land` (
  `land_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `land_size` decimal(10,2) DEFAULT NULL,
  `soil_type` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `upazila` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price_per_unit`) VALUES
(1, 2, 1, 5, 45.00),
(2, 3, 5, 1, 20.00),
(3, 4, 7, 3, 72.00),
(4, 4, 6, 2, 70.00),
(5, 4, 4, 2, 68.00),
(6, 5, 7, 1, 72.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `bkash_number` varchar(20) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'Paid',
  `payment_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `bkash_number`, `payment_status`, `payment_time`) VALUES
(1, 5, 'bKash', '01907980386', 'Paid', '2025-12-17 19:34:54');

-- --------------------------------------------------------

--
-- Table structure for table `transport`
--

CREATE TABLE `transport` (
  `transport_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `pickup_location` varchar(150) DEFAULT NULL,
  `dropoff_location` varchar(150) DEFAULT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_id` int(11) NOT NULL,
  `First_name` varchar(100) NOT NULL,
  `Last_name` varchar(100) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `User_type` enum('Farmer','Customer','Admin') NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `NID` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_id`, `First_name`, `Last_name`, `Username`, `Password`, `User_type`, `Phone`, `Email`, `NID`) VALUES
(7029193, 'Riyad', 'Miah', 'riyadm', 'Riyad@44', 'Farmer', '01877889911', 'riyad.miah@gmail.com', '2001123445'),
(7029344, 'Ahsan', 'Kabir', 'ahsan.k', 'Ahsan@111', 'Customer', '01722334455', 'ahsan.kabir@yahoo.com', '1991887345'),
(7065411, 'Nusrat', 'Jahan', 'nusratj', 'Nusrat#55', 'Customer', '01555667722', 'nusrat.jahan@yahoo.com', '2001456782'),
(7099123, 'Javed', 'Hasan', 'javedh', 'Javed!112', 'Farmer', '01799001133', 'javed.hasan@gmail.com', '1989789034'),
(7123988, 'Sultana', 'Begum', 'sultanab', 'Sultana303', 'Customer', '01766778812', 'sultana.begum@yahoo.com', '1990098712'),
(7159033, 'Mitu', 'Begum', 'mitubegum', 'Mitu@909', 'Customer', '01555667788', 'mitu.begum@yahoo.com', '1990456123'),
(7165321, 'Rakin', 'Hasib', 'rakin.h', 'Rakin*88', 'Customer', '01911223366', 'rakin.hasib@gmail.com', '1998876541'),
(7183440, 'Mariya', 'Sultana', 'mariyas', 'Mariya#12', 'Customer', '01988990033', 'mariya.sultana@gmail.com', '1995432178'),
(7203401, 'Tania', 'Sultana', 'tanias', 'Tania!21', 'Customer', '01799001122', 'tania.sultana@gmail.com', '2001567890'),
(7219033, 'Sabrina', 'Chowdhury', 'sabrinac', 'Sabrina#44', 'Customer', '01800112266', 'sabrina.chy@gmail.com', '2002876543'),
(7250934, 'Nahid', 'Hasan', 'nahidh', 'Nahid@13', 'Customer', '01988990044', 'nahid.hasan@yahoo.com', '2001345678'),
(7283590, 'Sakib', 'Ahmed', 'sakibbd', 'Sakib098!', 'Customer', '01766778899', 'sakib.ahmed@gmail.com', '2002456781'),
(7283944, 'Munna', 'Hossain', 'munnah', 'Munna@44', 'Farmer', '01555667711', 'munna.hossain@gmail.com', '1997894561'),
(7300444, 'Nayeem', 'Islam', 'nayeemi', 'Nayeem008', 'Farmer', '01911223355', 'nayeem.islam@gmail.com', '1997890045'),
(7331220, 'Mou', 'Akter', 'mouakter', 'Mou@303', 'Customer', '01944556688', 'mou.akter@yahoo.com', '1996684512'),
(7338912, 'Rumana', 'Akter', 'rumana.bd', 'Rumana@22', 'Customer', '01833445599', 'rumana.akter@gmail.com', '1998056789'),
(7348129, 'Abdul', 'Rahman', 'arahman', 'Abdul@123', 'Farmer', '01711223344', 'abdul.rahman@gmail.com', '1993456789'),
(7412891, 'Saif', 'Rahman', 'saifrahman', 'Saif908!', 'Customer', '01911223377', 'saif.rahman@gmail.com', '1997654411'),
(7419083, 'Lamia', 'Khan', 'lamiakhan', 'Lamia@19', 'Customer', '01800112277', 'lamia.khan@gmail.com', '1996587321'),
(7429834, 'Tamanna', 'Akter', 'tamannaa', 'Tamanna!10', 'Customer', '01833445588', 'tamanna.akter@gmail.com', '2001458791'),
(7431984, 'Omar', 'Faruk', 'omar.f', 'Omar2024', 'Farmer', '01799001155', 'omar.faruk@gmail.com', '1993123456'),
(7440193, 'Jannat', 'Khanam', 'jannatk', 'Jannat@71', 'Customer', '01766778891', 'jannat.khanam@gmail.com', '1993456678'),
(7452199, 'Fahim', 'Rahman', 'frahman', 'Fahim2024', 'Farmer', '01800112233', 'fahim.rahman@yahoo.com', '1999678901'),
(7502341, 'Monir', 'Uddin', 'monir.bd', 'Monir@778', 'Farmer', '01944556677', 'monir.uddin@gmail.com', '1978456120'),
(7532190, 'Ridoy', 'Hasan', 'ridoyh', 'Ridoy@71', 'Farmer', '01988990055', 'ridoy.hasan@gmail.com', '1990876543'),
(7567823, 'Shuvo', 'Biswas', 'shuvob', 'Shuvo234', 'Farmer', '01799001144', 'shuvo.biswas@yahoo.com', '1990987654'),
(7569211, 'Ishrat', 'Jahan', 'ishratj', 'Ishrat#66', 'Customer', '01944556699', 'ishrat.jahan@yahoo.com', '1991345678'),
(7634891, 'Shila', 'Akter', 'shilaakter', 'Shila!999', 'Customer', '01555667789', 'shila.akter@yahoo.com', '1990561234'),
(7644433, 'Sadia', 'Ferdous', 'sadiaf', 'Sadia#88', 'Customer', '01722334466', 'sadia.ferdous@yahoo.com', '1989654323'),
(7648201, 'Sumaiya', 'Khatun', 'sumaiyak', 'Sumaiya89', 'Customer', '01911223344', 'sumaiya.khatun@gmail.com', '2002987654'),
(7701893, 'Shamim', 'Reza', 'shamimr', 'Shamim505', 'Customer', '01799001166', 'shamim.reza@gmail.com', '2001345987'),
(7732091, 'Niloy', 'Karim', 'niloyk', 'Niloy2025', 'Customer', '01722334477', 'niloy.karim@yahoo.com', '1992567811'),
(7812393, 'Sohana', 'Sultana', 'sohana.s', 'Sohana!11', 'Customer', '01877889944', 'sohana.sultana@yahoo.com', '1999056781'),
(7829891, 'Rasel', 'Ahmed', 'rasel.bd', 'Rasel!22', 'Customer', '01555667790', 'rasel.ahmed@gmail.com', '2002123490'),
(7845990, 'Mamun', 'Chowdhury', 'mamun.c', 'Mamun2025', 'Customer', '01766778890', 'mamun.chowdhury@gmail.com', '1998987612'),
(7882345, 'Farzana', 'Yasmin', 'farzanay', 'Farzana@10', 'Customer', '01800112244', 'farzana.yasmin@gmail.com', '2001987600'),
(7932441, 'Sharmin', 'Akhter', 'sakhter', 'Sharmin!55', 'Customer', '01933445566', 'sharmin.akhter@yahoo.com', '2001346789'),
(7940012, 'Arif', 'Mahmud', 'arifm', 'Arif@333', 'Customer', '01766778823', 'arif.mahmud@gmail.com', '1992786541'),
(7993544, 'Rafi', 'Islam', 'rafiislm', 'Rafi#009', 'Customer', '01833445566', 'rafi.islam@gmail.com', '1992678910'),
(8124593, 'Hasan', 'Khan', 'hkhan', 'Hasan#2025', 'Customer', '01822334455', 'hasan.khan@gmail.com', '1987654321'),
(9500101, 'Abdullah', 'Ifaz', 'ifaz_AFC', 'londonisred', 'Admin', '0000000001', 'ifaz@mail.com', '0200000000'),
(9500102, 'Nausicaa', 'Ren', 'renn', 'ifaz', 'Customer', '011231231', 'rn@gmail.com', '123456'),
(9500105, 'Nausicaaa', 'Rren', 'meanw', 'ifaz', 'Customer', '011231231', 'rn@gmail.com', '123456');

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `user` FOR EACH ROW BEGIN
   
    IF NEW.User_type = 'Customer' THEN
        INSERT INTO customer (customer_id, address, phone)
        VALUES (
            NEW.User_id,
            CONCAT(
                'House-', FLOOR(1 + RAND() * 200),
                ', Road-', FLOOR(1 + RAND() * 60),
                ', Block-', CHAR(FLOOR(65 + (RAND() * 6))),
                ', Union/Ward-', FLOOR(1 + RAND() * 20),
                ', ',
                CASE FLOOR(1 + (RAND() * 10))
                    WHEN 1 THEN 'Dhanmondi, Dhaka-1209'
                    WHEN 2 THEN 'Gulshan-2, Dhaka-1212'
                    WHEN 3 THEN 'Mirpur-10, Dhaka-1216'
                    WHEN 4 THEN 'Uttara Sector-4, Dhaka-1230'
                    WHEN 5 THEN 'Kotwali, Chattogram-4000'
                    WHEN 6 THEN 'Pahartali, Chattogram-4212'
                    WHEN 7 THEN 'Ambarkhana, Sylhet-3100'
                    WHEN 8 THEN 'Rajshahi Sadar, Rajshahi-6000'
                    WHEN 9 THEN 'Khulna Sadar, Khulna-9000'
                    WHEN 10 THEN 'Barishal Sadar, Barishal-8200'
                END
            ),
            NEW.Phone
        );
    END IF;

    IF NEW.User_type = 'Farmer' THEN
        INSERT INTO farmer (farmer_id, address, bank_name, bank_account_number)
        VALUES (
            NEW.User_id,

         
            CONCAT(
                'House-', FLOOR(1 + RAND() * 200),
                ', Road-', FLOOR(1 + RAND() * 60),
                ', Village ',
                ELT(
                    FLOOR(1 + RAND() * 8),
                    'Charpara', 'Belkuchi', 'Bheramara', 'Kaliganj',
                    'Fenchuganj', 'Fulbari', 'Matlab', 'Mirzapur'
                ),
                ', Union ',
                ELT(
                    FLOOR(1 + RAND() * 8),
                    'Sadar', 'Belkuchi', 'Bheramara', 'Kalihati',
                    'Bagha', 'Fenchuganj', 'Fulbari', 'Matlab'
                ),
                ', Upazila ',
                ELT(
                    FLOOR(1 + RAND() * 8),
                    'Sadar', 'Belkuchi', 'Bheramara', 'Kaliganj',
                    'Bagha', 'Fenchuganj', 'Fulbari', 'Matlab'
                ),
                ', District ',
                ELT(
                    FLOOR(1 + RAND() * 10),
                    'Dhaka', 'Gazipur', 'Cumilla', 'Chattogram',
                    'Rajshahi', 'Kushtia', 'Tangail', 'Sylhet',
                    'Dinajpur', 'Chandpur'
                ),
                ', Bangladesh'
            ),

            
            ELT(
                FLOOR(1 + RAND() * 7),
                'Sonali Bank Limited',
                'Janata Bank Limited',
                'Agrani Bank Limited',
                'Rupali Bank Limited',
                'Islami Bank Bangladesh Limited',
                'Dutch-Bangla Bank Limited',
                'BRAC Bank Limited'
            ),

          
            CONCAT('AC-', LPAD(FLOOR(RAND() * 10000000000), 10, '0'))
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `set_admin_role` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
    IF NEW.Username = 'admin' THEN
        SET NEW.User_type = 'Admin';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_block`
--

CREATE TABLE `user_block` (
  `user_id` int(11) NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 1,
  `blocked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_block`
--

INSERT INTO `user_block` (`user_id`, `is_blocked`, `blocked_at`) VALUES
(7644433, 1, '2025-12-17 00:58:35');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse`
--

CREATE TABLE `warehouse` (
  `warehouse_id` int(11) NOT NULL,
  `warehouse_name` varchar(150) NOT NULL,
  `district` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_stock`
--

CREATE TABLE `warehouse_stock` (
  `ws_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `arrival_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `ai_log`
--
ALTER TABLE `ai_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_cart`
--
ALTER TABLE `customer_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_customer` (`customer_id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `customer_order`
--
ALTER TABLE `customer_order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_order_customer` (`customer_id`);

--
-- Indexes for table `farmer`
--
ALTER TABLE `farmer`
  ADD PRIMARY KEY (`farmer_id`);

--
-- Indexes for table `farm_inventory`
--
ALTER TABLE `farm_inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `fk_inventory_product` (`product_id`);

--
-- Indexes for table `farm_product`
--
ALTER TABLE `farm_product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_product_farmer` (`farmer_id`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indexes for table `land`
--
ALTER TABLE `land`
  ADD PRIMARY KEY (`land_id`),
  ADD KEY `fk_land_farmer` (`farmer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_orderitem_order` (`order_id`),
  ADD KEY `fk_orderitem_product` (`product_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `transport`
--
ALTER TABLE `transport`
  ADD PRIMARY KEY (`transport_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_id`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `user_block`
--
ALTER TABLE `user_block`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `warehouse`
--
ALTER TABLE `warehouse`
  ADD PRIMARY KEY (`warehouse_id`);

--
-- Indexes for table `warehouse_stock`
--
ALTER TABLE `warehouse_stock`
  ADD PRIMARY KEY (`ws_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_log`
--
ALTER TABLE `ai_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer_cart`
--
ALTER TABLE `customer_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `customer_order`
--
ALTER TABLE `customer_order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `farm_inventory`
--
ALTER TABLE `farm_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `farm_product`
--
ALTER TABLE `farm_product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `land`
--
ALTER TABLE `land`
  MODIFY `land_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transport`
--
ALTER TABLE `transport`
  MODIFY `transport_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9500106;

--
-- AUTO_INCREMENT for table `warehouse`
--
ALTER TABLE `warehouse`
  MODIFY `warehouse_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouse_stock`
--
ALTER TABLE `warehouse_stock`
  MODIFY `ws_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`admin_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `fk_customer_user` FOREIGN KEY (`customer_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_cart`
--
ALTER TABLE `customer_cart`
  ADD CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `farm_product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_order`
--
ALTER TABLE `customer_order`
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `farmer`
--
ALTER TABLE `farmer`
  ADD CONSTRAINT `fk_farmer_user` FOREIGN KEY (`farmer_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `farm_inventory`
--
ALTER TABLE `farm_inventory`
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `farm_product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `farm_product`
--
ALTER TABLE `farm_product`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmer` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `land`
--
ALTER TABLE `land`
  ADD CONSTRAINT `fk_land_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmer` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `customer_order` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `farm_product` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `customer_order` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
