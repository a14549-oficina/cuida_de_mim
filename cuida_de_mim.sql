-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 14-Jun-2026 às 05:54
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cuida_de_mim`
--
CREATE DATABASE IF NOT EXISTS `cuida_de_mim` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cuida_de_mim`;

-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracoes`
--

DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `notif_meds` tinyint(1) NOT NULL DEFAULT 1,
  `notif_consultas` tinyint(1) NOT NULL DEFAULT 1,
  `notif_semanal` tinyint(1) NOT NULL DEFAULT 1,
  `whatsapp_ativo` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_numero` varchar(20) DEFAULT NULL,
  `antec_meds` varchar(20) NOT NULL DEFAULT '1 hora',
  `antec_consultas` varchar(20) NOT NULL DEFAULT '1 dia',
  `tema` enum('claro','escuro','sistema') NOT NULL DEFAULT 'sistema',
  `notif_cuidador_tomas` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Envia alerta ao cuidador quando toma é registada',
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `configuracoes`
--

INSERT INTO `configuracoes` (`utilizador_id`, `notif_meds`, `notif_consultas`, `notif_semanal`, `whatsapp_ativo`, `whatsapp_numero`, `antec_meds`, `antec_consultas`, `tema`, `notif_cuidador_tomas`, `atualizado_em`) VALUES
(2, 1, 1, 1, 1, '+351925911895', '1 hora', '1 dia', 'sistema', 1, '2026-06-07 16:54:49');

-- --------------------------------------------------------

--
-- Estrutura da tabela `consultas`
--

DROP TABLE IF EXISTS `consultas`;
CREATE TABLE `consultas` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `medico` varchar(120) NOT NULL,
  `especialidade` varchar(100) NOT NULL DEFAULT 'Medicina Geral',
  `local` varchar(150) DEFAULT NULL,
  `datahora` datetime NOT NULL,
  `notas` text DEFAULT NULL,
  `lembrete_min` smallint(5) UNSIGNED DEFAULT NULL,
  `lembrete_enviado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `consultas`
--

INSERT INTO `consultas` (`id`, `utilizador_id`, `medico`, `especialidade`, `local`, `datahora`, `notas`, `lembrete_min`, `lembrete_enviado`, `criado_em`) VALUES
(1, 1, 'Dr. António Silva', 'Cardiologia', 'Hospital da Luz', '2026-05-17 09:40:41', 'Controlo anual', 1440, 0, '2026-05-14 09:40:41'),
(2, 1, 'Dra. Rita Pereira', 'Dermatologia', 'Clínica Saúde', '2026-05-28 09:40:41', '', 60, 0, '2026-05-14 09:40:41'),
(3, 2, 'Dr João Silva', 'Ortopedia', '', '2026-06-07 17:54:00', '', 60, 0, '2026-06-07 16:54:14'),
(4, 2, 'Dr João Silva', 'Ginecologia', 'Hospital de Famalicão', '2026-06-07 16:59:00', '', NULL, 0, '2026-06-07 16:56:42'),
(5, 2, 'Dr João Silva', 'Dermatologia', '', '2026-06-07 17:59:00', '', 60, 0, '2026-06-07 16:57:23'),
(7, 2, 'Dr João Silva', 'Medicina Geral', '', '2026-06-12 20:31:00', '', NULL, 0, '2026-06-11 19:31:39'),
(8, 2, 'Dr Sofia Matos', 'Dermatologia', 'Hospital de Famalicão', '2026-06-12 16:30:00', '', 60, 0, '2026-06-11 23:55:35');

-- --------------------------------------------------------

--
-- Estrutura da tabela `consultas_partilha`
--

DROP TABLE IF EXISTS `consultas_partilha`;
CREATE TABLE `consultas_partilha` (
  `id` int(10) UNSIGNED NOT NULL,
  `consulta_id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `token` char(32) NOT NULL,
  `expira_em` datetime NOT NULL,
  `vistas` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cuidadores`
--

DROP TABLE IF EXISTS `cuidadores`;
CREATE TABLE `cuidadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `utente_id` int(10) UNSIGNED NOT NULL COMMENT 'O utilizador que é cuidado',
  `cuidador_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'O utilizador que cuida (NULL se ainda não tem conta)',
  `email_cuidador` varchar(180) NOT NULL,
  `nome_cuidador` varchar(120) DEFAULT NULL,
  `whatsapp_cuidador` varchar(20) DEFAULT NULL,
  `token_convite` char(32) NOT NULL,
  `estado` enum('pendente','ativo','recusado') NOT NULL DEFAULT 'pendente',
  `permissoes` set('medicamentos','consultas','diario','lembretes','relatorio') NOT NULL DEFAULT 'medicamentos,consultas,lembretes',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `aceite_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `cuidadores`
