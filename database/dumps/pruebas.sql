-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-01-2026 a las 10:42:58
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pruebas`
--
CREATE DATABASE IF NOT EXISTS `pruebas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pruebas`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ad_archivo_digital`
--

DROP TABLE IF EXISTS `ad_archivo_digital`;
CREATE TABLE `ad_archivo_digital` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo_documento_codigo` varchar(5) DEFAULT NULL,
  `tipo_archivo_codigo` varchar(5) DEFAULT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `ruta` varchar(600) NOT NULL,
  `digital` longtext DEFAULT NULL,
  `tipo_mime` varchar(255) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `tamano` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` char(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ad_tipo_archivo`
--

DROP TABLE IF EXISTS `ad_tipo_archivo`;
CREATE TABLE `ad_tipo_archivo` (
  `codigo` varchar(5) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `tipo_mime` varchar(255) NOT NULL,
  `extension` varchar(10) NOT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ad_tipo_documento`
--

DROP TABLE IF EXISTS `ad_tipo_documento`;
CREATE TABLE `ad_tipo_documento` (
  `codigo` varchar(5) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `tamano_maximo` int(11) NOT NULL DEFAULT 10000,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_documentation`
--

DROP TABLE IF EXISTS `api_documentation`;
CREATE TABLE `api_documentation` (
  `id` int(10) UNSIGNED NOT NULL,
  `method_type` varchar(191) NOT NULL,
  `url` varchar(191) NOT NULL,
  `parameters` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `blog`
--

DROP TABLE IF EXISTS `blog`;
CREATE TABLE `blog` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(191) NOT NULL,
  `content` text NOT NULL,
  `meta_tags` varchar(191) NOT NULL,
  `meta_description` text NOT NULL,
  `slug` varchar(191) NOT NULL,
  `excerpt` text NOT NULL,
  `category` int(11) NOT NULL,
  `tags` varchar(191) NOT NULL,
  `author_name` varchar(191) NOT NULL,
  `status` int(11) NOT NULL,
  `image` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
CREATE TABLE `blog_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(191) NOT NULL,
  `status` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_configuraciones`
--

DROP TABLE IF EXISTS `email_configuraciones`;
CREATE TABLE `email_configuraciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mail_driver` varchar(191) NOT NULL DEFAULT 'smtp',
  `mail_host` varchar(191) DEFAULT NULL,
  `mail_port` int(10) UNSIGNED DEFAULT NULL,
  `mail_username` varchar(191) DEFAULT NULL,
  `mail_password` text DEFAULT NULL,
  `mail_encryption` varchar(191) DEFAULT NULL,
  `mail_from_address` varchar(191) DEFAULT NULL,
  `mail_from_name` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `email_configuraciones`
--

INSERT INTO `email_configuraciones` (`id`, `mail_driver`, `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_encryption`, `mail_from_address`, `mail_from_name`, `created_at`, `updated_at`, `estado`) VALUES
(1, 'smtp', 'mailtrap.io', 2525, NULL, NULL, NULL, 'hello@example.com', 'App Builder', '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_plantillas`
--

DROP TABLE IF EXISTS `email_plantillas`;
CREATE TABLE `email_plantillas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `from_name` varchar(191) DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `email_plantillas`
--

INSERT INTO `email_plantillas` (`id`, `slug`, `name`, `from_name`, `variables`, `created_at`, `updated_at`, `estado`) VALUES
(1, 'new_user', 'New User', 'Support', '[\"app_name\",\"company_name\",\"email\",\"password\",\"app_url\"]', '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL),
(2, 'reset_password', 'Reset Password', 'Support', '[\"app_name\",\"company_name\",\"email\",\"reset_link\",\"expire_minutes\"]', '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email_plantillas_traduccion`
--

DROP TABLE IF EXISTS `email_plantillas_traduccion`;
CREATE TABLE `email_plantillas_traduccion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email_template_id` bigint(20) UNSIGNED NOT NULL,
  `idioma_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(191) NOT NULL,
  `body` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `email_plantillas_traduccion`
--

INSERT INTO `email_plantillas_traduccion` (`id`, `email_template_id`, `idioma_id`, `subject`, `body`, `created_at`, `updated_at`, `estado`) VALUES
(1, 1, 1, 'New User', 'Hello,<br><br>Welcome to {app_name}.<br><br>Email: {email}<br>Password: {password}<br><br>Login here: {app_url}<br><br>Thanks,<br>{app_name}', '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL),
(2, 1, 2, 'Nuevo usuario', 'Hola,<br><br>Bienvenido/a a {app_name}.<br><br>Email: {email}<br>Contrase&ntilde;a: {password}<br><br>Ingresa aqu&iacute;: {app_url}<br><br>Gracias,<br>{app_name}', '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL),
(3, 2, 1, 'Reset Password', 'Hello,<br><br>You are receiving this email because we received a password reset request for your account.<br><br>Reset link: {reset_link}<br><br>This link will expire in {expire_minutes} minutes.<br><br>If you did not request a password reset, no further action is required.<br><br>Thanks,<br>{app_name}', '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL),
(4, 2, 2, 'Restablecer contraseña', 'Hola,<br><br>Recibiste este correo porque se solicit&oacute; un restablecimiento de contrase&ntilde;a para tu cuenta.<br><br>Enlace: {reset_link}<br><br>Este enlace caduca en {expire_minutes} minutos.<br><br>Si no lo solicitaste, ignora este correo.<br><br>Gracias,<br>{app_name}', '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fields`
--

DROP TABLE IF EXISTS `fields`;
CREATE TABLE `fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(191) NOT NULL,
  `field_text` varchar(191) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `validation_rules` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `idiomas`
--

DROP TABLE IF EXISTS `idiomas`;
CREATE TABLE `idiomas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `por_defecto` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `idiomas`
--

INSERT INTO `idiomas` (`id`, `codigo`, `nombre`, `activo`, `por_defecto`, `created_at`, `updated_at`, `estado`) VALUES
(1, 'en', 'English', 1, 0, '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL),
(2, 'es', 'Español', 1, 1, '2026-01-01 13:23:30', '2026-01-01 13:23:30', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoicedetails`
--

DROP TABLE IF EXISTS `invoicedetails`;
CREATE TABLE `invoicedetails` (
  `id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `product` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `subtotal` double(8,2) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int(10) UNSIGNED NOT NULL,
  `from_company_name` varchar(191) NOT NULL,
  `from_company_address` text NOT NULL,
  `from_company_phone` varchar(191) NOT NULL,
  `from_company_email` varchar(191) NOT NULL,
  `to_company_name` varchar(191) NOT NULL,
  `to_company_address` text NOT NULL,
  `to_company_phone` varchar(191) NOT NULL,
  `to_company_email` varchar(191) NOT NULL,
  `invoice_number` varchar(191) NOT NULL,
  `payment_due` date NOT NULL,
  `tax` double(8,2) NOT NULL,
  `shipping` double(8,2) NOT NULL,
  `total` double(8,2) NOT NULL,
  `payment_status` varchar(191) NOT NULL,
  `invoice_type` varchar(191) NOT NULL,
  `renewal_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ltm_translations`
--

DROP TABLE IF EXISTS `ltm_translations`;
CREATE TABLE `ltm_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `locale` varchar(191) NOT NULL,
  `group` varchar(191) NOT NULL,
  `key` text NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `permission_name` varchar(255) DEFAULT NULL,
  `url` varchar(256) NOT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'fa-cube',
  `type` varchar(20) NOT NULL DEFAULT 'module',
  `parent` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `hierarchy` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `module_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`id`, `name`, `permission_name`, `url`, `icon`, `type`, `parent`, `hierarchy`, `module_id`, `created_at`, `updated_at`, `estado`) VALUES
(1, 'Estado civil', 'pg_estado_civil', 'EstadoCivilIndex', 'fa-heart', 'module', 0, 50, 0, '2026-01-01 13:24:05', '2026-01-01 13:24:05', NULL),
(2, 'Tipo identificación', 'pg_tipo_identificacion', 'TipoIdentificacionIndex', 'fa-id-card', 'module', 0, 51, 0, '2026-01-01 13:24:05', '2026-01-01 13:24:05', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`, `estado`) VALUES
(1, '2014_04_02_193005_create_translations_table', 1, NULL),
(2, '2014_10_12_000000_create_users_table', 1, NULL),
(3, '2014_10_12_100000_create_password_reset_tokens_table', 1, NULL),
(4, '2016_06_01_000001_create_oauth_auth_codes_table', 1, NULL),
(5, '2016_06_01_000002_create_oauth_access_tokens_table', 1, NULL),
(6, '2016_06_01_000003_create_oauth_refresh_tokens_table', 1, NULL),
(7, '2016_06_01_000004_create_oauth_clients_table', 1, NULL),
(8, '2016_06_01_000005_create_oauth_personal_access_clients_table', 1, NULL),
(9, '2016_07_07_134058_create_menus_table', 1, NULL),
(10, '2017_03_29_045848_fields', 1, NULL),
(11, '2017_03_31_112807_modules', 1, NULL),
(12, '2017_03_31_112949_module_fields', 1, NULL),
(13, '2018_07_16_112554_entrust_setup_tables', 1, NULL),
(14, '2018_07_16_115326_settings_table', 1, NULL),
(15, '2019_05_17_145014_create_api_documentation_table', 1, NULL),
(16, '2019_07_11_000530_Widgets', 1, NULL),
(17, '2019_08_19_000000_create_failed_jobs_table', 1, NULL),
(18, '2019_10_06_103819_Invoices', 1, NULL),
(19, '2019_11_02_114930_Invoicedetails', 1, NULL),
(20, '2019_12_14_000001_create_personal_access_tokens_table', 1, NULL),
(21, '2021_01_22_112840_Blog_categories', 1, NULL),
(22, '2021_01_28_111400_Blog', 1, NULL),
(23, '2025_12_27_120000_create_email_settings_table', 1, NULL),
(24, '2025_12_27_120010_create_email_templates_tables', 1, NULL),
(25, '2025_12_28_000000_create_idiomas_table', 1, NULL),
(26, '2025_12_28_000001_create_ad_tipo_archivo', 1, NULL),
(27, '2025_12_28_000002_create_ad_tipo_documento', 1, NULL),
(28, '2025_12_28_000003_create_ad_archivo_digital', 1, NULL),
(29, '2025_12_28_000010_rename_email_tables_to_spanish', 1, NULL),
(30, '2025_12_28_000020_relacionar_email_plantillas_tranduccion_con_idiomas', 2, NULL),
(31, '2025_12_29_000001_add_digital_to_ad_archivo_digital', 2, NULL),
(32, '2025_12_29_000010_add_estado_to_all_tables', 2, NULL),
(33, '2025_12_30_000000_rename_users_to_pg_usuario_and_user_id_to_usuario_id', 2, NULL),
(34, '2025_12_30_000020_create_pg_persona', 2, NULL),
(35, '2025_12_30_000021_add_id_persona_to_pg_usuario', 2, NULL),
(36, '2025_12_30_000022_create_pg_persona_foto', 2, NULL),
(37, '2025_12_31_000000_create_pg_estado_civil', 2, NULL),
(38, '2025_12_31_000001_create_pg_tipo_identificacion', 2, NULL),
(39, '2025_12_31_000010_add_catalogos_permissions_and_menus', 2, NULL),
(40, '2025_12_31_000020_create_pg_opcion_menu_tables', 2, NULL),
(41, '2025_12_31_000021_seed_pg_opcion_menu_basico', 2, NULL),
(42, '2026_01_01_000001_add_id_rol_to_pg_opcion_menu_rol', 2, NULL),
(43, '2026_01_01_000002_drop_rol_from_pg_opcion_menu_rol', 2, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modules`
--

DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `id` int(10) UNSIGNED NOT NULL,
  `module_name` varchar(191) NOT NULL,
  `module_icon` varchar(191) NOT NULL,
  `module_table_name` varchar(191) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `module_fields`
--

DROP TABLE IF EXISTS `module_fields`;
CREATE TABLE `module_fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(191) NOT NULL,
  `field_label` varchar(191) NOT NULL,
  `field_type` int(11) NOT NULL,
  `field_length` int(11) NOT NULL DEFAULT 0,
  `field_options` text NOT NULL,
  `related_table` varchar(191) NOT NULL,
  `related_table_field` varchar(191) NOT NULL,
  `related_table_field_display` varchar(191) NOT NULL,
  `validation_rules` text NOT NULL,
  `show_in_list` tinyint(4) NOT NULL,
  `module_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oauth_access_tokens`
--

DROP TABLE IF EXISTS `oauth_access_tokens`;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oauth_auth_codes`
--

DROP TABLE IF EXISTS `oauth_auth_codes`;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oauth_clients`
--

DROP TABLE IF EXISTS `oauth_clients`;
CREATE TABLE `oauth_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(191) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oauth_personal_access_clients`
--

DROP TABLE IF EXISTS `oauth_personal_access_clients`;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oauth_refresh_tokens`
--

DROP TABLE IF EXISTS `oauth_refresh_tokens`;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `display_name` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`, `estado`) VALUES
(1, 'pg_estado_civil', 'Catálogo: Estado civil', 'Gestionar catálogo de estado civil', NULL, NULL, NULL),
(2, 'pg_tipo_identificacion', 'Catálogo: Tipo identificación', 'Gestionar catálogo de tipo de identificación', NULL, NULL, NULL),
(3, 'pg_opcion_menu', 'Administración: Menú', 'Gestionar opciones del menú y acceso por rol', NULL, NULL, NULL),
(4, 'user_all', 'Usuarios - Ver', 'Acceso a listado de usuarios', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(5, 'user_edit', 'Usuarios - Editar', 'Editar usuarios', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(6, 'user_create_update', 'Usuarios - Crear/Actualizar', 'Crear/Actualizar usuarios', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(7, 'user_delete', 'Usuarios - Eliminar', 'Eliminar usuarios', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(8, 'user_delete_muliple', 'Usuarios - Eliminar múltiple', 'Eliminar múltiple usuarios', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(9, 'user_profile', 'Perfil - Ver', 'Ver perfil', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(10, 'user_profile_update', 'Perfil - Actualizar', 'Actualizar perfil', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(11, 'roles_all', 'Roles - Ver', 'Acceso a roles', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(12, 'roles_edit', 'Roles - Editar', 'Editar roles', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(13, 'roles_create_update', 'Roles - Crear/Actualizar', 'Crear/Actualizar roles', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(14, 'roles_delete', 'Roles - Eliminar', 'Eliminar roles', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(15, 'roles_delete_multiple', 'Roles - Eliminar múltiple', 'Eliminar múltiple roles', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(16, 'permissions_all', 'Permisos - Ver', 'Acceso a permisos', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(17, 'permissions_edit', 'Permisos - Editar', 'Editar permisos', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(18, 'permissions_create_update', 'Permisos - Crear/Actualizar', 'Crear/Actualizar permisos', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(19, 'permissions_delete', 'Permisos - Eliminar', 'Eliminar permisos', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(20, 'permissions_delete_multiple', 'Permisos - Eliminar múltiple', 'Eliminar múltiple permisos', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(21, 'general_settings_all', 'General Settings - Ver', 'Acceso a configuración general', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(22, 'general_settings_create_update', 'General Settings - Actualizar', 'Actualizar configuración general', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(23, 'filemanager', 'File Manager', 'Acceso al file manager', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(24, 'modulebuilder_modules', 'Module Builder - Modules', 'Acceso a módulos', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(25, 'modulebuilder_menu', 'Module Builder - Menu', 'Acceso a menú del builder', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(26, 'Invoices', 'Invoices', 'Acceso a Invoices', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(27, 'Invoicedetails', 'Invoice Details', 'Acceso a Invoicedetails', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL),
(28, 'Widgets', 'Widgets', 'Acceso a Widgets', '2026-01-01 09:16:28', '2026-01-01 09:16:28', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permission_role`
--

DROP TABLE IF EXISTS `permission_role`;
CREATE TABLE `permission_role` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`, `estado`) VALUES
(1, 1, NULL),
(1, 2, NULL),
(2, 1, NULL),
(2, 2, NULL),
(3, 1, NULL),
(3, 2, NULL),
(4, 1, NULL),
(4, 2, NULL),
(5, 1, NULL),
(5, 2, NULL),
(6, 1, NULL),
(6, 2, NULL),
(7, 1, NULL),
(7, 2, NULL),
(8, 1, NULL),
(8, 2, NULL),
(9, 1, NULL),
(9, 2, NULL),
(10, 1, NULL),
(10, 2, NULL),
(11, 1, NULL),
(11, 2, NULL),
(12, 1, NULL),
(12, 2, NULL),
(13, 1, NULL),
(13, 2, NULL),
(14, 1, NULL),
(14, 2, NULL),
(15, 1, NULL),
(15, 2, NULL),
(16, 1, NULL),
(16, 2, NULL),
(17, 1, NULL),
(17, 2, NULL),
(18, 1, NULL),
(18, 2, NULL),
(19, 1, NULL),
(19, 2, NULL),
(20, 1, NULL),
(20, 2, NULL),
(21, 1, NULL),
(21, 2, NULL),
(22, 1, NULL),
(22, 2, NULL),
(23, 1, NULL),
(23, 2, NULL),
(24, 1, NULL),
(24, 2, NULL),
(25, 1, NULL),
(25, 2, NULL),
(26, 1, NULL),
(26, 2, NULL),
(27, 1, NULL),
(27, 2, NULL),
(28, 1, NULL),
(28, 2, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pg_estado_civil`
--

DROP TABLE IF EXISTS `pg_estado_civil`;
CREATE TABLE `pg_estado_civil` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(5) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `estado` char(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pg_estado_civil`
--

INSERT INTO `pg_estado_civil` (`id`, `codigo`, `descripcion`, `estado`, `created_at`, `updated_at`) VALUES
(1, '1', 'SOLTERO', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(2, '2', 'CASADO', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(3, '3', 'DIVORCIADO', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(4, '4', 'VIUDO', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(5, '5', 'UNION LIBRE', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pg_opcion_menu`
--

DROP TABLE IF EXISTS `pg_opcion_menu`;
CREATE TABLE `pg_opcion_menu` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `id_padre` bigint(20) UNSIGNED DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `tipo` char(1) NOT NULL DEFAULT 'G',
  `activo` char(1) NOT NULL DEFAULT 'S',
  `orden` smallint(6) NOT NULL DEFAULT 0,
  `id_archivo` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pg_opcion_menu`
--

INSERT INTO `pg_opcion_menu` (`id`, `titulo`, `id_padre`, `url`, `tipo`, `activo`, `orden`, `id_archivo`, `estado`) VALUES
(1, 'Gestión', NULL, '#', 'G', 'S', 40, NULL, NULL),
(2, 'Administración', NULL, '#', 'G', 'S', 90, NULL, NULL),
(3, 'Personas', 1, 'PersonasIndex', 'M', 'S', 1, NULL, NULL),
(4, 'Archivos digitales', 1, 'ArchivosDigitalesIndex', 'M', 'S', 2, NULL, NULL),
(5, 'Estado civil', 1, 'EstadoCivilIndex', 'M', 'S', 3, NULL, NULL),
(6, 'Tipo identificación', 1, 'TipoIdentificacionIndex', 'M', 'S', 4, NULL, NULL),
(7, 'Opciones de menú', 2, 'OpcionMenuIndex', 'M', 'S', 1, NULL, NULL),
(8, 'Account Settings', NULL, NULL, 'G', 'S', 30, NULL, NULL),
(9, 'Facturación', NULL, NULL, 'G', 'S', 15, NULL, NULL),
(10, 'Dashboard', NULL, 'dashboardIndex', 'M', 'S', 1, NULL, NULL),
(11, 'Widgets', NULL, 'admin/module/Widgets/1', 'G', 'S', 2, NULL, NULL),
(12, 'Invoices', 9, 'InvoicesIndex', 'M', 'S', 1, NULL, NULL),
(13, 'Invoice Details', 9, 'InvoicedetailsIndex', 'M', 'S', 2, NULL, NULL),
(14, 'Opciones Menú', 2, 'OpcionMenuIndex', 'M', 'S', 1, NULL, NULL),
(15, 'CRUD Builder', 2, 'builder', 'M', 'S', 2, NULL, NULL),
(16, 'Manage Users', 2, 'users', 'M', 'S', 3, NULL, NULL),
(17, 'Roles', 2, 'roles', 'M', 'S', 4, NULL, NULL),
(18, 'Permissions', 2, 'permissions', 'M', 'S', 5, NULL, NULL),
(19, 'File Manager', 2, 'admin/filemanage', 'G', 'S', 6, NULL, NULL),
(20, 'API Documentation', 2, 'ApiDocumentationIndex', 'M', 'S', 7, NULL, NULL),
(21, 'User Profile', 8, 'userprofile', 'M', 'S', 1, NULL, NULL),
(22, 'General Settings', 8, 'general-settings', 'M', 'S', 2, NULL, NULL),
(23, 'Email Settings', 8, 'email-settings', 'M', 'S', 3, NULL, NULL),
(24, 'Email Templates', 8, 'email-templates', 'M', 'S', 4, NULL, NULL),
(25, 'Translation Manager', 8, 'admin/translations', 'M', 'S', 5, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pg_opcion_menu_rol`
--

DROP TABLE IF EXISTS `pg_opcion_menu_rol`;
CREATE TABLE `pg_opcion_menu_rol` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_opcion_menu` bigint(20) UNSIGNED NOT NULL,
  `id_rol` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pg_opcion_menu_rol`
--

INSERT INTO `pg_opcion_menu_rol` (`id`, `id_opcion_menu`, `id_rol`, `estado`) VALUES
(1, 3, 2, NULL),
(2, 3, 1, NULL),
(3, 4, 2, NULL),
(4, 4, 1, NULL),
(5, 5, 2, NULL),
(6, 5, 1, NULL),
(7, 6, 2, NULL),
(8, 6, 1, NULL),
(9, 7, 2, NULL),
(10, 7, 1, NULL),
(11, 1, 2, NULL),
(12, 1, 1, NULL),
(13, 2, 2, NULL),
(14, 2, 1, NULL),
(15, 8, 2, NULL),
(16, 9, 2, NULL),
(17, 10, 2, NULL),
(18, 11, 2, NULL),
(19, 12, 2, NULL),
(20, 13, 2, NULL),
(21, 14, 2, NULL),
(22, 15, 2, NULL),
(23, 16, 2, NULL),
(24, 17, 2, NULL),
(25, 18, 2, NULL),
(26, 19, 2, NULL),
(27, 20, 2, NULL),
(28, 21, 2, NULL),
(29, 22, 2, NULL),
(30, 23, 2, NULL),
(31, 24, 2, NULL),
(32, 25, 2, NULL),
(46, 8, 1, NULL),
(47, 9, 1, NULL),
(48, 10, 1, NULL),
(49, 11, 1, NULL),
(50, 12, 1, NULL),
(51, 13, 1, NULL),
(52, 14, 1, NULL),
(53, 15, 1, NULL),
(54, 16, 1, NULL),
(55, 17, 1, NULL),
(56, 18, 1, NULL),
(57, 19, 1, NULL),
(58, 20, 1, NULL),
(59, 21, 1, NULL),
(60, 22, 1, NULL),
(61, 23, 1, NULL),
(62, 24, 1, NULL),
(63, 25, 1, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pg_persona`
--

DROP TABLE IF EXISTS `pg_persona`;
CREATE TABLE `pg_persona` (
  `id` varchar(10) NOT NULL,
  `tipo` char(1) NOT NULL DEFAULT 'N',
  `nombres` varchar(255) DEFAULT NULL,
  `apellido1` varchar(20) DEFAULT NULL,
  `apellido2` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` datetime DEFAULT NULL,
  `tipo_identificacion` char(1) NOT NULL DEFAULT 'C',
  `identificacion` varchar(15) DEFAULT NULL,
  `sexo` char(1) DEFAULT NULL,
  `celular` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `cod_estado_civil` char(1) DEFAULT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pg_persona_foto`
--

DROP TABLE IF EXISTS `pg_persona_foto`;
CREATE TABLE `pg_persona_foto` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_persona` varchar(10) NOT NULL,
  `id_archivo` bigint(20) UNSIGNED NOT NULL,
  `estado` char(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pg_tipo_identificacion`
--

DROP TABLE IF EXISTS `pg_tipo_identificacion`;
CREATE TABLE `pg_tipo_identificacion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(5) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `estado_actual` tinyint(4) NOT NULL DEFAULT 1,
  `asocia_persona` tinyint(4) NOT NULL DEFAULT 0,
  `validar` tinyint(4) NOT NULL DEFAULT 0,
  `longitud` int(11) DEFAULT NULL,
  `longitud_fija` tinyint(4) NOT NULL DEFAULT 0,
  `codigo_sri` varchar(10) DEFAULT NULL,
  `estado` char(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pg_tipo_identificacion`
--

INSERT INTO `pg_tipo_identificacion` (`id`, `codigo`, `descripcion`, `estado_actual`, `asocia_persona`, `validar`, `longitud`, `longitud_fija`, `codigo_sri`, `estado`, `created_at`, `updated_at`) VALUES
(1, '5', 'CERTIFICADO DE VOTACION', 1, 0, 0, 10, 1, NULL, NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(2, '4', 'LIBRETA MILITAR', 1, 0, 0, 10, 1, NULL, NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(3, '1', 'R.U.C.', 1, 1, 0, 13, 1, '04', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(4, '2', 'CEDULA DE IDENTIDAD', 1, 1, 1, 10, 1, '05', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(5, '3', 'PASAPORTE', 1, 1, 0, 7, 1, '06', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(6, '6', 'ACUERDO MINISTERIAL', 1, 1, 0, 10, 0, NULL, NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(7, '7', 'DOCUMENTO DE IDENTIFICACION DE REFUGIADO', 1, 1, 0, 10, 0, NULL, NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05'),
(8, '8', 'VENTA A CONSUMIDOR FINAL', 1, 1, 0, 13, 1, '07', NULL, '2026-01-01 13:24:05', '2026-01-01 13:24:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pg_usuario`
--

DROP TABLE IF EXISTS `pg_usuario`;
CREATE TABLE `pg_usuario` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_persona` varchar(10) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `image` varchar(150) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pg_usuario`
--

INSERT INTO `pg_usuario` (`id`, `id_persona`, `name`, `email`, `email_verified_at`, `password`, `image`, `remember_token`, `created_at`, `updated_at`, `estado`) VALUES
(1, NULL, 'Admin', 'mat22edu@gmail.com', NULL, '$2y$10$dELyL/4Yaz6XqryC5e1Sje.nnGxUdHcKOzIgAeXPxwvHEG4Pk4XYy', 'img.jpg', 'HXIcN36ep1bkKnF0hhicDIYaEPL309kip88107lcqEqYqIR8fQ0eSnCTgdHq', '2025-12-28 07:35:03', '2025-12-28 10:10:26', NULL),
(2, NULL, 'wer', 'workeasy_flutter@saasmonks.in', NULL, '$2y$10$DYkf0FjvFqXhy9BXbBf6gewsFsNdUhsezzMlV9HJ5YiJ/FgVxqShK', 'photos/img.jpg', NULL, '2025-12-28 08:00:36', '2025-12-30 08:50:11', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `display_name` varchar(191) DEFAULT NULL,
  `description` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`, `estado`) VALUES
(1, 'Super-Admin', 'Super Admin', 'Super Admin', '2026-01-01 13:24:05', '2026-01-01 13:24:05', NULL),
(2, 'Admin', 'Admin Role', 'This is Admin Role', '2026-01-01 13:24:05', '2026-01-01 13:24:05', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_user`
--

DROP TABLE IF EXISTS `role_user`;
CREATE TABLE `role_user` (
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `role_user`
--

INSERT INTO `role_user` (`usuario_id`, `role_id`, `estado`) VALUES
(1, 1, NULL),
(2, 2, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `registration` varchar(255) NOT NULL,
  `crudbuilder` varchar(255) NOT NULL,
  `filemanager` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `settings`
--

INSERT INTO `settings` (`id`, `registration`, `crudbuilder`, `filemanager`, `created_at`, `updated_at`, `estado`) VALUES
(1, 'true', 'true', 'true', NULL, '2025-12-28 07:36:35', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `widgets`
--

DROP TABLE IF EXISTS `widgets`;
CREATE TABLE `widgets` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` varchar(191) NOT NULL,
  `icon` varchar(191) NOT NULL,
  `title` varchar(191) NOT NULL,
  `module_id` int(11) NOT NULL,
  `table` varchar(191) NOT NULL,
  `tablefield` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `estado` char(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ad_archivo_digital`
--
ALTER TABLE `ad_archivo_digital`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_archivo_digital_estado_index` (`estado`),
  ADD KEY `ad_archivo_digital_tipo_documento_codigo_index` (`tipo_documento_codigo`),
  ADD KEY `ad_archivo_digital_tipo_archivo_codigo_index` (`tipo_archivo_codigo`);

--
-- Indices de la tabla `ad_tipo_archivo`
--
ALTER TABLE `ad_tipo_archivo`
  ADD PRIMARY KEY (`codigo`),
  ADD KEY `ad_tipo_archivo_estado_index` (`estado`);

--
-- Indices de la tabla `ad_tipo_documento`
--
ALTER TABLE `ad_tipo_documento`
  ADD PRIMARY KEY (`codigo`),
  ADD KEY `ad_tipo_documento_estado_index` (`estado`);

--
-- Indices de la tabla `api_documentation`
--
ALTER TABLE `api_documentation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `api_documentation_estado_index` (`estado`);

--
-- Indices de la tabla `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blog_estado_index` (`estado`);

--
-- Indices de la tabla `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `blog_categories_estado_index` (`estado`);

--
-- Indices de la tabla `email_configuraciones`
--
ALTER TABLE `email_configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_configuraciones_estado_index` (`estado`);

--
-- Indices de la tabla `email_plantillas`
--
ALTER TABLE `email_plantillas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_templates_slug_unique` (`slug`),
  ADD KEY `email_plantillas_estado_index` (`estado`);

--
-- Indices de la tabla `email_plantillas_traduccion`
--
ALTER TABLE `email_plantillas_traduccion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_plantillas_traduccion_template_idioma_unique` (`email_template_id`,`idioma_id`),
  ADD KEY `email_plantillas_traduccion_idioma_id_index` (`idioma_id`),
  ADD KEY `email_plantillas_traduccion_estado_index` (`estado`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_estado_index` (`estado`);

--
-- Indices de la tabla `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fields_estado_index` (`estado`);

--
-- Indices de la tabla `idiomas`
--
ALTER TABLE `idiomas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idiomas_codigo_unique` (`codigo`),
  ADD KEY `idiomas_estado_index` (`estado`);

--
-- Indices de la tabla `invoicedetails`
--
ALTER TABLE `invoicedetails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoicedetails_estado_index` (`estado`);

--
-- Indices de la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoices_estado_index` (`estado`);

--
-- Indices de la tabla `ltm_translations`
--
ALTER TABLE `ltm_translations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ltm_translations_estado_index` (`estado`);

--
-- Indices de la tabla `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menus_estado_index` (`estado`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `migrations_estado_index` (`estado`);

--
-- Indices de la tabla `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modules_estado_index` (`estado`);

--
-- Indices de la tabla `module_fields`
--
ALTER TABLE `module_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_fields_estado_index` (`estado`);

--
-- Indices de la tabla `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`),
  ADD KEY `oauth_access_tokens_estado_index` (`estado`);

--
-- Indices de la tabla `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_auth_codes_user_id_index` (`user_id`),
  ADD KEY `oauth_auth_codes_estado_index` (`estado`);

--
-- Indices de la tabla `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_user_id_index` (`user_id`),
  ADD KEY `oauth_clients_estado_index` (`estado`);

--
-- Indices de la tabla `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_personal_access_clients_estado_index` (`estado`);

--
-- Indices de la tabla `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`),
  ADD KEY `oauth_refresh_tokens_estado_index` (`estado`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`),
  ADD KEY `password_reset_tokens_estado_index` (`estado`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`),
  ADD KEY `permissions_estado_index` (`estado`);

--
-- Indices de la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`),
  ADD KEY `permission_role_estado_index` (`estado`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_estado_index` (`estado`);

--
-- Indices de la tabla `pg_estado_civil`
--
ALTER TABLE `pg_estado_civil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pg_estado_civil_codigo_unique` (`codigo`),
  ADD KEY `pg_estado_civil_estado_index` (`estado`);

--
-- Indices de la tabla `pg_opcion_menu`
--
ALTER TABLE `pg_opcion_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pg_opcion_menu_estado_index` (`estado`),
  ADD KEY `pg_opcion_menu_id_padre_index` (`id_padre`),
  ADD KEY `pg_opcion_menu_activo_index` (`activo`),
  ADD KEY `pg_opcion_menu_id_archivo_foreign` (`id_archivo`);

--
-- Indices de la tabla `pg_opcion_menu_rol`
--
ALTER TABLE `pg_opcion_menu_rol`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pg_opcion_menu_rol_opcion_rol` (`id_opcion_menu`,`id_rol`),
  ADD KEY `pg_opcion_menu_rol_estado_index` (`estado`),
  ADD KEY `idx_pg_opcion_menu_rol_id_rol` (`id_rol`);

--
-- Indices de la tabla `pg_persona`
--
ALTER TABLE `pg_persona`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pg_persona_estado_index` (`estado`),
  ADD KEY `pg_persona_identificacion_index` (`identificacion`),
  ADD KEY `pg_persona_email_index` (`email`);

--
-- Indices de la tabla `pg_persona_foto`
--
ALTER TABLE `pg_persona_foto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pg_persona_foto_estado_index` (`estado`),
  ADD KEY `pg_persona_foto_id_persona_index` (`id_persona`),
  ADD KEY `pg_persona_foto_id_archivo_index` (`id_archivo`);

--
-- Indices de la tabla `pg_tipo_identificacion`
--
ALTER TABLE `pg_tipo_identificacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pg_tipo_identificacion_codigo_unique` (`codigo`),
  ADD KEY `pg_tipo_identificacion_estado_index` (`estado`);

--
-- Indices de la tabla `pg_usuario`
--
ALTER TABLE `pg_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pg_usuario_email_unique` (`email`),
  ADD KEY `pg_usuario_estado_index` (`estado`),
  ADD KEY `pg_usuario_id_persona_index` (`id_persona`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`),
  ADD KEY `roles_estado_index` (`estado`);

--
-- Indices de la tabla `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`usuario_id`,`role_id`),
  ADD KEY `role_user_role_id_foreign` (`role_id`),
  ADD KEY `role_user_estado_index` (`estado`);

--
-- Indices de la tabla `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `settings_estado_index` (`estado`);

--
-- Indices de la tabla `widgets`
--
ALTER TABLE `widgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `widgets_estado_index` (`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ad_archivo_digital`
--
ALTER TABLE `ad_archivo_digital`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `api_documentation`
--
ALTER TABLE `api_documentation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `blog`
--
ALTER TABLE `blog`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `email_configuraciones`
--
ALTER TABLE `email_configuraciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `email_plantillas`
--
ALTER TABLE `email_plantillas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `email_plantillas_traduccion`
--
ALTER TABLE `email_plantillas_traduccion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fields`
--
ALTER TABLE `fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `idiomas`
--
ALTER TABLE `idiomas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `invoicedetails`
--
ALTER TABLE `invoicedetails`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ltm_translations`
--
ALTER TABLE `ltm_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `module_fields`
--
ALTER TABLE `module_fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `oauth_clients`
--
ALTER TABLE `oauth_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pg_estado_civil`
--
ALTER TABLE `pg_estado_civil`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `pg_opcion_menu`
--
ALTER TABLE `pg_opcion_menu`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `pg_opcion_menu_rol`
--
ALTER TABLE `pg_opcion_menu_rol`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT de la tabla `pg_persona_foto`
--
ALTER TABLE `pg_persona_foto`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pg_tipo_identificacion`
--
ALTER TABLE `pg_tipo_identificacion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pg_usuario`
--
ALTER TABLE `pg_usuario`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `widgets`
--
ALTER TABLE `widgets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `email_plantillas_traduccion`
--
ALTER TABLE `email_plantillas_traduccion`
  ADD CONSTRAINT `email_plantillas_traduccion_idioma_id_fk` FOREIGN KEY (`idioma_id`) REFERENCES `idiomas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `email_template_translations_email_template_id_foreign` FOREIGN KEY (`email_template_id`) REFERENCES `email_plantillas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pg_opcion_menu`
--
ALTER TABLE `pg_opcion_menu`
  ADD CONSTRAINT `pg_opcion_menu_id_archivo_foreign` FOREIGN KEY (`id_archivo`) REFERENCES `ad_archivo_digital` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pg_opcion_menu_id_padre_foreign` FOREIGN KEY (`id_padre`) REFERENCES `pg_opcion_menu` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pg_opcion_menu_rol`
--
ALTER TABLE `pg_opcion_menu_rol`
  ADD CONSTRAINT `fk_pg_opcion_menu_rol_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pg_opcion_menu_rol_id_opcion_menu_foreign` FOREIGN KEY (`id_opcion_menu`) REFERENCES `pg_opcion_menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pg_persona_foto`
--
ALTER TABLE `pg_persona_foto`
  ADD CONSTRAINT `pg_persona_foto_id_archivo_foreign` FOREIGN KEY (`id_archivo`) REFERENCES `ad_archivo_digital` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pg_persona_foto_id_persona_foreign` FOREIGN KEY (`id_persona`) REFERENCES `pg_persona` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pg_usuario`
--
ALTER TABLE `pg_usuario`
  ADD CONSTRAINT `pg_usuario_id_persona_foreign` FOREIGN KEY (`id_persona`) REFERENCES `pg_persona` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `role_user_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `pg_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
