-- WebIntoApp MySQL Schema Dump

SET FOREIGN_KEY_CHECKS=0;

create table `tenants` (`id` bigint unsigned not null auto_increment primary key, `ulid` char(26) not null, `name` varchar(255) not null, `owner_user_id` bigint unsigned null, `status` enum('active', 'suspended', 'trial') not null default 'trial', `created_at` timestamp null, `updated_at` timestamp null, `deleted_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `tenants` add unique `tenants_ulid_unique`(`ulid`);

alter table `tenants` add index `tenants_owner_user_id_index`(`owner_user_id`);

create table `users` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `name` varchar(255) not null, `email` varchar(255) not null, `phone` varchar(255) null, `email_verified_at` timestamp null, `password` varchar(255) not null, `role` enum('owner', 'editor', 'viewer') not null default 'owner', `two_factor_secret` text null, `remember_token` varchar(100) null, `created_at` timestamp null, `updated_at` timestamp null, `deleted_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `users` add constraint `users_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `users` add index `users_tenant_id_email_index`(`tenant_id`, `email`);

alter table `users` add unique `users_email_unique`(`email`);

create table `password_reset_tokens` (`email` varchar(255) not null, `token` varchar(255) not null, `created_at` timestamp null, primary key (`email`)) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

create table `sessions` (`id` varchar(255) not null, `user_id` bigint unsigned null, `ip_address` varchar(45) null, `user_agent` text null, `payload` longtext not null, `last_activity` int not null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `sessions` add index `sessions_user_id_index`(`user_id`);

alter table `sessions` add index `sessions_last_activity_index`(`last_activity`);

create table `cache` (`key` varchar(255) not null, `value` mediumtext not null, `expiration` int not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

create table `cache_locks` (`key` varchar(255) not null, `owner` varchar(255) not null, `expiration` int not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

create table `jobs` (`id` bigint unsigned not null auto_increment primary key, `queue` varchar(255) not null, `payload` longtext not null, `attempts` tinyint unsigned not null, `reserved_at` int unsigned null, `available_at` int unsigned not null, `created_at` int unsigned not null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `jobs` add index `jobs_queue_index`(`queue`);

create table `job_batches` (`id` varchar(255) not null, `name` varchar(255) not null, `total_jobs` int not null, `pending_jobs` int not null, `failed_jobs` int not null, `failed_job_ids` longtext not null, `options` mediumtext null, `cancelled_at` int null, `created_at` int not null, `finished_at` int null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

create table `failed_jobs` (`id` bigint unsigned not null auto_increment primary key, `uuid` varchar(255) not null, `connection` text not null, `queue` text not null, `payload` longtext not null, `exception` longtext not null, `failed_at` timestamp not null default CURRENT_TIMESTAMP) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `failed_jobs` add unique `failed_jobs_uuid_unique`(`uuid`);

create table `personal_access_tokens` (`id` bigint unsigned not null auto_increment primary key, `tokenable_type` varchar(255) not null, `tokenable_id` bigint unsigned not null, `name` text not null, `token` varchar(64) not null, `abilities` text null, `last_used_at` timestamp null, `expires_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `personal_access_tokens` add index `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`);

alter table `personal_access_tokens` add unique `personal_access_tokens_token_unique`(`token`);

alter table `personal_access_tokens` add index `personal_access_tokens_expires_at_index`(`expires_at`);

create table `plans` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(255) not null, `slug` varchar(255) not null, `price_monthly` decimal(10, 2) not null, `features` json null, `limits` json null, `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `plans` add unique `plans_slug_unique`(`slug`);

create table `subscriptions` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned null, `plan_id` bigint unsigned not null, `status` varchar(255) not null default 'trialing', `razorpay_sub_id` varchar(255) null, `current_period_end` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `subscriptions` add constraint `subscriptions_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `subscriptions` add constraint `subscriptions_plan_id_foreign` foreign key (`plan_id`) references `plans` (`id`) on delete restrict;

alter table `subscriptions` add index `subscriptions_tenant_id_status_index`(`tenant_id`, `status`);

alter table `subscriptions` add index `subscriptions_app_id_index`(`app_id`);