--

INSERT INTO `cuidadores` (`id`, `utente_id`, `cuidador_id`, `email_cuidador`, `nome_cuidador`, `whatsapp_cuidador`, `token_convite`, `estado`, `permissoes`, `criado_em`, `aceite_em`) VALUES
(5, 2, 2, 'teste@gmail.com', 'Pai', '+351925911895', '3603da8cfd28682cd3e4636351775bd2', 'ativo', 'medicamentos,consultas,diario,lembretes,relatorio', '2026-06-13 15:47:46', '2026-06-13 15:47:53');

-- --------------------------------------------------------

--
-- Estrutura da tabela `diario`
--

DROP TABLE IF EXISTS `diario`;
CREATE TABLE `diario` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `humor` enum('pessimo','mau','razoavel','bom','otimo') NOT NULL DEFAULT 'bom',
  `energia` tinyint(3) UNSIGNED NOT NULL DEFAULT 5,
  `dor` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `peso` decimal(5,2) DEFAULT NULL,
  `pressao` varchar(10) DEFAULT NULL,
  `sintomas` text DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `diario`
--

INSERT INTO `diario` (`id`, `utilizador_id`, `data`, `humor`, `energia`, `dor`, `peso`, `pressao`, `sintomas`, `notas`, `criado_em`) VALUES
(1, 1, '2026-05-14', 'bom', 7, 2, 70.20, NULL, 'cansaço leve', '', '2026-05-14 09:40:42'),
(2, 1, '2026-05-13', 'otimo', 9, 0, 70.10, NULL, '', 'Dia excelente', '2026-05-14 09:40:42'),
(3, 1, '2026-05-12', 'razoavel', 5, 4, 70.40, NULL, 'dor de cabeça', '', '2026-05-14 09:40:42'),
(5, 2, '2026-06-07', 'bom', 5, 0, 80.00, '120', 'dor de cabeça', '', '2026-06-07 16:57:44'),
(6, 2, '2026-06-11', 'razoavel', 5, 1, 0.00, '', 'dor de cabeça', '', '2026-06-11 19:22:14');

-- --------------------------------------------------------

--
-- Estrutura da tabela `lembretes`
--

DROP TABLE IF EXISTS `lembretes`;
CREATE TABLE `lembretes` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensagem` text NOT NULL,
  `datahora` datetime NOT NULL,
  `tipo` enum('medicamento','consulta','exercicio','urgente','geral') NOT NULL DEFAULT 'geral',
  `prioridade` enum('baixa','media','alta','urgente') NOT NULL DEFAULT 'media',
  `lido` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_enviado` tinyint(1) NOT NULL DEFAULT 0,
  `repetir` enum('','diario','semanal','mensal') NOT NULL DEFAULT '',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `lembretes`
--

INSERT INTO `lembretes` (`id`, `utilizador_id`, `titulo`, `mensagem`, `datahora`, `tipo`, `prioridade`, `lido`, `whatsapp_enviado`, `repetir`, `criado_em`) VALUES
(1, 1, 'Consulta de Cardiologia', 'Consulta com Dr. António Silva no Hospital da Luz', '2026-05-16 09:40:41', 'consulta', 'alta', 0, 0, '', '2026-05-14 09:40:41'),
(2, 1, 'Tomar Paracetamol', 'Hora de tomar o Paracetamol 500mg com água', '2026-05-14 10:40:41', 'medicamento', 'alta', 0, 0, 'diario', '2026-05-14 09:40:41'),
(18, 2, 'Consulta: Dr Sofia Matos', 'Consulta de Dermatologia com Dr Sofia Matos em Hospital de Famalicão', '2026-06-12 15:30:00', 'consulta', 'alta', 1, 0, '', '2026-06-11 23:55:35');

