-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/01/2025 às 15:55
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
(109, 23, 'gravacoes', 1),
(110, 23, 'transcricoes', 1),
(111, 23, 'relatorio_inteligente', 1),
(112, 23, 'gerenciar_empresas', 1),
(113, 23, 'administrador_sistema', 1),
(114, 9, 'gravacoes', 1),
(115, 9, 'transcricoes', 1),
(116, 9, 'relatorio_inteligente', 1),
(117, 9, 'gerenciar_empresas', 1),
(118, 9, 'administrador_sistema', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_permission` (`user_id`,`permission_key`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