alter table `subscriptions` add index `subscriptions_razorpay_sub_id_index`(`razorpay_sub_id`);

create table `payments` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `razorpay_payment_id` varchar(255) not null, `amount` decimal(10, 2) not null, `currency` varchar(3) not null default 'INR', `status` varchar(255) not null default 'captured', `raw` json null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `payments` add constraint `payments_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `payments` add index `payments_tenant_id_created_at_index`(`tenant_id`, `created_at`);

alter table `payments` add unique `payments_razorpay_payment_id_unique`(`razorpay_payment_id`);

create table `themes` (`id` bigint unsigned not null auto_increment primary key, `key` varchar(255) not null, `name` varchar(255) not null, `preview_url` varchar(255) null, `schema_version` int unsigned not null default '1', `defaults` json null, `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `themes` add unique `themes_key_unique`(`key`);

create table `apps` (`id` bigint unsigned not null auto_increment primary key, `ulid` char(26) not null, `tenant_id` bigint unsigned not null, `name` varchar(255) not null, `platform` enum('woo', 'shopify') not null default 'woo', `package_name` varchar(255) not null, `app_key` varchar(255) not null, `setup_step` varchar(255) not null default 'platform', `status` enum('draft', 'verified', 'configured', 'paid', 'building', 'live') not null default 'draft', `firebase_project_id` varchar(255) null, `firebase_app_id` varchar(255) null, `created_at` timestamp null, `updated_at` timestamp null, `deleted_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `apps` add constraint `apps_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `apps` add index `apps_tenant_id_status_index`(`tenant_id`, `status`);

alter table `apps` add unique `apps_ulid_unique`(`ulid`);

alter table `apps` add unique `apps_package_name_unique`(`package_name`);

alter table `apps` add unique `apps_app_key_unique`(`app_key`);

create table `connections` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `platform` enum('woo', 'shopify') not null default 'woo', `site_url` varchar(255) not null, `credentials` longtext null, `status` enum('not_started', 'configured', 'verified', 'failed') not null default 'not_started', `last_verified_at` timestamp null, `last_error_code` varchar(255) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `connections` add constraint `connections_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `connections` add constraint `connections_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `connections` add index `connections_tenant_id_app_id_index`(`tenant_id`, `app_id`);

create table `app_configs` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `version` int unsigned not null default '1', `state` enum('draft', 'published') not null default 'draft', `config` json not null, `published_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `app_configs` add constraint `app_configs_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `app_configs` add constraint `app_configs_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `app_configs` add index `app_configs_tenant_id_app_id_state_index`(`tenant_id`, `app_id`, `state`);

alter table `app_configs` add index `app_configs_app_id_version_index`(`app_id`, `version`);

create table `integrations` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `type` enum('firebase', 'whatsapp', 'otp', 'google') not null, `status` varchar(255) not null default 'not_configured', `credentials` longtext null, `meta` json null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `integrations` add constraint `integrations_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `integrations` add constraint `integrations_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `integrations` add unique `integrations_tenant_id_app_id_type_unique`(`tenant_id`, `app_id`, `type`);

create table `builds` (`id` bigint unsigned not null auto_increment primary key, `ulid` char(26) not null, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `config_version` int unsigned not null default '1', `status` enum('queued', 'preparing', 'building', 'signing', 'ready', 'failed') not null default 'queued', `logs_path` varchar(255) null, `artifact_path` varchar(255) null, `version_code` int unsigned not null default '1', `version_name` varchar(255) not null default '1.0.0', `error` text null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `builds` add constraint `builds_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `builds` add constraint `builds_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `builds` add index `builds_tenant_id_app_id_index`(`tenant_id`, `app_id`);

alter table `builds` add index `builds_tenant_id_status_index`(`tenant_id`, `status`);

alter table `builds` add unique `builds_ulid_unique`(`ulid`);

create table `keystores` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `path` varchar(255) not null, `alias` varchar(255) not null, `passwords` longtext not null, `sha1` varchar(255) null, `sha256` varchar(255) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `keystores` add constraint `keystores_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `keystores` add constraint `keystores_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `keystores` add index `keystores_tenant_id_app_id_index`(`tenant_id`, `app_id`);

alter table `keystores` add unique `keystores_app_id_unique`(`app_id`);

create table `app_users` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `phone` varchar(255) null, `email` varchar(255) null, `store_customer_id` varchar(255) null, `name` varchar(255) null, `last_login_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null, `deleted_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `app_users` add constraint `app_users_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `app_users` add constraint `app_users_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `app_users` add unique `app_users_tenant_id_app_id_phone_unique`(`tenant_id`, `app_id`, `phone`);

alter table `app_users` add index `app_users_tenant_id_app_id_store_customer_id_index`(`tenant_id`, `app_id`, `store_customer_id`);

create table `push_devices` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `app_user_id` bigint unsigned null, `fcm_token` varchar(255) not null, `platform` enum('android', 'ios') not null default 'android', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `push_devices` add constraint `push_devices_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `push_devices` add constraint `push_devices_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `push_devices` add constraint `push_devices_app_user_id_foreign` foreign key (`app_user_id`) references `app_users` (`id`) on delete set null;

alter table `push_devices` add index `push_devices_tenant_id_app_id_fcm_token_index`(`tenant_id`, `app_id`, `fcm_token`);

create table `notification_campaigns` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned not null, `title` varchar(255) not null, `body` text not null, `audience` json null, `scheduled_at` timestamp null, `status` enum('draft', 'scheduled', 'sent', 'failed') not null default 'draft', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `notification_campaigns` add constraint `notification_campaigns_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `notification_campaigns` add constraint `notification_campaigns_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete cascade;

alter table `notification_campaigns` add index `notification_campaigns_tenant_id_app_id_status_index`(`tenant_id`, `app_id`, `status`);

create table `whatsapp_wallets` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `balance` decimal(10, 2) not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `whatsapp_wallets` add constraint `whatsapp_wallets_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `whatsapp_wallets` add unique `whatsapp_wallets_tenant_id_unique`(`tenant_id`);

create table `usage_events` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned null, `type` varchar(255) not null, `qty` int unsigned not null default '1', `cost` decimal(10, 4) not null default '0', `created_at` timestamp not null default CURRENT_TIMESTAMP) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `usage_events` add constraint `usage_events_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `usage_events` add constraint `usage_events_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete set null;