-- --------------------------------------------------------

--
-- Estrutura da tabela `medicamentos`
--

DROP TABLE IF EXISTS `medicamentos`;
CREATE TABLE `medicamentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `dosagem` varchar(50) DEFAULT NULL,
  `forma` enum('comprimido','xarope','injetavel','pomada','gotas','outro') NOT NULL DEFAULT 'comprimido',
  `horario` time NOT NULL DEFAULT '08:00:00',
  `frequencia` enum('diário','dias alternados','semanal','mensal') NOT NULL DEFAULT 'diário',
  `quantidade` smallint(5) UNSIGNED NOT NULL DEFAULT 30,
  `instrucoes` text DEFAULT NULL,
  `lembrete` tinyint(1) NOT NULL DEFAULT 1,
  `lembrete_whatsapp_enviado_em` datetime DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `medicamentos`
--

INSERT INTO `medicamentos` (`id`, `utilizador_id`, `nome`, `dosagem`, `forma`, `horario`, `frequencia`, `quantidade`, `instrucoes`, `lembrete`, `lembrete_whatsapp_enviado_em`, `ativo`, `criado_em`) VALUES
(1, 1, 'Paracetamol', '500mg', 'comprimido', '08:00:00', 'diário', 24, 'Tomar com água', 1, NULL, 1, '2026-05-14 09:40:41'),
(2, 1, 'Vitamina D', '2000UI', 'comprimido', '09:00:00', 'diário', 60, 'Tomar com pequeno-almoço', 0, NULL, 1, '2026-05-14 09:40:41'),
(3, 1, 'Omeprazol', '20mg', 'comprimido', '07:30:00', 'diário', 8, 'Tomar em jejum', 1, NULL, 1, '2026-05-14 09:40:41'),
(7, 2, 'benuron', '500mg', 'comprimido', '16:55:00', 'diário', 30, '', 1, NULL, 0, '2026-06-07 16:54:02'),
(8, 2, 'benuron', '500mg', 'comprimido', '16:58:00', 'diário', 30, '', 1, NULL, 0, '2026-06-07 16:56:26'),
(9, 2, 'brufen', '500mg', 'comprimido', '19:30:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 19:23:39'),
(10, 2, 'benuron', '500mg', 'comprimido', '19:40:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 19:29:45'),
(11, 2, 'brufen', '500mg', 'comprimido', '20:00:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 19:39:02'),
(12, 2, 'brufen', '500mg', 'comprimido', '08:00:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 19:42:38'),
(13, 2, 'brufen', '500mg', 'comprimido', '08:00:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 23:44:54'),
(14, 2, 'benuron', '500mg', 'comprimido', '08:00:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 23:45:03'),
(15, 2, 'paracetamol', '500mg', 'comprimido', '12:00:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 23:45:18'),
(16, 2, 'brufen', '500mg', 'comprimido', '17:00:00', 'diário', 30, '', 1, NULL, 0, '2026-06-11 23:45:34');

-- --------------------------------------------------------

--
-- Estrutura da tabela `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expira_em`, `usado`, `criado_em`) VALUES
(3, 'simaorpr08@gmail.com', '3fa5c554a6076803586ad774082ce6ed16a76c69f09b0e993ebfa3de06b939c3', '2026-06-13 17:52:02', 0, '2026-06-13 15:52:02');

-- --------------------------------------------------------

--
-- Estrutura da tabela `peso_historico`
--

DROP TABLE IF EXISTS `peso_historico`;
CREATE TABLE `peso_historico` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `peso` decimal(5,2) NOT NULL,
  `imc` decimal(4,2) DEFAULT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `peso_historico`
--

INSERT INTO `peso_historico` (`id`, `utilizador_id`, `data`, `peso`, `imc`, `notas`, `criado_em`) VALUES
(5, 2, '2026-06-11', 75.00, 25.95, NULL, '2026-06-11 19:22:34'),
(6, 2, '2026-06-10', 77.00, 26.64, NULL, '2026-06-11 19:22:44');

-- --------------------------------------------------------

--
-- Estrutura da tabela `streaks`
--

DROP TABLE IF EXISTS `streaks`;
CREATE TABLE `streaks` (
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `streak_atual` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `streak_max` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `ultima_data` date DEFAULT NULL,
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `streaks`
--

INSERT INTO `streaks` (`utilizador_id`, `streak_atual`, `streak_max`, `ultima_data`, `atualizado_em`) VALUES
(2, 0, 2, '2026-06-14', '2026-06-14 01:34:28'),
(3, 0, 0, '2026-06-13', '2026-06-13 15:58:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `tensao_arterial`
--

DROP TABLE IF EXISTS `tensao_arterial`;
CREATE TABLE `tensao_arterial` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `sistolica` smallint(5) UNSIGNED NOT NULL,
  `diastolica` smallint(5) UNSIGNED NOT NULL,
  `pulsacao` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'bpm',
  `contexto` enum('repouso','apos_exercicio','manha','noite','outro') NOT NULL DEFAULT 'repouso',
  `notas` varchar(255) DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `tensao_arterial`
--

INSERT INTO `tensao_arterial` (`id`, `utilizador_id`, `data`, `hora`, `sistolica`, `diastolica`, `pulsacao`, `contexto`, `notas`, `criado_em`) VALUES
(1, 2, '2026-06-07', '17:58:00', 150, 67, 76, 'repouso', NULL, '2026-06-07 16:58:39'),
(2, 2, '2026-06-11', '18:30:00', 140, 70, 65, 'repouso', NULL, '2026-06-11 19:23:24');

-- --------------------------------------------------------

--
-- Estrutura da tabela `tomas`
--

DROP TABLE IF EXISTS `tomas`;
CREATE TABLE `tomas` (
  `id` int(10) UNSIGNED NOT NULL,
  `medicamento_id` int(10) UNSIGNED NOT NULL,
  `utilizador_id` int(10) UNSIGNED NOT NULL,
  `data` date NOT NULL,
  `tomado` tinyint(1) NOT NULL DEFAULT 0,
  `tomado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `tomas`
