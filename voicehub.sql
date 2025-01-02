-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/01/2025 às 13:51
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `voicehub`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `trade_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cnpj` varchar(18) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `companies`
--

INSERT INTO `companies` (`id`, `name`, `trade_name`, `created_at`, `cnpj`, `address`, `city`, `state`, `zip_code`, `phone`, `email`, `website`) VALUES
(1, 'Eagle Telecom', NULL, '2024-12-19 12:17:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Parceiros Externos', NULL, '2024-12-19 12:17:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'TELEMONT ENGENHARIA DE TELECOMUNICACOES S/A', '', '2025-01-02 01:58:54', '18.725.804/0001-13', 'R SANTA FE, 100 - SALA 101 201 E 202', 'BELO HORIZONTE', 'MG', '30.320-130', '(31) 3448-8788', 'ricardo@telemont.com.br', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `licenses`
--

CREATE TABLE `licenses` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `total_licenses` int(11) NOT NULL,
  `used_licenses` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `licenses`
--

INSERT INTO `licenses` (`id`, `company_id`, `total_licenses`, `used_licenses`, `created_at`) VALUES
(3, 2, 101, 5, '2024-12-29 06:14:31'),
(4, 1, 101, 18, '2024-12-29 06:15:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `recordings`
--

CREATE TABLE `recordings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `photo` mediumblob DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `is_confirmed` tinyint(1) DEFAULT 0,
  `confirmation_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `company_id`, `name`, `email`, `password`, `cpf`, `photo`, `gender`, `is_confirmed`, `confirmation_token`, `created_at`, `profile_picture`, `verification_code`, `reset_token`, `reset_token_expires`, `is_admin`, `is_active`) VALUES
(9, 1, 'LUCAS DOS SANTOS', 'lucasandre.sanos@gmail.com', '$2y$10$mQOYpeatIpPbg4/.i2N0NehsFJ6JIfHoR8MgJV7NQ/Fep/agNGm.y', '113.947.456-13', NULL, '', 1, NULL, '2024-12-29 06:15:42', NULL, NULL, NULL, NULL, 1, 1),
(23, 1, 'LUCAS DOS SANTOS', 'lucas.santos@eagletelecom.com.br', '$2y$10$5gNRypBo9/M4j8c9/7iSku4DU4L19VPFicXShRr8rreh2YL4KVn3.', '113.947.456-13', 0x363737323962383963646663342e6a7067, '', 1, NULL, '2024-12-30 13:09:29', NULL, NULL, NULL, NULL, 1, 0),
(24, 1, 'Ewander Ferreira', 'ewander.ferreira@eagletelecom.com.br', '$2y$10$BZ3wN8/GjwhFFPpx9ZDflOhmeV/3lH7dTlQBJJSkLQRvfwV/dKZRq', '141.423.946-73', NULL, '', 1, NULL, '2024-12-30 13:13:29', NULL, NULL, NULL, NULL, 1, 1),
(27, 1, 'Marco Túlio  Xavier de Queiroz', 'marcotulio@eagletelecom.com.br', '$2y$10$M.S45hhiWsI3z.RcEc7hR.zjjY.02pxSJ56vHYBRPwTuXBHT2Nhhe', '', 0x363737326130356636633539382e6a7067, '', 1, NULL, '2024-12-30 13:30:07', NULL, NULL, NULL, NULL, 1, 1),
(28, 1, 'Mateus Agusto Soares Figueira', 'mateus@eagletelecom.com.br', '$2y$10$S4PgMX1CPBz6iuFZA8LCvO8ZlVg9ardsvwO3w8C./SKzByi.X1llm', '', 0x363737326134366635623131652e706e67, '', 1, NULL, '2024-12-30 13:47:27', NULL, NULL, NULL, NULL, 0, 0),
(30, 1, 'Ythalo Antonio Danieleto Giusti', 'ythalo.giusti@eagletelecom.com.br', '$2y$10$nDGXZuGutr8d.fOMr80JLepByX2ke9ezOmVKe3wTLu3QrrhigAPMC', '', 0x363737326135623334373531362e6a7067, '', 1, NULL, '2024-12-30 13:52:51', NULL, NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  `value` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `permission_key`, `value`) VALUES
(81, 27, 'gravacoes', 1),
(82, 27, 'transcricoes', 1),
(83, 27, 'relatorio_inteligente', 1),
(84, 27, 'gerenciar_licencas', 1),
(85, 27, 'gerenciar_empresas', 1),
(86, 27, 'administrador_sistema', 1),
(87, 24, 'gravacoes', 1),
(88, 24, 'transcricoes', 1),
(89, 24, 'relatorio_inteligente', 1),
(90, 9, 'gravacoes', 1),
(91, 9, 'transcricoes', 1),
(92, 9, 'relatorio_inteligente', 1),
(93, 9, 'gerenciar_licencas', 1),
(94, 9, 'gerenciar_empresas', 1),
(95, 9, 'administrador_sistema', 1),
(109, 23, 'gravacoes', 1),
(110, 23, 'transcricoes', 1),
(111, 23, 'relatorio_inteligente', 1),
(112, 23, 'gerenciar_empresas', 1),
(113, 23, 'administrador_sistema', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `verification_codes`
--

CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `verification_codes`
--

INSERT INTO `verification_codes` (`id`, `user_id`, `code`, `created_at`) VALUES
(19, 9, '858018', '2024-12-30 03:59:35'),
(21, 24, '075808', '2024-12-30 14:36:17'),
(22, 24, '388171', '2024-12-30 14:43:05'),
(23, 24, '945889', '2024-12-30 14:43:43'),
(24, 24, '941992', '2024-12-30 14:50:17');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD UNIQUE KEY `cnpj_2` (`cnpj`);

--
-- Índices de tabela `licenses`
--
ALTER TABLE `licenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Índices de tabela `recordings`
--
ALTER TABLE `recordings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_permission` (`user_id`,`permission_key`);

--
-- Índices de tabela `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role`);

--
-- Índices de tabela `verification_codes`
--
ALTER TABLE `verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `licenses`
--
ALTER TABLE `licenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `recordings`
--
ALTER TABLE `recordings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT de tabela `verification_codes`
--
ALTER TABLE `verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `licenses`
--
ALTER TABLE `licenses`
  ADD CONSTRAINT `licenses_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Restrições para tabelas `recordings`
--
ALTER TABLE `recordings`
  ADD CONSTRAINT `recordings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `recordings_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Restrições para tabelas `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
