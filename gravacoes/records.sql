-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 06/01/2025 às 05:41
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

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
-- Estrutura para tabela `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `audio_file_uri` varchar(255) NOT NULL,
  `text_file` varchar(255) DEFAULT NULL,
  `audio_local` varchar(255) DEFAULT NULL,
  `transcricao_local` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `records`
--

INSERT INTO `records` (`id`, `created_at`, `audio_file_uri`, `text_file`, `audio_local`, `transcricao_local`) VALUES
(1, '2025-01-06 03:19:16', '/VoiceHub/gravacoes/4402-RR-4401-1735584023.4.wav', '/VoiceHub/gravacoes/4402-RR-4401-1735584023.4.txt', 'C:\\xampp\\htdocs\\VoiceHub\\gravacoes\\4402-RR-4401-1735584023.4.wav', 'C:\\xampp\\htdocs\\VoiceHub\\gravacoes\\4402-RR-4401-1735584023.4.txt');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