--

INSERT INTO `tomas` (`id`, `medicamento_id`, `utilizador_id`, `data`, `tomado`, `tomado_em`) VALUES
(1, 1, 1, '2026-05-14', 1, NULL),
(2, 2, 1, '2026-05-14', 0, NULL),
(3, 3, 1, '2026-05-14', 0, NULL),
(4, 7, 2, '2026-06-07', 0, NULL),
(5, 8, 2, '2026-06-07', 1, '2026-06-07 17:59:36'),
(9, 8, 2, '2026-06-11', 1, '2026-06-11 20:21:27'),
(13, 9, 2, '2026-06-11', 1, '2026-06-11 20:26:14'),
(19, 10, 2, '2026-06-11', 1, '2026-06-11 20:32:35'),
(33, 11, 2, '2026-06-11', 1, '2026-06-11 20:46:35'),
(34, 12, 2, '2026-06-11', 1, '2026-06-11 20:46:36'),
(42, 13, 2, '2026-06-11', 0, NULL),
(43, 14, 2, '2026-06-11', 0, NULL),
(44, 15, 2, '2026-06-11', 0, NULL),
(45, 16, 2, '2026-06-11', 0, NULL),
(50, 13, 2, '2026-06-12', 1, '2026-06-12 00:52:59'),
(55, 14, 2, '2026-06-12', 1, '2026-06-12 00:53:00'),
(60, 15, 2, '2026-06-12', 1, '2026-06-12 00:53:00'),
(65, 16, 2, '2026-06-12', 1, '2026-06-12 00:53:01'),
(118, 13, 2, '2026-06-13', 1, '2026-06-13 16:42:20'),
(119, 14, 2, '2026-06-13', 1, '2026-06-13 16:42:21'),
(120, 15, 2, '2026-06-13', 1, '2026-06-13 16:42:22'),
(121, 16, 2, '2026-06-13', 1, '2026-06-13 16:42:23');

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizadores`
--

DROP TABLE IF EXISTS `utilizadores`;
CREATE TABLE `utilizadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `nascimento` date DEFAULT NULL,
  `medico_familia` varchar(120) DEFAULT NULL,
  `altura` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'cm — para cálculo de IMC',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `nome`, `email`, `password_hash`, `telefone`, `nascimento`, `medico_familia`, `altura`, `criado_em`, `atualizado_em`, `foto_perfil`) VALUES
