-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 15-Jun-2026 às 21:20
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `medicamentos`
--
ALTER TABLE `medicamentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

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