alter table `usage_events` add index `usage_events_tenant_id_created_at_index`(`tenant_id`, `created_at`);

create table `webhook_events` (`id` bigint unsigned not null auto_increment primary key, `source` varchar(255) not null, `event_id` varchar(255) not null, `payload` json not null, `processed_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `webhook_events` add index `webhook_events_source_event_id_index`(`source`, `event_id`);

alter table `webhook_events` add unique `webhook_events_event_id_unique`(`event_id`);

create table `audit_logs` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned null, `actor_id` bigint unsigned null, `action` varchar(255) not null, `target` varchar(255) null, `ip` varchar(45) null, `user_agent` text null, `meta` json null, `created_at` timestamp not null default CURRENT_TIMESTAMP) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `audit_logs` add constraint `audit_logs_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `audit_logs` add index `audit_logs_tenant_id_created_at_index`(`tenant_id`, `created_at`);

alter table `audit_logs` add index `audit_logs_actor_id_index`(`actor_id`);

alter table `notification_campaigns` add `deep_link` varchar(255) null after `body`;

alter table `notification_campaigns` add `sent_count` int unsigned not null default '0' after `status`;

alter table `notification_campaigns` add `failed_count` int unsigned not null default '0' after `sent_count`;

create table `whatsapp_messages` (`id` bigint unsigned not null auto_increment primary key, `tenant_id` bigint unsigned not null, `app_id` bigint unsigned null, `phone` varchar(255) not null, `template_name` varchar(255) not null, `status` enum('queued', 'sent', 'delivered', 'read', 'failed') not null default 'queued', `cost` decimal(10, 4) not null default '0.8', `whatsapp_message_id` varchar(255) null, `meta` json null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

alter table `whatsapp_messages` add constraint `whatsapp_messages_tenant_id_foreign` foreign key (`tenant_id`) references `tenants` (`id`) on delete cascade;

alter table `whatsapp_messages` add constraint `whatsapp_messages_app_id_foreign` foreign key (`app_id`) references `apps` (`id`) on delete set null;

alter table `whatsapp_messages` add index `whatsapp_messages_tenant_id_status_index`(`tenant_id`, `status`);

alter table `whatsapp_messages` add index `whatsapp_messages_whatsapp_message_id_index`(`whatsapp_message_id`);

alter table `push_devices` add unique `push_devices_app_id_fcm_token_unique`(`app_id`, `fcm_token`);

alter table `notification_campaigns` add index `notification_campaigns_status_scheduled_at_index`(`status`, `scheduled_at`);

alter table `notification_campaigns` add index `notification_campaigns_tenant_id_app_id_created_at_index`(`tenant_id`, `app_id`, `created_at`);

-- Migration records tracking
create table if not exists `migrations` (`id` int unsigned not null auto_increment primary key, `migration` varchar(255) not null, `batch` int not null) default character set utf8mb4 collate 'utf8mb4_0900_ai_ci';

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('0001_01_01_000000_create_tenants_and_users_tables', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('0001_01_01_000001_create_cache_table', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('0001_01_01_000002_create_jobs_table', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_060257_create_personal_access_tokens_table', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_100001_create_plans_and_billing_tables', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_100002_create_apps_and_connections_tables', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_100003_create_integrations_and_builds_tables', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_100004_create_app_users_and_devices_tables', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_100005_create_events_and_audit_logs_tables', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_110001_create_whatsapp_messages_and_update_campaigns', 1) ON DUPLICATE KEY UPDATE `batch`=1;
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_10_13_110002_add_unique_and_performance_indexes_for_notifications', 1) ON DUPLICATE KEY UPDATE `batch`=1;

-- Default Themes
INSERT INTO `themes` (`key`, `name`, `preview_url`, `schema_version`, `defaults`, `is_active`, `created_at`, `updated_at`) VALUES 
('theme_a', 'Minimal Elegance', 'https://assets.webintoapp.test/themes/theme_a_preview.png', 1, '{"layout":"theme_a","colors":{"primary":"#111827","secondary":"#4B5563","background":"#FFFFFF","surface":"#F9FAFB","text":"#111827"},"radius":8,"font":"Inter","nav":"bottom"}', 1, NOW(), NOW()),
('theme_b', 'Boutique Glamour', 'https://assets.webintoapp.test/themes/theme_b_preview.png', 1, '{"layout":"theme_b","colors":{"primary":"#E11D48","secondary":"#BE185D","background":"#FFF1F2","surface":"#FFFFFF","text":"#881337"},"radius":16,"font":"Poppins","nav":"bottom"}', 1, NOW(), NOW()),
('theme_c', 'Apex Dark Luxury', 'https://assets.webintoapp.test/themes/theme_c_preview.png', 1, '{"layout":"theme_c","colors":{"primary":"#6366F1","secondary":"#818CF8","background":"#0F172A","surface":"#1E293B","text":"#F8FAFC"},"radius":12,"font":"Outfit","nav":"bottom"}', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `defaults`=VALUES(`defaults`);

-- Default Plans
INSERT INTO `plans` (`name`, `slug`, `price_monthly`, `features`, `limits`, `is_active`, `created_at`, `updated_at`) VALUES
('Starter Plan', 'starter', 999.00, '["builds", "push_notifications"]', '{"builds_per_month": 3, "push_per_month": 10000}', 1, NOW(), NOW()),
('Pro Plan', 'pro', 2999.00, '["builds", "push_notifications", "whatsapp"]', '{"builds_per_month": 10, "push_per_month": 50000}', 1, NOW(), NOW()),
('Enterprise Plan', 'enterprise', 7999.00, '["builds", "push_notifications", "whatsapp", "custom_domain", "priority_support"]', '{"builds_per_month": 50, "push_per_month": 500000}', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `price_monthly`=VALUES(`price_monthly`), `features`=VALUES(`features`), `limits`=VALUES(`limits`);

SET FOREIGN_KEY_CHECKS=1;