(2, 'Simão Rafael Pereira Ribeiro', 'simaorpr08@gmail.com', '$2y$10$oKsZumO7HLRw9Av3HYkmjehzeHCIS4gloH2MWaiFTF4XCa04VFR/S', '925911895', '2008-07-14', 'Ana Sofia', 170, '2026-06-07 16:53:42', '2026-06-13 15:49:29', NULL),
(3, 'José Pereira', 'admin@cuirademim.pt', '$2y$10$ZrJ7t5EmsvSLC3Kk7/qwbuEllK/sqJ9wa85ZbMCU9ElrlFNLkh.k6', NULL, NULL, NULL, NULL, '2026-06-13 15:58:14', '2026-06-13 15:58:14', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`utilizador_id`);

--
-- Índices para tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilizador_id` (`utilizador_id`),
  ADD KEY `idx_lembrete_cron` (`lembrete_enviado`,`lembrete_min`,`datahora`);

--
-- Índices para tabela `consultas_partilha`
--
ALTER TABLE `consultas_partilha`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `fk_partilha_consulta` (`consulta_id`),
  ADD KEY `fk_partilha_user` (`utilizador_id`);

--
-- Índices para tabela `cuidadores`
--
ALTER TABLE `cuidadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_convite` (`token_convite`),
  ADD UNIQUE KEY `uq_utente_email` (`utente_id`,`email_cuidador`);

--
-- Índices para tabela `diario`
--
ALTER TABLE `diario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_diario` (`utilizador_id`,`data`);

--
-- Índices para tabela `lembretes`
--
ALTER TABLE `lembretes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilizador_id` (`utilizador_id`),
  ADD KEY `idx_whatsapp_cron` (`whatsapp_enviado`,`datahora`);

--
-- Índices para tabela `medicamentos`
--
ALTER TABLE `medicamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilizador_id` (`utilizador_id`);

--
-- Índices para tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Índices para tabela `peso_historico`
--
ALTER TABLE `peso_historico`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_peso_dia` (`utilizador_id`,`data`);

--
-- Índices para tabela `streaks`
--
ALTER TABLE `streaks`
  ADD PRIMARY KEY (`utilizador_id`);

--
-- Índices para tabela `tensao_arterial`
--
ALTER TABLE `tensao_arterial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tensao_user` (`utilizador_id`);

--
-- Índices para tabela `tomas`
--
ALTER TABLE `tomas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_toma` (`medicamento_id`,`data`),
  ADD KEY `utilizador_id` (`utilizador_id`);

--
-- Índices para tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `consultas_partilha`
--
ALTER TABLE `consultas_partilha`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cuidadores`
--
ALTER TABLE `cuidadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `diario`
--
ALTER TABLE `diario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `lembretes`
--
ALTER TABLE `lembretes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `medicamentos`
--
ALTER TABLE `medicamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `peso_historico`
--
ALTER TABLE `peso_historico`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tensao_arterial`
--
ALTER TABLE `tensao_arterial`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `tomas`
--
ALTER TABLE `tomas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD CONSTRAINT `fk_conf_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `consultas_partilha`
--
ALTER TABLE `consultas_partilha`
  ADD CONSTRAINT `fk_partilha_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_partilha_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `cuidadores`
--
ALTER TABLE `cuidadores`
  ADD CONSTRAINT `fk_cuidador_utente` FOREIGN KEY (`utente_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `diario`
--
ALTER TABLE `diario`
  ADD CONSTRAINT `diario_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `lembretes`
--
ALTER TABLE `lembretes`
  ADD CONSTRAINT `lembretes_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `medicamentos`
--
ALTER TABLE `medicamentos`
  ADD CONSTRAINT `medicamentos_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `peso_historico`
--
ALTER TABLE `peso_historico`
  ADD CONSTRAINT `fk_peso_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `streaks`
--
ALTER TABLE `streaks`
  ADD CONSTRAINT `fk_streak_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `tensao_arterial`
--
ALTER TABLE `tensao_arterial`
  ADD CONSTRAINT `fk_tensao_user` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `tomas`
--
ALTER TABLE `tomas`
  ADD CONSTRAINT `tomas_ibfk_1` FOREIGN KEY (`medicamento_id`) REFERENCES `medicamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tomas_ibfk_2` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
